<?php
class _DevblocksSanitizationManager {
	static function arrayAs($array, $type) {
		array_walk($array, array('_DevblocksSanitizationManager', '_castArrayAs'), $type);
		return $array;
	}
	
	private static function _castArrayAs(&$value, $key, $type) {
		settype($value, $type);
	}
}