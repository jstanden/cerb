<?php

use Twig\Markup;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;

class _DevblocksTwigSecurityPolicy implements SecurityPolicyInterface {
	private $allowedTags;
	private $allowedFilters;
	private $allowedMethods;
	private $allowedProperties;
	private $allowedFunctions;
	
	public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedMethods = [], array $allowedProperties = [], array $allowedFunctions = []) {
		$this->allowedTags = $allowedTags;
		$this->allowedFilters = $allowedFilters;
		$this->setAllowedMethods($allowedMethods);
		$this->allowedProperties = $allowedProperties;
		$this->allowedFunctions = $allowedFunctions;
	}
	
	public function setAllowedTags(array $tags): void {
		$this->allowedTags = $tags;
	}
	
	public function setAllowedFilters(array $filters): void {
		$this->allowedFilters = $filters;
	}
	
	public function setAllowedMethods(array $methods): void {
		$this->allowedMethods = [];
		foreach ($methods as $class => $m) {
			$this->allowedMethods[$class] = array_map(function ($value) { return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'); }, \is_array($m) ? $m : [$m]);
		}
	}
	
	public function setAllowedProperties(array $properties): void {
		$this->allowedProperties = $properties;
	}
	
	public function setAllowedFunctions(array $functions): void {
		$this->allowedFunctions = $functions;
	}
	
	public function checkSecurity($tags, $filters, $functions): void {
		foreach ($tags as $tag) {
			if (!\in_array($tag, $this->allowedTags)) {
				throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
			}
		}
		
		foreach ($filters as $filter) {
			if (!\in_array($filter, $this->allowedFilters)) {
				throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
			}
		}
		
		foreach ($functions as $function) {
			if (!\in_array($function, $this->allowedFunctions)) {
				throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
			}
		}
	}
	
	public function checkMethodAllowed($obj, $method): void {
		if ($obj instanceof Template || $obj instanceof Markup) {
			return;
		}
		
		// Allow
		if($method == '__toString')
			return;
		
		$allowed = false;
		$method = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
		foreach ($this->allowedMethods as $class => $methods) {
			if ($obj instanceof $class) {
				$allowed = \in_array($method, $methods);
				
				break;
			}
		}
		
		if (!$allowed) {
			$class = \get_class($obj);
			throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
		}
	}
	
	public function checkPropertyAllowed($obj, $property): void {
		// Everything in our dictionary is okay
		if($obj instanceof DevblocksDictionaryDelegate)
			return;
		
		// Allow SimpleXMLElement objects
		if($obj instanceof SimpleXMLElement)
			return;
		
		$allowed = false;
		foreach ($this->allowedProperties as $class => $properties) {
			if ($obj instanceof $class) {
				$allowed = \in_array($property, \is_array($properties) ? $properties : [$properties]);
				
				break;
			}
		}
		
		if (!$allowed) {
			$class = \get_class($obj);
			throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
		}
	}
}

class _DevblocksTemplateBuilder {
	private $_twig = null;
	private $_errors = [];
	
	private function __construct($autoescaping=false) {
		$this->_twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]), [
			'cache' => false,
			'debug' => false,
			'strict_variables' => false,
			'auto_reload' => true,
			'trim_blocks' => true,
			'autoescape' => $autoescaping,
		]);
		
		if(class_exists('_DevblocksTwigExtensions', true)) {
			$this->_twig->addExtension(new _DevblocksTwigExtensions());
			
			// Sandbox Twig
			
			$tags = [
				'apply',
				//'autoescape',
				//'block',
				'do',
				//'embed',
				//'extends',
				'filter',
				//'flush',
				'for',
				//'from',
				'if',
				//'import',
				//'include',
				//'macro',
				'sandbox',
				'set',
				//'use',
				'verbatim',
				'with',
			];
			
			$filters = [
				'alphanum',
				'base_convert',
				'base64_encode',
				'base64_decode',
				'base64url_encode',
				'base64url_decode',
				'bytes_pretty',
				'cerb_translate',
				'context_alias',
				'context_name',
				'date_pretty',
				'hash_hmac',
				'json_pretty',
				'markdown_to_html',
				'md5',
				'parse_emails',
				'permalink',
				'quote',
				'regexp',
				'secs_pretty',
				'sha1',
				'spaceless',
				'split_crlf',
				'split_csv',
				'truncate',
				'unescape',
				'url_decode',
				
				'abs',
				'batch',
				'capitalize',
				'convert_encoding',
				'date',
				'date_modify',
				'default',
				'e',
				'escape',
				'first',
				'format',
				'join',
				'json_encode',
				'keys',
				'last',
				'length',
				'lower',
				'merge',
				'nl2br',
				'number_format',
				'raw',
				'replace',
				'reverse',
				'round',
				'slice',
				'sort',
				'split',
				'striptags',
				'title',
				'trim',
				'upper',
				'url_encode',
			];
			
			$functions = [
				'array_column',
				'array_combine',
				'array_diff',
				'array_intersect',
				'array_sort_keys',
				'array_unique',
				'array_values',
				'cerb_avatar_image',
				'cerb_avatar_url',
				'cerb_extract_uris',
				'cerb_file_url',
				'cerb_has_priv',
				'cerb_placeholders_list',
				'cerb_record_readable',
				'cerb_record_writeable',
				'cerb_url',
				'dict_set',
				'dict_unset',
				'json_decode',
				'jsonpath_set',
				'placeholders_list',
				'random_string',
				'regexp_match_all',
				'shuffle',
				'validate_email',
				'validate_number',
				'xml_attr',
				'xml_attrs',
				'xml_decode',
				'xml_encode',
				'xml_tag',
				'xml_xpath',
				'xml_xpath_ns',
				
				'attribute',
				//'block',
				//'constant',
				'cycle',
				'date',
				//'dump',
				//'include',
				'max',
				'min',
				//'parent',
				'random',
				'range',
				//'source',
				//'template_from_string',
			];
			
			$methods = [
				'SimpleXMLElement' => ['__toString'],
			];
			
			$properties = [
				'SimpleXMLElement' => ['*'],
			];
			
			$policy = new _DevblocksTwigSecurityPolicy($tags, $filters, $methods, $properties, $functions);
			$sandbox = new \Twig\Extension\SandboxExtension($policy, true);
			$this->_twig->addExtension($sandbox);
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
	
	static function newInstance($autoescaping=false) {
		return new _DevblocksTemplateBuilder($autoescaping);
	}

	/**
	 * @return \Twig\Environment
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
		$this->_errors = [];
	}
	
	private function _tearDown() {
	}
	
	function setLexer(\Twig\Lexer $lexer) {
		$this->_twig->setLexer($lexer);
	}
	
	function tokenize($templates) {
		$tokens = [];
		
		if(!is_array($templates))
			$templates = array($templates);

		foreach($templates as $template) {
			try {
				
				$token_stream = $this->_twig->tokenize($template); /* @var $token_stream \Twig\TokenStream */
				$node_stream = $this->_twig->parse($token_stream); /* @var $node_stream \Twig\Node\ModuleNode */
	
				$visitor = new _DevblocksTwigExpressionVisitor();
				$traverser = new \Twig\NodeTraverser($this->_twig);
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
	
	function addFilter(\Twig\TwigFilter $filter) {
		$this->_twig->addFilter($filter);
	}
	
	function addFunction(\Twig\TwigFunction $function) {
		$this->_twig->addFunction($function);
	}
	
	/**
	 *
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	function build($template, $dict, $lexer = null) {
		if($lexer && is_array($lexer)) {
			$this->setLexer(new \Twig\Lexer($this->_twig, $lexer));
		}
		
		$this->_setUp();
		$out = '';
		
		if(is_array($dict))
			$dict = new DevblocksDictionaryDelegate($dict);
		
		try {
			if(!is_null($template)) {
				$template = $this->_twig->createTemplate($template);
				$this->_twig->registerUndefinedVariableCallback([$dict, 'delegateUndefinedVariable']);
				
				$out = $template->render([]);
			}
			
		} catch(Exception $e) {
			$this->_errors[] = $e->getMessage();
		}
		$this->_tearDown();
		
		if($lexer) {
			$this->setLexer(new \Twig\Lexer($this->_twig));
		}

		if(!empty($this->_errors))
			return false;
		
		return $out;
	}
};

class DevblocksDictionaryDelegate implements JsonSerializable {
	private $_dictionary = null;
	private $_cached_contexts = null;
	private $_null = null;
	
	function __construct($dictionary) {
		if(is_array($dictionary))
		foreach($dictionary as $k => $v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_') && is_array($v)) {
				foreach($v as $id => $values) {
					if(is_array($values) && isset($values['_context'])) {
						$dictionary[$k][$id] = new DevblocksDictionaryDelegate($values);
					}
				}
			}
		}
		
		$this->_dictionary = $dictionary;
	}
	
	public static function instance($values) {
		return new DevblocksDictionaryDelegate($values);
	}
	
	function __toString() {
		$dictionary = $this->getDictionary(null, false);
		return DevblocksPlatform::strFormatJson(json_encode($dictionary));
	}
	
	function jsonSerialize() {
		return $this->_dictionary;
	}

	public function __set($name, $value) {
		// Clear the context cache if we're dynamically adding new contexts
		if(DevblocksPlatform::strEndsWith($name, '__context'))
			$this->clearCaches();
		
		$this->_dictionary[$name] = $value;
	}
	
	public function __unset($name) {
		unset($this->_dictionary[$name]);
	}
	
	public function clearCaches() {
		$this->_cached_contexts = null;
	}
	
	private function _cacheContexts() {
		$contexts = [];
		
		// Match our root context
		if(array_key_exists('_context', $this->_dictionary)) {
			$contexts[''] = [
				'key' => '_context',
				'prefix' => '',
				'context' => $this->_dictionary['_context'],
				'len' => 0,
			];
		}
		
		// Find the embedded contexts for each token
		foreach(array_keys($this->_dictionary) as $key) {
			$matches = [];
			
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
	
	public function get($name, $default=null) {
		$value = $this->$name;
		
		if(!is_null($value))
			return $value;
		
		return $default;
	}
	
	public function getKeyPath($name, $default=null) {
		$queue = $this->_getPathFromText($name);
		
		$ptr =& $this->_dictionary;
		
		if(is_array($queue))
		while(null !== ($k = array_shift($queue))) {
			if(is_array($ptr)) {
				if(!array_key_exists($k, $ptr)) {
					return $default;
				}
				
				$ptr =& $ptr[$k];
				
			} else {
				if(empty($queue)) {
					return $ptr;
				}
			}
		}
		
		return $ptr;
	}
	
	public function set($name, $value) {
		return $this->$name = $value;
	}
	
	public function _getPathFromText($name) {
		$path = explode('.', $name);
		return $path;
	}
	
	public function setKeyPath($name, $value) {
		$queue = $this->_getPathFromText($name);
		
		$ptr =& $this->_dictionary;
		
		if(is_array($queue))
		while(null !== ($k = array_shift($queue))) {
			if(!array_key_exists($k, $ptr)) {
				$ptr[$k] = [];
				$ptr =& $ptr[$k];
				
			} else if(!is_array($ptr[$k])) {
				if($queue)
					return false;
				
				$ptr =& $ptr[$k];
				
			} else {
				$ptr =& $ptr[$k];
			}
		}
		
		$ptr = $value;
		
		return $ptr;
	}
	
	public function setPush($name, $value) {
		$current_value = $this->get($name, []);
		
		if(!is_array($current_value))
			$current_value = [$current_value];
		
		array_push($current_value, $value);
		
		return $this->$name = $current_value;
	}
	
	public function unset($name) {
		return $this->__unset($name);
	}
	
	public function unsetKeyPath($name) {
		$path = $this->_getPathFromText($name);
		
		$ptr =& $this->_dictionary;
		
		while($k = array_shift($path)) {
			if(!array_key_exists($k, $ptr)) {
				return false;
			}
			
			if(empty($path)) {
				unset($ptr[$k]);
				return true;
				
			} else {
				$ptr =& $ptr[$k];
			}
		}
		
		return false;
	}
	
	public function &__get($name) {
		$name = $this->defuzzName($name);
		
		if($this->exists($name)) {
			return $this->_dictionary[$name];
		}
		
		// Lazy load
		
		$contexts = $this->getContextsForName($name);
		
		$is_cache_invalid = false;
		
		if(is_array($contexts))
		foreach($contexts as $context_data) {
			$context_ext = $this->_dictionary[$context_data['key']];
			
			$token = substr($name, strlen($context_data['prefix']));
			
			if(null == ($context = Extension_DevblocksContext::getByAlias($context_ext, true)))
				continue;
			
			if(!method_exists($context, 'lazyLoadContextValues'))
				continue;
	
			$local = $this->getDictionary($context_data['prefix'], false);
			
			$loaded_values = $context->lazyLoadContextValues($token, $local);
			
			// Push the context into the stack so we can track ancestry
			CerberusContexts::pushStack($context_data['context']);
			
			if(!is_array($loaded_values))
				continue;
			
			foreach($loaded_values as $k => $v) {
				$new_key = $context_data['prefix'] . $k;
				
				// Only invalidate the cache if we loaded new contexts the first time
				if(DevblocksPlatform::strEndsWith($new_key, '__context')
					&& !array_key_exists($new_key, $this->_dictionary)) {
					$is_cache_invalid = true;
				}
				
				if($k == '_types') {
					// If the parent has a `_types` key, append these values to it
					if(array_key_exists('_types', $this->_dictionary)) {
						foreach($v as $type_k => $type_v) {
							$this->_dictionary['_types'][$context_data['prefix'] . $type_k] = $type_v;
						}
					}
					continue;
				}
				
				// The getDictionary() call above already filters out _labels and _types
				
				if(array_key_exists($new_key, $this->_dictionary) && is_array($this->_dictionary[$new_key])) {
					$this->_dictionary[$new_key] = array_merge($this->_dictionary[$new_key], $v);
				} else {
					$this->_dictionary[$new_key] = $v;
				}
			}
		}
		
		if($is_cache_invalid)
			$this->clearCaches();
		
		if(is_array($contexts))
		for($n=0, $n_len=count($contexts); $n < $n_len; $n++) {
			CerberusContexts::popStack();
		}
		
		if(!array_key_exists($name, $this->_dictionary)) {
			// If the key isn't found and we invalidated the cache, recurse
			if($is_cache_invalid) {
				return $this->__get($name);
			} else {
				return $this->_null;
			}
		}
		
		return $this->_dictionary[$name];
	}
	
	// This lazy loads, and 'exists' doesn't.
	public function __isset($name) {
		if(null !== (@$this->__get($name)))
			return true;
		
		return false;
	}
	
	// Handle fuzzy key expansion
	public function defuzzName($name) {
		if($name == 'custom_') {
			return 'custom';
			
		} else if (DevblocksPlatform::strEndsWith($name, '_')) {
			$id_key = $name . 'id';
			$loaded_key = $name . '_loaded';
			
			// If the record was previously loaded
			if($this->exists($loaded_key,false)) {
				return $loaded_key;
				
			// If the ID is zero, consider the record previously loaded
			} else if($this->exists($id_key,false) && 0 == $this->get($id_key)) {
				return $id_key;
			}
		}
		
		return $name;
	}
	
	public function exists($name, $is_fuzzy=true) {
		if($is_fuzzy) {
			$name = $this->defuzzName($name);
		}
		
		return array_key_exists($name, $this->_dictionary);
	}
	
	public function delegateUndefinedVariable($name) {
		return $this->get($name);
	}
	
	public function getDictionary($with_prefix=null, $with_meta=true, $add_prefix=null) {
		$dict = $this->_dictionary;
		
		if(!$with_meta) {
			unset($dict['_labels']);
			unset($dict['_types']);
			unset($dict['__simulator_output']);
			unset($dict['__trigger']);
			unset($dict['__exit']);
		}
		
		// Convert any nested dictionaries to arrays
		array_walk_recursive($dict, function(&$v) use ($with_meta, $add_prefix) {
			if($v instanceof DevblocksDictionaryDelegate)
				$v = $v->getDictionary(null, $with_meta, $add_prefix);
		});
		
		if(!$with_prefix && !$add_prefix)
			return $dict;

		$new_dict = [];
		
		foreach($dict as $k => $v) {
			$len = strlen($with_prefix);
			if(0 == strcasecmp($with_prefix, substr($k,0,$len))) {
				$new_dict[$add_prefix . substr($k,$len)] = $v;
			}
		}
		
		return $new_dict;
	}
	
	public function scrubKeys($prefix) {
		if(is_array($this->_dictionary))
		foreach(array_keys($this->_dictionary) as $key) {
			if(DevblocksPlatform::strStartsWith($key, $prefix))
				unset($this->_dictionary[$key]);
		}
	}
	
	public function scrubKeySuffix($suffix) {
		if(is_array($this->_dictionary))
		foreach(array_keys($this->_dictionary) as $key) {
			if(DevblocksPlatform::strEndsWith($key, $suffix))
				unset($this->_dictionary[$key]);
		}
	}
	
	public function scrubKeyPathPrefix($name, $prefix) {
		$path = $this->_getPathFromText($name);
		
		$ptr =& $this->_dictionary;
		
		if(is_array($path))
		foreach($path as $k) {
			if(!array_key_exists($k, $ptr)) {
				return false;
			}
			
			$ptr =& $ptr[$k];
		}
		
		if(is_array($ptr))
		foreach(array_keys($ptr) as $key) {
			if(DevblocksPlatform::strStartsWith($key, $prefix))
				unset($ptr[$key]);
		}
		
		return true;
	}
	
	public function scrubKeyPathSuffix($name, $suffix) {
		$path = $this->_getPathFromText($name);
		
		$ptr =& $this->_dictionary;
		
		if(is_array($path))
		foreach($path as $k) {
			if(!array_key_exists($k, $ptr)) {
				return false;
			}
			
			$ptr =& $ptr[$k];
		}
		
		if(is_array($ptr))
		foreach(array_keys($ptr) as $key) {
			if(DevblocksPlatform::strEndsWith($key, $suffix))
				unset($ptr[$key]);
		}
		
		return true;
	}
	
	public function extract($prefix) {
		$values = [];
		
		if(is_array($this->_dictionary))
		foreach(array_keys($this->_dictionary) as $key) {
			if(DevblocksPlatform::strStartsWith($key, $prefix)) {
				$new_key = substr($key, strlen($prefix));
				$values[$new_key] = $this->_dictionary[$key];
			}
		}
		
		return DevblocksDictionaryDelegate::instance($values);
	}
	
	public function merge($token_prefix, $label_prefix, $src_labels, $src_values) {
		$dst_labels =& $this->_dictionary['_labels'];
		
		if(!is_array($dst_labels))
			return false;
		
		$dst_values =& $this->_dictionary;
		
		if(is_array($src_labels))
		foreach($src_labels as $token => $label) {
			$dst_labels[$token_prefix.$token] = $label_prefix.$label;
		}

		if(is_array($src_values))
		foreach($src_values as $token => $value) {
			if(in_array($token, array('_labels', '_types'))) {

				switch($token) {
					case '_labels':
						if(!isset($dst_values['_labels']))
							$dst_values['_labels'] = [];

						foreach($value as $key => $label) {
							$dst_values['_labels'][$token_prefix.$key] = $label_prefix.$label;
						}
						break;

					case '_types':
						if(!isset($dst_values['_types']))
							$dst_values['_types'] = [];

						foreach($value as $key => $type) {
							$dst_values['_types'][$token_prefix.$key] = $type;
						}
						break;
				}

			} else {
				$dst_values[$token_prefix.$token] = $value;
			}
		}
		
		return true;
	}
	
	public static function getDictionariesFromModels(array $models, $context, array $keys=[]) {
		$dicts = [];
		
		if(empty($models)) {
			return [];
		}
		
		$was_stack_max_empty_depth = CerberusContexts::setStackMaxEmptyDepth(1);
		
		foreach($models as $model_id => $model) {
			$labels = $values = [];
			
			if($context == CerberusContexts::CONTEXT_APPLICATION) {
				$values = ['_context' => $context, 'id' => 0, '_label' => 'Cerb'];
			} else {
				CerberusContexts::getContext($context, $model, $labels, $values, null, true, true);
			}
			
			if(isset($values['id']))
				$dicts[$model_id] = DevblocksDictionaryDelegate::instance($values);
		}
		
		// Batch load extra keys
		if(is_array($keys) && !empty($keys))
		foreach(array_unique($keys) as $key) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $key);
		}
		
		CerberusContexts::setStackMaxEmptyDepth($was_stack_max_empty_depth);
		
		return $dicts;
	}
	
	public static function bulkLazyLoad(array $dicts, $token, $skip_meta=false) {
		if(empty($dicts))
			return;
		
		// [TODO] Don't run (n) queries to lazy load custom fields
		
		// Examine contexts on the first dictionary
		/* @var DevblocksDictionaryDelegate $first_dict */
		$first_dict = reset($dicts);
		
		if(!($first_dict instanceof DevblocksDictionaryDelegate))
			return;
		
		if($first_dict->exists($token))
			return;
		
		// Get the list of embedded contexts
		$contexts = $first_dict->getContextsForName($token);
		
		foreach($contexts as $context_prefix => $context_data) {
			// The top-level context is always loaded
			if(empty($context_prefix))
				continue;
			
			$id_counts = [];
			
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
				
				$was_caching_loads = CerberusContexts::setCacheLoads(true);
				
				// These context loads will be cached
				if(is_array($models))
				foreach($models as $model) {
					$labels = $values = []; 
					CerberusContexts::getContext($context_data['context'], $model, $labels, $values, null, true, $skip_meta);
				}
				
				$prefix_key = $context_prefix . '_';
				
				// Load the contexts from the cache
				foreach($dicts as $dict) {
					$dict->$prefix_key;
				}
				
				// Flush the temporary cache
				CerberusContexts::setCacheLoads($was_caching_loads);
			}
		}
		
		// Now load the tokens, since we probably already lazy loaded the contexts
		foreach($dicts as $dict) { /* @var $dict DevblocksDictionaryDelegate */
			$dict->$token;
		}
	}
};

