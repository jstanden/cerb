<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class DAO_Mailbox extends Cerb_ORMHelper {
	const CHECKED_AT = 'checked_at';
	const CONNECTED_ACCOUNT_ID = 'connected_account_id';
	const DELAY_UNTIL = 'delay_until';
	const ENABLED = 'enabled';
	const HOST = 'host';
	const ID = 'id';
	const MAX_MSG_SIZE_KB = 'max_msg_size_kb';
	const NAME = 'name';
	const NUM_FAILS = 'num_fails';
	const PASSWORD = 'password';
	const PORT = 'port';
	const PROTOCOL = 'protocol';
	const TIMEOUT_SECS = 'timeout_secs';
	const UPDATED_AT = 'updated_at';
	const USERNAME = 'username';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		// int(10) unsigned
		$validation
			->addField(self::CHECKED_AT)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::CONNECTED_ACCOUNT_ID)
			->id()
			->addValidator($validation->validators()->contextId(Context_ConnectedAccount::ID, true))
			;
		// int(10) unsigned
		$validation
			->addField(self::DELAY_UNTIL)
			->timestamp()
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::ENABLED)
			->bit()
			;
		// varchar(128)
		$validation
			->addField(self::HOST)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::MAX_MSG_SIZE_KB)
			->uint(4)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// tinyint(4)
		$validation
			->addField(self::NUM_FAILS)
			->uint(1)
			;
		// varchar(128)
		$validation
			->addField(self::PASSWORD)
			->string()
			->setMaxLength(128)
			;
		// smallint(5) unsigned
		$validation
			->addField(self::PORT)
			->uint(2)
			;
		// varchar(32)
		$validation
			->addField(self::PROTOCOL)
			->string()
			->setMaxLength(32)
			;
		// mediumint(8) unsigned
		$validation
			->addField(self::TIMEOUT_SECS)
			->uint(2)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// varchar(128)
		$validation
			->addField(self::USERNAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();

		$sql = "INSERT INTO mailbox () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_MAILBOX, $id);
		
		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_MAILBOX;
		self::_updateAbstract($context, $ids, $fields);

		// Make a diff for the requested objects in batches

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_MAILBOX, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'mailbox', $fields);

			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.mailbox.update',
						array(
							'fields' => $fields,
						)
					)
				);

				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_MAILBOX, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mailbox', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_MAILBOX;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Mailbox[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, enabled, name, protocol, host, username, password, port, num_fails, delay_until, timeout_secs, max_msg_size_kb, updated_at, checked_at, connected_account_id ".
			"FROM mailbox ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}

		return self::_getObjectsFromResult($rs);
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_Mailbox[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, DAO_Mailbox::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_Mailbox
	 */
	static function get($id) {
		if(empty($id))
			return null;

		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));

		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	/**
	 *
	 * @param array $ids
	 * @return Model_Mailbox[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Mailbox[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return false;

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Mailbox();
			$object->id = intval($row['id']);
			$object->enabled = $row['enabled'] ? 1 : 0;
			$object->name = $row['name'];
			$object->protocol = $row['protocol'];
			$object->host = $row['host'];
			$object->username = $row['username'];
			$object->password = $row['password'];
			$object->port = intval($row['port']);
			$object->num_fails = intval($row['num_fails']);
			$object->delay_until = intval($row['delay_until']);
			$object->timeout_secs = intval($row['timeout_secs']);
			$object->max_msg_size_kb = intval($row['max_msg_size_kb']);
			$object->updated_at = intval($row['updated_at']);
			$object->checked_at = intval($row['checked_at']);
			$object->connected_account_id = intval($row['connected_account_id']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function count() {
		if(false == ($mailboxes = DAO_Mailbox::getAll()) || !is_array($mailboxes))
			return 0;

		return count($mailboxes);
	}

	static function random() {
		return self::_getRandom('mailbox');
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();

		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM mailbox WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_MAILBOX,
					'context_ids' => $ids
				)
			)
		);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Mailbox::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Mailbox', $sortBy);

		$select_sql = sprintf("SELECT ".
			"mailbox.id as %s, ".
			"mailbox.enabled as %s, ".
			"mailbox.name as %s, ".
			"mailbox.protocol as %s, ".
			"mailbox.host as %s, ".
			"mailbox.username as %s, ".
			"mailbox.password as %s, ".
			"mailbox.port as %s, ".
			"mailbox.num_fails as %s, ".
			"mailbox.delay_until as %s, ".
			"mailbox.timeout_secs as %s, ".
			"mailbox.max_msg_size_kb as %s, ".
			"mailbox.updated_at as %s, ".
			"mailbox.connected_account_id as %s, ".
			"mailbox.checked_at as %s ",
				SearchFields_Mailbox::ID,
				SearchFields_Mailbox::ENABLED,
				SearchFields_Mailbox::NAME,
				SearchFields_Mailbox::PROTOCOL,
				SearchFields_Mailbox::HOST,
				SearchFields_Mailbox::USERNAME,
				SearchFields_Mailbox::PASSWORD,
				SearchFields_Mailbox::PORT,
				SearchFields_Mailbox::NUM_FAILS,
				SearchFields_Mailbox::DELAY_UNTIL,
				SearchFields_Mailbox::TIMEOUT_SECS,
				SearchFields_Mailbox::MAX_MSG_SIZE_KB,
				SearchFields_Mailbox::UPDATED_AT,
				SearchFields_Mailbox::CONNECTED_ACCOUNT_ID,
				SearchFields_Mailbox::CHECKED_AT
			);

		$join_sql = "FROM mailbox ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Mailbox');

		return array(
			'primary_table' => 'mailbox',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}

	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_Mailbox::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

};

class Model_Mailbox {
	public $checked_at = 0;
	public $connected_account_id = 0;
	public $delay_until = 0;
	public $enabled=1;
	public $host;
	public $id;
	public $max_msg_size_kb = 25600;
	public $name;
	public $num_fails = 0;
	public $password;
	public $port=110;
	public $protocol='pop3';
	public $timeout_secs = 30;
	public $updated_at = 0;
	public $username;
	
	/**
	 * @param string $error
	 * @return Horde_Imap_Client_Base|false
	 */
	public function getClient(&$error=null) {
		try {
			$imap_timeout = $this->timeout_secs ?? 30;
			
			$options = [
				'username' => $this->username,
				'password' => $this->password,
				'hostspec' => $this->host,
				'port' => $this->port,
				'timeout' => $imap_timeout,
				'secure' =>  false,
			];
			
			if(in_array($this->protocol, ['imap-ssl','pop3-ssl'])) {
				$options['secure'] = 'tlsv1';
			} else if(in_array($this->protocol, ['imap-starttls','pop3-starttls'])) {
				$options['secure'] = 'tls';
			}
			
			// Are we using a connected account for XOAUTH2?
			if($this->connected_account_id) {
				if(false == ($connected_account = DAO_ConnectedAccount::get($this->connected_account_id))) {
					$error = "Failed to load the connected account";
					return false;
				}
				
				if(false == ($service = $connected_account->getService())) {
					$error = "Failed to load the connected service";
					return false;
				}
				
				if(false == ($service_extension = $service->getExtension())) {
					$error = "Failed to load the connected service extension";
					return false;
				}
				
				if(!($service_extension instanceof ServiceProvider_OAuth2)) {
					$error = "The connected account is not an OAuth2 provider";
					return false;
				}
				
				/** @var $service_extension ServiceProvider_OAuth2 */
				if(false == ($access_token = $service_extension->getAccessToken($connected_account))) {
					$error = "Failed to load the access token";
					return false;
				}
				
				$options['xoauth2_token'] = new Horde_Imap_Client_Password_Xoauth2($this->username, $access_token->getToken());
				
				if(!$options['password'])
					$options['password'] = 'XOAUTH2';
			}
			
			if (DevblocksPlatform::strStartsWith($this->protocol, 'pop3')) {
				$client = new Horde_Imap_Client_Socket_Pop3($options);
				
			} else {
				$client = new Horde_Imap_Client_Socket($options);
			}
			
			// [TODO] IMAP: capability_ignore
			// [TODO] Also allow disabling GSSAPI, NTLM from UI (requires patch)
			/*
			$disable_authenticators = [];
			
			if($account->auth_disable_plain)
				$disable_authenticators[] = 'PLAIN';
			
			if(defined('APP_MAIL_IMAP_DISABLE_NTLM') && APP_MAIL_IMAP_DISABLE_NTLM)
				$disable_authenticators[] = 'NTLM';
			
			if(defined('APP_MAIL_IMAP_DISABLE_GSSAPI') && APP_MAIL_IMAP_DISABLE_GSSAPI)
				$disable_authenticators[] = 'GSSAPI';
			
			if(!empty($disable_authenticators))
				$imap_options['DISABLE_AUTHENTICATOR'] = $disable_authenticators;
			*/
			
			$client->login();
			
			return $client;
			
		} catch (Horde_Imap_Client_Exception $e) {
			$error = $e->getMessage();
			return false;
		}
	}
};

