<?php
class CerberusUtils {
	static function isValidEmail($email) {
		require_once 'Zend/Validate/EmailAddress.php';
		$validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL);
		return $validator->isValid($email);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $string
	 * @return array
	 */
	static function parseRfcAddressList($string) {
		return imap_rfc822_parse_adrlist($string, 'localhost');
	}
}
?>