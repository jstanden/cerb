<?php
class _DevblocksSanitizationManager {
	static function arrayAs($array, $type) {
		if(is_array($array))
			array_walk($array, array('_DevblocksSanitizationManager', '_castArrayAs'), $type);
		return $array;
	}
	
	private static function _castArrayAs(&$value, $key, $type) {
		switch($type) {
			case 'bit':
				$value = @intval($value) ? 1 : 0;
				break;
				
			case 'bool':
			case 'boolean':
				if(is_string($value) && 0 == strcasecmp($value, 'false')) {
					$value = false;
				} else {
					$value = $value ? true : false;
				}
				break;
				
			case 'str':
			case 'string':
				$value = strval($value);
				break;
				
			default:
				settype($value, $type);
				break;
		}
	}
}