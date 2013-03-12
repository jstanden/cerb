<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_ContactPerson extends C4_ORMHelper {
	const CACHE_SERVER_ROSTER = 'ch_server_roster';

	const ID = 'id';
	const EMAIL_ID = 'email_id';
	const CREATED = 'created';
	const LAST_LOGIN = 'last_login';
	const AUTH_SALT = 'auth_salt';
	const AUTH_PASSWORD = 'auth_password';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		$sql = "INSERT INTO contact_person () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, 'contact_person', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.contact_person.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CONTACT_PERSON, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('contact_person', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContactPerson[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, email_id, created, last_login, auth_salt, auth_password ".
			"FROM contact_person ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	static function getServerRosters() {
		$cache = DevblocksPlatform::getCacheService();
		
		if (null === ($objects = $cache->load(self::CACHE_SERVER_ROSTER))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf('SELECT st.server_id, st.contact_id '.
				'FROM server_to_contact st '.
				'INNER JOIN contact_person c ON (st.contact_id=c.id) '.
				'INNER JOIN server s ON (st.server_id=s.id) '.
				'INNER JOIN address a ON (c.email_id=a.id) '.
				'ORDER BY a.last_name ASC, a.first_name ASC, a.email ASC '
			);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			
			$objects = array();
			
			while ($row = mysql_fetch_assoc($rs)) {
				$server_id = intval($row['server_id']);
				$contact_id = intval($row['contact_id']);
				
				if (!isset($objects[$server_id]))
					$objects[$server_id] = array();
				
				$owner = new Model_ServerOwner();
				$owner->id = $contact_id;
				$owner->server_id = $server_id;
				$objects[$server_id][$contact_id] = $owner;
			}
			
			mysql_free_result($rs);
			
			$cache->save($objects, self::CACHE_SERVER_ROSTER);
		}
		
		return $objects;
	}
	
	static function getOwnedServers($contact_id) {
		$rosters = self::getServerRosters();
		
		$servers = array();
		if (is_array($rosters))
		foreach ($rosters as $server_id => $owners) {
			if (isset($owners[$contact_id])) {
				$servers[$server_id] = $owners[$contact_id];
			}
		}
		
		return $servers;
	}

	/**
	 * @param integer $id
	 * @return Model_ContactPerson
	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContactPerson[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContactPerson();
			$object->id = intval($row['id']);
			$object->email_id = intval($row['email_id']);
			$object->created = intval($row['created']);
			$object->last_login = intval($row['last_login']);
			$object->auth_salt = $row['auth_salt'];
			$object->auth_password = $row['auth_password'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function setServerOwner($id, $server_id) {
		if (empty($id) || empty($server_id))
			return FALSE;
		
		$db = DevblocksPlatform::getDatabaseService();
		$cache = DevblocksPlatform::getCacheService();
		
		$db->Execute(sprintf("REPLACE INTO server_to_contact (server_id, contact_id)".
			"VALUES (%u, %u)", 
			$server_id,
			$id
		));
		
		$cache->remove(self::CACHE_SERVER_ROSTER);
	}
	
	static function unsetServerOwner($id, $server_id) {
		if (empty($id) || empty($server_id))
			return FALSE;
		
		$db = DevblocksPlatform::getDatabaseService();
		$cache = DevblocksPlatform::getCacheService();
		
		$db->Execute(sprintf("DELETE QUICK FROM server_to_contact WHERE server_id = %u AND contact_id IN (%u)",
			$server_id,
			$id
		));
		
		$cache->remove(self::CACHE_SERVER_ROSTER);
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		foreach($ids as $id) {
			if(null == ($person = DAO_ContactPerson::get($id)))
				continue;
				
			$addresses = $person->getAddresses();
			$address_ids = array_keys($addresses);
			
			// Remove shares
			// [TODO] A listener should really be handling this
			if(is_array($address_ids) && !empty($address_ids)) {
				$address_ids_str = implode(',', $address_ids);
				$db->Execute(sprintf("DELETE FROM supportcenter_address_share WHERE share_address_id IN (%s) OR with_address_id IN (%s)", $address_ids_str, $address_ids_str));
			}
			
			// Release OpenIDs
			if(class_exists('DAO_OpenIdToContactPerson', true))
				DAO_OpenIdToContactPerson::deleteByContactPerson($id);
		}
		
		// Release verified email addresses
		$db->Execute(sprintf("UPDATE address SET contact_person_id = 0 WHERE contact_person_id IN (%s)", $ids_list));
		
		// Remove Server owns
		$db->Execute(sprintf("DELETE FROM server_to_contact WHERE contact_id IN (%s)", $ids_list));
		
		// Remove records
		$db->Execute(sprintf("DELETE FROM contact_person WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CONTACT_PERSON,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('contact_person');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContactPerson::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"contact_person.id as %s, ".
			"contact_person.email_id as %s, ".
			"contact_person.created as %s, ".
			"contact_person.last_login as %s, ".
			"contact_person.auth_salt as %s, ".
			"contact_person.auth_password as %s, ".
			"address.first_name as %s, ".
			"address.last_name as %s, ".
			"address.email as %s ",
				SearchFields_ContactPerson::ID,
				SearchFields_ContactPerson::EMAIL_ID,
				SearchFields_ContactPerson::CREATED,
				SearchFields_ContactPerson::LAST_LOGIN,
				SearchFields_ContactPerson::AUTH_SALT,
				SearchFields_ContactPerson::AUTH_PASSWORD,
				SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
				SearchFields_ContactPerson::ADDRESS_LAST_NAME,
				SearchFields_ContactPerson::ADDRESS_EMAIL
			);
			
		$join_sql = "FROM contact_person ".
			"INNER JOIN address ON (contact_person.email_id=address.id) ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.contact_person' AND context_link.to_context_id = contact_person.id) " : " ")
			;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'contact_person.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_ContactPerson', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'contact_person',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		$from_context = CerberusContexts::CONTEXT_CONTACT_PERSON;
		$from_index = 'contact_person.id';
		
		switch($param_key) {
			case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			
			case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
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
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY contact_person.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_ContactPerson::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT contact_person.id) " : "SELECT COUNT(contact_person.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "UPDATE address ".
			"LEFT JOIN contact_person ON (address.contact_person_id=contact_person.id) ".
			"SET address.contact_person_id = 0 ".
			"WHERE address.contact_person_id != 0 ".
			"AND contact_person.id IS NULL"
		;
		$db->Execute($sql);

		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_CONTACT_PERSON,
					'context_table' => 'contact_person',
					'context_key' => 'id',
				)
			)
		);
	}

};

class SearchFields_ContactPerson implements IDevblocksSearchFields {
	const ID = 'c_id';
	const EMAIL_ID = 'c_email_id';
	const CREATED = 'c_created';
	const LAST_LOGIN = 'c_last_login';
	const AUTH_SALT = 'c_auth_salt';
	const AUTH_PASSWORD = 'c_auth_password';
	
	const ADDRESS_EMAIL = 'a_email';
	const ADDRESS_FIRST_NAME = 'a_first_name';
	const ADDRESS_LAST_NAME = 'a_last_name';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'contact_person', 'id', $translate->_('common.id')),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'contact_person', 'email_id'),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'contact_person', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::LAST_LOGIN => new DevblocksSearchField(self::LAST_LOGIN, 'contact_person', 'last_login', $translate->_('dao.contact_person.last_login'), Model_CustomField::TYPE_DATE),
			self::AUTH_SALT => new DevblocksSearchField(self::AUTH_SALT, 'contact_person', 'auth_salt', $translate->_('dao.contact_person.auth_salt')),
			self::AUTH_PASSWORD => new DevblocksSearchField(self::AUTH_PASSWORD, 'contact_person', 'auth_password', $translate->_('dao.contact_person.auth_password')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'address', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE),
			self::ADDRESS_FIRST_NAME => new DevblocksSearchField(self::ADDRESS_FIRST_NAME, 'address', 'first_name', $translate->_('address.first_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::ADDRESS_LAST_NAME => new DevblocksSearchField(self::ADDRESS_LAST_NAME, 'address', 'last_name', $translate->_('address.last_name'), Model_CustomField::TYPE_SINGLE_LINE),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContactPerson {
	public $id;
	public $email_id;
	public $created;
	public $last_login;
	public $auth_salt;
	public $auth_password;
	
	private $_addresses = array();
	
	public function getAddresses() {
		if(empty($this->_addresses) && !empty($this->id)) {
			$this->_addresses = DAO_Address::getWhere(sprintf("%s = %d",
				DAO_Address::CONTACT_PERSON_ID,
				$this->id
			));
		}
		
		return $this->_addresses;
	}

	/**
	 * @return Model_Address
	 */
	public function getPrimaryAddress() {
		$addresses = $this->getAddresses();
		if(isset($addresses[$this->email_id]))
			return $addresses[$this->email_id];
			
		return NULL;
	}
};