class _DevblocksTwigExpressionVisitor implements NodeVisitorInterface {
	protected $_tokens = [];
	
	public function enterNode(\Twig\Node\Node $node, \Twig\Environment $env) : \Twig\Node\Node {
		if($node instanceof \Twig\Node\Expression\NameExpression) {
			$this->_tokens[$node->getAttribute('name')] = true;
			
		} elseif($node instanceof \Twig\Node\SetNode) {
			$this->_tokens[$node->getAttribute('name')] = true;
			
		}
		return $node;
	}
	
	public function leaveNode(\Twig\Node\Node $node, \Twig\Environment $env) : \Twig\Node\Node  {
		return $node;
	}
	
	function getPriority() {
		return 0;
	}
	
	function getFoundTokens() {
		return array_keys($this->_tokens);
	}
};

class _DevblocksTwigExtensions extends \Twig\Extension\AbstractExtension {
	public function getName() {
		return 'devblocks_twig';
	}
	
	public function getFunctions() {
		return array(
			new \Twig\TwigFunction('array_column', [$this, 'function_array_column']),
			new \Twig\TwigFunction('array_combine', [$this, 'function_array_combine']),
			new \Twig\TwigFunction('array_diff', [$this, 'function_array_diff']),
			new \Twig\TwigFunction('array_intersect', [$this, 'function_array_intersect']),
			new \Twig\TwigFunction('array_sort_keys', [$this, 'function_array_sort_keys']),
			new \Twig\TwigFunction('array_unique', [$this, 'function_array_unique']),
			new \Twig\TwigFunction('array_values', [$this, 'function_array_values']),
			new \Twig\TwigFunction('cerb_avatar_image', [$this, 'function_cerb_avatar_image']),
			new \Twig\TwigFunction('cerb_avatar_url', [$this, 'function_cerb_avatar_url']),
			new \Twig\TwigFunction('cerb_extract_uris', [$this, 'function_cerb_extract_uris']),
			new \Twig\TwigFunction('cerb_file_url', [$this, 'function_cerb_file_url']),
			new \Twig\TwigFunction('cerb_has_priv', [$this, 'function_cerb_has_priv']),
			new \Twig\TwigFunction('cerb_placeholders_list', [$this, 'function_cerb_placeholders_list'], ['needs_environment' => true]),
			new \Twig\TwigFunction('cerb_record_readable', [$this, 'function_cerb_record_readable']),
			new \Twig\TwigFunction('cerb_record_writeable', [$this, 'function_cerb_record_writeable']),
			new \Twig\TwigFunction('cerb_url', [$this, 'function_cerb_url']),
			new \Twig\TwigFunction('dict_set', [$this, 'function_dict_set']),
			new \Twig\TwigFunction('dict_unset', [$this, 'function_dict_unset']),
			new \Twig\TwigFunction('json_decode', [$this, 'function_json_decode']),
			new \Twig\TwigFunction('jsonpath_set', [$this, 'function_jsonpath_set']),
			new \Twig\TwigFunction('placeholders_list', [$this, 'function_cerb_placeholders_list'], ['needs_environment' => true]),
			new \Twig\TwigFunction('random_string', [$this, 'function_random_string']),
			new \Twig\TwigFunction('regexp_match_all', [$this, 'function_regexp_match_all']),
			new \Twig\TwigFunction('shuffle', [$this, 'function_shuffle']),
			new \Twig\TwigFunction('validate_email', [$this, 'function_validate_email']),
			new \Twig\TwigFunction('validate_number', [$this, 'function_validate_number']),
			new \Twig\TwigFunction('xml_attr', [$this, 'function_xml_attr']),
			new \Twig\TwigFunction('xml_attrs', [$this, 'function_xml_attrs']),
			new \Twig\TwigFunction('xml_decode', [$this, 'function_xml_decode']),
			new \Twig\TwigFunction('xml_encode', [$this, 'function_xml_encode']),
			new \Twig\TwigFunction('xml_tag', [$this, 'function_xml_tag']),
			new \Twig\TwigFunction('xml_xpath_ns', [$this, 'function_xml_xpath_ns']),
			new \Twig\TwigFunction('xml_xpath', [$this, 'function_xml_xpath']),
		);
	}
	
