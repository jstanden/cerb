<?php
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
	
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=null) {
		if(is_null($this->db))
			return false;
		
		@$index = $this->_config['index'];
		
		if(empty($index))
			return false;
		
		$where_sql = array();
		$field_sql = array();

		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
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
			
			$cache->save($ids, $cache_key, array(), 300, $is_only_cached_for_request);
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
		
		$fields = array(
			'id' => intval($id),
			'content' => sprintf("'%s'", mysqli_real_escape_string($this->db, $content)),
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
	
	private function _execute($verb='GET', $url, $payload=[], $timeout=20000) {
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
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=null) {
		@$type = $schema->getNamespace();
		//@$version = $this->_config['version'];
		
		if(empty($type))
			return false;
		
		$db = DevblocksPlatform::services()->database();
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
		$is_cached = true;
		$temp_table = uniqid('_search_');
		$start_time = microtime(true);
		$prefetch_sql = null;
		
		if(isset($attributes['id']) && is_array($attributes['id']) && isset($attributes['id']['sql'])) {
			$prefetch_sql = sprintf("CREATE TEMPORARY TABLE %s (id int unsigned) ENGINE=MyISAM %s LIMIT 1000",
				$db->escape($temp_table),
				$attributes['id']['sql']
			);
			
			$cache_key = sprintf("elasticsearch:%s:%s", $type, sha1($query.$attributes['id']['sql']));
		}
		
		if(null === ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
			$is_cached = false;
			$filtered_query = $query;
			
			if($prefetch_sql) {
				$db->QueryReader($prefetch_sql);
			
				$sql = sprintf("SELECT id FROM %s LIMIT %d",
					$db->escape($temp_table),
					$max_results
				);
				
				if(false == ($results = $db->GetArrayReader($sql)))
					$results = [];
				
				$db->QueryReader(sprintf("DROP TABLE %s", $db->escape($temp_table)));
					
				$filter_ids = array_column($results, 'id');
				
				if(empty($filter_ids))
					$filter_ids = array('-1');
				
				if($prefetch_sql) {
					$filtered_query = $query . sprintf(' AND _id:(%s)',
						implode(' ', $filter_ids)
					);
				}
			}
			
			$json = $this->_getSearch($type, $filtered_query, $max_results);
			
			@$took_ms = intval($json['took']);
			//@$total_hits = intval($json['hits']['total']);
			@$results_hits = intval(count($json['hits']['hits']));
			
			if($results_hits) {
				$ids = array_column($json['hits']['hits'], '_id');
			} else {
				$ids = [];
			}
			
			$cache->save($ids, $cache_key, [], $cache_ttl, $is_only_cached_for_request);
		}
		
		$count = count($ids);
		
		// With fewer results, use the more efficient IN(...)
		if($count <= 5000) {
			// Keep $ids
			
		// Otherwise, populate a temporary table and return it
		} else {
			$temp_table = sprintf("_search_%s", uniqid());
			
			$sql = sprintf("CREATE TEMPORARY TABLE IF NOT EXISTS %s (id int unsigned not null, PRIMARY KEY (id))", $temp_table);
			$db->QueryReader($sql);
			
			while($ids_part = array_splice($ids, 0, 500, null)) {
				$sql = sprintf("INSERT IGNORE INTO %s (id) VALUES (%s)", $temp_table, implode('),(', $ids_part));
				$db->QueryReader($sql);
			}
			
			$ids = $temp_table;
		}
		
		// Store the search info in a request registry for later use
		$meta_key = 'fulltext_meta';
		$engine = 'elasticsearch';
		$meta = DevblocksPlatform::getRegistryKey($meta_key, DevblocksRegistryEntry::TYPE_JSON, '[]');
		$entry_key = sha1($engine.$query.$count.$type);
		$took_ms = !isset($took_ms) ? ((microtime(true) - $start_time)*1000) : $took_ms;
		
		if(!isset($meta[$entry_key])) {
			$meta[$entry_key] = array('engine' => $engine, 'query' => $query, 'took_ms' => $took_ms, 'results' => $count, 'ns' => $type, 'is_cached' => $is_cached, 'max' => $max_results);
			DevblocksPlatform::setRegistryKey($meta_key, $meta, DevblocksRegistryEntry::TYPE_JSON, false);
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
		
		return intval($db->GetOneReader(sprintf("SELECT MAX(id) FROM fulltext_%s", $db->escape($ns))));
	}
	
	private function _getCount(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();

		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		$row = $db->GetRowReader(sprintf("EXPLAIN SELECT COUNT(id) FROM fulltext_%s", $db->escape($ns)));
		
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
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=null) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		if(false == ($query_parts = $this->_parseQuery($query)))
			return false;
		
		if(!isset($query_parts['terms']) || empty($query_parts['terms']))
			return false;
		
		$escaped_query = $db->escape($query_parts['terms']);
		$where_sql = array();
		
		if(isset($query_parts['phrases']) && isset($query_parts['phrases']))
		foreach($query_parts['phrases'] as $phrase) {
			$where_sql[] = sprintf("content LIKE '%%%s%%'",
				$db->escape($phrase)
			);
		}
		
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					if(is_array($attr_val)) {
						if(!empty($attr_val)) {
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
					if(is_array($attr_val)) {
						if(!empty($attr_val)) {
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
		
		// The max desired results (blank for unlimited)
		@$max_results = intval($limit) ?: intval($this->_config['max_results']) ?: 1000;
		@$max_results = DevblocksPlatform::intClamp($max_results, 1, 10000);
		
		// Randomly named temporary table
		$temp_table = sprintf("_search_%s", uniqid());
		
		$start_time = microtime(true);
		
		$cache = DevblocksPlatform::services()->cache();
		$is_only_cached_for_request = !$cache->isVolatile();
		$cache_ttl = 300;
		$is_cached = true;
		
		if(array_key_exists('id', $attributes) && is_array($attributes['id']) && array_key_exists('sql', $attributes['id'])) {
			$cache_key = sprintf("search:%s", sha1($ns.$escaped_query.$attributes['id']['sql'].json_encode($where_sql)));
			
			if(null == ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
				$is_cached = false;
				
				// Without locks
				$db->QueryReader("SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
				
				$sql = sprintf("CREATE TEMPORARY TABLE %s (id int unsigned, content text) ENGINE=MyISAM SELECT id, content FROM fulltext_%s WHERE id IN (%s)",
					$db->escape($temp_table),
					$this->escapeNamespace($ns),
					$attributes['id']['sql']
				);
				$db->QueryReader($sql);
				
				// Resume locking
				$db->QueryReader("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
				
				$sql = sprintf("SELECT id ".
					"FROM %s ".
					"WHERE MATCH (content) AGAINST ('%s' IN BOOLEAN MODE) ".
					"%s ".
					"LIMIT %d",
					$db->escape($temp_table),
					$escaped_query,
					!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : '',
					$max_results
				);
				$results = $db->GetArrayReader($sql);
				
				$db->QueryReader(sprintf("DROP TABLE %s",
					$temp_table
				));
				
				$ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::extractArrayValues($results, 'id'), 'int');
				$cache->save($ids, $cache_key, array(), $cache_ttl, $is_only_cached_for_request);
			}
			
		} else {
			$sql = sprintf("SELECT id ".
				"FROM fulltext_%s ".
				"WHERE MATCH content AGAINST ('%s' IN BOOLEAN MODE) ".
				"%s ".
				($max_results ? sprintf("LIMIT %d ", $max_results) : ''),
				$this->escapeNamespace($ns),
				$escaped_query,
				!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : ''
			);
			
			$cache_key = sprintf("search:%s", sha1($sql));
			
			if(null == ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
				$is_cached = false;
				
				if(false === ($results = $db->GetArrayReader($sql))) {
					$ids = array();
				} else {
					$ids = DevblocksPlatform::sanitizeArray(array_column($results, 'id'), 'int');
					$cache->save($ids, $cache_key, array(), $cache_ttl, $is_only_cached_for_request);
				}
			}
		}
		
		@$took_ms = (microtime(true) - $start_time) * 1000;
		$count = count($ids);
		
		// Store the search info in a request registry for later use
		$meta_key = 'fulltext_meta';
		$engine = 'mysql-fulltext';
		$meta = DevblocksPlatform::getRegistryKey($meta_key, DevblocksRegistryEntry::TYPE_JSON, '[]');
		$entry_key = sha1($engine.$query.$count.$ns);
		
		if(!isset($meta[$entry_key])) {
			$meta[$entry_key] = array('engine' => $engine, 'query' => $query, 'took_ms' => $took_ms, 'results' => $count, 'ns' => $ns, 'is_cached' => $is_cached, 'max' => $max_results, 'database' => APP_DB_DATABASE);
			DevblocksPlatform::setRegistryKey($meta_key, $meta, DevblocksRegistryEntry::TYPE_JSON, false);
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
		$words = array(
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
			'www'
		);
		
		return $words;
	}
	
	public function prepareText($text, $is_query=false) {
		$text = DevblocksPlatform::strUnidecode($text);

		// Allow wildcards in queries
		if($is_query) {
			$regexp = '[^[:alnum:]_\*]';
			$text = mb_ereg_replace($regexp, ' ', mb_convert_case($text, MB_CASE_LOWER));
			
			$words = explode(' ', $text);
			
			foreach($words as $word)
				$word = ltrim($word, '+-');
				
			unset($text);
		}

		// Remove stop words from queries
		if($is_query) {
			$words = $this->removeStopWords($words);
			
			// Remove min/max sizes
			// [TODO] Make this configurable
			$words = array_filter($words, function($word) {
				// Less than 3 characters and not a wildcard
				if(strlen($word) < 3 && !DevblocksPlatform::strEndsWith($word, '*'))
					return false;
				
				if(strlen($word) > 83)
					return false;
				
				return true;
			});
		}
		
		// Reassemble
		$text = implode(' ', $words);
		unset($words);
		
		// Flatten multiple spaces into a single
		$text = preg_replace('# +#', ' ', $text);
		
		return $text;
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=array()) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		$content = $this->_getTextFromDoc($doc);
		
		// If the table doesn't exist, create it at index time
		if(!isset($tables['fulltext_' . $this->escapeNamespace($ns)]))
			if(false === $this->_createTable($schema))
				return false;
		
		// Remove 4 byte characters
		// [TODO] Move to Devblocks?
		$content = preg_replace('%(?:
					\xF0[\x90-\xBF][\x80-\xBF]{2}
				| [\xF1-\xF3][\x80-\xBF]{3}
				| \xF4[\x80-\x8F][\x80-\xBF]{2}
		)%xs', '\xEF\xBF\xBD', $content);
		
		$fields = array(
			'id' => intval($id),
			'content' => $db->qstr($content),
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
		
		$return = (false !== $result) ? true : false;
		
		if($result instanceof mysqli_result)
			mysqli_free_result($result);
		
		return $return;
	}
	
	public function index(Extension_DevblocksSearchSchema $schema, $id, array $doc, array $attributes=array()) {
		return $this->_index($schema, $id, $doc, $attributes);
	}
	
	private function _createTable(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		$namespace = $schema->getNamespace();
		$attributes = $schema->getAttributes();
		
		$attributes_sql = array();
		
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
		
		$sql = sprintf(
			"CREATE TABLE IF NOT EXISTS fulltext_%s (
				id INT UNSIGNED NOT NULL DEFAULT 0,
				content LONGTEXT,
				%s
				PRIMARY KEY (id),
				FULLTEXT content (content)
			) ENGINE=%s CHARACTER SET=utf8;",
			$this->escapeNamespace($namespace),
			(!empty($attributes_sql) ? implode(",\n", $attributes_sql) : ''),
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
			return;
			
		$namespace = $this->escapeNamespace($ns);
		
		if(!isset($tables['fulltext_'.$namespace]))
			return true;
		
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