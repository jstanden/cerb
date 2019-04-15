<?php
class DAO_CommunitySession extends Cerb_ORMHelper {
	const CREATED = 'created';
	const CSRF_TOKEN = 'csrf_token';
	const PORTAL_ID = 'portal_id';
	const PROPERTIES = 'properties';
	const SESSION_ID = 'session_id';
	const UPDATED = 'updated';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::CSRF_TOKEN)
			->string()
			;
		$validation
			->addField(self::PORTAL_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_PORTAL))
			->setRequired(true)
			;
		$validation
			->addField(self::PROPERTIES)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::SESSION_ID)
			->string()
			->setMaxLength(64)
			->setRequired(true)
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		
		return $validation->getFields();
	}
	
	static public function save(Model_CommunitySession $session) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("UPDATE community_session SET updated = %d, properties = %s WHERE session_id = %s AND portal_id = %d",
			time(),
			$db->qstr(serialize($session->getProperties())),
			$db->qstr($session->session_id),
			$session->portal_id
		);
		$db->ExecuteMaster($sql);
	}
	
	/**
	 * @param string $session_id
	 * @param integer $portal_id
	 * @return Model_CommunitySession
	 */
	static public function get($session_id, $portal_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT session_id, portal_id, created, updated, csrf_token, properties ".
			"FROM community_session ".
			"WHERE session_id = %s ".
			"AND portal_id = %d ",
			$db->qstr($session_id),
			$portal_id
		);
		$row = $db->GetRowSlave($sql);
		
		if(empty($row)) {
			$session = self::create($session_id, $portal_id);
			
		} else {
			$session = new Model_CommunitySession();
			$session->session_id = $row['session_id'];
			$session->portal_id = intval($row['portal_id']);
			$session->created = intval($row['created']);
			$session->updated = intval($row['updated']);
			$session->csrf_token = $row['csrf_token'];
			
			if(!empty($row['properties']))
				@$session->setProperties(unserialize($row['properties']));
		}
		
		return $session;
	}
	
	static public function delete($session_id, $portal_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM community_session WHERE session_id = %s AND portal_id = %d",
			$db->qstr($session_id),
			$portal_id
		);
		$db->ExecuteMaster($sql);
		
		return TRUE;
	}
	
	static public function deleteByPortalId($portal_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM community_session WHERE portal_id = %d",
			$portal_id
		);
		$db->ExecuteMaster($sql);
		
		return TRUE;
	}
	
	/**
	 * @param string $session_id
	 * @param integer $portal_id
	 * @return Model_CommunitySession
	 */
	static private function create($session_id, $portal_id) {
		$db = DevblocksPlatform::services()->database();

		$session = new Model_CommunitySession();
		$session->session_id = $session_id;
		$session->portal_id = $portal_id;
		$session->created = time();
		$session->updated = time();
		$session->csrf_token = CerberusApplication::generatePassword(128);
		
		$sql = sprintf("INSERT INTO community_session (session_id, portal_id, created, updated, csrf_token, properties) ".
			"VALUES (%s, %d, %d, %d, %s, '')",
			$db->qstr($session->session_id),
			$session->portal_id,
			$session->created,
			$session->updated,
			$db->qstr($session->csrf_token)
		);
		$db->ExecuteMaster($sql);
		
		self::gc(); // garbage collection
		
		return $session;
	}
	
	static private function gc() {
		$db = DevblocksPlatform::services()->database();
		$sql = sprintf("DELETE FROM community_session WHERE updated < %d",
			(time()-(60*60)) // 1 hr
		);
		$db->ExecuteMaster($sql);
	}
};

class Model_CommunitySession {
	public $session_id = '';
	public $portal_id = 0;
	public $created = 0;
	public $updated = 0;
	public $csrf_token = '';
	private $_properties = [];

	function login(Model_Contact $contact) {
		if(empty($contact) || empty($contact->id)) {
			$this->logout();
			return;
		}
		
		$this->setProperty('sc_login', $contact);
		
		DAO_Contact::update($contact->id, [
			DAO_Contact::LAST_LOGIN_AT => time(),
		]);
	}
	
	function logout() {
		$this->setProperty('sc_login', null);
	}
	
	function setProperties($properties) {
		$this->_properties = $properties;
	}
	
	function getProperties() {
		return $this->_properties;
	}
	
	function setProperty($key, $value) {
		if(null==$value) {
			unset($this->_properties[$key]);
		} else {
			$this->_properties[$key] = $value;
		}
		DAO_CommunitySession::save($this);
	}
	
	function getProperty($key, $default = null) {
		return isset($this->_properties[$key]) ? $this->_properties[$key] : $default;
	}
	
	function destroy() {
		$this->_properties = [];
		DAO_CommunitySession::delete($this->session_id, $this->portal_id);
	}
};
