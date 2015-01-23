<?php
class _DevblocksTemplateBuilder {
	private $_twig = null;
	private $_errors = array();
	
	private function _DevblocksTemplateBuilder() {
		$this->_twig = new Twig_Environment(new Twig_Loader_String(), array(
			'cache' => false,
			'debug' => false,
			'strict_variables' => false,
			'auto_reload' => true,
			'trim_blocks' => true,
			'autoescape' => false,
		));
		
		if(class_exists('_DevblocksTwigExtensions', true)) {
			$this->_twig->addExtension(new _DevblocksTwigExtensions());
		}
	}
	
	/**
	 *
	 * @return _DevblocksTemplateBuilder
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksTemplateBuilder();
		}
		return $instance;
	}

	/**
	 * @return Twig_Environment
	 */
	public function getEngine() {
		return $this->_twig;
	}
	
	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	private function _setUp() {
		$this->_errors = array();
	}
	
	private function _tearDown() {
	}
	
	function tokenize($templates) {
		$tokens = array();
		
		if(!is_array($templates))
			$templates = array($templates);

		foreach($templates as $template) {
			try {
				$token_stream = $this->_twig->tokenize($template); /* @var $token_stream Twig_TokenStream */
				$node_stream = $this->_twig->parse($token_stream); /* @var $node_stream Twig_Node_Module */
	
				$visitor = new _DevblocksTwigExpressionVisitor();
				$traverser = new Twig_NodeTraverser($this->_twig);
				$traverser->addVisitor($visitor);
				$traverser->traverse($node_stream);
				
				//var_dump($visitor->getFoundTokens());
				$tokens = array_merge($tokens, $visitor->getFoundTokens());
				
			} catch(Exception $e) {
				//var_dump($e->getMessage());
			}
		}
		
		$tokens = array_unique($tokens);
		
		return $tokens;
	}
	
	function stripModifiers($array) {
		array_walk($array, array($this,'_stripModifiers'));
		return $array;
	}
	
	function _stripModifiers(&$item, $key) {
		if(false != ($pos = strpos($item, '|'))) {
			$item = substr($item, 0, $pos);
		}
	}
	
	/**
	 *
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	function build($template, $dict) {
		$this->_setUp();
		
		if(is_array($dict))
			$dict = new DevblocksDictionaryDelegate($dict);
		
		try {
			$template = $this->_twig->loadTemplate($template); /* @var $template Twig_Template */
			$this->_twig->registerUndefinedVariableCallback(array($dict, 'delegateUndefinedVariable'), true);
			$out = $template->render(array());
			
		} catch(Exception $e) {
			$this->_errors[] = $e->getMessage();
		}
		$this->_tearDown();

		if(!empty($this->_errors))
			return false;
		
		return $out;
	}
};

class DevblocksDictionaryDelegate {
	private $_dictionary = null;
	private $_cached_contexts = null;
	private $_null = null;
	
	function __construct($dictionary) {
		$this->_dictionary = $dictionary;
	}

	public static function instance($values) {
		return new DevblocksDictionaryDelegate($values);
	}
	
	public function __set($name, $value) {
		$this->_dictionary[$name] = $value;
	}
	
	public function __unset($name) {
		unset($this->_dictionary[$name]);
	}
	
	private function _cacheContexts() {
		$contexts = array();
		
		// Match our root context
		if(isset($this->_dictionary['_context'])) {
			$contexts[''] = array(
				'key' => '_context',
				'prefix' => '',
				'context' => $this->_dictionary['_context'],
				'len' => 0,
			);
		}
		
		// Find the embedded contexts for each token
		foreach(array_keys($this->_dictionary) as $key) {
			if(preg_match('#(.*)__context#', $key, $matches)) {
				$contexts[$matches[1]] = array(
					'key' => $key,
					'prefix' => $matches[1] . '_',
					'context' => $this->_dictionary[$key],
					'len' => strlen($matches[1]),
				);
			}
		}
		
		DevblocksPlatform::sortObjects($contexts, '[len]', true);
		
		$this->_cached_contexts = $contexts;
	}
	
