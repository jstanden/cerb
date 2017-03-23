<?php
class _DevblocksEncryptionService {
	private static $instance = null;
	
	private function __construct() {}
	
	/**
	 * @return _DevblocksEncryptionService
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			require_once(DEVBLOCKS_PATH . 'libs/defuse-crypto.phar');
			self::$instance = new _DevblocksEncryptionService();
		}
		return self::$instance;
	}
	
	public function encrypt($data, $key_ascii=null) {
		if(!$key_ascii)
			$key_ascii = $this->_getSystemKey();
		
		try {
			$key = Defuse\Crypto\Key::loadFromAsciiSafeString($key_ascii);
			$raw_ciphertext = Defuse\Crypto\Crypto::encrypt($data, $key, true);
			$ciphertext = base64_encode($raw_ciphertext);
			
		} catch (Exception $e) {
			return false;
		}
		
		return $ciphertext;
	}
	
	public function decrypt($ciphertext, $key_ascii=null) {
		if(!$key_ascii)
			$key_ascii = $this->_getSystemKey();
		
		try {
			$key = Defuse\Crypto\Key::loadFromAsciiSafeString($key_ascii);
			$plaintext = Defuse\Crypto\Crypto::decrypt(base64_decode($ciphertext), $key, true);
			
		} catch (Exception $e) {
			return false;
		}
		
		return $plaintext;
	}
	
	public function generateKey() {
		$key = Defuse\Crypto\Key::createNewRandomKey();
		$text_key = $key->saveToAsciiSafeString();
		return $text_key;
	}
	
	private function _getSystemKey() {
		$key_path = APP_STORAGE_PATH . '/_master.key';
		
		if(false == ($system_key = @file_get_contents($key_path))) {
			if(false == ($system_key = $this->generateKey()))
				return false;
			
			if(false == (file_put_contents($key_path, $system_key)))
				return false;
			
			@chmod($key_path, 0660);
		}
		
		return $system_key;
	}
};