<?php
class _DevblocksGPGService {
	static $instance = null;
	
	private $_gpg = null;
	
	private function __construct(){
		if(!extension_loaded('gnupg'))
			return null;
		
		putenv("GNUPGHOME=" . APP_STORAGE_PATH . '/.gnupg');
		$this->_gpg = new gnupg();
		$this->_gpg->seterrormode(gnupg::ERROR_EXCEPTION);
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksGPGService();
		}
		
		return self::$instance;
	}
	
	public function isEnabled() {
		if(!$this->_gpg)
			return false;
		
		return true;
	}
	
	public function importKey($ascii_key, $is_armored=1) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->setarmor($is_armored ? 1 : 0);
		
		return $this->_gpg->import($ascii_key);
	}
	
	public function exportKey($fingerprint) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->setarmor(1);
		
		return $this->_gpg->export($fingerprint);
	}
	
	public function deleteKey($fingerprint) {
		if(!$this->_gpg)
			return false;
		
		if(method_exists($this->_gpg, 'deletekey'))
			return $this->_gpg->deletekey($fingerprint);
		
		return false;
	}
	
	public function keyinfo($fingerprint) {
		if(!$this->_gpg)
			return false;
		
		return $this->_gpg->keyinfo($fingerprint);
	}
	
	public function encrypt($plaintext, $key_fingerprints) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->clearencryptkeys();
		
		if(is_array($key_fingerprints))
		foreach($key_fingerprints as $key_fingerprint) {
			$this->_gpg->addencryptkey($key_fingerprint);
		}
		
		$this->_gpg->setarmor(1);
		
		return $this->_gpg->encrypt($plaintext);
	}
	
	public function decrypt($encrypted_content) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->cleardecryptkeys();
		return $this->_gpg->decrypt($encrypted_content);
	}
	
	public function sign($plaintext, $key_fingerprint, $is_detached=true) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->clearsignkeys();
		$this->_gpg->addsignkey($key_fingerprint,'');
		
		if($is_detached) {
			$this->_gpg->setsignmode(gnupg::SIG_MODE_DETACH);
		} else {
			$this->_gpg->setsignmode(gnupg::SIG_MODE_CLEAR);
		}
		
		$this->_gpg->setarmor(1);
		
		try {
			$signed = $this->_gpg->sign($plaintext);
			
		} catch(Exception $e) {
			error_log($e->getMessage());
			return false;
		}
		
		return $signed;
	}
	
	public function verify($signed_content, $signature=false) {
		if(!$this->_gpg)
			return false;
		
		if(false == ($info = $this->_gpg->verify($signed_content, $signature)))
			return false;
		
		return $this->keyinfo($info[0]['fingerprint']);
	}
	
	public function encryptAndSign($plaintext, $recipient_keys, $sign_key) {
		if(!$this->_gpg)
			return false;
		
		$this->_gpg->clearencryptkeys();
		$this->_gpg->clearsignkeys();
		
		$this->_gpg->addsignkey($sign_key, '');
		
		if(is_array($recipient_keys))
		foreach($recipient_keys as $key_fingerprint) {
			$this->_gpg->addencryptkey($key_fingerprint);
		}
		
		$this->_gpg->setarmor(1);
		
		return $this->_gpg->encryptsign($plaintext);
	}
};
