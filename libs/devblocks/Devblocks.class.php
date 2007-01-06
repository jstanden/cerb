<?php
include_once(DEVBLOCKS_PATH . "/libs/devblocks/pear/i18n/I18N_UnicodeString.php");
include_once(DEVBLOCKS_PATH . "/languages/".DEVBLOCKS_LANGUAGE."/strings.php");

/**
 *  @defgroup core Devblocks Framework Core
 *  Core data structures of the framework
 */

/**
 *  @defgroup plugin Devblocks Framework Plugins
 *  Components for plugin/extensions
 */

/**
 *  @defgroup services Devblocks Framework Services
 *  Services provided by the framework
 */

/**
 * A platform container for plugin/extension registries.
 *
 * @static 
 * @ingroup core
 * @author Jeff Standen
 */
class DevblocksPlatform {
	/**
	 * @private
	 */
	private function DevblocksPlatform() {}
	
	/**
	 * Returns the list of extensions on a given extension point.
	 *
	 * @static
	 * @param string $point
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensions($point) {
		$results = array();
		$extensions = DevblocksPlatform::getExtensionRegistry();
		
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			if(0 == strcasecmp($extension->point,$point)) {
				$results[] = $extension;
			}
		}
		return $results;
	}
	
	/**
	 * Returns the manifest of a given extension ID.
	 *
	 * @static
	 * @param string $extension_id
	 * @return DevblocksExtensionManifest
	 */
	static function getExtension($extension_id) {
		$result = null;
		$extensions = DevblocksPlatform::getExtensionRegistry();
		
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			if(0 == strcasecmp($extension->id,$extension_id)) {
				$result = $extension;
			}
		}		
		
