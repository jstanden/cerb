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
		
		while($row = mysqli_fetch_assoc($rs)) {
			$indexes[strtolower($row['Index'])] = strtolower($row['Type']);
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
		$tpl = DevblocksPlatform::getTemplateService();
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
	
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=500) {
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
		
		$sql = sprintf("SELECT id ".
			"FROM %s ".
			"WHERE MATCH ('(%s)%s') ".
			"%s ".
			"LIMIT 0,%d ",
			$this->escapeNamespace($index),
			mysqli_real_escape_string($this->db, $query),
			!empty($field_sql) ? (' ' . implode(' ', $field_sql)) : '',
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : '',
			$limit
		);

		$cache = DevblocksPlatform::getCacheService();
		$cache_key = sprintf("search:%s", md5($sql));
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
	
	public function getQueryFromParam($param) {
		$values = array();
		$value = null;
		$scope = null;

		if(!is_array($param->value) && !is_string($param->value))
			return false;
		
		if(!is_array($param->value) && preg_match('#^\[.*\]$#', $param->value)) {
			$values = json_decode($param->value, true);
			
		} elseif(is_array($param->value)) {
			$values = $param->value;
			
		} else {
			$values = $param->value;
			
		}
		
		if(!is_array($values)) {
			$value = $values;
			$scope = 'expert';
		} else {
			$value = $values[0];
			$scope = $values[1];
		}
		
		switch($scope) {
			case 'all':
				$value = $value;
				break;
				
			// OR
			case 'any':
				$words = explode(' ', $value);
				$value = implode(' | ', $words);
				break;
				
			case 'phrase':
				$value = '"'.$value.'"';
				break;
				
			default:
			case 'expert':
				// Left-hand wildcards aren't supported in Sphinx
				$value = ltrim($value, '*');
				
				// If this is a single term
				if(false === strpos($value, ' ')) {
					// If without quotes or wildcards, quote it (email addy, URL)
					if(false === strpos($value, '"') && false === strpos($value, '*')) {
						if(preg_match('#([\+\-]*)(\S*)#ui', $value, $matches))
							$value = sprintf('%s"%s"', $matches[1], $matches[2]);
					}
				}
				
				break;
		}
		
		return $value;
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
		if(false === ($ids = $this->_index($schema, $id, $doc, $attributes)))
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
			$result = mysqli_query($this->db, sprintf("DELETE FROM %s WHERE id = %d",
				$this->escapeNamespace($index_rt),
				$id
			));
		}
		
		return true;
	}
};

class DevblocksSearchEngineElasticSearch extends Extension_DevblocksSearchEngine {
	const ID = 'devblocks.search.engine.elasticsearch';
	
	private $_config = array();
	
	private function _execute($verb='GET', $url, $payload=array()) {
		$headers = array();
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		switch($verb) {
			case 'PUT':
				$headers[] = 'Content-Type: application/json';
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
				break;
		}
		
		$out = curl_exec($ch);
		
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		
		curl_close($ch);
		
		if($status != 200 || false == (@$json = json_decode($out, true)))
			return false; 
		
		return $json;
	}
	
	private function _putRecord($type, $id, $doc) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		$url = sprintf("%s/%s/%s/%d",
			$base_url,
			urlencode($index),
			urlencode($type),
			$id
		);
		
		if(false == ($json = $this->_execute('PUT', $url, $doc)))
			return false;
		