	public function getContextsForName($name) {
		if(is_null($this->_cached_contexts))
			$this->_cacheContexts();
		
		return array_filter($this->_cached_contexts, function($context) use ($name) {
			return substr($name, 0, strlen($context['prefix'])) == $context['prefix'];
		});
	}
	
	public function &__get($name) {
		if($this->exists($name))
			return $this->_dictionary[$name];
		
		// Lazy load
		
		$contexts = $this->getContextsForName($name);
		
		if(is_array($contexts))
		foreach($contexts as $context_data) {
			$context_ext = $this->_dictionary[$context_data['key']];
			
			$token = substr($name, strlen($context_data['prefix']));
			
			if(null == ($context = Extension_DevblocksContext::get($context_ext)))
				continue;
			
			if(!method_exists($context, 'lazyLoadContextValues'))
				continue;
	
			$local = $this->getDictionary($context_data['prefix'], false);
			
			$loaded_values = $context->lazyLoadContextValues($token, $local);

			// Push the context into the stack so we can track ancestry
			CerberusContexts::pushStack($context_data['context']);
			
			if(empty($loaded_values))
				continue;
			
			if(is_array($loaded_values))
			foreach($loaded_values as $k => $v) {
				// The getDictionary() call above already filters out _labels and _types
				$this->_dictionary[$context_data['prefix'] . $k] = $v;
			}
			
			// [TODO] Is there a better way to test that we loaded new contexts?
			$this->_cached_contexts = null;
		}
		
		if(is_array($contexts))
		for($n=0; $n < count($contexts); $n++)
			CerberusContexts::popStack();
		
		if(!$this->exists($name))
			return $this->_null;
		
		return $this->_dictionary[$name];
	}
	
	// This lazy loads, and 'exists' doesn't.
	public function __isset($name) {
		if(null !== (@$this->__get($name)))
			return true;
		
		return false;
	}
	
	public function exists($name) {
		return isset($this->_dictionary[$name]);
	}
	
	public function delegateUndefinedVariable($name) {
		return $this->$name;
	}
	
	public function getDictionary($with_prefix=null, $with_meta=true) {
		$dict = $this->_dictionary;
		
		if(!$with_meta) {
			unset($dict['_labels']);
			unset($dict['_types']);
		}
		
		// Convert any nested dictionaries to arrays
		array_walk_recursive($dict, function(&$v) use ($with_meta) {
			if($v instanceof DevblocksDictionaryDelegate)
				$v = $v->getDictionary(null, $with_meta);
		});
		
		if(empty($with_prefix))
			return $dict;

		$new_dict = array();
		
		foreach($dict as $k => $v) {
			$len = strlen($with_prefix);
			if(0 == strcasecmp($with_prefix, substr($k,0,$len))) {
				$new_dict[substr($k,$len)] = $v;
 			}
		}
		
		return $new_dict;
	}
	
	public function scrubKeys($prefix) {
		if(is_array($this->_dictionary))
		foreach(array_keys($this->_dictionary) as $key) {
			if($prefix == substr($key, 0, strlen($prefix)))
				unset($this->_dictionary[$key]);
		}
	}
	
	public static function bulkLazyLoad(array $dicts, $token) {
		// Examine contexts on the first dictionary
		$first_dict = reset($dicts);

		// Get the list of embedded contexts
		$contexts = $first_dict->getContextsForName($token);

		foreach($contexts as $context_prefix => $context_data) {
			// The top-level context is always loaded
			if(empty($context_prefix))
				continue;
			
			// If the context is already loaded, skip it
			$loaded_key = $context_prefix . '__loaded';
			if($first_dict->exists($loaded_key))
				continue;
			
			$id_counts = array();
			
			foreach($dicts as $dict) {
				$id_key = $context_prefix . '_id';
				$id = $dict->$id_key;
				
				if(!isset($id_counts[$id])) {
					$id_counts[$id] = 1;
					
				} else {
					$id_counts[$id]++;
				}
			}
			
			// Preload the contexts before lazy loading
			if(false != ($context_ext = Extension_DevblocksContext::get($context_data['context']))) {
				
				// Load model objects from the context
				$models = $context_ext->getModelObjects(array_keys($id_counts));
				
				CerberusContexts::setCacheLoads(true);
				
				// These context loads will be cached
				if(is_array($models))
				foreach($models as $model_id => $model) {
					$labels = array();
					$values = array();
					CerberusContexts::getContext($context_data['context'], $model, $labels, $values, null, true);
				}
				
				$prefix_key = $context_prefix . '_';
				
				// Load the contexts from the cache
				foreach($dicts as $dict) {
					$dict->$prefix_key;
				}
				
				// Flush the temporary cache
				CerberusContexts::setCacheLoads(false);
			}
		}
		
		// Now load the tokens, since we probably already lazy loaded the contexts
		foreach($dicts as $dict) { /* @var $dict DevblocksDictionaryDelegate */
			$dict->$token;
		}
	}
};

