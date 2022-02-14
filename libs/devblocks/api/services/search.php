<?php
/**
 * Class DevblocksSearchEngineSphinx
 * @deprecated 
 */
class DevblocksSearchEngineSphinx extends Extension_DevblocksSearchEngine {
	const ID = 'devblocks.search.engine.sphinx';
	
	private $_db = null;
	private $_config = array();
	
	public function __get($name) {
		switch($name) {
			case 'db':
				if(!is_null($this->_db))
					return $this->_db;
				
				if(false != ($this->_db = $this->_connect()))
					return $this->_db;
					
				break;
		}
		
		return null;
	}
	
	private function _connect() {
		@$host = $this->_config['host'];
		$port = isset($this->_config['port']) ? intval($this->_config['port']) : 9306;

		if(empty($host))
			return false;
		
		// Don't allow port 3306 to prevent malicious connections to MySQL
		if($port == 3306)
			return false;
		
		if(false == ($db = @mysqli_connect($host, null, null, null, $port)))
			return null;
		
		return $db;
	}
	
	public function testConfig(array $config) {
		@$host = $config['host'];
		$port = isset($config['port']) ? intval($config['port']) : 9306;
		@$index = $config['index'];
		@$index_rt = $config['index_rt'];

		if(empty($host))
			return "A hostname is required.";
		
		if($port == 3306)
			return "Port 3306 is not allowed for security reasons.";
		
		if(empty($index))
			return "A search index is required.";
		
		if(!empty($host))
			@$db = mysqli_connect($host, null, null, null, $port);
		
		if(!($db instanceof mysqli))
			return "Failed to connect to Sphinx.  Check your host and port settings.";
		
		$rs = mysqli_query($db, "SHOW TABLES");
		$indexes = array();
		
		if(!($rs instanceof mysqli_result))
			return "Failed to query the search tables.";
		
		while($row = mysqli_fetch_assoc($rs)) {
			$indexes[DevblocksPlatform::strLower($row['Index'])] = DevblocksPlatform::strLower($row['Type']);
		}
		
		mysqli_free_result($rs);
		
		// Check if the search index exists
		if(!isset($indexes[$index]))
			return sprintf("The index '%s' does not exist.", $index);
		
		// Check if the real-time index exists
		if($index_rt) {
			if(!isset($indexes[$index_rt]))
				return sprintf("The real-time index '%s' does not exist.", $index_rt);
		}
		
		return true;
	}
	
	public function setConfig(array $config) {
		$this->_config = $config;
	}
	
	public function renderConfigForSchema(Extension_DevblocksSearchSchema $schema) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('engine', $this);
		
		$engine_params = $schema->getEngineParams();
		@$engine_extension_id = $engine_params['engine_extension_id'];
		
		if($engine_extension_id == $this->id & isset($engine_params['config']))
			$tpl->assign('engine_params', $engine_params['config']);
		
