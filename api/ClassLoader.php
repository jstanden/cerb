<?php
function __autoload($className) {
	CerberusClassLoader::loadClass($className);
}

// [JAS]: [TODO] This should move to Platform
class CerberusClassLoader {
	static private $classMap = array();
	static private $init = false;
	
	public static function loadClass($className) {
		if(class_exists($className)) return;
		if(!self::$init) self::_init();
		
		$file = self::$classMap[$className];
		
		if(!is_null($file)) {
			require_once($file);
		} else {
	       	// [TODO]: Exception, log
	       	// [TODO] It's probably not a good idea to send this much info to the browser
	       	echo sprintf("<b>ERROR: ClassLoader could not find '%s':</b><br><pre>",
	       	    $className
	       	);
	       	print_r(debug_backtrace());
	       	echo "</pre>";
	       	die;
		}
	}
	
	public static function registerClasses($file,$classes=array()) {
	    if(!self::$init) self::_init();
	    
		if(is_array($classes))
		foreach($classes as $class) {
			self::$classMap[$class] = $file;
		}
	}
	
	private static function _init() {
	    self::$init = true;
		self::_initApp();
		self::_initDAO();
		self::_initModel();
		self::_initExtension();
		self::_initPEAR();	
		self::_initZend();
		self::_initLibs();
	}
	
	private static function _initApp() {
		$path = APP_PATH . '/api/app/';
		
		self::registerClasses($path . 'Bayes.php', array(
			'CerberusBayes',
		));
		
		self::registerClasses($path . 'Mail.php', array(
			'CerberusMail',
		));
		
		self::registerClasses($path . 'Parser.php', array(
			'CerberusParser',
		));
		
		self::registerClasses($path . 'Utils.php', array(
			'CerberusUtils',
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

	private static function _initLibs() {
		self::registerClasses(DEVBLOCKS_PATH . 'libs/markdown/markdown.php',array(
			'Markdown',
		));
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
		
		self::registerClasses('Text/Password.php', array(
			'Text_Password',
		));
	}
	
	private static function _initZend() {
		$path = APP_PATH . '/libs/devblocks/libs/ZendFramework/Zend/';
		
		self::registerClasses(APP_PATH . '/libs/devblocks/libs/ZendFramework/Zend.php', array(
			'Zend',
		));
		
		self::registerClasses($path . 'Cache.php', array(
			'Zend_Cache',
		));
		
		self::registerClasses($path . 'Exception.php', array(
			'Zend_Exception',
		));
		
	    self::registerClasses($path . 'Registry.php', array(
			'Zend_Registry',
		));
		
		self::registerClasses($path . 'Date.php', array(
			'Zend_Date',
		));
		
		self::registerClasses($path . 'Locale.php', array(
			'Zend_Locale',
		));
		
		self::registerClasses($path . 'Translate.php', array(
			'Zend_Translate',
		));
		
		self::registerClasses($path . 'Translate/Adapter/Tmx.php', array(
			'Zend_Translate_Adapter_Tmx',
		));
		
		self::registerClasses($path . 'Mail.php', array(
			'Zend_Mail',
		));
		
		self::registerClasses($path . 'Mime.php', array(
			'Zend_Mime',
		));
		
		self::registerClasses($path . 'Validate/EmailAddress.php.php', array(
			'Zend_Validate_EmailAddress',
		));
		
		self::registerClasses($path . 'Mail/Transport/Smtp.php', array(
			'Zend_Mail_Transport_Smtp',
		));
		
		self::registerClasses($path . 'Mail/Transport/Sendmail.php', array(
			'Zend_Mail_Transport_Sendmail',
		));
		
	}
	
};
?>
