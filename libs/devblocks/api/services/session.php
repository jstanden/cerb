<?php
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
		    $url_writer = DevblocksPlatform::getUrlService();
		    
			if(is_null($db) || !$db->isConnected()) { 
				return null;
			}
			
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
			
			@session_destroy();
			
			$handler = '_DevblocksSessionDatabaseDriver';
			
			session_set_save_handler(
				array($handler, 'open'),
				array($handler, 'close'),
				array($handler, 'read'),
				array($handler, 'write'),
				array($handler, 'destroy'),
				array($handler, 'gc')
			);

			$session_lifespan = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::SESSION_LIFESPAN, CerberusSettingsDefaults::SESSION_LIFESPAN);

			session_name(APP_SESSION_NAME);
			session_set_cookie_params($session_lifespan, '/', NULL, $url_writer->isSSL(), true);
			session_start();
			
			$instance = new _DevblocksSessionManager();
			$instance->visit = isset($_SESSION['db_visit']) ? $_SESSION['db_visit'] : NULL; /* @var $visit DevblocksVisit */
		}
		
		return $instance;
	}
	
	function decodeSession($data) {
		$vars=preg_split(
			'/([a-zA-Z_\.\x7f-\xff][a-zA-Z0-9_\.\x7f-\xff^|]*)\|/',
			$data,
			-1,
			PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
		);
		
		$scope = array();
		
		while(!empty($vars)) {
			@$key = array_shift($vars);
			@$value = unserialize(array_shift($vars));
			$scope[$key] = $value;
		}
		
		return $scope; 		
	}
	
	/**
	 * Returns the current session or NULL if no session exists.
	 * 
	 * @return DevblocksVisit
	 */
	function getVisit() {
		return $this->visit;
	}
	
	/**
	 * @param DevblocksVisit $visit
	 */
	function setVisit(DevblocksVisit $visit = null) {
		$this->visit = $visit;
		$_SESSION['db_visit'] = $this->visit;
	}
	
	function getAll() {
		return _DevblocksSessionDatabaseDriver::getAll();
	}
	
	/**
	 * Kills the specified or current session.
	 *
	 */
	function clear($key=null) {
		if(is_null($key)) {
			$this->visit = null;
			setcookie('Devblocks', null, 0, '/', null);
			session_unset();
			session_destroy();
		} else {
			_DevblocksSessionDatabaseDriver::destroy($key);
		}
	}
	
	function clearAll() {
		self::clear();
		// [TODO] Allow subclasses to be cleared here too
		_DevblocksSessionDatabaseDriver::destroyAll();
	}
};

class _DevblocksSessionDatabaseDriver {
	static $_data = null;
	
	static function open($save_path, $session_name) {
		return true;
	}
	
	static function close() {
		return true;
	}
	
	static function read($id) {
		$db = DevblocksPlatform::getDatabaseService();
		if(null != (self::$_data = $db->GetOne(sprintf("SELECT session_data FROM devblocks_session WHERE session_key = %s", $db->qstr($id)))))
			return self::$_data;
			
		return false;
	}
	
	static function write($id, $session_data) {
		// Nothing changed!
		if(self::$_data==$session_data) {
			return true;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// Update
		$result = $db->Execute(sprintf("UPDATE devblocks_session SET updated=%d, session_data=%s WHERE session_key=%s",
			time(),
			$db->qstr($session_data),
			$db->qstr($id)
		));
		
		if(0==$db->Affected_Rows()) {
			// Insert
			$db->Execute(sprintf("INSERT INTO devblocks_session (session_key, created, updated, session_data) ".
				"VALUES (%s, %d, %d, %s)",
				$db->qstr($id),
				time(),
				time(),
				$db->qstr($session_data)
			));
		}
		
		return true;
	}
	
	static function destroy($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM devblocks_session WHERE session_key = %s", $db->qstr($id)));
		return true;
	}
	
	static function gc($maxlifetime) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM devblocks_session WHERE updated + %d < %d", $maxlifetime, time()));
		return true;
	}
	
	static function getAll() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetArray("SELECT session_key, created, updated, session_data FROM devblocks_session");
	}
	
	static function destroyAll() {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute("DELETE FROM devblocks_session");
	}
};