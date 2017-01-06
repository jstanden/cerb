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
	 * @param bool $encrypted
	 * @return boolean
	 */
	public function set($plugin_id, $key, $value, $json_encode=false, $encrypted=false) {
		if($json_encode)
			$value = json_encode($value);
		
		if($encrypted) {
			$encrypt = DevblocksPlatform::getEncryptionService();
			$value = $encrypt->encrypt($value);
		}
		
		DAO_DevblocksSetting::set($plugin_id, $key, $value);
		
		$this->_clearCache($plugin_id);
		
		return TRUE;
	}
	
	/**
	 * @param string $plugin_id
	 * @param string $key
	 * @param mixed $default
	 * @param bool $json_decode
	 * @param bool $encrypted
	 * @return mixed
	 */
	public function get($plugin_id, $key, $default=null, $json_decode=false, $encrypted=false) {
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = $this->_getCacheKey($plugin_id);
		
		if(null === ($settings = $cache->load($cache_key))) {
			$settings = DAO_DevblocksSetting::getSettings($plugin_id);
			
			if(!is_array($settings))
				return $default;
			
			$cache->save($settings, $cache_key);
		}
		
		if(isset($settings[$key])) {
			$value = $settings[$key];
			
			if($encrypted) {
				$encrypt = DevblocksPlatform::getEncryptionService();
				$value = $encrypt->decrypt($value);
			}
			
			if($json_decode)
				$value = @json_decode($value, true);
			
			return $value;
		}
		
		return $default;
	}
	
	/**
	 * @param string $plugin_id
	 * @param array $keys
	 */
	public function delete($plugin_id, array $keys=[]) {
		DAO_DevblocksSetting::delete($plugin_id, $keys);
		$this->_clearCache($plugin_id);
	}
	
	/**
	 * @param string $plugin_id
	 * @return boolean
	 */
	private function _clearCache($plugin_id) {
		// Clear the plugin's settings cache when changed
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = $this->_getCacheKey($plugin_id);
		return $cache->remove($cache_key);
	}
};