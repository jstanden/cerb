<?php
include_once(UM_PATH . "/libs/pear/i18n/I18N_UnicodeString.php");
include_once(UM_PATH . "/languages/".UM_LANGUAGE."/strings.php");

/*
com.usermeet.page.instanced
com.usermeet.page.standalone
com.usermeet.menu.main
com.usermeet.submenu
*/

/**
 *  @defgroup core CloudGlue Framework Core
 *  Core data structures of the framework
 */

/**
 *  @defgroup plugin CloudGlue Framework Plugins
 *  Components for plugin/extensions
 */

/**
 *  @defgroup services CloudGlue Framework Services
 *  Services provided by the framework
 */

/**
 * A platform container for plugin/extension registries.
 *
 * @static 
 * @ingroup core
 * @author Jeff Standen
 */
class CgPlatform {
	/**
	 * @private
	 */
	private function CgPlatform() {}
	
	/**
	 * Returns the list of extensions on a given extension point.
	 *
	 * @static
	 * @param string $point
	 * @return CgExtensionManifest[]
	 */
	static function getExtensions($point) {
		$results = array();
		$extensions = CgPlatform::getExtensionRegistry();
		
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension CgExtensionManifest */
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
	 * @return CgExtensionManifest
	 */
	static function getExtension($extension_id) {
		$result = null;
		$extensions = CgPlatform::getExtensionRegistry();
		
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension CgExtensionManifest */
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
	 * @return CgExtensionManifest[]
	 */
	static function getExtensionRegistry() {
		static $extensions = array();
		
		if(!empty($extensions))
			return $extensions;
		
		$um_db = CgPlatform::getDatabaseService();
		$plugins = CgPlatform::getPluginRegistry();
		
		$sql = sprintf("SELECT e.id , e.plugin_id, e.point, e.name , e.file , e.class, e.params ".
			"FROM extension e"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$extension = new CgExtensionManifest();
			$extension->id = stripslashes($rs->fields['id']);
			$extension->plugin_id = intval($rs->fields['plugin_id']);
			$extension->point = stripslashes($rs->fields['point']);
			$extension->name = stripslashes($rs->fields['name']);
			$extension->file = stripslashes($rs->fields['file']);
			$extension->class = stripslashes($rs->fields['class']);
			$extension->params = @unserialize(stripslashes($rs->fields['params']));
			
			if(empty($extension->params))
				$extension->params = array();
			
			@$plugin = $plugins[$extension->plugin_id]; /* @var $plugin CgPluginManifest */
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
	 * @return CgPluginManifest[]
	 */
	static function getPluginRegistry() {
		static $plugins = array();
		
		if(!empty($plugins))
			return $plugins;
		
		$um_db = CgPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT p.id , p.enabled , p.name, p.author, p.dir ".
			"FROM plugin p"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$plugin = new CgPluginManifest();
			$plugin->id = intval($rs->fields['id']);
//			$plugin->enabled = intval($rs->fields['enabled']);
			$plugin->name = stripslashes($rs->fields['name']);
			$plugin->author = stripslashes($rs->fields['author']);
			$plugin->dir = stripslashes($rs->fields['dir']);
			
			if(file_exists(UM_PLUGIN_PATH . $plugin->dir)) {
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
	 * @return CgPluginManifest[]
	 */
	static function readPlugins() {
		$dir = UM_PLUGIN_PATH;
		$plugins = array();
		
		if (is_dir($dir)) {
		    if ($dh = opendir($dir)) {
		        while (($file = readdir($dh)) !== false) {
		        	if($file=="." || $file == ".." || 0 == strcasecmp($file,"CVS"))
		        		continue;
		        		
		        	$path = $dir . '/' . $file;
		        	if(is_dir($path) && file_exists($path.'/plugin.xml')) {
		        		$manifest = CgPlatform::_readPluginManifest($file);
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
	 * @return CgPluginManifest
	 */
	static private function _readPluginManifest($dir) {
		if(!file_exists(UM_PLUGIN_PATH.$dir.'/plugin.xml'))
			return NULL;
			
		include_once(UM_PATH . '/libs/domit/xml_domit_include.php');
		$rssRoot =& new DOMIT_Document();
		$success = $rssRoot->loadXML(UM_PLUGIN_PATH.$dir.'/plugin.xml', false);
		$doc =& $rssRoot->documentElement; /* @var $doc DOMIT_Node */
		
		$eName = $doc->getElementsByPath("name",1);
		$eAuthor = $doc->getElementsByPath("author",1);
			
		$manifest = new CgPluginManifest();
		$manifest->dir = $dir;
		$manifest->author = $eAuthor->getText();
		$manifest->name = $eName->getText();
		
		$um_db = CgPlatform::getDatabaseService();
		
		// [JAS]: Check if the plugin exists already
		$sql = sprintf("SELECT id FROM plugin WHERE dir = %s",
			$um_db->qstr($manifest->dir)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($rs->NumRows()) { // exists
			$manifest->id = intval($rs->fields['id']);
			$sql = sprintf("UPDATE plugin SET name=%s,author=%s,dir=%s WHERE id=%d",
				$um_db->qstr($manifest->name),
				$um_db->qstr($manifest->author),
				$um_db->qstr($manifest->dir),
				$manifest->id
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
		} else { // new
			$id = $um_db->GenID('plugin_seq');
			$sql = sprintf("INSERT INTO plugin (id,name,enabled,author,dir) ".
				" VALUES (%d,%s,%d,%s,%s)",
				$id,
				$um_db->qstr($manifest->name),
				1,
				$um_db->qstr($manifest->author),
				$um_db->qstr($manifest->dir)
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
			$manifest->id = $id;
		}

		$eExtensions =& $doc->getElementsByPath("extensions/extension"); /* @var $eExtensions DOMIT_NodeList */
		if(is_array($eExtensions->arNodeList))
		foreach($eExtensions->arNodeList as $eExtension) { /* @var $eExtension DOMIT_Node */
			
			$sPoint = $eExtension->getAttribute('point');
			$eId = $eExtension->getElementsByPath('id',1);
			$eName = $eExtension->getElementsByPath('name',1);
			$eClassName = $eExtension->getElementsByPath('class/name',1);
			$eClassFile = $eExtension->getElementsByPath('class/file',1);
			$params = $eExtension->getElementsByPath('params/param');
			$extension = new CgExtensionManifest();

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
		foreach($manifest->extensions as $extension_idx => $extension) { /* @var $extension CgExtensionManifest */
			$sql = sprintf("SELECT id FROM extension WHERE plugin_id = '%d' AND point = %s AND class = %s",
				$extension->plugin_id,
				$um_db->qstr($extension->point),
				$um_db->qstr($extension->class)
			);
			$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
			if($rs->NumRows()) { // exists
				$manifest->extensions[$extension_idx]->extension_id = stripslashes($rs->fields['id']);
				$sql = sprintf("UPDATE extension SET plugin_id=%d,point=%s,name=%s,file=%s,class=%s,params=%s WHERE id=%s",
					$extension->plugin_id,
					$um_db->qstr($extension->point),
					$um_db->qstr($extension->name),
					$um_db->qstr($extension->file),
					$um_db->qstr($extension->class),
					$um_db->qstr(serialize($extension->params)),
					$um_db->qstr($manifest->extensions[$extension_idx]->extension_id)
				);
				$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
			} else { // new
				$sql = sprintf("INSERT INTO extension (id,plugin_id,point,name,file,class,params) ".
					" VALUES (%s,%d,%s,%s,%s,%s,%s)",
					$um_db->qstr($extension->id),
					$extension->plugin_id,
					$um_db->qstr($extension->point),
					$um_db->qstr($extension->name),
					$um_db->qstr($extension->file),
					$um_db->qstr($extension->class),
					$um_db->qstr(serialize($extension->params))
				);
				$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
			}
			
		}

		return $manifest;
	}

	static function getDatabaseService() {
		return _CgDatabaseManager::getInstance();
	}
	
	static function getMailService() {
		return _CgEmailManager::getInstance();
	}
	
	static function getSessionService() {
		return _CgSessionManager::getInstance();
	}
	
	static function getTemplateService() {
		return _CgTemplateManager::getInstance();
	}
	
	static function getTranslationService() {
		return _CgTranslationManager::getInstance();
	}
	
	/**
	 * Initializes the plugin platform (paths, etc).
	 *
	 * @static 
	 * @return void
	 */
	static function init() {
		// [JAS] [MDF]: Automatically determine the relative webpath to CloudGlue files
		if(!defined('UM_WEBPATH')) {
			$php_self = $_SERVER["PHP_SELF"];
			$pos = strrpos($php_self,'/');
			$php_self = substr($php_self,0,$pos) . '/' . UM_DIRECTORY . '/';
			@define('UM_WEBPATH',$php_self);
		}
	}
	
};

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class CgPluginManifest {
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
class CgExtensionManifest {
	var $id = '';
	var $plugin_id = 0;
	var $point = '';
	var $name = '';
	var $file = '';
	var $class = '';
	var $params = array();

	function CgExtensionManifest() {}
	
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

		$plugins = CgPlatform::getPluginRegistry();
		
		if(!isset($plugins[$this->plugin_id]))
			return null;
		
		$plugin = $plugins[$this->plugin_id]; /* @var $plugin CgPluginManifest */
		
		$class_file = UM_PLUGIN_PATH . $plugin->dir . '/' . $this->file;
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
class CgExtension {
	var $manifest = null;
	var $instance_id = 0;
	var $id  = '';
	var $params = array();
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param CgExtensionManifest $manifest
	 * @param int $instance_id
	 * @return CgExtension
	 */
	function CgExtension($manifest,$instance_id) { /* @var $manifest CgExtensionManifest */
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
		$um_db = CgPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT property,value ".
			"FROM property_store ".
			"WHERE extension_id=%s AND instance_id='%d' ",
			$um_db->qstr($this->id),
			$this->instance_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		
		$um_db = CgPlatform::getDatabaseService();
		
		if(is_array($this->params))
		foreach($this->params as $k => $v) {
			$um_db->Replace(
				'property_store',
				array('extension_id'=>$this->id,'instance_id'=>$this->instance_id,'property'=>$um_db->qstr($k),'value'=>$um_db->qstr($v)),
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
class _CgSessionManager {
	var $visit = null;
	
	/**
	 * @private
	 */
	private function _CgSessionManager() {}
	
	/**
	 * Returns an instance of the session manager
	 *
	 * @static
	 * @return CgSessionManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			include_once(UM_PATH . "/libs/adodb/session/adodb-session2.php");
			$options = array();
			$options['table'] = 'session';
			ADOdb_Session::config(UM_DB_DRIVER, UM_DB_HOST, UM_DB_USER, UM_DB_PASS, UM_DB_DATABASE, $options);
			ADOdb_session::Persist($connectMode=false);
			//session_name("cerb4");
			session_set_cookie_params(0);
			session_start();
			$instance = new _CgSessionManager();
			$instance->visit = $_SESSION['um_visit']; /* @var $visit CgSession */
		}
		
		return $instance;
	}
	
	/**
	 * Returns the current session or NULL if no session exists.
	 *
	 * @return CgSession
	 */
	function getVisit() {
		return $this->visit;
	}
	
	/**
	 * Attempts to create a session by login/password.  On success a CgSession 
	 * is returned.  On failure NULL is returned.
	 *
	 * @param string $login
	 * @param string $password
	 * @return CgSession
	 */
	function login($login,$password) {
		$um_db = CgPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,login,admin ".
			"FROM login ".
			"WHERE login = %s ".
			"AND password = MD5(%s)",
				$um_db->qstr($login),
				$um_db->qstr($password)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($rs->NumRows()) {
			$visit = new CgSession();
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
class _CgEmailManager {
	/**
	 * @private
	 */
	private function _CgEmailManager() {}
	
	public function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _CgEmailManager();
		}
		return $instance;
	}
	
	static function send($server, $sRCPT, $headers, $body) {
		// mailer setup
		require_once(UM_PATH . '/libs/pear/Mail.php');
		$mail_params = array();
		$mail_params['host'] = $server;
		$mailer =& Mail::factory("smtp", $mail_params);

		$result = $mailer->send($sRCPT, $headers, $body);
		return $result;
	}
	
	static function getMessages($server, $port, $service, $username, $password) {
		if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
		require_once(UM_PATH . '/libs/pear/mimeDecode.php');
		
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
class CgSession {
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
class _CgTemplateManager {
	/**
	 * Constructor
	 * 
	 * @private
	 */
	private function _CgTemplateManager() {}
	/**
	 * Returns an instance of the Smarty Template Engine
	 * 
	 * @static 
	 * @return Smarty
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			require(UM_PATH . '/libs/smarty/Smarty.class.php');
			$instance = new Smarty();
			$instance->template_dir = UM_PATH . '/templates';
			$instance->compile_dir = UM_PATH . '/templates_c';
			$instance->cache_dir = UM_PATH . '/cache';
			
			//$smarty->config_dir = UM_PATH . '/configs';
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
class _CgDatabaseManager {
	
	/**
	 * Constructor 
	 * 
	 * @private
	 */
	private function _CgDatabaseManager() {}
	
	/**
	 * Returns an ADODB database resource
	 *
	 * @static 
	 * @return ADOConnection
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			include_once(UM_PATH . "/libs/adodb/adodb.inc.php");
			$ADODB_CACHE_DIR = UM_PATH . "/cache";
			$instance =& ADONewConnection(UM_DB_DRIVER);
			$instance->Connect(UM_DB_HOST,UM_DB_USER,UM_DB_PASS,UM_DB_DATABASE);
			$instance->SetFetchMode(ADODB_FETCH_ASSOC);
		}
		return $instance;
	}
	
};

/**
 * Unicode Translation Singleton
 *
 * @ingroup services
 */
class _CgTranslationManager {
	/**
	 * Constructor
	 * 
	 * @private
	 */
	private function _CgTranslationManager() {}
	
	/**
	 * Returns an instance of the translation singleton.
	 *
	 * @static 
	 * @return CgTranslationManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _CgTranslationManager();
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
