<?php
function __autoload($className) {
	CerberusClassLoader::loadClass($className);
}

class CerberusClassLoader {
	static private $classMap = array();
	
	public static function loadClass($className) {
		if(class_exists($className)) return;
		if(null == self::$classMap) self::_init();
		
		$file = self::$classMap[$className];
		
		if(!is_null($file)) {
			require_once($file);
		} else {
	       	// [TODO]: Exception, log
	       	die("ERROR: ClassLoader could not find '$className'.");
		}
	}
	
	public static function registerClasses($file,$classes=array()) {
		if(is_array($classes))
		foreach($classes as $class) {
			self::$classMap[$class] = $file;
		}
	}
	
	private static function _init() {
		self::_initApp();
		self::_initDAO();
		self::_initModel();
		self::_initExtension();
		self::_initPEAR();	
		self::_initZend();
	}
	
	private static function _initApp() {
		$path = APP_PATH . '/api/app/';
		
		self::registerClasses($path . 'Bayes.php', array(
			'CerberusBayes',
		));
		
		self::registerClasses($path . 'Parser.php', array(
			'CerberusParser',
		));
	}
	
	private static function _initDAO() {
		$path = APP_PATH . '/api/dao/';
	}
	
	private static function _initModel() {
		$path = APP_PATH . '/api/model/';
	}
	
	private static function _initExtension() {
		$path = APP_PATH . '/api/ext/';
	}

	private static function _initPEAR() {
		self::registerClasses('Mail.php',array(
			'Mail',
		));
		
		self::registerClasses('Mail/mimeDecode.php', array(
			'Mail_mimeDecode',
		));

		self::registerClasses('Mail/RFC822.php', array(
			'Mail_RFC822',
		));
	}
	
	private static function _initZend() {
		$path = APP_PATH . '/libs/devblocks/libs/Zend';
		
		self::registerClasses($path . '/Mail.php', array(
			'Zend_Mail',
		));
		
		self::registerClasses($path . '/Mime.php', array(
			'Zend_Mime',
		));
		
		self::registerClasses($path . '/Mail/Transport/Smtp.php', array(
			'Zend_Mail_Transport_Smtp',
		));
	}
	
};
?>