		return $result;
	}
	
	/**
	 * Returns an array of all contributed extension manifests.
	 *
	 * @static 
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensionRegistry() {
		static $extensions = array();
		
		if(!empty($extensions))
			return $extensions;
		
		$db = DevblocksPlatform::getDatabaseService();
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		$sql = sprintf("SELECT e.id , e.plugin_id, e.point, e.pos, e.name , e.file , e.class, e.params ".
			"FROM extension e ".
			"ORDER BY e.plugin_id ASC, e.pos ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$extension = new DevblocksExtensionManifest();
			$extension->id = stripslashes($rs->fields['id']);
			$extension->plugin_id = intval($rs->fields['plugin_id']);
			$extension->point = stripslashes($rs->fields['point']);
			$extension->name = stripslashes($rs->fields['name']);
			$extension->file = stripslashes($rs->fields['file']);
			$extension->class = stripslashes($rs->fields['class']);
			$extension->params = @unserialize(stripslashes($rs->fields['params']));
			
			if(empty($extension->params))
				$extension->params = array();
			
			@$plugin = $plugins[$extension->plugin_id]; /* @var $plugin DevblocksPluginManifest */
			if(!empty($plugin)) {
				$extensions[$extension->id] = $extension;
			}
			
			$rs->MoveNext();
		}
		
		return $extensions;
	}
	
	/**
	 * Returns an array of all contributed plugin manifests.
	 *
	 * @static
	 * @return DevblocksPluginManifest[]
	 */
	static function getPluginRegistry() {
		static $plugins = array();
		
		if(!empty($plugins))
			return $plugins;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT p.id , p.enabled , p.name, p.author, p.dir ".
			"FROM plugin p"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$plugin = new DevblocksPluginManifest();
			$plugin->id = intval($rs->fields['id']);
//			$plugin->enabled = intval($rs->fields['enabled']);
			$plugin->name = stripslashes($rs->fields['name']);
			$plugin->author = stripslashes($rs->fields['author']);
			$plugin->dir = stripslashes($rs->fields['dir']);
			
			if(file_exists(DEVBLOCKS_PLUGIN_PATH . $plugin->dir)) {
				$plugins[$plugin->id] = $plugin;
			}
			
			$rs->MoveNext();
		}
		
		return $plugins;
	}
	
	/**
	 * Reads and caches manifests from the plugin directory.
	 *
	 * @static 
	 * @return DevblocksPluginManifest[]
	 */
	static function readPlugins() {
		$dir = DEVBLOCKS_PLUGIN_PATH;
		$plugins = array();
		
		if (is_dir($dir)) {
		    if ($dh = opendir($dir)) {
		        while (($file = readdir($dh)) !== false) {
		        	if($file=="." || $file == ".." || 0 == strcasecmp($file,"CVS"))
		        		continue;
		        		
		        	$path = $dir . '/' . $file;
		        	if(is_dir($path) && file_exists($path.'/plugin.xml')) {
		        		$manifest = DevblocksPlatform::_readPluginManifest($file);
		        		if(null != $manifest) {
//							print_r($manifest);
							$plugins[] = $manifest;
		        		}
		        	}
		        }
		        closedir($dh);
		    }
		}
		
		return $plugins; // [TODO] Move this to the DB
	}
	
	/**
	 * Reads and caches a single manifest from a given plugin directory.
	 * 
	 * @static 
	 * @private
	 * @param string $file
	 * @return DevblocksPluginManifest
	 */
	static private function _readPluginManifest($dir) {
		if(!file_exists(DEVBLOCKS_PLUGIN_PATH.$dir.'/plugin.xml'))
			return NULL;
			
		include_once(DEVBLOCKS_PATH . '/libs/devblocks/domit/xml_domit_include.php');
		$rssRoot =& new DOMIT_Document();
		$success = $rssRoot->loadXML(DEVBLOCKS_PLUGIN_PATH.$dir.'/plugin.xml', false);
		$doc =& $rssRoot->documentElement; /* @var $doc DOMIT_Node */
		
		$eName = $doc->getElementsByPath("name",1);
		$eAuthor = $doc->getElementsByPath("author",1);
			
		$manifest = new DevblocksPluginManifest();
		$manifest->dir = $dir;
		$manifest->author = $eAuthor->getText();
		$manifest->name = $eName->getText();
		
		$db = DevblocksPlatform::getDatabaseService();

		// [JAS]: Check if the plugin exists already
		$pluginId = $db->GetOne(sprintf("SELECT id FROM plugin WHERE dir = %s",
			$db->QMagic($manifest->dir)
		)); // or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(empty($pluginId))
			$pluginId = $db->GenID('plugin_seq');
			
		$manifest->id = $pluginId;
		
		$db->Replace(
			'plugin',
			array(
				'id' => $manifest->id,
				'name' => $db->QMagic($manifest->name),
				'author' => $db->QMagic($manifest->author),
				'dir' => $db->QMagic($manifest->dir),
				'enabled' => 1
			),
			array('id'),
			false
		);
		
		$eExtensions =& $doc->getElementsByPath("extensions/extension"); /* @var $eExtensions DOMIT_NodeList */
		if(is_array($eExtensions->arNodeList))
		foreach($eExtensions->arNodeList as $eExtension) { /* @var $eExtension DOMIT_Node */
			
			$sPoint = $eExtension->getAttribute('point');
			$eId = $eExtension->getElementsByPath('id',1);
			$eName = $eExtension->getElementsByPath('name',1);
			$eClassName = $eExtension->getElementsByPath('class/name',1);
			$eClassFile = $eExtension->getElementsByPath('class/file',1);
			$params = $eExtension->getElementsByPath('params/param');
			$extension = new DevblocksExtensionManifest();

			if(empty($eId) || empty($eName))
				continue;

			$extension->id = $eId->getText();
			$extension->plugin_id = $manifest->id;
			$extension->point = $sPoint;
			$extension->name = $eName->getText();
			$extension->file = $eClassFile->getText();
			$extension->class = $eClassName->getText();
				
			if(null != $params && !empty($params->arNodeList)) {
				foreach($params->arNodeList as $pnode) {
					$sKey = $pnode->getAttribute('key');
					$sValue = $pnode->getAttribute('value');
					$extension->params[$sKey] = $sValue;
				}
			}
				
			$manifest->extensions[] = $extension;
		}
		
		// [JAS]: Extension caching
		if(is_array($manifest->extensions))
		foreach($manifest->extensions as $pos => $extension) { /* @var $extension DevblocksExtensionManifest */
			$db->Replace(
				'extension',
				array(
					'id' => $db->QMagic($extension->id),
					'plugin_id' => $extension->plugin_id,
					'point' => $db->QMagic($extension->point),
					'pos' => $pos,
					'name' => $db->QMagic($extension->name),
					'file' => $db->QMagic($extension->file),
					'class' => $db->QMagic($extension->class),
					'params' => $db->QMagic(serialize($extension->params))
				),
				array('id'),
				false
			);
		}

		return $manifest;
	}

	/**
	 * @return DevblocksResourceManager
	 */
	static function getResourceService() {
		return DevblocksResourceManager::getInstance();
	}
		
	/**
	 * @return DevblocksUrlManager
	 */
	static function getUrlService() {
		return DevblocksUrlManager::getInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return ADOConnection
	 */
	static function getDatabaseService() {
		return _DevblocksDatabaseManager::getInstance();
	}
	
	static function getMailService() {
		return _DevblocksEmailManager::getInstance();
	}
	
	/**
	 * @return _DevblocksSessionManager
	 */
	static function getSessionService() {
		return _DevblocksSessionManager::getInstance();
	}
	
	static function getTemplateService() {
		return _DevblocksTemplateManager::getInstance();
	}
	
	static function getTranslationService() {
		return _DevblocksTranslationManager::getInstance();
	}
	
	/**
	 * Initializes the plugin platform (paths, etc).
	 *
	 * @static 
	 * @return void
	 */
	static function init() {
		// [JAS] [MDF]: Automatically determine the relative webpath to Devblocks files
		if(!defined('DEVBLOCKS_WEBPATH')) {
			$php_self = $_SERVER["PHP_SELF"];
			$pos = strrpos($php_self,'/');
			$php_self = substr($php_self,0,$pos) . '/'; //  . '/' . DEVBLOCKS_DIRECTORY
			@define('DEVBLOCKS_WEBPATH',$php_self);
		}
	}
	
};

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class DevblocksPluginManifest {
	var $id = 0;
	var $name = '';
	var $author = '';
	var $dir = '';
	var $extensions = array();
};

