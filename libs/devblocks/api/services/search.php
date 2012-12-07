<?php
class _DevblocksSearchManager {
	static $_instance = null;
	
	/**
	 * @return _DevblocksSearchEngineMysqlFulltext
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksSearchEngineMysqlFulltext();
			return self::$_instance;
		}
		
		return self::$_instance;
	}
};

class _DevblocksSearchEngineMysqlFulltext {
	private $_db = null;
	
	public function __construct() {
		$db = DevblocksPlatform::getDatabaseService();
		$this->_db = $db->getConnection();
	}
	
	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNum($namespace, '\_'));
	}
	
	public function query($ns, $query, $limit=25, $boolean_mode=true) {
		$escaped_query = mysql_real_escape_string($query);
		
		// [TODO] Process the query

		if(!$boolean_mode) {
			$result = mysql_query(sprintf("SELECT id ".
				"FROM fulltext_%s ".
				"WHERE MATCH content AGAINST ('%s') ".
				"LIMIT 0,%d ",
				$this->escapeNamespace($ns),
				$escaped_query,
				$limit
			), $this->_db);
			
		} else {
			$result = mysql_query(sprintf("SELECT id, MATCH content AGAINST ('%s' IN BOOLEAN MODE) AS score ".
				"FROM fulltext_%s ".
				"WHERE MATCH content AGAINST ('%s' IN BOOLEAN MODE) ".
				"ORDER BY score DESC ".
				"LIMIT 0,%d ",
				$escaped_query,
				$this->escapeNamespace($ns),
				$escaped_query,
				$limit
			), $this->_db);
		}
		
		if(false == $result)
			return false;
			
		$ids = array();
		
		while($row = mysql_fetch_row($result)) {
			$ids[] = $row[0];
		}
		
		return $ids;
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
			'me' => true,
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
	
	public function prepareText($text) {
		$text = DevblocksPlatform::strUnidecode($text);

		//$string = preg_replace("/[^\p{Greek}\p{N}]/u", ' ', $string);
		
		if(function_exists('mb_ereg_replace')) {
			$text = mb_ereg_replace("[^[:alnum:]]", ' ', mb_convert_case($text, MB_CASE_LOWER));
		} else {
			$text = preg_replace("/[^[:alnum:]]/u", ' ', mb_convert_case($text, MB_CASE_LOWER));
		}
		
		$words = explode(' ', $text);
		unset($text);

		// Remove common words
		$stop_words = $this->_getStopWords();
		$words = array_diff($words, array_keys($stop_words));

		// Reassemble
		$text = implode(' ', $words);
		unset($words);
		
		// Flatten multiple spaces into a single
		$text = preg_replace('# +#', ' ', $text);
		
		return $text;
	}
	
	private function _index($ns, $id, $content, $replace=true) {
		$content = $this->prepareText($content);
		
		if($replace) {
			$result = mysql_query(sprintf("REPLACE INTO fulltext_%s VALUES (%d, '%s') ",
				$this->escapeNamespace($ns),
				$id,
				mysql_real_escape_string($content)
			), $this->_db);
			
		} else {
			$result = mysql_query(sprintf("UPDATE fulltext_%s SET content=CONCAT(content,' %s') WHERE id = %d",
				$this->escapeNamespace($ns),
				mysql_real_escape_string($content),
				$id
			), $this->_db);
		}
		
		return (false !== $result) ? true : false;
	}
	
	public function index($ns, $id, $content, $replace=true) {
		if(false === ($ids = $this->_index($ns, $id, $content, $replace))) {
			// Create the table dynamically
			if($this->_createTable($ns)) {
				return $this->_index($ns, $id, $content, $replace);
			}
			return false;
		}
		
		return true;
	}
	
	private function _createTable($namespace) {
		$rs = mysql_query("SHOW TABLES", $this->_db);

		$tables = array();
		while($row = mysql_fetch_row($rs)) {
			$tables[$row[0]] = true;
		}
		
		$namespace = $this->escapeNamespace($namespace);
		
		if(isset($tables['fulltext_'.$namespace]))
			return true;
		
		$result = mysql_query(sprintf(
			"CREATE TABLE IF NOT EXISTS fulltext_%s (
				id INT UNSIGNED NOT NULL DEFAULT 0,
				content LONGTEXT,
				PRIMARY KEY (id),
				FULLTEXT content (content)
			) ENGINE=MyISAM CHARACTER SET=utf8;", // MUST stay ENGINE=MyISAM
			$this->escapeNamespace($namespace)
		), $this->_db);
		
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