class _DevblocksTwigExpressionVisitor implements Twig_NodeVisitorInterface {
	protected $_tokens = array();
	
	public function enterNode(Twig_NodeInterface $node, Twig_Environment $env) {
		if($node instanceof Twig_Node_Expression_Name) {
			$this->_tokens[$node->getAttribute('name')] = true;
			
		} elseif($node instanceof Twig_Node_SetTemp) {
			$this->_tokens[$node->getAttribute('name')] = true;
			
		}
		return $node;
	}
	
	public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env) {
		return $node;
	}
	
 	function getPriority() {
 		return 0;
 	}
 	
 	function getFoundTokens() {
 		return array_keys($this->_tokens);
 	}
};

if(class_exists('Twig_Extension', true)):
class _DevblocksTwigExtensions extends Twig_Extension {
	public function getName() {
		return 'devblocks_twig';
	}
	
	public function getFunctions() {
		return array(
			'array_diff' => new Twig_Function_Method($this, 'function_array_diff'),
			'dict_set' => new Twig_Function_Method($this, 'function_dict_set'),
			'json_decode' => new Twig_Function_Method($this, 'function_json_decode'),
			'jsonpath_set' => new Twig_Function_Method($this, 'function_jsonpath_set'),
			'regexp_match_all' => new Twig_Function_Method($this, 'function_regexp_match_all'),
			'xml_decode' => new Twig_Function_Method($this, 'function_xml_decode'),
			'xml_encode' => new Twig_Function_Method($this, 'function_xml_encode'),
			'xml_xpath_ns' => new Twig_Function_Method($this, 'function_xml_xpath_ns'),
			'xml_xpath' => new Twig_Function_Method($this, 'function_xml_xpath'),
		);
	}
	
	function function_array_diff($arr1, $arr2) {
		if(!is_array($arr1) || !is_array($arr2))
			return;
		
		return array_diff($arr1, $arr2);
	}
	
	function function_json_decode($str) {
		return json_decode($str, true);
	}
	
	function function_jsonpath_set($var, $path, $val) {
		if(empty($var))
			$var = array();
		
		$parts = explode('.', $path);
		$ptr =& $var;
		
		if(is_array($parts))
		foreach($parts as $part) {
			$is_array_set = false;
		
			if(substr($part,-2) == '[]') {
				$part = rtrim($part, '[]');
				$is_array_set = true;
			}
		
			if(!isset($ptr[$part]))
				$ptr[$part] = array();
			
			if($is_array_set) {
				$ptr =& $ptr[$part][];
				
			} else {
				$ptr =& $ptr[$part];
			}
		}
		
		$ptr = $val;
		
		return $var;
	}
	
	function function_dict_set($var, $path, $val) {
		if(empty($var))
			$var = new stdClass();
		
		$parts = explode('.', $path);
		$ptr =& $var;
		
		if(is_array($parts))
		foreach($parts as $part) {
			if('[]' == $part) {
				if(is_array($ptr))
					$ptr =& $ptr[];
				
			} elseif(is_array($ptr)) {
				if(!isset($ptr[$part]))
					$ptr[$part] = array();

				$ptr =& $ptr[$part];
				
			} elseif(is_object($ptr)) {
				if(!isset($ptr->$part))
					$ptr->$part = array();
				
				$ptr =& $ptr->$part;
			}
		}
		
		$ptr = $val;
		
		return $var;
	}
	