/**
 * Manifest information for a plugin's extension.
 * @ingroup plugin
 */
class DevblocksExtensionManifest {
	var $id = '';
	var $plugin_id = 0;
	var $point = '';
	var $name = '';
	var $file = '';
	var $class = '';
	var $params = array();

	function DevblocksExtensionManifest() {}
	
	/**
	 * Creates and loads a usable extension from a manifest record.  The object returned 
	 * will be of type $class defined by the manifest.  $instance_id is passed as an 
	 * argument to uniquely identify multiple instances of an extension.
	 *
	 * @param integer $instance_id
	 * @return object
	 */
	function createInstance($instance_id=0) {
		if(empty($this->id) || empty($this->plugin_id)) // empty($instance_id) || 
			return null;

		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(!isset($plugins[$this->plugin_id]))
			return null;
		
		$plugin = $plugins[$this->plugin_id]; /* @var $plugin DevblocksPluginManifest */
		
		$class_file = DEVBLOCKS_PLUGIN_PATH . $plugin->dir . '/' . $this->file;
		$class_name = $this->class;

		if(!file_exists($class_file))
			return null;
			
		include_once($class_file);
		if(!class_exists($class_name)) {
			return null;
		}
			
		$instance = new $class_name($this,$instance_id);
		return $instance;
	}
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract 
 * @ingroup plugin
 */
class DevblocksExtension {
	var $manifest = null;
	var $instance_id = 0;
	var $id  = '';
	var $params = array();
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @param int $instance_id
	 * @return DevblocksExtension
	 */
	function DevblocksExtension($manifest,$instance_id) { /* @var $manifest DevblocksExtensionManifest */
		$this->manifest = $manifest;
		$this->id = $manifest->id;
		$this->instance_id = $instance_id;
		$this->params = $this->_getParams();
	}
	
	/**
	 * Loads parameters unique to this extension instance.  Returns an 
	 * associative array indexed by parameter key.
	 *
	 * @private
	 * @return array
	 */
	function _getParams() {
//		static $params = null;
		
		if(empty($this->id) || empty($this->instance_id))
			return null;
		
//		if(null != $params)
//			return $params;
		
		$params = $this->manifest->params;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT property,value ".
			"FROM property_store ".
			"WHERE extension_id=%s AND instance_id='%d' ",
			$db->QMagic($this->id),
			$this->instance_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$params[stripslashes($rs->fields['property'])] = stripslashes($rs->fields['value']);
			$rs->MoveNext();
		}
		
		return $params;
	}
	
