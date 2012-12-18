<?php
class _DevblocksPluginSettingsManager {
	private static $_instance = null;
	private $_settings = array();
	
	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	private function __construct() {
		// Defaults (dynamic)
		$plugin_settings = DAO_DevblocksSetting::getSettings();
		foreach($plugin_settings as $plugin_id => $kv) {
			if(!isset($this->_settings[$plugin_id]))
				$this->_settings[$plugin_id] = array();
				
			if(is_array($kv))
			foreach($kv as $k => $v)
				$this->_settings[$plugin_id][$k] = $v;
		}
	}
	
	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	public static function getInstance() {
		if(self::$_instance==null) {
			self::$_instance = new _DevblocksPluginSettingsManager();
		}
		
		return self::$_instance;
	}
	
	public function set($plugin_id,$key,$value) {
		DAO_DevblocksSetting::set($plugin_id,$key,$value);
		
		if(!isset($this->_settings[$plugin_id]))
			$this->_settings[$plugin_id] = array();
		
		$this->_settings[$plugin_id][$key] = $value;
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DevblocksPlatform::CACHE_SETTINGS);
		
		return TRUE;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function get($plugin_id,$key,$default=null) {
		if(isset($this->_settings[$plugin_id][$key]))
			return $this->_settings[$plugin_id][$key];
		else
			return $default;
	}
};