		$tpl->display('devblocks:devblocks.core::search_engine/sphinx.tpl');
	}
	
	public function getIndexMeta(Extension_DevblocksSearchSchema $schema) {
		@$index = $this->_config['index'];
		@$index_rt = $this->_config['index_rt'];
		
		return array(
			'count' => false, // Sphinx can't always count rows (if no attributes, non-extern-docinfo)
			'max_id' => false, // Sphinx can't tell us the max ID w/o attributes
			'is_indexed_externally' => empty($index_rt) || ($index_rt != $index),
		);
	}
	
	public function getQuickSearchExamples(Extension_DevblocksSearchSchema $schema) {
		$engine_params = $schema->getEngineParams();
		
		if(isset($engine_params['config']) && isset($engine_params['config']['quick_search_examples']))
			if(!empty($engine_params['config']['quick_search_examples']))
				return DevblocksPlatform::parseCrlfString($engine_params['config']['quick_search_examples']);
		
		return array(
			"(all of these words)",
			'("this exact phrase")',
			'(this | that)',
			'wildcard*',
			'("a quorum of at least three of these words"/3)',
		);
	}
	
	function canGenerateSql() : bool {
		return false;
	}
	
	function generateSql(Extension_DevblocksSearchSchema $schema, string $query, array $attributes=[], ?callable $where_callback=null) : ?string {
		return null;
	}
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=[], $limit=null, &$error=null) : ?array {
		if(is_null($this->db))
			return null;
		
		$index = $this->_config['index'] ?? null;
		
		if(empty($index))
			return null;
		
		$where_sql = [];
		$field_sql = [];

		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			$attr_type = $schema_attributes[$attr] ?? null;
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$field_sql[] = sprintf("(@%s \"%s\")",
						mysqli_real_escape_string($this->db, $attr),
						mysqli_real_escape_string($this->db, $attr_val)
					);
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$where_sql[] = sprintf("%s = %d",
						mysqli_real_escape_string($this->db, $attr),
						$attr_val
					);
					break;
					
				case 'uint4':
				case 'uint8':
					$where_sql[] = sprintf("%s = %u",
						mysqli_real_escape_string($this->db, $attr),
						$attr_val
					);
					break;
			}
		}
		
		@$max_results = intval($limit) ?: intval($this->_config['max_results']) ?: 1000;
		@$max_results = DevblocksPlatform::intClamp($max_results, 1, 10000);
		
		$sql = sprintf("SELECT id ".
			"FROM %s ".
			"WHERE MATCH ('(%s)%s') ".
			"%s ".
			"LIMIT 0,%d ",
			$this->escapeNamespace($index),
			mysqli_real_escape_string($this->db, $query),
			!empty($field_sql) ? (' ' . implode(' ', $field_sql)) : '',
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : '',
			$max_results
		);

		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("search:%s", sha1($sql));
		$is_only_cached_for_request = !$cache->isVolatile();
		
		if(null === ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
			$ids = array();
			
			$result = mysqli_query($this->db, $sql);
			
			if($result instanceof mysqli_result) {
				while($row = mysqli_fetch_row($result)) {
					$ids[] = intval($row[0]);
				}
				
				mysqli_free_result($result);
			}
			
			$cache->save($ids, $cache_key, [], 300, $is_only_cached_for_request);
		}
		
		return $ids;
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=array()) {
		if(is_null($this->db))
			return false;
		
		@$index_rt = $this->_config['index_rt'];
		
		if(empty($index_rt))
			return false;
		
		$content = $this->_getTextFromDoc($doc);
		$content_key = $schema->getDataField();
		
		$fields = array(
			'id' => intval($id),
			$content_key => sprintf("'%s'", mysqli_real_escape_string($this->db, $content)),
		);
		
		// Attributes
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$fields[mysqli_real_escape_string($this->db, $attr)] = sprintf("'%s'", mysqli_real_escape_string($this->db, $attr_val));
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$fields[mysqli_real_escape_string($this->db, $attr)] = sprintf("%d", $attr_val);
					break;
					
				case 'uint4':
				case 'uint8':
					$fields[mysqli_real_escape_string($this->db, $attr)] = sprintf("%u", $attr_val);
					break;
			}
		}
		
		$sql = sprintf("REPLACE INTO %s (%s) VALUES (%s) ",
			$this->escapeNamespace($index_rt),
			implode(',', array_keys($fields)),
			implode(',', $fields)
		);
		$result = mysqli_query($this->db, $sql);
		
		return (false !== $result) ? true : false;
	}
	
	public function index(Extension_DevblocksSearchSchema $schema, $id, array $doc, array $attributes=array()) {
		if(false === ($this->_index($schema, $id, $doc, $attributes)))
			return false;
		
		return true;
	}

	public function delete(Extension_DevblocksSearchSchema $schema, $ids) {
		if(is_null($this->db))
			return false;
		
		@$index_rt = $this->_config['index_rt'];
		
		if(empty($index_rt))
			return false;
		
		if(!is_array($ids))
			$ids = array($ids);
			
		foreach($ids as $id) {
			/** @noinspection SqlResolve */
			mysqli_query($this->db, sprintf("DELETE FROM %s WHERE id = %d",
				$this->escapeNamespace($index_rt),
				$id
			));
		}
		
		return true;
	}
};

class DevblocksSearchEngineElasticSearch extends Extension_DevblocksSearchEngine {
	const ID = 'devblocks.search.engine.elasticsearch';
	const READ_TIMEOUT_MS = 15000;
	const WRITE_TIMEOUT_MS = 20000;
	
	private $_config = [];
	