	/**
	 * Persists any changed instanced extension parameters.
	 *
	 * @return void
	 */
	function saveParams() {
		if(empty($this->instance_id) || empty($this->id))
			return FALSE;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(is_array($this->params))
		foreach($this->params as $k => $v) {
			$db->Replace(
				'property_store',
				array('extension_id'=>$this->id,'instance_id'=>$this->instance_id,'property'=>$db->QMagic($k),'value'=>$db->QMagic($v)),
				array('extension_id','instance_id','property'),
				true
			);
		}
	}
	
};

/**
 * Session Management Singleton
 *
 * @static 
 * @ingroup services
 */
class _DevblocksSessionManager {
	var $visit = null;
	
	/**
	 * @private
	 */
	private function _DevblocksSessionManager() {}
	
	/**
	 * Returns an instance of the session manager
	 *
	 * @static
	 * @return _DevblocksSessionManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$db = DevblocksPlatform::getDatabaseService();
			if(!$db->IsConnected()) return null;
			
			include_once(DEVBLOCKS_PATH . "/libs/devblocks/adodb/session/adodb-session2.php");
			$options = array();
			$options['table'] = 'session';
			ADOdb_Session::config(DEVBLOCKS_DB_DRIVER, DEVBLOCKS_DB_HOST, DEVBLOCKS_DB_USER, DEVBLOCKS_DB_PASS, DEVBLOCKS_DB_DATABASE, $options);
			ADOdb_session::Persist($connectMode=false);
			ADOdb_session::lifetime($lifetime=86400);
			
			//session_name("cerb4");
			session_set_cookie_params(0);
			session_start();
			$instance = new _DevblocksSessionManager();
			$instance->visit = $_SESSION['um_visit']; /* @var $visit DevblocksSession */
		}
		
		return $instance;
	}
	
	/**
	 * Returns the current session or NULL if no session exists.
	 *
	 * @return DevblocksSession
	 */
	function getVisit() {
		return $this->visit;
	}
	
	/**
	 * Attempts to create a session by login/password.  On success a DevblocksSession 
	 * is returned.  On failure NULL is returned.
	 *
	 * @param string $login
	 * @param string $password
	 * @return DevblocksSession
	 */
	function login($login,$password) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,login,admin ".
			"FROM login ".
			"WHERE login = %s ".
			"AND password = MD5(%s)",
				$db->QMagic($login),
				$db->QMagic($password)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($rs->NumRows()) {
			$visit = new DevblocksSession();
				$visit->id = intval($rs->fields['id']);
				$visit->login = stripslashes($rs->fields['login']);
				$visit->admin = intval($rs->fields['admin']);
			$this->visit = $visit;
			$_SESSION['um_visit'] = $visit;
			return $this->visit;
		}
		
		$_SESSION['um_visit'] = null;
		return null;
	}
	
	/**
	 * Kills the current session.
	 *
	 */
	function logout() {
		$this->visit = null;
		unset($_SESSION['um_visit']);
	}
}

/**
 * Email Management Singleton
 *
 * @static 
 * @ingroup services
 */
class _DevblocksEmailManager {
	/**
	 * @private
	 */
	private function _DevblocksEmailManager() {}
	
	public function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksEmailManager();
		}
		return $instance;
	}
	
	static function send($server, $sRCPT, $headers, $body) {
		// mailer setup
		require_once(DEVBLOCKS_PATH . '/libs/devblocks/pear/Mail.php');
		$mail_params = array();
		$mail_params['host'] = $server;
		$mailer =& Mail::factory("smtp", $mail_params);

		$result = $mailer->send($sRCPT, $headers, $body);
		return $result;
	}
	
	static function getMessages($server, $port, $service, $username, $password) {
		if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
		require_once(DEVBLOCKS_PATH . '/libs/devblocks/pear/mimeDecode.php');
		
		$mailbox = imap_open("{".$server.":".$port."/service=".$service."/notls}INBOX",
							 !empty($username)?$username:"superuser",
							 !empty($password)?$password:"superuser")
			or die("Failed with error: ".imap_last_error());
		$check = imap_check($mailbox);
		
		$messages = array();
		$params = array();
		$params['include_bodies']	= true;
		$params['decode_bodies']	= true;
		$params['decode_headers']	= true;
		$params['crlf']				= "\r\n";
	
		for ($i=1; $i<=$check->Nmsgs; $i++) {
			$headers = imap_fetchheader($mailbox, $i);
			$body = imap_body($mailbox, $i);
			$params['input'] = $headers . "\r\n\r\n" . $body;
			$structure = Mail_mimeDecode::decode($params);
			$messages[] = $structure;
		}
		
		imap_close($mailbox);
		return $messages;
	}
}