	function function_regexp_match_all($pattern, $text, $group = 0) {
		$group = intval($group);
		
		@preg_match_all($pattern, $text, $matches, PREG_PATTERN_ORDER);
		
		if(!empty($matches)) {
			
			if(empty($group))
				return $matches;
			
			if(is_array($matches) && isset($matches[$group])) {
				return $matches[$group];
			}
		}
		
		return array();
	}
	
	function function_xml_encode($xml) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		return $xml->asXML();
	}
	
	function function_xml_decode($str, $namespaces=array()) {
		$xml = simplexml_load_string($str);
		
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		if(is_array($namespaces))
		foreach($namespaces as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);
		
		return $xml;
	}
	
	function function_xml_xpath_ns($xml, $prefix, $ns) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		$xml->registerXPathNamespace($prefix, $ns);
		
		return $xml;
	}
	
	function function_xml_xpath($xml, $path, $element=null) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		$result = $xml->xpath($path);
		
		if(!is_null($element) && isset($result[$element]))
			return $result[$element];
		
		return $result;
	}
	
	public function getFilters() {
		return array(
			'bytes_pretty' => new Twig_Filter_Method($this, 'filter_bytes_pretty'),
			'date_pretty' => new Twig_Filter_Method($this, 'filter_date_pretty'),
			'json_pretty' => new Twig_Filter_Method($this, 'filter_json_pretty'),
			'md5' => new Twig_Filter_Method($this, 'filter_md5'),
			'nlp_parse' => new Twig_Filter_Method($this, 'filter_nlp_parse'),
			'regexp' => new Twig_Filter_Method($this, 'filter_regexp'),
			'secs_pretty' => new Twig_Filter_Method($this, 'filter_secs_pretty'),
			'truncate' => new Twig_Filter_Method($this, 'filter_truncate'),
		);
	}
	
	function filter_bytes_pretty($string, $precision='0') {
		return DevblocksPlatform::strPrettyBytes($string, $precision);
	}
	
	function filter_date_pretty($string, $is_delta=false) {
		return DevblocksPlatform::strPrettyTime($string, $is_delta);
	}
	
	function filter_json_pretty($string) {
		return DevblocksPlatform::strFormatJson($string);
	}
	
	function filter_md5($string) {
		return md5($string);
	}
	
	function filter_nlp_parse($string, $patterns) {
		if(!is_array($patterns))
			$patterns = array($patterns);
		
		$nlp = DevblocksPlatform::getNaturalLanguageService();
		
		if(is_array($patterns))
		foreach($patterns as $pattern) {
			if(!is_string($pattern))
				continue;

			if(false !== ($json = $nlp->parseTextWithPattern($string, $pattern)))
				return json_encode($json);
		}
		 
		return null;
	}
	
	function filter_regexp($string, $pattern, $group = 0) {
		$matches = array();
		@preg_match($pattern, $string, $matches);
		
		$string = '';
		
		if(is_array($matches) && isset($matches[$group])) {
			$string = $matches[$group];
		}
		
		return $string;
	}
	
	function filter_secs_pretty($string, $precision=0) {
		return DevblocksPlatform::strSecsToString($string, $precision);
	}
	
	/**
	 * https://github.com/fabpot/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
	 *
	 * @param string $value
	 * @param integer $length
	 * @param boolean $preserve
	 * @param string $separator
	 *
	 */
	function filter_truncate($value, $length = 30, $preserve = false, $separator = '...') {
		if (mb_strlen($value, LANG_CHARSET_CODE) > $length) {
			if ($preserve) {
				if (false !== ($breakpoint = mb_strpos($value, ' ', $length, LANG_CHARSET_CODE))) {
					$length = $breakpoint;
				}
			}
			return mb_substr($value, 0, $length, LANG_CHARSET_CODE) . $separator;
		}
		return $value;
	}
};
endif;