	function function_array_column($array, $column_key, $index_key=null) {
		if(!is_array($array) || !is_string($column_key))
			return;
		
		return array_column($array, $column_key, $index_key);
	}
	
	function function_array_combine($keys, $values) {
		if(!is_array($keys) || !is_array($values))
			return;
		
		return array_combine($keys, $values);
	}
	
	function function_array_diff($arr1, $arr2) {
		if(!is_array($arr1) && is_null($arr1))
			$arr1 = [];
		
		if(!is_array($arr2) && is_null($arr2))
			$arr2 = [];
		
		if(!is_array($arr1) || !is_array($arr2))
			return;
		
		return array_diff($arr1, $arr2);
	}
	
	function function_array_intersect($arr1, $arr2) {
		if(!is_array($arr1) && is_null($arr1))
			$arr1 = [];
		
		if(!is_array($arr2) && is_null($arr2))
			$arr2 = [];

		if(!is_array($arr1) || !is_array($arr2))
			return;
		
		return array_intersect($arr1, $arr2);
	}
	
	function function_array_sort_keys($arr) {
		if(!is_array($arr))
			return;
		
		ksort($arr);
		
		return $arr;
	}
	
	function function_array_unique($arr) {
		if(!is_array($arr))
			return;
		
		return array_unique($arr);
	}
	