/**
 * A single session instance
 *
 * @ingroup core
 */
class DevblocksSession {
	var $id = 0;
	var $login = '';
	var $admin = 0;
	
	/**
	 * Returns TRUE if the current session has administrative privileges, or FALSE otherwise.
	 *
	 * @return boolean
	 */
	function isAdmin() {
		return $this->admin != 0;
	}
};

/**
 * Smarty Template Manager Singleton
 *
 * @ingroup services
 */
class _DevblocksTemplateManager {
	/**
	 * Constructor
	 * 
	 * @private
	 */
	private function _DevblocksTemplateManager() {}
	/**
	 * Returns an instance of the Smarty Template Engine
	 * 
	 * @static 
	 * @return Smarty
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			require(DEVBLOCKS_PATH . '/libs/devblocks/smarty/Smarty.class.php');
			$instance = new Smarty();
			$instance->template_dir = DEVBLOCKS_PATH . '/templates';
			$instance->compile_dir = DEVBLOCKS_PATH . '/templates_c';
			$instance->cache_dir = DEVBLOCKS_PATH . '/cache';
			
			//$smarty->config_dir = DEVBLOCKS_PATH . '/configs';
			$instance->caching = 0;
			$instance->cache_lifetime = 0;
		}
		return $instance;
	}
};

/**
 * ADODB Database Singleton
 *
 * @ingroup services
 */
class _DevblocksDatabaseManager {
	
	/**
	 * Constructor 
	 * 
	 * @private
	 */
	private function _DevblocksDatabaseManager() {}
	
	/**
	 * Returns an ADODB database resource
	 *
	 * @static 
	 * @return ADOConnection
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			include_once(DEVBLOCKS_PATH . "/libs/devblocks/adodb/adodb.inc.php");
			$ADODB_CACHE_DIR = DEVBLOCKS_PATH . "/cache";
			@$instance =& ADONewConnection(DEVBLOCKS_DB_DRIVER); /* @var $instance ADOConnection */
			@$instance->Connect(DEVBLOCKS_DB_HOST,DEVBLOCKS_DB_USER,DEVBLOCKS_DB_PASS,DEVBLOCKS_DB_DATABASE);
			@$instance->SetFetchMode(ADODB_FETCH_ASSOC);
		}
		return $instance;
	}
	
};

class DevblocksResourceManager {
	private function DevblocksResourceManager() {}

	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new DevblocksResourceManager();
		}
		return $instance;
	}
}

class DevblocksUrlManager {
	private function DevblocksUrlManager() {}

	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new DevblocksUrlManager();
		}
		return $instance;
	}
	
	static function write($c,$a,$args) {
		if(defined('DEVBLOCKS_URL_REWRITE') && DEVBLOCKS_URL_REWRITE) {
			return sprintf("x"
			);
			
		} else {
			return sprintf("index.php?%s%s%s",
				(!empty($c) ? "c=" . $c : "x="),
				(!empty($a) ? "&a=" . $a : ""),
				(!empty($args) ? "&args=" . $args : "")
			);
		}
	}
}

/**
 * Unicode Translation Singleton
 *
 * @ingroup services
 */
class _DevblocksTranslationManager {
	/**
	 * Constructor
	 * 
	 * @private
	 */
	private function _DevblocksTranslationManager() {}
	
	/**
	 * Returns an instance of the translation singleton.
	 *
	 * @static 
	 * @return DevblocksTranslationManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksTranslationManager();
		}
		return $instance;
	}

	/**
	 * Translate an externalized string token into a Unicode string in the 
	 * current language.  The $vars argument provides a list of substitutions 
	 * similar to sprintf().
	 *
	 * @param string $token The externalized string token to replace
	 * @param array $vars A list of substitutions
	 * @return string A string with the Unicode values encoded in UTF-8
	 */
	function say($token,$vars=array()) {
		global $language;
		
		if(!isset($language[$token]))
			return "[#".$token."#]";
		
		if(!empty($vars)) {
			$u = new I18N_UnicodeString(vsprintf($language[$token],$vars),'UTF8');
		} else {
			$u = new I18N_UnicodeString($language[$token],'UTF8');
		}
		return $u->toUtf8String();
	}

}

?>