class SearchFields_Mailbox extends DevblocksSearchFields {
	const CHECKED_AT = 'p_checked_at';
	const CONNECTED_ACCOUNT_ID = 'p_connected_account_id';
	const DELAY_UNTIL = 'p_delay_until';
	const ENABLED = 'p_enabled';
	const HOST = 'p_host';
	const ID = 'p_id';
	const MAX_MSG_SIZE_KB = 'p_max_msg_size_kb';
	const NAME = 'p_name';
	const NUM_FAILS = 'p_num_fails';
	const PASSWORD = 'p_password';
	const PORT = 'p_port';
	const PROTOCOL = 'p_protocol';
	const TIMEOUT_SECS = 'p_timeout_secs';
	const UPDATED_AT = 'p_updated_at';
	const USERNAME = 'p_username';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';

	static private $_fields = null;

	static function getPrimaryKey() {
		return 'mailbox.id';
	}

	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_MAILBOX => new DevblocksSearchFieldContextKeys('mailbox.id', self::ID),
		);
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_MAILBOX, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_MAILBOX), '%s'), self::getPrimaryKey());

			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_MAILBOX, self::getPrimaryKey());

			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Mailbox::ID:
				$models = DAO_Mailbox::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();

		return self::$_fields;
	}

	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = array(
			self::CHECKED_AT => new DevblocksSearchField(self::CHECKED_AT, 'mailbox', 'checked_at', $translate->_('dao.mailbox.checked_at'), Model_CustomField::TYPE_DATE, true),
			self::CONNECTED_ACCOUNT_ID => new DevblocksSearchField(self::CONNECTED_ACCOUNT_ID, 'mailbox', 'connected_account_id', $translate->_('common.connected_account'), Model_CustomField::TYPE_NUMBER, true),
			self::DELAY_UNTIL => new DevblocksSearchField(self::DELAY_UNTIL, 'mailbox', 'delay_until', $translate->_('dao.mailbox.delay_until'), Model_CustomField::TYPE_DATE, true),
			self::ENABLED => new DevblocksSearchField(self::ENABLED, 'mailbox', 'enabled', $translate->_('common.enabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::HOST => new DevblocksSearchField(self::HOST, 'mailbox', 'host', $translate->_('common.host'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'mailbox', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::MAX_MSG_SIZE_KB => new DevblocksSearchField(self::MAX_MSG_SIZE_KB, 'mailbox', 'max_msg_size_kb', $translate->_('dao.mailbox.max_msg_size_kb'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'mailbox', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NUM_FAILS => new DevblocksSearchField(self::NUM_FAILS, 'mailbox', 'num_fails', $translate->_('dao.mailbox.num_fails'), Model_CustomField::TYPE_NUMBER, true),
			self::PASSWORD => new DevblocksSearchField(self::PASSWORD, 'mailbox', 'password', $translate->_('common.password'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PORT => new DevblocksSearchField(self::PORT, 'mailbox', 'port', $translate->_('dao.mailbox.port'), Model_CustomField::TYPE_NUMBER, true),
			self::PROTOCOL => new DevblocksSearchField(self::PROTOCOL, 'mailbox', 'protocol', $translate->_('dao.mailbox.protocol'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIMEOUT_SECS => new DevblocksSearchField(self::TIMEOUT_SECS, 'mailbox', 'timeout_secs', $translate->_('dao.mailbox.timeout_secs'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'mailbox', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::USERNAME => new DevblocksSearchField(self::USERNAME, 'mailbox', 'username', $translate->_('common.user'), Model_CustomField::TYPE_SINGLE_LINE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);

		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class View_Mailbox extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'mailboxes';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.mailboxes');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Mailbox::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Mailbox::NAME,
			SearchFields_Mailbox::PROTOCOL,
			SearchFields_Mailbox::HOST,
			SearchFields_Mailbox::USERNAME,
			SearchFields_Mailbox::PORT,
			SearchFields_Mailbox::NUM_FAILS,
			SearchFields_Mailbox::TIMEOUT_SECS,
			SearchFields_Mailbox::MAX_MSG_SIZE_KB,
			SearchFields_Mailbox::UPDATED_AT,
			SearchFields_Mailbox::CHECKED_AT,
			SearchFields_Mailbox::CONNECTED_ACCOUNT_ID,
		);

		$this->addColumnsHidden(array(
			SearchFields_Mailbox::PASSWORD,
			SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK,
			SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET,
			SearchFields_Mailbox::VIRTUAL_WATCHERS,
		));

		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Mailbox::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Mailbox');

		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Mailbox', $ids);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Mailbox', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);

		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				// Fields
				case SearchFields_Mailbox::HOST:
				case SearchFields_Mailbox::PROTOCOL:
					$pass = true;
					break;

				// Virtuals
				case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Mailbox::VIRTUAL_WATCHERS:
					$pass = true;
					break;

				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}

			if($pass)
				$fields[$field_key] = $field_model;
		}

		return $fields;
	}

	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_MAILBOX;

		if(!isset($fields[$column]))
			return [];

		switch($column) {
			case SearchFields_Mailbox::HOST:
			case SearchFields_Mailbox::PROTOCOL:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;

			case SearchFields_Mailbox::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;

			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}

				break;
		}

		return $counts;
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_Mailbox::getFields();

		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Mailbox::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'checkedAt' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Mailbox::CHECKED_AT),
				),
			'enabled' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Mailbox::ENABLED),
				),
			'fail.count' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Mailbox::NUM_FAILS),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_MAILBOX],
					]
				),
			'host' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Mailbox::HOST),
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Mailbox::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_MAILBOX, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Mailbox::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'protocol' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Mailbox::PROTOCOL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Mailbox::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Mailbox::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);

		// Add quick search links

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK);

		// Add searchable custom fields

		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_MAILBOX, $fields, null);

		// Add is_sortable

		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);

		// Sort by keys
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Mailbox::VIRTUAL_WATCHERS, $tokens);
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);

				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}

	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MAILBOX);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/mailbox/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_Mailbox::ENABLED:
				parent::_renderCriteriaParamBoolean($param);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;

		switch($key) {
			case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;

			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;

			case SearchFields_Mailbox::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Mailbox::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Mailbox::NAME:
			case SearchFields_Mailbox::PROTOCOL:
			case SearchFields_Mailbox::HOST:
			case SearchFields_Mailbox::USERNAME:
			case SearchFields_Mailbox::PASSWORD:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case SearchFields_Mailbox::CONNECTED_ACCOUNT_ID:
			case SearchFields_Mailbox::ID:
			case SearchFields_Mailbox::NUM_FAILS:
			case SearchFields_Mailbox::PORT:
			case SearchFields_Mailbox::TIMEOUT_SECS:
			case SearchFields_Mailbox::MAX_MSG_SIZE_KB:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Mailbox::CHECKED_AT:
			case SearchFields_Mailbox::DELAY_UNTIL:
			case SearchFields_Mailbox::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Mailbox::ENABLED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;

			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;

			case SearchFields_Mailbox::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;

			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_Mailbox extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = 'cerberusweb.contexts.mailbox';
	const URI = 'mailbox';
	
	static function isReadableByActor($models, $actor) {
		// Only admins can read
		return self::isWriteableByActor($models, $actor);
	}

	static function isWriteableByActor($models, $actor) {
		// Only admins can modify

		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);

		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);

		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Mailbox::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=mailbox&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Mailbox();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['connected_account_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.connected_account'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->connected_account_id,
			'params' => [
				'context' => Context_ConnectedAccount::ID,
			]
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['enabled'] = array(
			'label' => mb_ucfirst($translate->_('common.enabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->enabled,
		);
		
		$properties['protocol'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.protocol')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->protocol,
		);
			
		$properties['host'] = array(
			'label' => mb_ucfirst($translate->_('common.host')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->host,
		);
			
		$properties['username'] = array(
			'label' => mb_ucfirst($translate->_('common.user')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->username,
		);
		
		$properties['port'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.port')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->port,
		);
			
		$properties['num_fails'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.num_fails')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_fails,
		);
		
		$properties['delay_until'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.delay_until')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->delay_until,
		);
		
		$properties['delay_until'] = array(
			'label' => 'Timeout (secs)',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->timeout_secs,
		);
		
		$properties['max_msg_size_kb'] = array(
			'label' => 'Max. Msg. Size',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => DevblocksPlatform::strPrettyBytes($model->max_msg_size_kb * 1000),
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($mailbox = DAO_Mailbox::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($mailbox->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return array(
			'id' => $mailbox->id,
			'name' => $mailbox->name,
			'permalink' => $url,
			'updated' => $mailbox->updated_at,
		);
	}

	function getDefaultProperties() {
		return array(
			'is_enabled',
			'checked_at',
			'host',
			'port',
			'protocol',
			'username',
			'num_fails',
			'timeout_secs',
			'max_msg_size_kb',
			'updated_at',
		);
	}

	function getContext($mailbox, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Mailbox:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MAILBOX);

		// Polymorph
		if(is_numeric($mailbox)) {
			$mailbox = DAO_Mailbox::get($mailbox);
		} elseif($mailbox instanceof Model_Mailbox) {
			// It's what we want already.
		} elseif(is_array($mailbox)) {
			$mailbox = Cerb_ORMHelper::recastArrayToModel($mailbox, 'Model_Mailbox');
		} else {
			$mailbox = null;
		}

		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'checked_at' => $prefix.$translate->_('dao.mailbox.checked_at'),
			'connected_account_id' => $prefix.$translate->_('common.connected_account'),
			'host' => $prefix.$translate->_('common.host'),
			'id' => $prefix.$translate->_('common.id'),
			'is_enabled' => $prefix.$translate->_('common.enabled'),
			'max_msg_size_kb' => $prefix.$translate->_('dao.mailbox.max_msg_size_kb'),
			'name' => $prefix.$translate->_('common.name'),
			'num_fails' => $prefix.$translate->_('dao.mailbox.num_fails'),
			'port' => $prefix.$translate->_('dao.mailbox.port'),
			'protocol' => $prefix.$translate->_('dao.mailbox.protocol'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'timeout_secs' => $prefix.$translate->_('dao.mailbox.timeout_secs'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'username' => $prefix.$translate->_('common.username'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'checked_at' => Model_CustomField::TYPE_DATE,
			'connected_account_id' => Model_CustomField::TYPE_NUMBER,
			'host' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_enabled' => Model_CustomField::TYPE_CHECKBOX,
			'max_msg_size_kb' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'num_fails' => Model_CustomField::TYPE_NUMBER,
			'port' => Model_CustomField::TYPE_NUMBER,
			'protocol' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'timeout_secs' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'username' => Model_CustomField::TYPE_SINGLE_LINE,
		);

		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Mailbox::ID;
		$token_values['_type'] = Context_Mailbox::URI;
		
		$token_values['_types'] = $token_types;

		if($mailbox) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $mailbox->name;
			$token_values['checked_at'] = $mailbox->checked_at;
			$token_values['connected_account_id'] = $mailbox->connected_account_id;
			$token_values['host'] = $mailbox->host;
			$token_values['id'] = $mailbox->id;
			$token_values['is_enabled'] = $mailbox->enabled;
			$token_values['max_msg_size_kb'] = $mailbox->max_msg_size_kb;
			$token_values['name'] = $mailbox->name;
			$token_values['num_fails'] = $mailbox->num_fails;
			$token_values['port'] = $mailbox->port;
			$token_values['protocol'] = $mailbox->protocol;
			$token_values['timeout_secs'] = $mailbox->timeout_secs;
			$token_values['updated_at'] = $mailbox->updated_at;
			$token_values['username'] = $mailbox->username;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($mailbox, $token_values);

			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=mailbox&id=%d-%s",$mailbox->id, DevblocksPlatform::strToPermalink($mailbox->name)), true);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'checked_at' => DAO_Mailbox::CHECKED_AT,
			'connected_account_id' => DAO_Mailbox::CONNECTED_ACCOUNT_ID,
			'host' => DAO_Mailbox::HOST,
			'id' => DAO_Mailbox::ID,
			'is_enabled' => DAO_Mailbox::ENABLED,
			'links' => '_links',
			'max_msg_size_kb' => DAO_Mailbox::MAX_MSG_SIZE_KB,
			'name' => DAO_Mailbox::NAME,
			'num_fails' => DAO_Mailbox::NUM_FAILS,
			'password' => DAO_Mailbox::PASSWORD,
			'port' => DAO_Mailbox::PORT,
			'protocol' => DAO_Mailbox::PROTOCOL,
			'timeout_secs' => DAO_Mailbox::TIMEOUT_SECS,
			'updated_at' => DAO_Mailbox::UPDATED_AT,
			'username' => DAO_Mailbox::USERNAME,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['checked_at']['notes'] = "The date/time this mailbox was last checked for new messages";
		$keys['connected_account_id']['notes'] = "The optional connected account to use for XOAUTH2";
		$keys['host']['notes'] = "The mail server hostname";
		$keys['is_enabled']['notes'] = "Is this mailbox enabled? `1` for true and `0` for false";
		$keys['max_msg_size_kb']['notes'] = "The maximum message size to download (in kilobytes); `0` to disable limits";
		$keys['num_fails']['notes'] = "The number of consecutive failures";
		$keys['password']['notes'] = "The mailbox password";
		$keys['port']['notes'] = "The port to connect to; e.g. `587`";
		$keys['protocol']['notes'] = "The protocol to use: `pop3`, `pop3-ssl`, `imap`, `imap-ssl`";
		$keys['timeout_secs']['notes'] = "The socket timeout in seconds when downloading mail";
		$keys['username']['notes'] = "The mailbox username";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = CerberusContexts::CONTEXT_MAILBOX;
		$context_id = $dictionary['id'];

		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}

		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translateCapitalized('common.mailboxes');
		/*
		$view->addParams(array(
			SearchFields_Mailbox::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Mailbox::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Mailbox::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';

		return $view;
	}

	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translateCapitalized('common.mailboxes');

		$params_req = [];

		if($context && $context_id) {
			$params_req = [
				new DevblocksSearchCriteria(SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			];
		}

		$view->addParamsRequired($params_req, true);

		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_MAILBOX;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if(!empty($context_id)) {
			if(false == ($model = DAO_Mailbox::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(isset($model))
				$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/mailbox/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
