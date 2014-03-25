<?php
class _DevblocksSearchManager {
	static $_instance = null;
	
	/**
	 * @return _DevblocksSearchEngine
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksSearchEngineMysqlFulltext();
			return self::$_instance;
		}
		
		return self::$_instance;
	}
};


interface IDevblocksSearchEngine {
	public function getQueryFromParam($param);
	public function query($class, $query, array $attributes=array(), $limit=250);
	public function index($class, $id, $content, array $attributes=array());
	public function delete($class, $ids);
}

abstract class _DevblocksSearchEngine implements IDevblocksSearchEngine {
	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNum($namespace, '\_'));
	}
	
	public function truncateOnWhitespace($content, $length) {
		$start = 0;
		$len = mb_strlen($content);
		$end = $start + $length;
		$next_ws = $end;
		
		// If our offset is past EOS, use the last pos
		if($end > $len) {
			$next_ws = $len;
			
		} else {
			if(false === ($next_ws = mb_strpos($content, ' ', $end)))
				if(false === ($next_ws = mb_strpos($content, "\n", $end)))
					$next_ws = $end;
		}
		
		return mb_substr($content, $start, $next_ws-$start);
	}
}

class _DevblocksSearchEngineMysqlFulltext extends _DevblocksSearchEngine {
	private $_db = null;
	
	public function __construct() {
		$db = DevblocksPlatform::getDatabaseService();
		$this->_db = $db->getConnection();
	}
	
	public function query($class, $query, array $attributes=array(), $limit=250) {
		$ns = call_user_func(array($class, 'getNamespace'));
		
		$escaped_query = mysql_real_escape_string($query);
		$where_sql = null;
		
		if(method_exists($class, 'getAttributes')) {
			$schema_attributes = call_user_func(array($class, 'getAttributes'));
			
			if(is_array($attributes))
			foreach($attributes as $attr => $attr_val) {
				@$attr_type = $schema_attributes[$attr];
				
				if(empty($attr_type))
					continue;
				
				switch($attr_type) {
					case 'string':
						$where_sql[] = sprintf("%s = '%s'",
							mysql_real_escape_string($attr),
							mysql_real_escape_string($attr_val)
						);
						break;
					
					case 'int':
					case 'int4':
					case 'int8':
						$where_sql[] = sprintf("%s = %d",
							mysql_real_escape_string($attr),
							$attr_val
						);
						break;
						
					case 'uint4':
					case 'uint8':
						$where_sql[] = sprintf("%s = %u",
							mysql_real_escape_string($attr),
							$attr_val
						);
						break;
				}
			}
		}
		
		$sql = sprintf("SELECT id, MATCH content AGAINST ('%s' IN BOOLEAN MODE) AS score ".
			"FROM fulltext_%s ".
			"WHERE MATCH content AGAINST ('%s' IN BOOLEAN MODE) ".
			"%s ".
			"ORDER BY score DESC ".
			"LIMIT 0,%d ",
			$escaped_query,
			$this->escapeNamespace($ns),
			$escaped_query,
			!empty($where_sql) ? ('AND ' . implode(' AND ', $where_sql)) : '',
			$limit
		);
		
		$result = mysql_query($sql, $this->_db);
		
		if(false == $result)
			return false;
			
		$ids = array();
		
		while($row = mysql_fetch_row($result)) {
			$ids[] = intval($row[0]);
		}
		
		return $ids;
	}
	
	public function getQueryFromParam($param) {
		$values = array();
		$value = null;
		$scope = null;

		if(!is_array($param->value) && !is_string($param->value))
			break;
		
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
	
	private function _index($class, $id, $content, $attributes=array()) {
		$ns = call_user_func(array($class, 'getNamespace'));
		$content = $this->prepareText($content);
		
		$fields = array(
			'id' => intval($id),
			'content' => sprintf("'%s'", mysql_real_escape_string($content)),
		);
		
		// Attributes
		if(method_exists($class, 'getAttributes')) {
			$schema_attributes = call_user_func(array($class, 'getAttributes'));
			
			if(is_array($attributes))
			foreach($attributes as $attr => $attr_val) {
				@$attr_type = $schema_attributes[$attr];
				
				if(empty($attr_type))
					continue;
				
				switch($attr_type) {
					case 'string':
						$fields[mysql_real_escape_string($attr)] = sprintf("'%s'", mysql_real_escape_string($attr_val));
						break;
					
					case 'int':
					case 'int4':
					case 'int8':
						$fields[mysql_real_escape_string($attr)] = sprintf("%d", $attr_val);
						break;
						
					case 'uint4':
					case 'uint8':
						$fields[mysql_real_escape_string($attr)] = sprintf("%u", $attr_val);
						break;
				}
			}
		}
		
		$sql = sprintf("REPLACE INTO fulltext_%s (%s) VALUES (%s) ",
			$this->escapeNamespace($ns),
			implode(',', array_keys($fields)),
			implode(',', $fields)
		);
		$result = mysql_query($sql, $this->_db);
		
		return (false !== $result) ? true : false;
	}
	
	public function index($class, $id, $content, array $attributes=array()) {
		if(false === ($ids = $this->_index($class, $id, $content, $attributes))) {
			// Create the table dynamically
			if($this->_createTable($class)) {
				return $this->_index($class, $id, $content, $attributes);
			}
			return false;
		}
		
		return true;
	}
	
	private function _createTable($class) {
		$namespace = call_user_func(array($class, 'getNamespace'));
		
		$attributes_sql = array();
		
		if(method_exists($class, 'getAttributes')) {
			$attributes = call_user_func(array($class, 'getAttributes'));
			
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
					mysql_real_escape_string($attr),
					mysql_real_escape_string($field_type)
				);
			}
		}
		
		$rs = mysql_query("SHOW TABLES", $this->_db);

		$tables = array();
		while($row = mysql_fetch_row($rs)) {
			$tables[$row[0]] = true;
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
			) ENGINE=MyISAM CHARACTER SET=utf8;", // MUST stay ENGINE=MyISAM
			$this->escapeNamespace($namespace),
			(!empty($attributes_sql) ? implode(",\n", $attributes_sql) : '')
		);
		
		$result = mysql_query($sql, $this->_db);
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return (false !== $result) ? true : false;
	}
	
	public function delete($ns, $ids) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(empty($ns) || empty($ids))
			return;
			
		$result = mysql_query(sprintf("DELETE FROM fulltext_%s WHERE id IN (%s) ",
			$this->escapeNamespace($ns),
			implode(',', $ids)
		), $this->_db);
		
		return (false !== $result) ? true : false;
	}
};