	private function _execute($verb='GET', $url=null, $payload=[], $timeout=20000) {
		$headers = [];
		
		$ch = DevblocksPlatform::curlInit($url);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		switch($verb) {
			case 'POST':
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
				break;
			
			case 'PUT':
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
				break;
		}
		
		$out = DevblocksPlatform::curlExec($ch, true);
		
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		
		curl_close($ch);
		
		if($status != 200 || false == (@$json = json_decode($out, true)))
			return false;
		
		return $json;
	}
	
	private function _putRecord($type, $id, $doc) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		@$version = $this->_config['version'];
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		if($version >= 6) {
			// No types within indices
			$url = sprintf("%s/%s_%s/_doc/%d",
				$base_url,
				urlencode($index),
				urlencode($type),
				$id
			);
			
			if(false == ($json = $this->_execute('POST', $url, $doc)))
				return false;
			
		} else {
			$url = sprintf("%s/%s/%s/%d",
				$base_url,
				urlencode($index),
				urlencode($type),
				$id
			);
			
			if(false == ($json = $this->_execute('PUT', $url, $doc)))
				return false;
		}
		
		return $json;
	}
	
	private function _getSearch($type, $query, $limit=1000) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		@$version = $this->_config['version'];
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		// [TODO] Paging
		
		if($version >= 6) {
			// [TODO] Phase out filter_path?
			$url = sprintf("%s/%s_%s/_doc/_search?q=%s&_source=false&size=%d&default_operator=OR&filter_path=%s",
				$base_url,
				rawurlencode($index),
				rawurlencode($type),
				rawurlencode($query),
				$limit,
				rawurlencode('took,hits.total,hits.hits._id')
			);
			
		} else {
			$url = sprintf("%s/%s/%s/_search?q=%s&_source=false&size=%d&default_operator=OR&filter_path=%s",
				$base_url,
				rawurlencode($index),
				rawurlencode($type),
				rawurlencode($query),
				$limit,
				rawurlencode('took,hits.total,hits.hits._id')
			);
		}
		
		if(false == ($json = $this->_execute('GET', $url, array(), DevblocksSearchEngineElasticSearch::READ_TIMEOUT_MS)))
			return false;
		
		return $json;
	}
	
	private function _getCount($type) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		@$version = $this->_config['version'];
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		if($version >= 6) {
			$url = sprintf("%s/%s_%s/_doc/_count",
				$base_url,
				urlencode($index),
				urlencode($type)
			);
			
		} else {
			$url = sprintf("%s/%s/%s/_count",
				$base_url,
				urlencode($index),
				urlencode($type)
			);
		}
		
		if(false == ($json = $this->_execute('GET', $url, array(), DevblocksSearchEngineElasticSearch::READ_TIMEOUT_MS)))
			return false;
		
		if(!is_array($json) || !isset($json['count']))
			return false;
		
		return intval($json['count']);
	}
	
	public function testConfig(array $config) {
		@$base_url = $config['base_url'];
		@$index = $config['index'];
		
		if(empty($base_url))
			return "A base URL is required.";
		
		if(empty($index))
			return "An index name is required.";
		
		if(false === ($json = $this->_execute('GET', $base_url, array(), DevblocksSearchEngineElasticSearch::READ_TIMEOUT_MS)))
			return false;
		
		if(isset($json['version']) && isset($json['version']['number'])) {
			return true;
			
		} else {
			return false;
		}
	}
	
	public function setConfig(array $config) {
		$this->_config = $config;
	}
	
	public function renderConfigForSchema(Extension_DevblocksSearchSchema $schema) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('engine', $this);
		
		$engine_params = $schema->getEngineParams();
		@$engine_extension_id = $engine_params['engine_extension_id'];
		
		if($engine_extension_id == $this->id & isset($engine_params['config']))
			$tpl->assign('engine_params', $engine_params['config']);
		
		$tpl->display('devblocks:devblocks.core::search_engine/elasticsearch.tpl');
	}
	
	public function getIndexMeta(Extension_DevblocksSearchSchema $schema) {
		//@$index = $this->_config['index'];
		@$type = $schema->getNamespace();
		
		$count = $this->_getCount($type);
		
		return array(
			'count' => $count, // Elasticsearch can't always count rows (if no attributes, non-extern-docinfo)
			'max_id' => false, // Elasticsearchcan't tell us the max ID w/o attributes
			'is_indexed_externally' => false,
		);
	}
	
	public function getQuickSearchExamples(Extension_DevblocksSearchSchema $schema) {
		$engine_params = $schema->getEngineParams();
		
		if(isset($engine_params['config']) && isset($engine_params['config']['quick_search_examples']))
			if(!empty($engine_params['config']['quick_search_examples']))
				return DevblocksPlatform::parseCrlfString($engine_params['config']['quick_search_examples']);
		
		return array(
			"all of these words",
			'"this exact phrase"',
			'this && that',
			'this || that',
			'(this || that) !(this && that)',
			'wildcard*',
			'person@example.com',
		);
	}
	
	function canGenerateSql() : bool {
		return false;
	}
	
	function generateSql(Extension_DevblocksSearchSchema $schema, string $query, array $attributes=[], ?callable $where_callback=null) : ?string {
		return null;
	}
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=[], $limit=null, &$error=null) : ?array {
		@$type = $schema->getNamespace();
		
		if(empty($type))
			return null;
		
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$query .= sprintf(' AND %s:"%s"',
						$attr,
						$attr_val
					);
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$query .= sprintf(' AND %s:%d',
						$attr,
						$attr_val
					);
					break;
					
				case 'uint4':
				case 'uint8':
					$query .= sprintf(' AND %s:%d',
						$attr,
						$attr_val
					);
					break;
			}
		}
		
		// The max desired results (blank for unlimited)
		@$max_results = intval($limit) ?: intval($this->_config['max_results']) ?: 1000;
		@$max_results = DevblocksPlatform::intClamp($max_results, 1, 1000);
		
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("elasticsearch:%s:%s", $type, sha1($query));
		$cache_ttl = 300;
		$is_only_cached_for_request = !$cache->isVolatile();
		
		if(null === ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
			$filtered_query = $query;
			
			$json = $this->_getSearch($type, $filtered_query, $max_results);
			
			@$results_hits = count($json['hits']['hits'] ?? 0);
			
			if($results_hits) {
				$ids = array_column($json['hits']['hits'], '_id');
			} else {
				$ids = [];
			}
			
			$cache->save($ids, $cache_key, [], $cache_ttl, $is_only_cached_for_request);
		}
		
		return $ids;
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=[]) {
		@$type = $schema->getNamespace();
		
		if(empty($type))
			return false;
		
		// Do we need to add attributes to the document?
		
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$doc[$attr] = $attr_val;
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$doc[$attr] = intval($attr_val);
					break;
					
				case 'uint4':
				case 'uint8':
					$doc[$attr] = intval($attr_val);
					break;
			}
		}
		
		// Send to Elasticsearch
		
		$this->_putRecord($type, $id, $doc);
		
		return true;
	}
	
	public function index(Extension_DevblocksSearchSchema $schema, $id, array $doc, array $attributes=[]) {
		if(false === ($this->_index($schema, $id, $doc, $attributes)))
			return false;
		
		return true;
	}

	public function delete(Extension_DevblocksSearchSchema $schema, $ids) {
		@$base_url = $this->_config['base_url'];
		@$index = $this->_config['index'];
		@$type = $schema->getNamespace();
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		if(!is_array($ids))
			return false;
		
		foreach($ids as $id) {
			$url = sprintf("%s/%s/%s/%d",
				$base_url,
				urlencode($index),
				urlencode($type),
				$id
			);
			
			$this->_execute('DELETE', $url, [], DevblocksSearchEngineElasticSearch::WRITE_TIMEOUT_MS);
		}
		
		return true;
	}
};

