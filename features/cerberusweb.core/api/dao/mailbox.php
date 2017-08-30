<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
	const AUTH_DISABLE_PLAIN = 'auth_disable_plain';
	const CHECKED_AT = 'checked_at';
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
	const SSL_IGNORE_VALIDATION = 'ssl_ignore_validation';
	const TIMEOUT_SECS = 'timeout_secs';
	const UPDATED_AT = 'updated_at';
	const USERNAME = 'username';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		// tinyint(3) unsigned
		$validation
			->addField(self::AUTH_DISABLE_PLAIN)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::CHECKED_AT)
			->timestamp()
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
		// tinyint(3) unsigned
		$validation
			->addField(self::SSL_IGNORE_VALIDATION)
			->bit()
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
			;

		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();

		$sql = "INSERT INTO mailbox () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

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
		$sql = "SELECT id, enabled, name, protocol, host, username, password, port, num_fails, delay_until, timeout_secs, max_msg_size_kb, ssl_ignore_validation, auth_disable_plain, updated_at, checked_at ".
			"FROM mailbox ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
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
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::services()->database();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}

	/**
	 * @param resource $rs
	 * @return Model_Mailbox[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

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
			$object->ssl_ignore_validation = $row['ssl_ignore_validation'] ? 1 : 0;
			$object->auth_disable_plain = $row['auth_disable_plain'] ? 1 : 0;
			$object->updated_at = intval($row['updated_at']);
			$object->checked_at = intval($row['checked_at']);
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

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Mailbox', $sortBy);

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
			"mailbox.ssl_ignore_validation as %s, ".
			"mailbox.auth_disable_plain as %s, ".
			"mailbox.updated_at as %s, ".
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
				SearchFields_Mailbox::SSL_IGNORE_VALIDATION,
				SearchFields_Mailbox::AUTH_DISABLE_PLAIN,
				SearchFields_Mailbox::UPDATED_AT,
				SearchFields_Mailbox::CHECKED_AT
			);

		$join_sql = "FROM mailbox ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Mailbox');

		// Virtuals

		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);

		array_walk_recursive(
			$params,
			array('DAO_Mailbox', '_translateVirtualParameters'),
			$args
		);

		return array(
			'primary_table' => 'mailbox',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;

		$from_context = CerberusContexts::CONTEXT_MAILBOX;
		$from_index = 'mailbox.id';

		$param_key = $param->field;
		settype($param_key, 'string');

		switch($param_key) {
			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];

		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;

		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}

		$results = array();

		if(!($rs instanceof mysqli_result))
			return false;

		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Mailbox::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);

		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(mailbox.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}

		mysqli_free_result($rs);

		return array($results,$total);
	}

};

class Model_Mailbox {
	public $id;
	public $enabled=1;
	public $name;
	public $protocol='pop3';
	public $host;
	public $username;
	public $password;
	public $port=110;
	public $num_fails = 0;
	public $delay_until = 0;
	public $timeout_secs = 30;
	public $max_msg_size_kb = 25600;
	public $ssl_ignore_validation = 0;
	public $auth_disable_plain = 0;
	public $updated_at = 0;
	public $checked_at = 0;

	function getImapConnectString() {
		$connect = null;

		switch($this->protocol) {
			default:
			case 'pop3': // 110
				$connect = sprintf("{%s:%d/pop3/notls}INBOX",
					$this->host,
					$this->port
				);
				break;

			case 'pop3-ssl': // 995
				$connect = sprintf("{%s:%d/pop3/ssl%s}INBOX",
					$this->host,
					$this->port,
					$this->ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;

			case 'imap': // 143
				$connect = sprintf("{%s:%d/notls}INBOX",
					$this->host,
					$this->port
				);
				break;

			case 'imap-ssl': // 993
				$connect = sprintf("{%s:%d/imap/ssl%s}INBOX",
					$this->host,
					$this->port,
					$this->ssl_ignore_validation ? '/novalidate-cert' : ''
				);
				break;
		}

		return $connect;
	}
};

class SearchFields_Mailbox extends DevblocksSearchFields {
	const ID = 'p_id';
	const ENABLED = 'p_enabled';
	const NAME = 'p_name';
	const PROTOCOL = 'p_protocol';
	const HOST = 'p_host';
	const USERNAME = 'p_username';
	const PASSWORD = 'p_password';
	const PORT = 'p_port';
	const NUM_FAILS = 'p_num_fails';
	const DELAY_UNTIL = 'p_delay_until';
	const TIMEOUT_SECS = 'p_timeout_secs';
	const MAX_MSG_SIZE_KB = 'p_max_msg_size_kb';
	const SSL_IGNORE_VALIDATION = 'p_ssl_ignore_validation';
	const AUTH_DISABLE_PLAIN = 'p_auth_disable_plain';
	const UPDATED_AT = 'p_updated_at';
	const CHECKED_AT = 'p_checked_at';

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
				break;

			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_MAILBOX, self::getPrimaryKey());
				break;

			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
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
			self::ID => new DevblocksSearchField(self::ID, 'mailbox', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::ENABLED => new DevblocksSearchField(self::ENABLED, 'mailbox', 'enabled', $translate->_('common.enabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'mailbox', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PROTOCOL => new DevblocksSearchField(self::PROTOCOL, 'mailbox', 'protocol', $translate->_('dao.mailbox.protocol'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::HOST => new DevblocksSearchField(self::HOST, 'mailbox', 'host', $translate->_('common.host'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::USERNAME => new DevblocksSearchField(self::USERNAME, 'mailbox', 'username', $translate->_('common.user'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PASSWORD => new DevblocksSearchField(self::PASSWORD, 'mailbox', 'password', $translate->_('common.password'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PORT => new DevblocksSearchField(self::PORT, 'mailbox', 'port', $translate->_('dao.mailbox.port'), Model_CustomField::TYPE_NUMBER, true),
			self::NUM_FAILS => new DevblocksSearchField(self::NUM_FAILS, 'mailbox', 'num_fails', $translate->_('dao.mailbox.num_fails'), Model_CustomField::TYPE_NUMBER, true),
			self::DELAY_UNTIL => new DevblocksSearchField(self::DELAY_UNTIL, 'mailbox', 'delay_until', $translate->_('dao.mailbox.delay_until'), Model_CustomField::TYPE_DATE, true),
			self::TIMEOUT_SECS => new DevblocksSearchField(self::TIMEOUT_SECS, 'mailbox', 'timeout_secs', $translate->_('dao.mailbox.timeout_secs'), Model_CustomField::TYPE_NUMBER, true),
			self::MAX_MSG_SIZE_KB => new DevblocksSearchField(self::MAX_MSG_SIZE_KB, 'mailbox', 'max_msg_size_kb', $translate->_('dao.mailbox.max_msg_size_kb'), Model_CustomField::TYPE_NUMBER, true),
			self::SSL_IGNORE_VALIDATION => new DevblocksSearchField(self::SSL_IGNORE_VALIDATION, 'mailbox', 'ssl_ignore_validation', $translate->_('dao.mailbox.ssl_ignore_validation'), Model_CustomField::TYPE_CHECKBOX, true),
			self::AUTH_DISABLE_PLAIN => new DevblocksSearchField(self::AUTH_DISABLE_PLAIN, 'mailbox', 'auth_disable_plain', $translate->_('dao.mailbox.auth_disable_plain'), Model_CustomField::TYPE_CHECKBOX, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'mailbox', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::CHECKED_AT => new DevblocksSearchField(self::CHECKED_AT, 'mailbox', 'checked_at', $translate->_('dao.mailbox.checked_at'), Model_CustomField::TYPE_DATE, true),

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
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.mailbox'), MB_CASE_TITLE);
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
		);

		$this->addColumnsHidden(array(
			SearchFields_Mailbox::PASSWORD,
			SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK,
			SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET,
			SearchFields_Mailbox::VIRTUAL_WATCHERS,
		));

		$this->addParamsHidden(array(
			SearchFields_Mailbox::PASSWORD,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Mailbox::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);

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

		$fields = array();

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
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}

			if($pass)
				$fields[$field_key] = $field_model;
		}

		return $fields;
	}

	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_MAILBOX;

		if(!isset($fields[$column]))
			return array();

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
				if('cf_' == substr($column,0,3)) {
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
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Mailbox::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Mailbox::VIRTUAL_WATCHERS),
				),
		);

		// Add quick search links

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');

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
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);

				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}

		return false;
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

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Mailbox::NAME:
			case SearchFields_Mailbox::PROTOCOL:
			case SearchFields_Mailbox::HOST:
			case SearchFields_Mailbox::USERNAME:
			case SearchFields_Mailbox::PASSWORD:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Mailbox::ID:
			case SearchFields_Mailbox::PORT:
			case SearchFields_Mailbox::NUM_FAILS:
			case SearchFields_Mailbox::TIMEOUT_SECS:
			case SearchFields_Mailbox::MAX_MSG_SIZE_KB:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case SearchFields_Mailbox::ENABLED:
			case SearchFields_Mailbox::SSL_IGNORE_VALIDATION:
			case SearchFields_Mailbox::AUTH_DISABLE_PLAIN:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;

			case SearchFields_Mailbox::CHECKED_AT:
			case SearchFields_Mailbox::DELAY_UNTIL:
			case SearchFields_Mailbox::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;

			case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;

			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_MAILBOX);
				break;

			case SearchFields_Mailbox::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;

			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Mailbox::ENABLED:
			case SearchFields_Mailbox::SSL_IGNORE_VALIDATION:
			case SearchFields_Mailbox::AUTH_DISABLE_PLAIN:
				parent::_renderCriteriaParamBoolean($param);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;

		$translate = DevblocksPlatform::getTranslationService();

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
			case SearchFields_Mailbox::SSL_IGNORE_VALIDATION:
			case SearchFields_Mailbox::AUTH_DISABLE_PLAIN:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_Mailbox::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;

			case SearchFields_Mailbox::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;

			case SearchFields_Mailbox::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
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
	static function isReadableByActor($models, $actor) {
		// Only admins can read
		return self::isWriteableByActor($models, $actor);
	}

	static function isWriteableByActor($models, $actor) {
		// Only admins can modify

		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);

		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);

		return CerberusContexts::denyEverything($models);
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

	function getMeta($context_id) {
		$mailbox = DAO_Mailbox::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();

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
			'checked_at',
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
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'checked_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);

		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		// Token values
		$token_values = array();

		$token_values['_context'] = CerberusContexts::CONTEXT_MAILBOX;
		$token_values['_types'] = $token_types;

		if($mailbox) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $mailbox->name;
			$token_values['checked_at'] = $mailbox->checked_at;
			$token_values['id'] = $mailbox->id;
			$token_values['name'] = $mailbox->name;
			$token_values['updated_at'] = $mailbox->updated_at;

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
			'id' => DAO_Mailbox::ID,
			'name' => DAO_Mailbox::NAME,
			'updated_at' => DAO_Mailbox::UPDATED_AT,
		];
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = CerberusContexts::CONTEXT_MAILBOX;
		$context_id = $dictionary['id'];

		@$is_loaded = $dictionary['_loaded'];
		$values = array();

		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;

			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;

			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}

		return $values;
	}

	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Mailbox';
		/*
		$view->addParams(array(
			SearchFields_Mailbox::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Mailbox::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Mailbox::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';

		return $view;
	}

	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Mailbox';

		$params_req = array();

		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Mailbox::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Mailbox::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		$view->addParamsRequired($params_req, true);

		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker->is_superuser)
			return;

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($context_id) && null != ($mailbox = DAO_Mailbox::get($context_id))) {
			$tpl->assign('model', $mailbox);
		}

		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MAILBOX, false);
		$tpl->assign('custom_fields', $custom_fields);

		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MAILBOX, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MAILBOX, $context_id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);

		$tpl->display('devblocks:cerberusweb.core::internal/mailbox/peek.tpl');
	}

};