class View_ContactPerson extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'contactperson';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.people');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ContactPerson::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
			SearchFields_ContactPerson::ADDRESS_LAST_NAME,
			SearchFields_ContactPerson::ADDRESS_EMAIL,
			SearchFields_ContactPerson::CREATED,
			SearchFields_ContactPerson::LAST_LOGIN,
		);
		
		// Filter fields
		$this->addColumnsHidden(array(
			SearchFields_ContactPerson::EMAIL_ID,
			SearchFields_ContactPerson::AUTH_PASSWORD,
			SearchFields_ContactPerson::AUTH_SALT,
			SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK,
			SearchFields_ContactPerson::VIRTUAL_WATCHERS,
		));
		
		// Filter fields
		$this->addParamsHidden(array(
			SearchFields_ContactPerson::EMAIL_ID,
			SearchFields_ContactPerson::AUTH_PASSWORD,
			SearchFields_ContactPerson::AUTH_SALT,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContactPerson::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ContactPerson', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContactPerson', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ContactPerson::ADDRESS_EMAIL:
				case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
				case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
					$pass = true;
					break;
					
				case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ContactPerson::ADDRESS_EMAIL:
			case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
			case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_ContactPerson', $column);
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_ContactPerson', CerberusContexts::CONTEXT_CONTACT_PERSON, $column);
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_ContactPerson', $column);
				break;

			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_ContactPerson', $column, 'c.id');
				}
				
				break;
		}
		
		return $counts;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::contacts/people/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContactPerson::ADDRESS_EMAIL:
			case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
			case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
			case SearchFields_ContactPerson::AUTH_SALT:
			case SearchFields_ContactPerson::AUTH_PASSWORD:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_ContactPerson::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_ContactPerson::CREATED:
			case SearchFields_ContactPerson::LAST_LOGIN:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;

			case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContactPerson::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContactPerson::ADDRESS_EMAIL:
			case SearchFields_ContactPerson::ADDRESS_FIRST_NAME:
			case SearchFields_ContactPerson::ADDRESS_LAST_NAME:
			case SearchFields_ContactPerson::AUTH_SALT:
			case SearchFields_ContactPerson::AUTH_PASSWORD:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContactPerson::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContactPerson::CREATED:
			case SearchFields_ContactPerson::LAST_LOGIN:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ContactPerson::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'delete':
					//$change_fields[DAO_ContactPerson::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContactPerson::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContactPerson::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(isset($do['delete'])) {
				// Re-check ACL
				if($active_worker->hasPriv('core.addybook.person.actions.delete'))
					DAO_ContactPerson::delete($batch_ids);
				
			} else {
				DAO_ContactPerson::update($batch_ids, $change_fields);
				
				// Custom Fields
				//self::_doBulkSetCustomFields(ChCustomFieldSource_ContactPerson::ID, $custom_fields, $batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_ContactPerson extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	static function searchInboundLinks($from_context, $from_context_id) {
		list($results, $null) = DAO_ContactPerson::search(
			array(
				SearchFields_ContactPerson::ID,
			),
			array(
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK,'=',$from_context),
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK_ID,'=',$from_context_id),
			),
			-1,
			0,
			SearchFields_ContactPerson::LAST_LOGIN,
			false,
			false
		);
		
		return $results;
	}
	
	function getRandom() {
		return DAO_ContactPerson::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=contact_person&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$contact = DAO_ContactPerson::get($context_id);

		$address = $contact->getPrimaryAddress();

		$name = $address->getName();
		
		if(!empty($name))
			$name .= ' <' . $address->email . '>';
		else
			$name = $address->email;
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($address->email);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $contact->id,
			'name' => $name,
			'permalink' => $url,
		);
	}
	
	function getContext($person, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Person:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON);
		
		// Polymorph
		if(is_numeric($person)) {
			$person = DAO_ContactPerson::get($person);
		} elseif($person instanceof Model_ContactPerson) {
			// It's what we want already.
		} else {
			$person = null;
		}
			
		// Token labels
		$token_labels = array(
			'created' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'last_login' => $prefix.$translate->_('dao.contact_person.last_login'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CONTACT_PERSON;
		
		// Address token values
		if(null != $person) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = '(contact person)';
			$token_values['id'] = $person->id;
			if(!empty($person->created))
				$token_values['created'] = $person->created;
			if(!empty($person->last_login))
				$token_values['last_login'] = $person->last_login;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=contact_person&id=%d",$person->id), true);
			
			// Primary Email
			$email_id = (null != $person && !empty($person->email_id)) ? $person->email_id : null;
			$token_values['email_id'] = $email_id;
		}
		
		// Primary Email
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'email_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CONTACT_PERSON;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$translate = DevblocksPlatform::getTranslationService();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = $translate->_('addy_book.tab.people');
		
		$view->view_columns = array(
			SearchFields_ContactPerson::ADDRESS_FIRST_NAME,
			SearchFields_ContactPerson::ADDRESS_LAST_NAME,
			SearchFields_ContactPerson::ADDRESS_EMAIL,
		);
		
		$view->renderSortBy = SearchFields_ContactPerson::LAST_LOGIN;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = $translate->_('addy_book.tab.people');
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_ContactPerson::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$contact = DAO_ContactPerson::get($context_id);
		$tpl->assign('contact', $contact);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CONTACT_PERSON, $context_id);
		if(isset($custom_field_values[$context_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_CONTACT_PERSON, $context_id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		// View
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/people/peek.tpl');
	}
};

class Model_ServerOwner {
	public $id;
	public $server_id;
}