		return $json;
	}
	
	private function _getSearch($type, $query, $limit=500) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		// [TODO] Paging
		
		$url = sprintf("%s/%s/%s/_search?q=%s&_source=false&size=%d&default_operator=AND",
			$base_url,
			urlencode($index),
			urlencode($type),
			urlencode($query),
			$limit
		);
		
		if(false == ($json = $this->_execute('GET', $url)))
			return false;
		
		return $json;
	}
	
	private function _getCount($type) {
		@$base_url = rtrim($this->_config['base_url'], '/');
		@$index = trim($this->_config['index'], '/');
		
		if(empty($base_url) || empty($index) || empty($type))
			return false;
		
		$url = sprintf("%s/%s/%s/_count",
			$base_url,
			urlencode($index),
			urlencode($type)
		);
		
		if(false == ($json = $this->_execute('GET', $url)))
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
		
		if(false === ($json = $this->_execute('GET', $base_url)))
			return false;
		
		if(isset($json['status']) && 200 == $json['status']) {
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('engine', $this);
		
		$engine_params = $schema->getEngineParams();
		@$engine_extension_id = $engine_params['engine_extension_id'];
		
		if($engine_extension_id == $this->id & isset($engine_params['config']))
			$tpl->assign('engine_params', $engine_params['config']);
		
		$tpl->display('devblocks:devblocks.core::search_engine/elasticsearch.tpl');
	}
	
	public function getIndexMeta(Extension_DevblocksSearchSchema $schema) {
		@$index = $this->_config['index'];
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
			"(all of these words)",
			'("this exact phrase")',
			'(this OR that)',
			'[(this OR that) NOT (this OR that)]',
			'(wildcard*)',
			'(person@example.com)',
		);
	}
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=500) {
		@$type = $schema->getNamespace();
		
		if(empty($type))
			return false;
		
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$query .= sprintf(' %s:"%s"',
						$attr,
						$attr_val
					);
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$query .= sprintf(' %s:%d',
						$attr,
						$attr_val
					);
					break;
					
				case 'uint4':
				case 'uint8':
					$query .= sprintf(' %s:%d',
						$attr,
						$attr_val
					);
					break;
			}
		}
		
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = sprintf("elasticsearch:%s:%s", $type, md5($query));
		$is_only_cached_for_request = !$cache->isVolatile();
		
		if(null === ($ids = $cache->load($cache_key, false, $is_only_cached_for_request))) {
			$ids = array();
			$json = $this->_getSearch($type, $query, $limit);
			
			if(is_array($json) && isset($json['hits']))
			foreach($json['hits']['hits'] as $hit) {
				$ids[] = $hit['_id'];
			}
			
			$cache->save($ids, $cache_key, array(), 300, $is_only_cached_for_request);
		}
		
		return $ids;
	}
	
	public function getQueryFromParam($param) {
		$values = array();
		$value = null;
		$scope = null;

		if(!is_array($param->value) && !is_string($param->value))
			return false;
		
		if(!is_array($param->value) && preg_match('#^\[.*\]$#', $param->value)) {
			$values = json_decode($param->value, true);
			
		} elseif(is_array($param->value)) {
			$values = $param->value;
			
		} else {
			$values = $param->value;
			
		}
		
		if(!is_array($values)) {
			$value = $values;
			$scope = 'expert';
			
		} else {
			$value = $values[0];
			$scope = $values[1];
		}
		
		switch($scope) {
			case 'all':
				$value = $value;
				break;
				
			// OR
			case 'any':
				$words = explode(' ', $value);
				$value = implode(' | ', $words);
				break;
				
			case 'phrase':
				$value = '"'.$value.'"';
				break;
				
			default:
			case 'expert':
				break;
		}
		
		return $value;
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=array()) {
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
		
		$json = $this->_putRecord($type, $id, $doc);
		
		return true;
	}
	
	public function index(Extension_DevblocksSearchSchema $schema, $id, array $doc, array $attributes=array()) {
		if(false === ($ids = $this->_index($schema, $id, $doc, $attributes)))
			return false;
		
		return true;
	}

	public function delete(Extension_DevblocksSearchSchema $schema, $ids) {
		@$base_url = $this->_config['base_url'];
		@$index = $this->_config['index'];
		@$ns = $schema->getNamespace();
		
		/*
		if(!is_array($ids))
			$ids = array($ids);
			
		foreach($ids as $id) {
			$result = mysqli_query($this->db, sprintf("DELETE FROM %s WHERE id = %d",
				$this->escapeNamespace($index_rt),
				$id
			));
		}
		*/
		
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
		$tpl = DevblocksPlatform::getTemplateService();
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
		);
	}
	
	private function _getMaxId(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::getDatabaseService();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		return intval($db->GetOneSlave(sprintf("SELECT MAX(id) FROM fulltext_%s", $db->escape($ns))));
	}
	
	private function _getCount(Extension_DevblocksSearchSchema $schema) {
		$db = DevblocksPlatform::getDatabaseService();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();

		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		return intval($db->GetOneSlave(sprintf("SELECT COUNT(id) FROM fulltext_%s", $db->escape($ns))));
	}
	
	public function getQuickSearchExamples(Extension_DevblocksSearchSchema $schema) {
		return array(
			'(+all +of +these +terms)',
			'(+this -that)',
			'"a multiple word phrase"',
			'("any" "of" "these terms")',
			'person@example.com',
		);
	}
	
	public function query(Extension_DevblocksSearchSchema $schema, $query, array $attributes=array(), $limit=500) {
		$db = DevblocksPlatform::getDatabaseService();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		if(!isset($tables['fulltext_' . $ns]))
			return false;
		
		$escaped_query = $db->escape($query);
		$where_sql = null;
		
		$schema_attributes = $schema->getAttributes();
		
		if(is_array($attributes))
		foreach($attributes as $attr => $attr_val) {
			@$attr_type = $schema_attributes[$attr];
			
			if(empty($attr_type))
				continue;
			
			switch($attr_type) {
				case 'string':
					$where_sql[] = sprintf("%s = '%s'",
						$db->escape($attr),
						$db->escape($attr_val)
					);
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$where_sql[] = sprintf("%s = %d",
						$db->escape($attr),
						$attr_val
					);
					break;
					
				case 'uint4':
				case 'uint8':
					$where_sql[] = sprintf("%s = %u",
						$db->escape($attr),
						$attr_val
					);
					break;
			}
		}

		// The max desired results (blank for unlimited)
		$max_results = isset($this->_config['max_results']) ? intval($this->_config['max_results']) : 0;

		// Our temp table name is consistently named because we may keep it around for the duration of the request
		$temp_table = sprintf("_search_%s", sha1($ns.$query));
		
		$sql = sprintf("CREATE TEMPORARY TABLE IF NOT EXISTS %s (PRIMARY KEY (id)) ".
			"SELECT id, MATCH content AGAINST ('%s' IN BOOLEAN MODE) AS score ".
			"FROM fulltext_%s ".
			"WHERE MATCH content AGAINST ('%s' IN BOOLEAN MODE) ".
			"%s ".
			"ORDER BY score DESC ".
			($max_results ? sprintf("LIMIT 0,%d ", $max_results) : ''),
			$temp_table,
			$escaped_query,
			$this->escapeNamespace($ns),
			$escaped_query,
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : ''
		);
		
		$db->ExecuteSlave($sql);
		
		return $temp_table;
	}
	
	public function getQueryFromParam($param) {
		$values = array();
		$value = null;
		$scope = null;

		if(!is_array($param->value) && !is_string($param->value))
			return false;
		
		if(!is_array($param->value) && preg_match('#^\[.*\]$#', $param->value)) {
			$values = json_decode($param->value, true);
			
		} elseif(is_array($param->value)) {
			$values = $param->value;
			
		} else {
			$values = $param->value;
			
		}
		
		if(!is_array($values)) {
			$value = $values;
			$scope = 'expert';
		} else {
			$value = $values[0];
			$scope = $values[1];
		}
		
		switch($scope) {
			case 'all':
				$value = $this->prepareText($value);
				$value = '+'.str_replace(' ', ' +', $value);
				break;
				
			case 'any':
				$value = $this->prepareText($value);
				break;
				
			case 'phrase':
				$value = '"'.$this->prepareText($value).'"';
				break;
				
			default:
			case 'expert':
				// We don't want to strip punctuation in expert mode
				$value = DevblocksPlatform::strUnidecode($value);
				
				// Left-hand wildcards aren't supported in MySQL fulltext
				$value = ltrim($value, '*');
				
				// If this is a single term
				if(false === strpos($value, ' ')) {
					// If without quotes, wildcards, or plus, quote it (email addy, URL)
					if(false === strpos($value, '"')
						&& false === strpos($value, '*')
						&& false === strpos($value, '+')
						) {
						if(preg_match('#([\+\-]*)(\S*)#ui', $value, $matches))
							$value = sprintf('%s"%s"', $matches[1], $matches[2]);
					}
					
				} else {
					$search = $this;
					
					// If the user provided their own quotes
					if(false !== strpos($value, '"')) {
						
						// Extract quotes and remove stop words
						$value = preg_replace_callback(
							'#"(.*?)"#',
							function($matches) use ($search) {
								return sprintf('"%s"', implode(' ', $search->removeStopWords(explode(' ', $matches[1]))));
							},
							$value
						);
							
					// If the user didn't provide their own quotes
					} else {
						
						// And they didn't use wildcards or booleans
						if(false === strpos($value, '*') && false === strpos($value, '+')) {
							// Wrap the entire text in quotes
							$value = '"' . implode(' ', $search->removeStopWords(explode(' ', $value))) . '"';
							
						// Or they did use wildcards
						} else if (false !== strpos($value, '*')) {
							// Split terms on spaces
							$terms = explode(' ', $value);
							
							// Quote each term if it doesn't contain wildcards
							foreach($terms as $term_idx => $term) {
								if(false === strpos($term, '*')) {
									$matches = null;
									if(preg_match('#([\+\-]*)(\S*)#ui', $term, $matches)) {
										$terms[$term_idx] = sprintf('%s"%s"', $matches[1], $matches[2]);
									}
								}
							}
							
							$value = implode(' ', $terms);
						}
						
					}
				}
				
				break;
		}
		
		return $value;
	}
	
	public function removeStopWords($words) {
		$stop_words = $this->_getStopWords();
		return array_diff($words, array_keys($stop_words));
	}
	
	private function _getStopWords() {
		// English
		$words = array(
			'' => true,
			'a' => true,
			'about' => true,
			'all' => true,
			'am' => true,
			'an' => true,
			'and' => true,
			'any' => true,
			'as' => true,
			'at' => true,
			'are' => true,
			'be' => true,
			'been' => true,
			'but' => true,
			'by' => true,
			'can' => true,
			'could' => true,
			'did' => true,
			'do' => true,
			'doesn\'t' => true,
			'don\'t' => true,
			'e.g.' => true,
			'eg' => true,
			'for' => true,
			'from' => true,
			'get' => true,
			'had' => true,
			'has' => true,
			'have' => true,
			'hello' => true,
			'hi' => true,
			'how' => true,
			'i' => true,
			'i.e.' => true,
			'ie' => true,
			'i\'m' => true,
			'if' => true,
			'in' => true,
			'into' => true,
			'is' => true,
			'it' => true,
			'it\'s' => true,
			'its' => true,
			'may' => true,
			'my' => true,
			'not' => true,
			'of' => true,
			'on' => true,
			'or' => true,
			'our' => true,
			'out' => true,
			'please' => true,
			'p.s.' => true,
			'ps' => true,
			'so' => true,
			'than' => true,
			'thank' => true,
			'thanks' => true,
			'that' => true,
			'the' => true,
			'their' => true,
			'them' => true,
			'then' => true,
			'there' => true,
			'these' => true,
			'they' => true,
			'this' => true,
			'those' => true,
			'to' => true,
			'us' => true,
			'want' => true,
			'was' => true,
			'we' => true,
			'were' => true,
			'what' => true,
			'when' => true,
			'which' => true,
			'while' => true,
			'why' => true,
			'will' => true,
			'with' => true,
			'would' => true,
			'you' => true,
			'your' => true,
			'you\'re' => true,
		);
		return $words;
	}
	
	public function prepareText($text) {
		$text = DevblocksPlatform::strUnidecode($text);

		$text = str_replace("'", '', $text);
		
		if(function_exists('mb_ereg_replace')) {
			$text = mb_ereg_replace("[^[:alnum:]]", ' ', mb_convert_case($text, MB_CASE_LOWER));
		} else {
			$text = preg_replace("/[^[:alnum:]]/u", ' ', mb_convert_case($text, MB_CASE_LOWER));
		}
		
		$words = explode(' ', $text);
		unset($text);

		// Remove common words
		$words = $this->removeStopWords($words);

		// Reassemble
		$text = implode(' ', $words);
		unset($words);
		
		// Flatten multiple spaces into a single
		$text = preg_replace('# +#', ' ', $text);
		
		return $text;
	}
	
	private function _index(Extension_DevblocksSearchSchema $schema, $id, array $doc, $attributes=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		$tables = DevblocksPlatform::getDatabaseTables();
		$ns = $schema->getNamespace();
		
		$content = $this->_getTextFromDoc($doc);
		
		$content = $this->prepareText($content);
		
		// If the table doesn't exist, create it at index time
		if(!isset($tables['fulltext_' . $this->escapeNamespace($ns)]))
			if(false === $this->_createTable($schema))
				return false;
		
		$fields = array(
			'id' => intval($id),
			'content' => sprintf("'%s'", $db->escape($content)),
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
					$fields[$db->escape($attr)] = sprintf("'%s'", $db->escape($attr_val));
					break;
				
				case 'int':
				case 'int4':
				case 'int8':
					$fields[$db->escape($attr)] = sprintf("%d", $attr_val);
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
		$db = DevblocksPlatform::getDatabaseService();
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
			APP_DB_ENGINE_FULLTEXT
		);
		
		$result = $db->ExecuteMaster($sql);
		
		$return = (false !== $result) ? true : false;
		
		if($result instanceof mysqli_result)
			mysqli_free_result($result);
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return $return;
	}
	
	public function delete(Extension_DevblocksSearchSchema $schema, $ids) {
		$db = DevblocksPlatform::getDatabaseService();
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