	function function_array_values($arr) {
		if(!is_array($arr))
			return;
		
		return array_values($arr);
	}
	
	function function_cerb_has_priv($priv, $actor_context=null, $actor_id=null) {
		if(is_null($actor_context) && is_null($actor_context)) {
			$active_worker = CerberusApplication::getActiveWorker();
			return $active_worker->hasPriv($priv);
		}
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($actor_context, true)))
		if(false == ($context_ext = Extension_DevblocksContext::get($actor_context)))
			return false;
		
		if(!($context_ext instanceof Context_Worker))
			return false;
		
		if(false == ($worker = DAO_Worker::get($actor_id)))
			return false;
		
		return $worker->hasPriv($priv);
	}
	
	function function_cerb_record_readable($record_context, $record_id, $actor_context=null, $actor_context_id=null) {
		if(is_null($actor_context) && is_null($actor_context)) {
			$actor = CerberusApplication::getActiveWorker();
		} else {
			$actor = [$actor_context, $actor_context_id];
		}
		
		return CerberusContexts::isReadableByActor($record_context, $record_id, $actor);
	}
	
	function function_cerb_record_writeable($record_context, $record_id, $actor_context=null, $actor_context_id=null) {
		if(is_null($actor_context) && is_null($actor_context)) {
			$actor = CerberusApplication::getActiveWorker();
		} else {
			$actor = [$actor_context, $actor_context_id];
		}
		
		return CerberusContexts::isWriteableByActor($record_context, $record_id, $actor);
	}
	
	function function_cerb_avatar_image($context, $id, $updated=0) {
		$url = $this->function_cerb_avatar_url($context, $id, $updated);
		
		return sprintf('<img src="%s" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;">',
			$url
		);
	}
	
	function function_cerb_avatar_url($context, $id, $updated=0) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return null;
		
		if(false == ($aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest)))
			return null;
		
		$type = @$aliases['uri'] ?: $context_ext->manifest->id;
		
		$url = $url_writer->write(sprintf('c=avatars&type=%s&id=%d', rawurlencode($type), $id), true, true);
		
		if($updated)
			$url .= '?v=' . intval($updated);
		
		return $url;
	}
	
	function function_cerb_extract_uris($html) {
		$filter = new Cerb_HTMLPurifier_URIFilter_Extract();
		
		$template = DevblocksPlatform::purifyHTML($html, false, true, [$filter]);
		
		$results = $filter->flush();
		
		$results['template'] = $template;
		
		return $results;
	}
	
	function function_cerb_file_url($id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($file = DAO_Attachment::get($id)))
			return null;
		
		return $url_writer->write(sprintf('c=files&id=%d&name=%s', $id, rawurlencode($file->name)), true, true);
	}
	
	function function_cerb_url($url, $full=true, $proxy=true) {
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->write($url, $full, $proxy);
	}
	
	function function_json_decode($str) {
		return json_decode($str, true);
	}
	
	function function_jsonpath_set($var, $path, $val) {
		if(empty($var))
			$var = [];
		
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
				$ptr[$part] = [];
			
			if($is_array_set) {
				$ptr =& $ptr[$part][];
				
			} else {
				$ptr =& $ptr[$part];
			}
		}
		
		$ptr = $val;
		
		return $var;
	}
	
	function function_cerb_placeholders_list(\Twig\Environment $env) {
		if(false == (@$callback = $env->getUndefinedVariableCallbacks()[0]) || !is_array($callback))
			return [];
		
		if(false == (@$dict = $callback[0]))
			return [];
		
		return $dict->getDictionary('', false);
	}
	
	function function_random_string($length=8) {
		$length = DevblocksPlatform::intClamp($length, 1, 255);
		return CerberusApplication::generatePassword($length);
	}
	
	function function_dict_set($var, $path, $val) {
		return DevblocksPlatform::arrayDictSet($var, $path, $val);
	}
	
	function function_dict_unset($var, $path) {
		return DevblocksPlatform::arrayDictUnset($var, $path);
	}
	
	function function_regexp_match_all($pattern, $text, $group = 0) {
		$group = intval($group);
		$matches = [];
		
		@preg_match_all($pattern, $text, $matches, PREG_PATTERN_ORDER);
		
		if(!empty($matches)) {
			
			if(empty($group))
				return $matches;
			
			if(is_array($matches) && isset($matches[$group])) {
				return $matches[$group];
			}
		}
		
		return [];
	}
	
	function function_xml_encode($xml) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		return $xml->asXML();
	}
	
	function function_xml_decode($str, $namespaces=[], $mode=null) {
		switch(DevblocksPlatform::strLower($mode)) {
			case 'html':
				$doc = new DOMDocument();
				$doc->loadHTML($str);
				$xml = simplexml_import_dom($doc);
				break;
				
			default:
				$xml = simplexml_load_string($str);
				break;
		}
		
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		if(is_array($namespaces))
		foreach($namespaces as $prefix => $ns)
			$xml->registerXPathNamespace($prefix, $ns);
		
		return $xml;
	}
	
	function function_xml_tag($xml) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		return $xml->getName();
	}
	
	function function_xml_attr($xml, $attr, $default=null) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		if(isset($xml[$attr]))
			return $xml[$attr];
		
		return $default;
	}
	
	function function_xml_attrs($xml) {
		if(!($xml instanceof SimpleXMLElement))
			return false;
		
		$attrs = [];
		
		foreach($xml->attributes() as $attr) {
			$attrs[$attr->getName()] = $attr->__toString();
		}

		return $attrs;
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
	
	function function_shuffle($array) {
		if(!is_array($array))
			return false;
		
		shuffle($array);
		
		return $array;
	}
	
	function function_validate_email($string) {
		if(!is_string($string))
			return false;
		
		if(!stripos($string, '@'))
			return false;
		
		if(false == ($addresses = CerberusMail::parseRfcAddresses($string)))
			return false;
		
		if(!is_array($addresses) || 1 != count($addresses))
			return false;
		
		return true;
	}
	
	function function_validate_number($number) {
		if(!is_numeric($number))
			return false;
		
		return true;
	}
	
	public function getFilters() {
		return array(
			new \Twig\TwigFilter('alphanum', [$this, 'filter_alphanum']),
			new \Twig\TwigFilter('base_convert', [$this, 'filter_base_convert']),
			new \Twig\TwigFilter('base64_encode', [$this, 'filter_base64_encode']),
			new \Twig\TwigFilter('base64_decode', [$this, 'filter_base64_decode']),
			new \Twig\TwigFilter('base64url_encode', [$this, 'filter_base64url_encode']),
			new \Twig\TwigFilter('base64url_decode', [$this, 'filter_base64url_decode']),
			new \Twig\TwigFilter('bytes_pretty', [$this, 'filter_bytes_pretty']),
			new \Twig\TwigFilter('cerb_translate', [$this, 'filter_cerb_translate']),
			new \Twig\TwigFilter('context_alias', [$this, 'filter_context_alias']),
			new \Twig\TwigFilter('context_name', [$this, 'filter_context_name']),
			new \Twig\TwigFilter('date_pretty', [$this, 'filter_date_pretty']),
			new \Twig\TwigFilter('hash_hmac', [$this, 'filter_hash_hmac']),
			new \Twig\TwigFilter('json_pretty', [$this, 'filter_json_pretty']),
			new \Twig\TwigFilter('markdown_to_html', [$this, 'filter_markdown_to_html']),
			new \Twig\TwigFilter('md5', [$this, 'filter_md5']),
			new \Twig\TwigFilter('parse_emails', [$this, 'filter_parse_emails']),
			new \Twig\TwigFilter('permalink', [$this, 'filter_permalink']),
			new \Twig\TwigFilter('quote', [$this, 'filter_quote']),
			new \Twig\TwigFilter('regexp', [$this, 'filter_regexp']),
			new \Twig\TwigFilter('secs_pretty', [$this, 'filter_secs_pretty']),
			new \Twig\TwigFilter('sha1', [$this, 'filter_sha1']),
			new \Twig\TwigFilter('split_crlf', [$this, 'filter_split_crlf']),
			new \Twig\TwigFilter('split_csv', [$this, 'filter_split_csv']),
			new \Twig\TwigFilter('truncate', [$this, 'filter_truncate']),
			new \Twig\TwigFilter('unescape', [$this, 'filter_unescape']),
			new \Twig\TwigFilter('url_decode', [$this, 'filter_url_decode']),
		);
	}
	
	function filter_alphanum($string, $also=null, $replace='') {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::strAlphaNum($string, $also, $replace);
	}
	
	function filter_base_convert($string, $base_from, $base_to) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string) && !is_numeric($string))
			return '';
		
		if(!is_numeric($base_from) || !is_numeric($base_to))
			return '';
		
		return base_convert($string, $base_from, $base_to);
	}
	
	function filter_base64_encode($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return base64_encode($string);
	}
	
	function filter_base64_decode($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return base64_decode($string);
	}
	
	function filter_base64url_encode($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::services()->string()->base64UrlEncode($string);
	}
	
	function filter_base64url_decode($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::services()->string()->base64UrlDecode($string);
	}
	
	function filter_bytes_pretty($string, $precision='0') {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string) && !is_numeric($string))
			return '';
		
		return DevblocksPlatform::strPrettyBytes($string, $precision);
	}
	
	function filter_cerb_translate($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		return DevblocksPlatform::translate($string);
	}
	
	function filter_context_alias($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		return $this->filter_context_name($string, 'uri');
	}
	
	function filter_context_name($string, $type='plural') {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		if(false == ($ctx_manifest = Extension_DevblocksContext::getByAlias($string, false)))
			return '';
		
		if('id' == $type)
			return $ctx_manifest->id;
		
		if('uri' == $type)
			return $ctx_manifest->params['alias'];
		
		if(false == ($aliases = Extension_DevblocksContext::getAliasesForContext($ctx_manifest)))
			return '';
		
		if(isset($aliases[$type]))
			return $aliases[$type];
		
		return '';
	}
	
	function filter_date_pretty($string, $is_delta=false) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string) && !is_numeric($string))
			return '';
		
		return DevblocksPlatform::strPrettyTime($string, $is_delta);
	}
	
	function filter_hash_hmac($string, $key='', $algo='sha256', $raw=false) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string) 
			|| !is_string($key) 
			|| !is_string($algo) 
			|| empty($string)
			)
			return '';
			
		if(false == ($hash = hash_hmac($algo, $string, $key, $raw)))
			return '';
		
		return $hash;
	}
	
	function filter_json_pretty($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::strFormatJson($string);
	}
	
	function filter_markdown_to_html($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		@$string = strval($string);
		return DevblocksPlatform::parseMarkdown($string);
	}
	
	function filter_md5($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return md5($string);
	}
	
	function filter_parse_emails($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		$results = CerberusMail::parseRfcAddresses($string);
		return $results;
	}
	
	function filter_permalink($string, $spaces_as='-') {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::strToPermalink($string, $spaces_as);
	}
	
	function filter_quote($string, $wrap_to=76) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		$lines = DevblocksPlatform::parseCrlfString(trim($string), true, false);
		
		array_walk($lines, function(&$line) {
			$line = '> ' . $line;
		});
		
		return _DevblocksTemplateManager::modifier_devblocks_email_quote(implode(PHP_EOL, $lines), $wrap_to);
	}

	function filter_regexp($string, $pattern, $group = 0) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		$matches = [];
		@preg_match($pattern, $string, $matches);
		
		$string = '';
		
		if(is_array($matches) && isset($matches[$group])) {
			$string = $matches[$group];
		}
		
		return $string;
	}
	
	function filter_secs_pretty($string, $precision=0) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_numeric($string))
			return '';
		
		return DevblocksPlatform::strSecsToString($string, $precision);
	}
	
	function filter_sha1($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return sha1($string);
	}
	
	function filter_split_crlf($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::parseCrlfString($string);
	}
	
	function filter_split_csv($string) {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		return DevblocksPlatform::parseCsvString($string);
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
		if($value instanceof Twig\Markup)
			$value = strval($value);
		
		if(!is_string($value))
			return '';
		
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
	
	function filter_unescape($string, $mode='html', $flags=null) {
		if(!is_string($string))
			$string = strval($string);
		
		return html_entity_decode($string, ENT_HTML401 | ENT_QUOTES); // $flags, LANG_CHARSET_CODE
	}
	
	function filter_url_decode($string, $as='') {
		if($string instanceof Twig\Markup)
			$string = strval($string);
		
		if(!is_string($string))
			return '';
		
		switch(DevblocksPlatform::strLower($as)) {
			case 'json':
				$array = DevblocksPlatform::strParseQueryString($string);
				return json_encode($array);
				break;
			
			default:
				return rawurldecode($string);
				break;
		}
	}
	
	public function getTests() {
		return array(
			new \Twig\TwigTest('numeric', [$this, 'test_numeric']),
		);
	}
	
	function test_numeric($value) {
		return is_numeric($value);
	}
};