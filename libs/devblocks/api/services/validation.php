<?php
class Exception_DevblocksValidationError extends Exception_Devblocks {};

class DevblocksValidationField {
	public $_name = null;
	public $_label = null;
	public $_type = null;
	
	function __construct($name, $label=null) {
		$this->_name = $name;
		$this->_label = $label ?: $name;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeArray
	 */
	function array() {
		$this->_type = new _DevblocksValidationTypeArray('array');
		
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function bit() {
		$this->_type = new _DevblocksValidationTypeNumber('bit');
		
		// Defaults for bit type
		return $this->_type
			->setMin(0)
			->setMax(1)
			;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeBoolean
	 */
	function boolean() {
		$this->_type = new _DevblocksValidationTypeBoolean('boolean');
		
		// Defaults for booleanß type
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeContext
	 */
	function context() {
		$this->_type = new _DevblocksValidationTypeContext('context');
		$validation = DevblocksPlatform::services()->validation();
		return $this->_type
			->addFormatter($validation->formatters()->context())
			;
	}
	
	function error() : _DevblocksValidationTypeError {
		$this->_type = new  _DevblocksValidationTypeError('error');
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeFloat
	 */
	function float() {
		$this->_type = new _DevblocksValidationTypeFloat('float');
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeGeoPoint
	 */
	function geopoint() {
		$this->_type = new _DevblocksValidationTypeGeoPoint('geopoint');
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function id() {
		$this->_type = new _DevblocksValidationTypeNumber('id');
		
		// Defaults for id type
		return $this->_type
			->setMin(0)
			->setMax('32 bits')
			;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeIdArray
	 */
	function idArray() {
		$this->_type = new _DevblocksValidationTypeIdArray('idArray');
		return $this->_type;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeString
	 */
	function image($type='image/png', $min_width=1, $min_height=1, $max_width=1000, $max_height=1000, $max_size=512000) {
		$validation = DevblocksPlatform::services()->validation();
		$this->_type = new _DevblocksValidationTypeString('image');
		return $this->_type
			->setMaxLength(512000)
			->addValidator($validation->validators()->image($type, $min_width, $min_height, $max_width, $max_height, $max_size))
			;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function number() {
		$this->_type = new _DevblocksValidationTypeNumber('number');
		return $this->_type;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeString
	 */
	function string($options = 0) {
		$validation = DevblocksPlatform::services()->validation();
		$this->_type = new _DevblocksValidationTypeString('string');
		
		// Default max length
		$this->_type->setMaxLength(255);
		
		// Default truncation enabled
		$this->_type->setTruncation();
		
		// If utf8mb4 is not enabled for this field, strip 4-byte chars
		if(0 == $options & _DevblocksValidationService::STRING_UTF8MB4) {
			$this->_type->addFormatter($validation->formatters()->stringWithoutEmoji());
		}
		
		return $this->_type;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeStringOrArray
	 */
	function stringOrArray() {
		$this->_type = new _DevblocksValidationTypeStringOrArray('stringOrArray');
		return $this->_type
			->setMaxLength(255)
			->setTruncation()
			;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeNumber
	 */
	function timestamp() {
		$this->_type = new _DevblocksValidationTypeNumber('timestamp');
		return $this->_type
			->setMin(0)
			->setMax('32 bits') // 4 unsigned bytes
			;
	}
	
	/**
	 *
	 * @return _DevblocksValidationTypeNumber
	 */
	function uint($bytes=4) {
		$this->_type = new _DevblocksValidationTypeNumber('uint');
		return $this->_type
			->setMin(0)
			->setMax('32 bits')
			;
	}
	
	/**
	 * 
	 * @return _DevblocksValidationTypeString
	 */
	function url() {
		$validation = DevblocksPlatform::services()->validation();
		$this->_type = new _DevblocksValidationTypeString('url');
		
		return $this->_type
			->setMaxLength(255)
			->addValidator($validation->validators()->url())
			;
	}
}

class _DevblocksFormatters {
	function context($allow_empty=false) {
		return function(&$value, &$error=null) use ($allow_empty) {
			if(empty($value) && $allow_empty)
				return true;
			
			// If this is a valid fully qualified extension ID, accept
			if(false != (Extension_DevblocksContext::get($value, false)))
				return true;
			
			// Otherwise, check aliases
			if(false != ($context_mft = Extension_DevblocksContext::getByAlias($value, false))) {
				$value = $context_mft->id;
				return true;
			}
			
			$error = "is not a valid context.";
			return false;
		};
	}
	
	function stringUpper() {
		return function(&$value, &$error=null) {
			if(!is_null($value) && !is_string($value)) {
				$error = "is not a string.";
				return false;
			}

			$value = DevblocksPlatform::strUpper($value);
			return true;
		};
	}
	
	function stringWithoutEmoji() {
		return function(&$value, &$error=null) {
			if(!is_null($value) && !is_string($value)) {
				$error = "is not a string.";
				return false;
			}
			
			if(DevblocksPlatform::services()->string()->has4ByteChars($value)) {
				$value = DevblocksPlatform::services()->string()->strip4ByteChars($value);
			}
			
			return true;
		};
	}
}

class _DevblocksValidators {
	function context($allow_empty=false) {
		return function($value, &$error=null) use ($allow_empty) {
			if(empty($value) & $allow_empty)
				return true;
			
			if(false == (Extension_DevblocksContext::getByAlias($value, false))) {
				$error = sprintf("is not a valid context (%s)", $value);
				return false;
			}
			
			return true;
		};
	}
	
	function contextId($context, $allow_empty=false) {
		return function(&$value, &$error=null) use ($context, $allow_empty) {
			if(!is_numeric($value)) {
				if(DevblocksPlatform::strStartsWith($value, 'cerb:')) {
					if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($value))) {
						$error = "must be an ID or URI.";
						return false;
					}
					
					if(!CerberusContexts::isSameContext($uri_parts['context'], $context)) {
						$error = sprintf('(%s) must be a URI of type `%s`', $value, $context);
						return false;
					}
					
					if(is_numeric($uri_parts['context_id'])) {
						$value = $uri_parts['context_id'];
					} else {
						$context_ext = Extension_DevblocksContext::get($uri_parts['context'], true);
						$value = $context_ext->getContextIdFromAlias($uri_parts['context_id']);
					}
					
				} else {
					$error = "must be an ID or URI.";
					return false;
				}
			}
			
			$value = intval($value);
			
			if(empty($value)) {
				if($allow_empty) {
					return true;
					
				} else {
					$error = "must not be blank.";
					return false;
				}
			}
			
			$models = CerberusContexts::getModels($context, [$value]);
			
			if(!isset($models[$value])) {
				$error = "is not a valid target record.";
				return false;
			}
			
			return true;
		};
	}
	
	function contextIds($context, $allow_empty=false) {
		return function($value, &$error=null) use ($context, $allow_empty) {
			if(!is_array($value)) {
				$error = "must be an array of IDs.";
				return false;
			}
			
			$ids = array_filter(DevblocksPlatform::sanitizeArray($value, 'int'), function($v) {
				return $v != 0;
			});
			
			if(empty($ids)) {
				if($allow_empty) {
					return true;
					
				} else {
					$error = sprintf("must not be blank.");
					return false;
				}
			}
			
			$models = CerberusContexts::getModels($context, $ids);
			
			foreach($ids as $id) {
				if(!isset($models[$id])) {
					$error = sprintf("value (%d) is not a valid target record.", $id);
					return false;
				}
			}
			
			return true;
		};
	}
	
	function date() {
		return function($value, &$error) {
			if(empty($value))
				return true;
			
			if(false === strtotime($value)) {
				$error = sprintf("(%s) is not a valid date string. Format like: tomorrow 8am",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	function email($allow_empty=false) : callable {
		return function(&$value, &$error=null) use ($allow_empty) {
			if($allow_empty && 0 == strlen($value))
				return true;
			
			if(!is_string($value)) {
				$error = "must be a string.";
				return false;
			}
			
			$validated_emails = CerberusMail::parseRfcAddresses($value);
			
			if(!is_array($validated_emails) || !$validated_emails) {
				$error = "is invalid. It must be a properly formatted email address.";
				return false;
			}
			
			if(1 !== count($validated_emails)) {
				$error = "must be a single email address.";
				return false;
			}
			
			$value = DevblocksPlatform::strLower(key($validated_emails));
			
			return true;
		};
	}
	
	function emails($allow_empty=false) : callable {
		return function(&$value, &$error=null) use ($allow_empty) {
			if($allow_empty && 0 == strlen($value))
				return true;
			
			if(!is_string($value)) {
				$error = "must be a comma-separated string.";
				return false;
			}
			
			$validated_emails = CerberusMail::parseRfcAddresses($value);
			
			if(empty($validated_emails) || !is_array($validated_emails)) {
				$error = "is invalid. It must be a comma-separated list of properly formatted email address.";
				return false;
			}
			
			$value = implode(', ', array_map(['DevblocksPlatform','strUpper'], array_keys($validated_emails)));
			
			return true;
		};
	}
	
	function extension($extension_class) {
		return function($value, &$error=null) use ($extension_class) {
			if(false == ($extension_class::get($value))) {
				$error = sprintf("(%s) is not a valid extension ID on (%s).",
					$value,
					$extension_class::POINT
				);
				return false;
			}
			
			return true;
		};
	}
	
	function image($type='image/png', $min_width=1, $min_height=1, $max_width=1000, $max_height=1000, $max_size=512000, $is_nullable=true) {
		return function($value, &$error=null) use ($type, $min_width, $max_width, $min_height, $max_height, $max_size, $is_nullable) {
			if(!is_string($value)) {
				$error = "must be a base64-encoded string.";
				return false;
			}
			
			if(!DevblocksPlatform::strStartsWith($value, 'data:')) {
				$error = "must be start with 'data:'.";
				return false;
			}
			
			$imagedata = substr($value, 5);
			
			if($is_nullable && 'null' == $imagedata) {
				return true;
			}
			
			if(!DevblocksPlatform::strStartsWith($imagedata,'image/png;base64,')) {
				$error = "must be a base64-encoded string with the format 'data:image/png;base64,<data>";
				return false;
			}
			
			// Decode it to binary
			if(false == ($imagedata = base64_decode(substr($imagedata, 17)))) {
				$error = "does not contain a valid base64 encoded PNG image.";
				return false;
			}
			
			if(strlen($imagedata) > $max_size) {
				$error = sprintf("must be smaller than %s (%s).", DevblocksPlatform::strPrettyBytes($max_size), DevblocksPlatform::strPrettyBytes(strlen($imagedata)));
				return false;
			}
			
			// Verify the "magic bytes": 89 50 4E 47 0D 0A 1A 0A
			if('89504e470d0a1a0a' != bin2hex(substr($imagedata,0,8))) {
				$error = "is not a valid PNG image.";
				return false;
			}
			
			// Test dimensions
			
			if(false == ($size_data = getimagesizefromstring($imagedata)) || !is_array($size_data)) {
				$error = "error. Failed to determine image dimensions.";
				return false;
			}
			
			if($size_data[0] < $min_width) {
				$error = sprintf("must be at least %dpx in width (%dpx).", $min_width, $size_data[0]);
				return false;
			}
			
			if($size_data[0] > $max_width) {
				$error = sprintf("must be no more than %dpx in width (%dpx).", $max_width, $size_data[0]);
				return false;
			}
			
			if($size_data[1] < $min_height) {
				$error = sprintf("must be at least %dpx in height (%dpx).", $min_height, $size_data[1]);
				return false;
			}
			
			if($size_data[1] > $max_height) {
				$error = sprintf("must be no more than %dpx in height (%dpx).", $max_height, $size_data[1]);
				return false;
			}
			
			return true;
		};
	}
	
	public function ip() {
		return function($value, &$error) {
			if(false === filter_var($value, FILTER_VALIDATE_IP)) {
				$error = sprintf("(%s) is not a valid IPv4 or IPv6 address. Format like: 1.2.3.4",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	public function ipv4() {
		return function($value, &$error) {
			if(false === filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$error = sprintf("(%s) is not a valid IPv4 address. Format like: 1.2.3.4",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	public function ipv6() {
		return function($value, &$error) {
			if(false === filter_var($value, FILTER_VALIDATE_IP,  FILTER_FLAG_IPV6)) {
				$error = sprintf("(%s) is not a valid IPv6 address. Format like: a1b2:c3d4:e5f6:a1b2:c3d4:e5f6:a1b2:c3d4",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	function language() {
		return function($value, &$error) {
			if(empty($value))
				return true;
			
			$translate = DevblocksPlatform::getTranslationService();
			$locales = $translate->getLocaleStrings();
			
			if(!$value || !isset($locales[$value])) {
				$error = sprintf("(%s) is not a valid language. Format like: en_US",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	function timezone() {
		return function($value, &$error) {
			if(empty($value))
				return true;
			
			$date = DevblocksPlatform::services()->date();
			$timezones = $date->getTimezones();
			
			if(!in_array($value, $timezones)) {
				$error = sprintf("(%s) is not a valid timezone. Format like: America/Los_Angeles",
					$value
				);
				return false;
			}
			
			return true;
		};
	}
	
	function uri() {
		return function($value, &$error=null) {
			// [TODO] Can't start with a number or dot?
			// [TODO] Can't be all dots or underscores
			
			if(0 != strcmp($value, DevblocksPlatform::strAlphaNum($value, '._'))) {
				$error = "may only contain letters, numbers, dots, and underscores";
				return false;
			}
			
			if(strlen($value) > 128) {
				$error = "must be shorter than 128 characters.";
				return false;
			}
			
			return true;
		};
	}
	
	function url() {
		return function($value, &$error=null) {
			if(!is_string($value)) {
				$error = "must be a string.";
				return false;
			}
			
			// Empty strings are fine
			if(0 == strlen($value))
				return true;
			
			if(false == filter_var($value, FILTER_VALIDATE_URL)) {
				$error = "is not a valid URL. It must start with http:// or https://";
				return false;
			}
			
			if(false == ($url_parts = parse_url($value))) {
				$error = "is not a valid URL.";
				return false;
			}
			
			if(!isset($url_parts['scheme'])) {
				$error = "must start with http:// or https://";
				return false;
			}
			
			if(!in_array(strtolower($url_parts['scheme']), ['http','https'])) {
				$error = "is invalid. The URL must start with http:// or https://";
				return false;
			}
			
			return true;
		};
	}
	
	function yaml() {
		return function($value, &$error=null) {
			if(false === @yaml_parse($value, -1)) {
				$error = "is not valid YAML.";
				return false;
			}
			
			return true;
		};
	}
}

class _DevblocksValidationType {
	public $_type_name = null;
	
	public $_data = [
		'editable' => true,
		'required' => false,
	];
	
	function __construct($type_name=null) {
		$this->_type_name = $type_name;
		return $this;
	}
	
	function getName() {
		return $this->_type_name;
	}
	
	function isEditable() {
		return @$this->_data['editable'] ? true : false;
	}
	
	function setEditable($bool) {
		$this->_data['editable'] = $bool ? true : false;
		return $this;
	}
	
	function isRequired() {
		return @$this->_data['required'] ? true : false;
	}
	
	function setRequired($bool) {
		// If required, it also must not be empty
		$this->setNotEmpty(true);
		
		$this->_data['required'] = (bool) $bool;
		return $this;
	}
	
	function setUnique($dao_class) {
		$this->_data['unique'] = true;
		$this->_data['dao_class'] = $dao_class;
		unset($this->_data['callback']);
		$this->setNotEmpty(true);
		return $this;
	}
	
	function setUniqueCallback(callable $callback) {
		$this->_data['unique'] = true;
		$this->_data['callback'] = $callback;
		unset($this->_data['dao_class']);
		$this->setNotEmpty(true);
		return $this;
	}
	
	function canBeEmpty() {
		if(!array_key_exists('not_empty', $this->_data))
			return true;
		
		return $this->_data['not_empty'] ? false : true;
	}
	
	function isEmpty($value) {
		if('id' == $this->_type_name) {
			return (0 == strlen($value) || 0 === $value);
		} else {
			return 0 == strlen($value);	
		}
	}
	
	function setNotEmpty($bool) {
		$this->_data['not_empty'] = $bool ? true : false;
		return $this;
	}
	
	function addFormatter($callable) {
		if(!is_callable($callable))
			return false;
		
		if(!isset($this->_data['formatters']))
			$this->_data['formatters'] = [];
		
		$this->_data['formatters'][] = $callable;
		return $this;
	}
	
	function addValidator($callable) {
		if(!is_callable($callable))
			return false;
		
		if(!isset($this->_data['validators']))
			$this->_data['validators'] = [];
		
		$this->_data['validators'][] = $callable;
		return $this;
	}
}

class _DevblocksValidationTypeContext extends _DevblocksValidationType {
	function __construct($type_name='context') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationTypeFloat extends _DevblocksValidationType {
	function __construct($type_name='float') {
		parent::__construct($type_name);
		return $this;
	}
	
	function setMin($n) {
		if(!is_numeric($n))
			return false;
		
		$this->_data['min'] = floatval($n);
		return $this;
	}
	
	function setMax($n) {
		if(!is_numeric($n))
			return false;
		
		$this->_data['max'] = floatval($n);
		return $this;
	}
}

class _DevblocksValidationTypeArray extends _DevblocksValidationType {
	function __construct($type_name='array') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationTypeBoolean extends _DevblocksValidationType {
	function __construct($type_name='boolean') {
		parent::__construct($type_name);
		return $this;
	}
}

class  _DevblocksValidationTypeError extends _DevblocksValidationType {
	function __construct($type_name='false') {
		parent::__construct($type_name);
		return $this;
	}
	
	function setError($message) : _DevblocksValidationTypeError {
		$this->_data['error'] = $message;
		return $this;
	}
}

class _DevblocksValidationTypeGeoPoint extends _DevblocksValidationType {
	function __construct($type_name='geopoint') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationTypeIdArray extends _DevblocksValidationType {
	function __construct($type_name='idArray') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationTypeNumber extends _DevblocksValidationType {
	function __construct($type_name='number') {
		parent::__construct($type_name);
		return $this;
	}
	
	function setMin($n) {
		if(is_string($n)) {
			$n = DevblocksPlatform::strBitsToInt($n);
		}
		
		if(!is_numeric($n))
			return false;
		
		$this->_data['min'] = $n;
		return $this;
	}
	
	function setMax($n) {
		if(is_string($n)) {
			$n = DevblocksPlatform::strBitsToInt($n);
		}
		
		if(!is_numeric($n))
			return false;
		
		$this->_data['max'] = $n;
		return $this;
	}
}

trait _DevblocksValidationStringTrait {
	function setMinLength($length) {
		if(is_string($length)) {
			$length = DevblocksPlatform::strBitsToInt($length);
		}
		
		if(!is_numeric($length))
			return false;
		
		$this->_data['length_min'] = $length;
		return $this;
	}
	
	function setMaxLength($length) {
		if(is_string($length)) {
			$length = DevblocksPlatform::strBitsToInt($length);
		}
		
		if(!is_numeric($length))
			return false;
		
		$this->_data['length_max'] = $length;
		return $this;
	}
	
	function setTruncation(bool $enabled=true) {
		$this->_data['truncation'] = $enabled;
		return $this;
	}
	
	function setPossibleValues(array $possible_values) {
		$this->_data['possible_values'] = $possible_values;
		return $this;
	}
}

class _DevblocksValidationTypeString extends _DevblocksValidationType {
	use _DevblocksValidationStringTrait;
	
	function __construct($type_name='string') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationTypeStringOrArray extends _DevblocksValidationType {
	use _DevblocksValidationStringTrait;
	
	function __construct($type_name='stringOrArray') {
		parent::__construct($type_name);
		return $this;
	}
}

class _DevblocksValidationService {
	const STRING_UTF8MB4 = 1;
	
	private $_fields = [];
	
	/**
	 * 
	 * @param string $name
	 * @return DevblocksValidationField
	 */
	function addField($name, $label=null) {
		$this->_fields[$name] = new DevblocksValidationField($name, $label);
		return $this->_fields[$name];
	}
	
	function reset() {
		$this->_fields = [];
	}
	
	/*
	 * @return DevblocksValidationField[]
	 */
	function getFields() {
		return $this->_fields;
	}
	
	/**
	 * return _DevblocksFormatters
	 */
	function formatters() {
		return new _DevblocksFormatters();
	}
	
	/**
	 * return _DevblocksValidators
	 */
	function validators() {
		return new _DevblocksValidators();
	}
	
	// (ip, email, phone, etc)
	
	/**
	 * @param DevblocksValidationField $field
	 * @param mixed $value
	 * @param array $scope
	 * @return bool
	 * @throws Exception_DevblocksValidationError
	 */
	function validate(DevblocksValidationField $field, &$value, $scope=[]) {
		$field_name = $field->_name;
		$field_label = $field->_label;
		
		$error = null;
		
		if(!$field->_type || false == ($class_name = get_class($field->_type)))
			throw new Exception_DevblocksValidationError(sprintf("'%s' has an invalid type.", $field_label));
		
		$data = $field->_type->_data;
		
		if(isset($data['editable'])) {
			if(!$data['editable'])
				throw new Exception_DevblocksValidationError(sprintf("'%s' is not editable.", $field_label));
		}
		
		if(isset($data['not_empty']) && $data['not_empty']) {
			if(
				(is_string($value) && 0 == strlen($value))
				|| (is_array($value) && 0 == count($value))
				|| (is_null($value))
			)
			throw new Exception_DevblocksValidationError(sprintf("'%s' must not be blank.", $field_label));
		}
		
		if(isset($data['formatters']) && is_array($data['formatters']))
		foreach($data['formatters'] as $formatter) {
			if(!is_callable($formatter)) {
				throw new Exception_DevblocksValidationError(sprintf("'%s' has an invalid formatter.", $field_label));
			}
			
			if(!$formatter($value, $error)) {
				throw new Exception_DevblocksValidationError(sprintf("'%s' %s", $field_label, $error));
			}
		}
		
		if(isset($data['validators']) && is_array($data['validators']))
		foreach($data['validators'] as $validator) {
			if(!is_callable($validator)) {
				throw new Exception_DevblocksValidationError(sprintf("'%s' has an invalid validator.", $field_label));
			}
			
			if(!$validator($value, $error)) {
				throw new Exception_DevblocksValidationError(sprintf("'%s' %s", $field_label, $error));
			}
		}
		
		// [TODO] This would have trouble if we were bulk updating a unique field
		if(isset($data['unique']) && $data['unique']) {
			if($field->_type->canBeEmpty() && $field->_type->isEmpty($value)) {
				// May be empty
				
			} else {
				$dao_class = $data['dao_class'] ?? null;
				$callback = $data['callback'] ?? null;
				
				if($dao_class) {
					if(isset($scope['id'])) {
						$results = $dao_class::getWhere(sprintf("%s = %s AND id != %d", $dao_class::escape($field_name), $dao_class::qstr($value), $scope['id']), null, null, 1);
					} else {
						$results = $dao_class::getWhere(sprintf("%s = %s", $dao_class::escape($field_name), $dao_class::qstr($value)), null, null, 1);
					}
					
					if(!empty($results)) {
						throw new Exception_DevblocksValidationError(sprintf("A record already exists with this '%s' (%s). It must be unique.", $field_label, $value));
					}
					
				} elseif(is_callable($callback)) {
					$error = null;
					
					if(!$callback($field, $value, $scope, $error)) {
						throw new Exception_DevblocksValidationError($error);
					}
					
				} else {
					throw new Exception_DevblocksValidationError("'%s' has an invalid unique constraint.", $field_label);
				}
			}
		}
		
		switch($class_name) {
			case '_DevblocksValidationTypeContext':
				if(!is_string($value) || false == (Extension_DevblocksContext::getByAlias($value, false))) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' is not a valid context (%s).", $field_label, $value));
				}
				// [TODO] Filter to specific contexts for certain fields
				break;
				
			case '_DevblocksValidationTypeArray':
				if(!is_array($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be an array.", $field_label));
				}
				break;
				
			case '_DevblocksValidationTypeBoolean':
				if(!is_bool($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a boolean.", $field_label));
				}
				break;
				
			case '_DevblocksValidationTypeError':
				throw new Exception_DevblocksValidationError($data['error'] ?? sprintf("'%s' is incorrect.", $field_label));
				
			case '_DevblocksValidationTypeGeoPoint':
				if(!is_string($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be text.", $field_label));
				}
				
				$error = null;
				$coords = DevblocksPlatform::parseGeoPointString($value, $error);
				
				if(false === $coords)
					throw new Exception_DevblocksValidationError(sprintf("'%s': %s", $field_label, $error));
				
				break;
				
			case '_DevblocksValidationTypeFloat':
				if($field->_type->canBeEmpty() && $field->_type->isEmpty($value))
					$value = 0;
				
				if(!is_numeric($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a decimal (%s: %s).", $field_label, gettype($value), json_encode($value)));
				}
				break;
				
			case '_DevblocksValidationTypeId':
			case '_DevblocksValidationTypeNumber':
				if(!is_numeric($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a number (%s: %s).", $field_label, gettype($value), json_encode($value)));
				}
				
				if($data) {
					if(isset($data['min']) && $value < $data['min']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be >= %d (%d)", $field_label, $data['min'], $value));
					}
					
					if(isset($data['max']) && $value > $data['max']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be <= %d (%d)", $field_label, $data['max'], $value));
					}
				}
				break;
				
			case '_DevblocksValidationTypeIdArray':
				
				if(!is_array($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be an array of IDs (%s).", $field_label, gettype($value)));
				}
				
				$values = $value;
				
				foreach($values as $id) {
					if(empty($id))
						$id = 0;
					
					if(!is_numeric($id)) {
						throw new Exception_DevblocksValidationError(sprintf("Value '%s' must be a number (%s: %s).", $field_label, gettype($id), json_encode($id)));
					}
				}
				break;
				
			case '_DevblocksValidationTypeString':
				if(!is_null($value) && !is_string($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a string (%s).", $field_label, gettype($value)));
				}
				
				if($data) {
					if(isset($data['length_min']) && strlen($value) < $data['length_min']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be %d or more characters.", $field_label, $data['length_min']));
					}
					
					if(isset($data['length_max']) && strlen($value) > $data['length_max']) {
						// Truncation
						if(array_key_exists('truncation', $data) && $data['truncation']) {
							$value = substr($value, 0, $data['length_max'] - 3) . '...';
						} else {
							throw new Exception_DevblocksValidationError(sprintf("'%s' must be no longer than %d characters.", $field_label, $data['length_max']));
						}
					}
					
					$possible_values = $data['possible_values'] ?? null;
					
					if(!($field->_type->canBeEmpty() && $field->_type->isEmpty($value))) {
						if ($possible_values && !in_array($value, $possible_values)) {
							throw new Exception_DevblocksValidationError(sprintf("'%s' must be one of: %s", $field_label, implode(', ', $data['possible_values'])));
						}
					}
				}
				break;
				
			case '_DevblocksValidationTypeStringOrArray':
				if(!is_null($value) && !is_string($value) && !is_array($value)) {
					throw new Exception_DevblocksValidationError(sprintf("'%s' must be a string or array (%s).", $field_label, gettype($value)));
				}
				
				if(is_string($value)) {
					if(isset($data['length_min']) && strlen($value) < $data['length_min']) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be longer than %d characters.", $field_label, $data['length_min']));
					}
					
					if(isset($data['length_max']) && strlen($value) > $data['length_max']) {
						// Truncation
						if(array_key_exists('truncation', $data) && $data['truncation']) {
							$value = substr($value, 0, $data['length_max'] - 3) . '...';
						} else {
							throw new Exception_DevblocksValidationError(sprintf("'%s' must be no longer than %d characters.", $field_label, $data['length_max']));
						}
					}
					
					$possible_values = $data['possible_values'] ?? null;
					
					if($possible_values && !in_array($value, $possible_values)) {
						throw new Exception_DevblocksValidationError(sprintf("'%s' must be one of: %s", $field_label, implode(', ', $data['possible_values'])));
					}
				}
				break;
		}
		
		return true;
	}
	
	function validateAll(array &$values, &$error=null) {
		$fields = $this->getFields();
		
		// Are any required fields not provided?
		foreach($fields as $field_key => $field) { /* @var $field DevblocksValidationField */
			if($field->_type->isRequired() && !array_key_exists($field_key, $values)) {
				$error = sprintf("`%s` is required.", $field->_label);
				return false;
			}
		}
		
		if(is_array($values))
		foreach($values as $field_key => &$value) {
			if(!array_key_exists($field_key, $fields)) {
				$error = sprintf("`%s` is an unknown field.", $field_key);
				return false;
			}
		
			$field = $fields[$field_key];
			
			try {
				$this->validate($field, $value);
				
			} catch (Exception_DevblocksValidationError $e) {
				$error = $e->getMessage();
				return false;
			}
		}
		
		return true;
	}
};