<?php
class _DevblocksPluginSettingsManager {
	private static $_instance = null;
	
	private function _getCacheKey($plugin_id) {
		return sprintf("devblocks:plugin:%s:params",
			DevblocksPlatform::strAlphaNum($plugin_id, '_.')
		);
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
	
	/**
	 *
	 * @param string $plugin_id
	 * @param string $key
	 * @param mixed $value
	 * @param bool $json_encode
	 * @return boolean
	 */
	public function set($plugin_id, $key, $value, $json_encode=false) {
		if($json_encode)
			$value = json_encode($value);
		
		DAO_DevblocksSetting::set($plugin_id, $key, $value);
		
		// Clear the plugin's settings cache when changed
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = $this->_getCacheKey($plugin_id);
		$cache->remove($cache_key);
		
		return TRUE;
	}
	
	/**
	 * @param string $plugin_id
	 * @param string $key
	 * @param mixed $default
	 * @param bool $json_decode
	 * @return mixed
	 */
	public function get($plugin_id, $key, $default=null, $json_decode=false) {
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = $this->_getCacheKey($plugin_id);
		
		if(null === ($settings = $cache->load($cache_key))) {
			$settings = DAO_DevblocksSetting::getSettings($plugin_id);
			
			if(!is_array($settings))
				return false;
			
			$cache->save($settings, $cache_key);
		}
		
		if(isset($settings[$key]))
			return $json_decode ? @json_decode($settings[$key], true) : $settings[$key];
		
		return $default;
	}
};