class DevblocksSearchEngineMysqlFulltext extends Extension_DevblocksSearchEngine {
	const ID = 'devblocks.search.engine.mysql_fulltext';
	
	private $_config = array();
	
	public function __get($name) {
		switch($name) {
		}
	}
	
	public function setConfig(array $config) {
		$this->_config = $config;
	}
	
	public function testConfig(array $config) {
		// There's nothing to test yet
		return true;
	}
	
	public function renderConfigForSchema(Extension_DevblocksSearchSchema $schema) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('engine', $this);
		
		$engine_params = $schema->getEngineParams();
		@$engine_extension_id = $engine_params['engine_extension_id'];
		
		if($engine_extension_id == $this->id & isset($engine_params['config']))
			$tpl->assign('engine_params', $engine_params['config']);
		
		$tpl->display('devblocks:devblocks.core::search_engine/mysql_fulltext.tpl');
	}
	
	public function getIndexMeta(Extension_DevblocksSearchSchema $schema) {
		return array(
			'count' => $this->_getCount($schema),
			'max_id' => $this->_getMaxId($schema),
			'is_indexed_externally' => false,
			'is_count_approximate' => true,
		);
	}
	
	private function _getMaxId(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		$id_key = $schema->getIdField();
		
		return intval($db->GetOneReader(sprintf("SELECT MAX(%s) FROM fulltext_%s", $db->escape($id_key), $db->escape($ns))));
	}
	
	private function _getCount(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();

		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		$id_key = $schema->getIdField();
		
		$row = $db->GetRowReader(sprintf("EXPLAIN SELECT COUNT(%s) FROM fulltext_%s", $db->escape($id_key), $db->escape($ns)));
		
		if(array_key_exists('rows', $row))
			return intval($row['rows']);
		
		return 0;
	}
	
	public function getQuickSearchExamples(Extension_DevblocksSearchSchema $schema) {
		return array(
			'all of these words',
			'"this exact phrase"',
			'"mail@example.com"',
			'"127.0.0.1"',
		);
	}
	
	private function _getQueryWhereAnnotations(Extension_DevblocksSearchSchema $schema, array $attributes) : array {
		$db = DevblocksPlatform::services()->database();
		
		$schema_attributes = $schema->getAttributes();
		$where_sql = [];
		
		if(is_array($attributes)) {
			foreach ($attributes as $attr => $attr_val) {
				$attr_type = $schema_attributes[$attr] ?? null;
				
				if (empty($attr_type))
					continue;
				
				switch ($attr_type) {
					case 'string':
						if (is_array($attr_val)) {
							if (!empty($attr_val)) {
								$where_sql[] = sprintf("%s IN (%s)",
									$db->escape($attr),
									implode(',', $db->qstrArray($attr_val))
								);
							} else {
								$where_sql[] = sprintf("%s IS NULL",
									$db->escape($attr)
								);
								
							}
							
						} else {
							$where_sql[] = sprintf("%s = '%s'",
								$db->escape($attr),
								$db->escape($attr_val)
							);
						}
						
						break;
					
					case 'int':
					case 'int4':
					case 'int8':
					case 'uint4':
					case 'uint8':
						if (is_array($attr_val)) {
							if (!empty($attr_val)) {
								$where_sql[] = sprintf("%s IN (%s)",
									$db->escape($attr),
									implode(',', DevblocksPlatform::sanitizeArray($attr_val, 'int'))
								);
							} else {
								$where_sql[] = sprintf("%s = %d",
									$db->escape($attr),
									-1
								);
							}
							
						} else {
							$where_sql[] = sprintf("%s = %s",
								$db->escape($attr),
								intval($attr_val)
							);
						}
						break;
				}
			}
		}
		
		return $where_sql;
	}
	
	private function _getQueryWhereClauses(Extension_DevblocksSearchSchema $schema, array $query_parts) {
		$db = DevblocksPlatform::services()->database();
		
		$content_key = $schema->getDataField();

		$where_sql = [];
		
		if(isset($query_parts['phrases']) && isset($query_parts['phrases']))
			foreach($query_parts['phrases'] as $phrase) {
				$where_sql[] = sprintf("%s LIKE '%%%s%%'",
					$db->escape($content_key),
					$db->escape($phrase)
				);
			}
		
		return $where_sql;
	}
	
	function canGenerateSql() : bool {
		return true;
	}
	
	public function generateSql(Extension_DevblocksSearchSchema $schema, string $query, array $attributes=[], ?callable $where_callback=null, &$as_exists=false) : ?string {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		$threshold_ids = APP_OPT_FULLTEXT_THRESHOLD_IDS;
		$threshold_exists = APP_OPT_FULLTEXT_THRESHOLD_EXISTS;
		
		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		if(false == ($query_parts = $this->_parseQuery($query)))
			return false;
		
		if(!isset($query_parts['terms']) || empty($query_parts['terms']))
			return false;
		
		$id_key = $schema->getIdField();
		$content_key = $schema-> getDataField();
		$escaped_query = $db->escape($query_parts['terms']);
		
		if(!trim($escaped_query,'+*'))
			$escaped_query = '';
		
		$where_annotation_sql = $this->_getQueryWhereAnnotations($schema, $attributes);
		$where_sql = $this->_getQueryWhereClauses($schema, $query_parts);
		
		if(!empty($where_annotation_sql)) {
			$where_sql = array_merge($where_sql, $where_annotation_sql);
		}
		
		if(is_callable($where_callback)) {
			if(false != ($and_where = $where_callback($id_key, $content_key))) {
				$where_sql = array_merge($where_sql, $and_where);
			}
		}
		
		try {
			if(APP_OPT_FULLTEXT_OPTIMIZE_IN_EXISTS) { // kill-switch
				// COUNT the matches first, to later decide on IN or EXISTS
				$sql_hits = sprintf("SELECT COUNT(1) " .
					"FROM fulltext_%s " .
					"WHERE MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE) ".
					"%s",
					$this->escapeNamespace($ns),
					$db->escape($content_key),
					$escaped_query,
					$where_annotation_sql ? ('AND ' . implode(' AND ', $where_annotation_sql)) : ''
				);
				$hits = $db->GetOneReader($sql_hits, 3500);
				
			} else {
				$hits = false;
			}
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			// If this times out, we can assume an EXISTS would be faster
			$hits = false;
			$as_exists = true;
		}
		
		// If fewer than 1,000 return IDs
		
		if(false !== $hits) {
			if($hits <= $threshold_ids) {
				try {
					$sql_ids = sprintf("SELECT %s ".
						"FROM fulltext_%s ".
						"WHERE MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE) ".
						"%s",
						$db->escape($id_key),
						$this->escapeNamespace($ns),
						$db->escape($content_key),
						$escaped_query,
						$where_annotation_sql ? ('AND ' . implode(' AND ', $where_annotation_sql)) : ''
					);
					
					$ids = $db->GetArrayReader($sql_ids, 5000);
					
					if(!is_array($ids))
						$ids = [];
					
					$ids = array_column($ids, $id_key);
					
					// If we don't have any extra where clauses, return IDs
					if(empty($where_sql))
						return implode(',', array_column($ids, $id_key));
					
					if(empty($ids))
						$ids = [-1];
					
					return sprintf("SELECT %s ".
						"FROM fulltext_%s ".
						"WHERE %s IN (%s) ".
						"%s",
						$db->escape($id_key),
						$this->escapeNamespace($ns),
						$db->escape($id_key),
						implode(',', $db->qstrArray($ids)),
						('AND ' . implode(' AND ', $where_sql))
					);
					
				} catch (Exception_DevblocksDatabaseQueryTimeout $e) {}
			
			// If over 10,000 recommend EXISTS
			} elseif($hits >= $threshold_exists) {
				$as_exists = true;
			}
		}
		
		return sprintf("SELECT %s ".
			"FROM fulltext_%s ".
			"WHERE MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE) ".
			"%s",
			$db->escape($id_key),
			$this->escapeNamespace($ns),
			$db->escape($content_key),
			$escaped_query,
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : ''
		);
	}
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=[], $limit=null, &$error=null) : ?array {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		if(!isset($tables['fulltext_' . $ns]))
			return null;
		
		if(false == ($query_parts = $this->_parseQuery($query)))
			return null;
		
		if(!isset($query_parts['terms']) || empty($query_parts['terms']))
			return null;
		
		$id_key = $schema->getIdField();
		$content_key = $schema-> getDataField();
		$escaped_query = $db->escape($query_parts['terms']);
		
		if(!trim($escaped_query,'+*'))
			$escaped_query = '';
		
		$where_annotation_sql = $this->_getQueryWhereAnnotations($schema, $attributes);
		$where_sql = $this->_getQueryWhereClauses($schema, $query_parts);
		
		if(!empty($where_annotation_sql)) {
			$where_sql = array_merge($where_sql, $where_annotation_sql);
		}
		
		// The max desired results (blank for unlimited)
		$max_results = intval($limit) ?: intval($this->_config['max_results'] ?? null) ?: 1000;
		$max_results = DevblocksPlatform::intClamp($max_results, 1, 10000);
		
		$cache = DevblocksPlatform::services()->cache();
		$is_only_cached_for_request = !$cache->isVolatile();
		$cache_ttl = 300;
		$timeout_ms = 15000;
		
		$sql = sprintf("SELECT %s ".
			"FROM fulltext_%s ".
			"WHERE MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE) ".
			"%s ".
			($max_results ? sprintf("LIMIT %d ", $max_results) : ''),
			$db->escape($id_key),
			$this->escapeNamespace($ns),
			$db->escape($content_key),
			$escaped_query,
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : ''
		);
		
		$cache_key = sprintf("search:%s", sha1($sql));
		
		if(null == ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
			$results = $db->QueryReaderAsync($sql, $timeout_ms);
			
			if(false === $results || $results instanceof Exception_DevblocksDatabaseQueryTimeout) {
				$ids = [];
			} else {
				$results = $results->fetch_all(MYSQLI_ASSOC);
				$ids = DevblocksPlatform::sanitizeArray(array_column($results, $id_key), 'int');
				$cache->save($ids, $cache_key, array(), $cache_ttl, $is_only_cached_for_request);
			}
		}
		
		return $ids;
	}
	
	private function _parseQuery($query) {
		// Extract quotes
		$phrases = array();
		$start = 0;
		
		while(false !== ($from = strpos($query, '"', $start))) {
			if(false === ($to = strpos($query, '"', $from+1)))
				break;
			
			$cut = substr($query, $from, $to-$from+1);
			$phrase = trim($cut,'"');
			
			// Ignore single word phrases with no symbols
			if($phrase != mb_ereg_replace('[^[:alnum:]]', '', $phrase))
				$phrases[] = $phrase;
			
			$start = $to+1;
		}
		
		// Required terms
		$terms = $this->prepareText($query, true);
		
		if(empty($terms))
			$terms = 'THIS_SHOULD_NEVER_MATCH_ANYTHING';
		
		$terms = '+'.str_replace(' ', ' +', $terms);
		
		return array('terms' => $terms, 'phrases' => $phrases);
	}
	
	public function removeStopWords($words) {
		$stop_words = $this->_getStopWords();
		return array_diff($words, $stop_words);
	}
	
	private function _getStopWords() {
		// InnoDB stop words
		// [TODO] Make this configurable
		return [
			'a',
			'about',
			'an',
			'are',
			'as',
			'at',
			'be',
			'by',
			'com',
			'de',
			'en',
			'for',
			'from',
			'how',
			'i',
			'in',
			'is',
			'it',
			'la',
			'of',
			'on',
			'or',
			'that',
			'the',
			'this',
			'to',
			'was',
			'what',
			'when',
			'where',
			'who',
			'will',
			'with',
			'und',
			'the',
			'www',
		];
	}
	
	public function prepareText($text, $is_query=false) {
		$text = DevblocksPlatform::strUnidecode($text);

		if($is_query) {
			// Allow wildcards in queries
			$regexp = '[^[:alnum:]_\*]';
			$text = mb_ereg_replace($regexp, ' ', mb_convert_case($text, MB_CASE_LOWER));
			
			$words = explode(' ', $text);
			
			foreach($words as $word)
				$word = ltrim($word, '+-');
				
			unset($text);
			
			// Remove stop words from queries
			$words = $this->removeStopWords($words);
			
			// Remove min/max sizes
			$words = array_filter($words, function($word) {
				// Less than 3 characters and not a wildcard
				if(strlen($word) < 3 && !DevblocksPlatform::strEndsWith($word, '*'))
					return false;
				
				if(strlen($word) > 83)
					return false;
				
				return true;
			});
			
			// Reassemble
			$text = implode(' ', $words);
			unset($words);
		}
		
		// Flatten multiple spaces into a single
		return preg_replace('# +#', ' ', $text);
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=[]) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$strings = DevblocksPlatform::services()->string();
		$ns = $schema->getNamespace();
		
		$id_key = $schema->getIdField();
		$content_key = $schema->getDataField();
		
		$content = $this->_getTextFromDoc($doc);
		
		// If the table doesn't exist, create it at index time
		if(!isset($tables['fulltext_' . $this->escapeNamespace($ns)]))
			if(false === $this->_createTable($schema))
				return false;
		
		// Remove 4 byte characters
		$content = $strings->strip4ByteChars($content);
		
		$fields = [
			$id_key => intval($id),
			$content_key => $db->qstr($content),
		];
		
		// Attributes
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$fields[$db->escape($attr)] = $db->qstr($attr_val);
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$fields[$db->escape($attr)] = intval($attr_val);
					break;
					
				case 'uint4':
				case 'uint8':
					$fields[$db->escape($attr)] = sprintf("%u", $attr_val);
					break;
			}
		}
		
		$sql = sprintf("REPLACE INTO fulltext_%s (%s) VALUES (%s) ",
			$this->escapeNamespace($ns),
			implode(',', array_keys($fields)),
			implode(',', $fields)
		);
		
		$result = $db->ExecuteMaster($sql);
		
		$return = false !== $result;
		
		if($result instanceof mysqli_result)
			mysqli_free_result($result);
		
		return $return;
	}
	
	public function index(Extension_DevblocksSearchSchema $schema, $id, array $doc, array $attributes=[]) {
		return $this->_index($schema, $id, $doc, $attributes);
	}
	
	private function _createTable(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$namespace = $schema->getNamespace();
		$attributes = $schema->getAttributes();
		
		$id_key = $schema->getIdField();
		$content_key = $schema->getDataField();
		$primary_key = $schema->getPrimaryKey();
		
		$attributes_sql = [];
		
		if(is_array($attributes))
		foreach($attributes as $attr => $type) {
			$field_type = null;
			
			switch($type) {
				case 'text':
				case 'string':
					$field_type = 'varchar(255)';
					break;
					
				case 'int':
				case 'int4':
					$field_type = 'int default 0';
					break;
					
				case 'int8':
					$field_type = 'bigint default 0';
					break;
					
				case 'uint4':
					$field_type = 'int unsigned default 0';
					break;
					
				case 'uint8':
					$field_type = 'bigint unsigned default 0';
					break;
					
				default:
					break;
			}
			
			if(null == $field_type)
				return false;
			
			$attributes_sql[] = sprintf("%s %s,",
				$db->escape($attr),
				$db->escape($field_type)
			);
		}
		
		$namespace = $this->escapeNamespace($namespace);
		
		if(isset($tables['fulltext_'.$namespace]))
			return true;
		
		// Prior to MySQL 5.6 we can only do fulltext in MyISAM tables
		if(mysqli_get_server_version($db->getMasterConnection()) < 50600) {
			$db_engine_fulltext = 'MyISAM';
		} else {
			$db_engine_fulltext = APP_DB_ENGINE_FULLTEXT;
		}
		
		// Primary key
		if(is_string($primary_key))
			$primary_key = [$primary_key];
		
		$primary_key = array_map(function($key) use ($db) {
			return $db->escape($key);
		}, $primary_key);
		
		// Create table
		/** @noinspection SqlResolve */
		$sql = sprintf(
			"CREATE TABLE IF NOT EXISTS fulltext_%s (
				%s INT UNSIGNED NOT NULL DEFAULT 0,
				%s LONGTEXT,
				%s
				PRIMARY KEY (%s),
				FULLTEXT (%s)
			) ENGINE=%s CHARACTER SET=utf8;",
			$this->escapeNamespace($namespace),
			$db->escape($id_key),
			$db->escape($content_key),
			(!empty($attributes_sql) ? implode(",\n", $attributes_sql) : ''),
			implode(',', $primary_key),
			$db->escape($content_key),
			$db_engine_fulltext
		);
		
		$result = $db->ExecuteMaster($sql);
		
		$return = (false !== $result) ? true : false;
		
		if($result instanceof mysqli_result)
			mysqli_free_result($result);
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return $return;
	}
	
	public function delete(Extension_DevblocksSearchSchema $schema, $ids) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$ns = $schema->getNamespace();
		
		if(!is_array($ids))
			$ids = array($ids);
			
		if(empty($ns) || empty($ids))
			return false;
			
		$namespace = $this->escapeNamespace($ns);
		
		if(!isset($tables['fulltext_'.$namespace]))
			return true;
		
		/** @noinspection SqlResolve */
		$result = $db->ExecuteMaster(sprintf("DELETE FROM fulltext_%s WHERE id IN (%s) ",
			$namespace,
			implode(',', $ids)
		));
		
		$return = (false !== $result) ? true : false;
		
		if($result instanceof mysqli_result)
			mysqli_free_result($result);
		
		return $return;
	}
};