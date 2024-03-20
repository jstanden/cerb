<?php
class DAO_Contact extends Cerb_ORMHelper {
	const AUTH_PASSWORD = 'auth_password';
	const AUTH_SALT = 'auth_salt';
	const CREATED_AT = 'created_at';
	const DOB = 'dob';
	const FIRST_NAME = 'first_name';
	const GENDER = 'gender';
	const ID = 'id';
	const LANGUAGE = 'language';
	const LAST_LOGIN_AT = 'last_login_at';
	const LAST_NAME = 'last_name';
	const LOCATION = 'location';
	const MOBILE = 'mobile';
	const ORG_ID = 'org_id';
	const PHONE = 'phone';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const TIMEZONE = 'timezone';
	const TITLE = 'title';
	const UPDATED_AT = 'updated_at';
	const USERNAME = 'username';
	
	const _IMAGE = '_image';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::AUTH_PASSWORD)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::AUTH_SALT)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::DOB)
			->string()
			->addValidator(function($value, &$error) {
				if($value && false == (strtotime($value . ' 00:00 GMT'))) {
					$error = sprintf("(%s) is not formatted properly (YYYY-MM-DD).",
						$value
					);
					return false;
				}
				
				return true;
			});
			;
		$validation
			->addField(self::FIRST_NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		$validation
			->addField(self::GENDER)
			->string()
			->setMaxLength(1)
			->setPossibleValues(['','F','M'])
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::LANGUAGE)
			->string()
			->setMaxLength(16)
			->addValidator($validation->validators()->language())
			;
		$validation
			->addField(self::LAST_LOGIN_AT)
			->timestamp()
			;
		$validation
			->addField(self::LAST_NAME)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::LOCATION)
			->string()
			;
		$validation
			->addField(self::MOBILE)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::ORG_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ORG, true))
			;
		$validation
			->addField(self::PHONE)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::PRIMARY_EMAIL_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS, true))
			->setUnique(get_class())
			->setNotEmpty(false)
			;
		$validation
			->addField(self::TIMEZONE)
			->string()
			->setMaxLength(128)
			->addValidator($validation->validators()->timezone())
			;
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::USERNAME)
			->string()
			->setMaxLength(64)
			;
		// base64 blob png
		$validation
			->addField(self::_IMAGE)
			->image('image/png', 50, 50, 500, 500, 1000000)
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
		
		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();
		
		$sql = "INSERT INTO contact () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CONTACT, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CONTACT;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CONTACT, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'contact', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.contact.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CONTACT, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('contact', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CONTACT;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	static function onUpdateByActor($actor, $fields, $id) {
		@$email_id = $fields[self::PRIMARY_EMAIL_ID];
		
		// If we're setting a primary email address on the contact, set the link in reverse on the address
		if($email_id) {
			DAO_Address::update($email_id, [
				DAO_Address::CONTACT_ID => $id,
			]);
		}
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];
		$deleted = false;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'delete':
					$deleted = true;
					break;
				
				case 'title':
					$change_fields[DAO_Contact::TITLE] = $v;
					break;
					
				case 'location':
					$change_fields[DAO_Contact::LOCATION] = $v;
					break;
					
				case 'language':
					$change_fields[DAO_Contact::LANGUAGE] = $v;
					break;
					
				case 'timezone':
					$change_fields[DAO_Contact::TIMEZONE] = $v;
					break;
					
				case 'gender':
					if(in_array($v, array('M','F','')))
						$change_fields[DAO_Contact::GENDER] = $v;
					break;
					
				case 'org_id':
					$change_fields[DAO_Contact::ORG_ID] = intval($v);
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		if($deleted) {
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CONTACT, $ids);
			
			DAO_Contact::delete($ids);
			
		} else {
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CONTACT, $ids);
			
			// Fields
			if(!empty($change_fields))
				DAO_Contact::update($ids, $change_fields, false);
	
			// Custom Fields
			if(!empty($custom_fields))
				C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_CONTACT, $custom_fields, $ids);
			
			// Watchers
			if(isset($do['watchers']))
				C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_CONTACT, $do['watchers'], $ids);
			
			// Broadcast
			if(isset($do['broadcast']))
				C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_CONTACT, $do['broadcast'], $ids);
			
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CONTACT, $ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	static function maint() {
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Contact[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, primary_email_id, first_name, last_name, title, org_id, username, gender, dob, location, phone, mobile, auth_salt, auth_password, created_at, updated_at, last_login_at, language, timezone ".
			"FROM contact ".
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
	 * @param integer $id
	 * @return Model_Contact
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
	 * @return Model_Contact[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Contact[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Contact();
			$object->id = $row['id'];
			$object->primary_email_id = intval($row['primary_email_id']);
			$object->first_name = $row['first_name'];
			$object->last_name = $row['last_name'];
			$object->title = $row['title'];
			$object->org_id = intval($row['org_id']);
			$object->username = $row['username'];
			$object->gender = $row['gender'];
			$object->dob = $row['dob'];
			$object->location = $row['location'];
			$object->phone = $row['phone'];
			$object->mobile = $row['mobile'];
			$object->auth_salt = $row['auth_salt'];
			$object->auth_password = $row['auth_password'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			$object->last_login_at = intval($row['last_login_at']);
			$object->language = $row['language'];
			$object->timezone = $row['timezone'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function countByOrgId($org_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM contact WHERE org_id = %d",
			$org_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function random() {
		return self::_getRandom('contact');
	}
	
	static function mergeIds($from_ids, $to_id) {
		$db = DevblocksPlatform::services()->database();

		$context = CerberusContexts::CONTEXT_CONTACT;
		
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		self::_mergeIds($context, $from_ids, $to_id);
		
		// Merge email addresses
		$db->ExecuteMaster(sprintf("UPDATE address SET contact_id = %d WHERE contact_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		return true;
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_CONTACT;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		// Clear address foreign keys to these contacts
		$db->ExecuteMaster(sprintf("UPDATE address SET contact_id = 0 WHERE contact_id IN (%s)", $ids_list));
		
		$db->ExecuteMaster(sprintf("DELETE FROM contact WHERE id IN (%s)", $ids_list));
		
		// Clear search records
		$search = Extension_DevblocksSearchSchema::get(Search_Contact::ID);
		$search->delete($ids);
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Contact::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Contact', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"contact.id as %s, ".
			"contact.primary_email_id as %s, ".
			"contact.first_name as %s, ".
			"contact.last_name as %s, ".
			"contact.title as %s, ".
			"contact.org_id as %s, ".
			"contact.username as %s, ".
			"contact.gender as %s, ".
			"contact.dob as %s, ".
			"contact.location as %s, ".
			"contact.phone as %s, ".
			"contact.mobile as %s, ".
			"contact.auth_salt as %s, ".
			"contact.auth_password as %s, ".
			"contact.created_at as %s, ".
			"contact.updated_at as %s, ".
			"contact.language as %s, ".
			"contact.timezone as %s, ".
			"contact.last_login_at as %s ",
				SearchFields_Contact::ID,
				SearchFields_Contact::PRIMARY_EMAIL_ID,
				SearchFields_Contact::FIRST_NAME,
				SearchFields_Contact::LAST_NAME,
				SearchFields_Contact::TITLE,
				SearchFields_Contact::ORG_ID,
				SearchFields_Contact::USERNAME,
				SearchFields_Contact::GENDER,
				SearchFields_Contact::DOB,
				SearchFields_Contact::LOCATION,
				SearchFields_Contact::PHONE,
				SearchFields_Contact::MOBILE,
				SearchFields_Contact::AUTH_SALT,
				SearchFields_Contact::AUTH_PASSWORD,
				SearchFields_Contact::CREATED_AT,
				SearchFields_Contact::UPDATED_AT,
				SearchFields_Contact::LANGUAGE,
				SearchFields_Contact::TIMEZONE,
				SearchFields_Contact::LAST_LOGIN_AT
			);
			
		$join_sql = "FROM contact ".
			(isset($tables['address']) ? "INNER JOIN address ON (address.id=contact.primary_email_id) " : '').
			(isset($tables['contact_org']) ? sprintf("INNER JOIN contact_org ON (contact_org.id = contact.org_id) ") : " ").
			'';
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Contact');
	
		return array(
			'primary_table' => 'contact',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];
		
		if(DevblocksPlatform::strStartsWith($term, '@')) {
			$sql = sprintf("SELECT id ".
				"FROM contact ".
				"WHERE primary_email_id IN (".
					"SELECT id FROM address WHERE host LIKE %s ORDER BY num_nonspam DESC ".
				") ".
				"LIMIT %d",
				$db->qstr(substr($term, 1) . '%'),
				25
			);
			$results = $db->GetArrayReader($sql);
			
		} else {
			$sql = sprintf("SELECT contact.id, address.num_nonspam ".
				"FROM contact ".
				"INNER JOIN address ON (contact.primary_email_id=address.id) ".
				"WHERE (".
					"first_name LIKE %s ".
					"OR last_name LIKE %s ".
					"%s".
				") ".
				"ORDER BY num_nonspam DESC ".
				"LIMIT %d",
				$db->qstr($term.'%'),
				$db->qstr($term.'%'),
				(false != strpos($term,' ')
					? sprintf("OR concat(first_name,' ',last_name) LIKE %s ", $db->qstr($term.'%'))
					: ''),
				25
			);
			$results = $db->GetArrayReader($sql);
		}
		
		if(is_array($results))
			$ids = array_column($results, 'id');
		
		switch($as) {
			case 'ids':
				return $ids;
				break;
				
			default:
				return DAO_Contact::getIds($ids);
				break;
		}
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
			SearchFields_Contact::ID,
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

class SearchFields_Contact extends DevblocksSearchFields {
	const ID = 'c_id';
	const PRIMARY_EMAIL_ID = 'c_primary_email_id';
	const FIRST_NAME = 'c_first_name';
	const LAST_NAME = 'c_last_name';
	const TITLE = 'c_title';
	const ORG_ID = 'c_org_id';
	const USERNAME = 'c_username';
	const GENDER = 'c_gender';
	const DOB = 'c_dob';
	const LOCATION = 'c_location';
	const PHONE = 'c_phone';
	const MOBILE = 'c_mobile';
	const AUTH_SALT = 'c_auth_salt';
	const AUTH_PASSWORD = 'c_auth_password';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';
	const LAST_LOGIN_AT = 'c_last_login_at';
	const LANGUAGE = 'c_language';
	const TIMEZONE = 'c_timezone';
	
	const PRIMARY_EMAIL_ADDRESS = 'a_email_address';
	
	const ORG_NAME = 'o_name';
	
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	const FULLTEXT_CONTACT = 'ft_contact';

	const VIRTUAL_ALIAS = '*_alias';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_EMAIL_SEARCH = '*_email_search';
	const VIRTUAL_ORG_SEARCH = '*_org_search';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'contact.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CONTACT => new DevblocksSearchFieldContextKeys('contact.id', self::ID),
			CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('contact.org_id', self::ORG_ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('contact.primary_email_id', self::PRIMARY_EMAIL_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_CONTACT:
				return self::_getWhereSQLFromFulltextField($param, Search_Contact::ID, self::getPrimaryKey());
				
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_CONTACT, self::getPrimaryKey());
				
			case self::VIRTUAL_ALIAS:
				return  self::_getWhereSQLFromAliasesField($param, CerberusContexts::CONTEXT_CONTACT, self::getPrimaryKey());
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CONTACT, self::getPrimaryKey());
				
			case self::VIRTUAL_EMAIL_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'contact.primary_email_id');
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CONTACT), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_ORG_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ORG, 'contact.org_id');
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_CONTACT, self::getPrimaryKey());
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'email':
				$key = 'email.id';
				break;
				
			case 'language':
				$key = 'lang';
				break;
				
			case 'org':
				$key = 'org.id';
				break;
				
			case 'tz':
				$key = 'timezone';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Contact::GENDER:
				$label_map = [
					'' => DevblocksPlatform::translateCapitalized('common.unknown'),
					'F' => DevblocksPlatform::translateCapitalized('common.gender.female'),
					'M' => DevblocksPlatform::translateCapitalized('common.gender.male'),
				];
				return $label_map;
				break;
				
			case SearchFields_Contact::ID:
				$models = DAO_Contact::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_CONTACT);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				break;
				
			case SearchFields_Contact::ORG_ID:
				$records = DAO_ContactOrg::getIds($values);
				return array_column($records, 'name', 'id');
				break;
				
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
				$records = DAO_Address::getIds($values);
				return array_column($records, 'email', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'contact', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'contact', 'primary_email_id', $translate->_('common.email'), null, true),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'contact', 'first_name', $translate->_('common.name.first'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'contact', 'last_name', $translate->_('common.name.last'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'contact', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'contact', 'org_id', $translate->_('common.organization'), null, true),
			self::USERNAME => new DevblocksSearchField(self::USERNAME, 'contact', 'username', $translate->_('common.username'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::GENDER => new DevblocksSearchField(self::GENDER, 'contact', 'gender', $translate->_('common.gender'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::DOB => new DevblocksSearchField(self::DOB, 'contact', 'dob', $translate->_('common.dob.abbr'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LOCATION => new DevblocksSearchField(self::LOCATION, 'contact', 'location', $translate->_('common.location'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'contact', 'phone', $translate->_('common.phone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MOBILE => new DevblocksSearchField(self::MOBILE, 'contact', 'mobile', $translate->_('common.mobile'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::AUTH_SALT => new DevblocksSearchField(self::AUTH_SALT, 'contact', 'auth_salt', null, null, true),
			self::AUTH_PASSWORD => new DevblocksSearchField(self::AUTH_PASSWORD, 'contact', 'auth_password', null, null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'contact', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'contact', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::LAST_LOGIN_AT => new DevblocksSearchField(self::LAST_LOGIN_AT, 'contact', 'last_login_at', $translate->_('common.last_login'), Model_CustomField::TYPE_DATE, true),
			self::LANGUAGE => new DevblocksSearchField(self::LANGUAGE, 'contact', 'language', $translate->_('common.language'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TIMEZONE => new DevblocksSearchField(self::TIMEZONE, 'contact', 'timezone', $translate->_('common.timezone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			self::PRIMARY_EMAIL_ADDRESS => new DevblocksSearchField(self::PRIMARY_EMAIL_ADDRESS, 'address', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, false), // [TODO]
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'contact_org', 'name', $translate->_('common.organization'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
			self::FULLTEXT_CONTACT => new DevblocksSearchField(self::FULLTEXT_CONTACT, 'ft', 'contact', $translate->_('common.search.fulltext'), 'FT', false),
			
			self::VIRTUAL_ALIAS => new DevblocksSearchField(self::VIRTUAL_ALIAS, '*', 'alias', $translate->_('common.aliases'), null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_EMAIL_SEARCH => new DevblocksSearchField(self::VIRTUAL_EMAIL_SEARCH, '*', 'email_search', null, null, false),
			self::VIRTUAL_ORG_SEARCH => new DevblocksSearchField(self::VIRTUAL_ORG_SEARCH, '*', 'org_search', null, null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_CONTACT]->ft_schema = Search_Contact::ID;
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_Contact extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.contact';
	
	public function getNamespace() {
		return 'contact';
	}
	
	public function getAttributes() {
		return array();
	}
	
	public function getIdField() {
		return 'id';
	}
	
	public function getDataField() {
		return 'content';
	}
	
	public function getPrimaryKey() {
		return 'id';
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the index has a delta, start from the current record
		if($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
		// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}
	
	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
				
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	private function _indexDictionary($dict, $engine) {
		$logger = DevblocksPlatform::services()->log();

		$id = $dict->id;
		
		if(empty($id))
			return false;
		
		$doc = array(
			'content' => implode("\n", array(
				$dict->_label,
				$dict->email_address,
				implode("\n", $dict->emails),
				$dict->org_name,
				$dict->title,
				$dict->location,
				$dict->phone,
				$dict->mobile,
			))
		);
		
		$logger->info(sprintf("[Search] Indexing %s %d...",
			$this->getNamespace(),
			$id
		));
		
		if(false === ($engine->index($this, $id, $doc)))
			return false;
		
		return true;
	}
	
	public function indexIds(array $ids=array()) {
		if(empty($ids))
			return;
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		if(false == ($models = DAO_Worker::getIds($ids)))
			return;
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_CONTACT, array('email_'));
		
		if(empty($dicts))
			return;
		
		foreach($dicts as $dict) {
			$this->_indexDictionary($dict, $engine);
		}
	}
	
	public function index($stop_time=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		//$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%s = %d AND %s > %d) OR (%s > %d)',
				DAO_Contact::UPDATED_AT,
				$ptr_time,
				DAO_Contact::ID,
				$id,
				DAO_Contact::UPDATED_AT,
				$ptr_time
			);
			$models = DAO_Contact::getWhere($where, array(DAO_Contact::UPDATED_AT, DAO_Contact::ID), array(true, true), 100);

			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_CONTACT, array('email_'));
			
			if(empty($dicts)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			// Loop dictionaries
			foreach($dicts as $dict) {
				$id = $dict->id;
				$ptr_time = $dict->updated_at;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				if(false == $this->_indexDictionary($dict, $engine))
					return false;
			}
		}
		
		// If we ran out of records, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		$this->setParam('last_indexed_id', $ptr_id);
		$this->setParam('last_indexed_time', $ptr_time);
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Model_Contact extends DevblocksRecordModel {
	public $id;
	public $primary_email_id;
	public $first_name;
	public $last_name;
	public $title;
	public $org_id;
	public $username;
	public $gender;
	public $dob;
	public $location;
	public $phone;
	public $mobile;
	public $auth_salt;
	public $auth_password;
	public $created_at;
	public $updated_at;
	public $last_login_at;
	public $language;
	public $timezone;
	
	private $_org_model = null;
	
	function getName() {
		return sprintf("%s%s%s",
			$this->first_name,
			(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
			$this->last_name
		);
	}
	
	function getNameWithEmail() {
		$name = $this->getName();
		
		if(false == ($addy = DAO_Address::get($this->primary_email_id)))
			return $name;

		if(!empty($name))
			$name .= ' <' . $addy->email . '>';
		else
			$name = $addy->email;
		
		return $name;
	}
	
	function getGenderAsString() {
		$genders = [
			'' => '',
			'M' => DevblocksPlatform::translateCapitalized('common.gender.male'),
			'F' => DevblocksPlatform::translateCapitalized('common.gender.female'),
		];
		
		return @$genders[$this->gender];
	}
	
	function getInitials() {
		return mb_convert_case(DevblocksPlatform::strToInitials($this->getName()), MB_CASE_UPPER);
	}
	
	function getImageUrl() {
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->write(sprintf('c=avatars&type=contact&id=%d', $this->id)) . '?v=' . $this->updated_at;
	}
	
	function getOrg() {
		if(empty($this->org_id))
			return null;
		
		if($this->_org_model)
			return $this->_org_model;
		
		$this->_org_model = DAO_ContactOrg::get($this->org_id);
		
		return $this->_org_model;
	}
	
	function setOrg(Model_ContactOrg $org) {
		$this->_org_model = $org;
	}
	
	function getOrgAsString() {
		if(empty($this->org_id))
			return null;
		
		if(false == ($org = DAO_ContactOrg::get($this->org_id)))
			return null;
		
		return $org->name;
	}
	
	function getOrgImageUrl() {
		if(false == ($org = $this->getOrg()))
			return null;
		
		return $org->getImageUrl();
	}
	
	// Primary
	function getEmail() {
		if(empty($this->primary_email_id))
			return null;
		
		return DAO_Address::get($this->primary_email_id);
	}
	
	function getEmailAsString() {
		if(empty($this->primary_email_id))
			return null;
		
		if(false == ($addy = DAO_Address::get($this->primary_email_id)))
			return null;
		
		return $addy->email;
	}
	
	/**
	 * Primary plus alternates
	 * 
	 * @return Model_Address[]
	 */
	function getEmails() {
		return DAO_Address::getWhere(
			sprintf("%s = %d",
				DAO_Address::CONTACT_ID,
				$this->id
			)
		);
	}
};

class View_Contact extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'contact';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('common.contacts');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Contact::UPDATED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Contact::PRIMARY_EMAIL_ID,
			SearchFields_Contact::TITLE,
			SearchFields_Contact::ORG_ID,
			SearchFields_Contact::USERNAME,
			SearchFields_Contact::GENDER,
			SearchFields_Contact::LOCATION,
			SearchFields_Contact::LANGUAGE,
			SearchFields_Contact::TIMEZONE,
			SearchFields_Contact::UPDATED_AT,
			SearchFields_Contact::LAST_LOGIN_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_Contact::ORG_NAME,
			SearchFields_Contact::AUTH_SALT,
			SearchFields_Contact::AUTH_PASSWORD,
			SearchFields_Contact::PRIMARY_EMAIL_ADDRESS,
			SearchFields_Contact::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Contact::FULLTEXT_CONTACT,
			SearchFields_Contact::VIRTUAL_ALIAS,
			SearchFields_Contact::VIRTUAL_CONTEXT_LINK,
			SearchFields_Contact::VIRTUAL_EMAIL_SEARCH,
			SearchFields_Contact::VIRTUAL_ORG_SEARCH,
			SearchFields_Contact::VIRTUAL_HAS_FIELDSET,
			SearchFields_Contact::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Contact::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Contact');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Contact', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Contact', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Contact::GENDER:
				case SearchFields_Contact::ORG_ID:
				case SearchFields_Contact::LANGUAGE:
				case SearchFields_Contact::TIMEZONE:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Contact::VIRTUAL_WATCHERS:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_CONTACT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Contact::GENDER:
				$label_map = array(
					'M' => 'Male',
					'F' => 'Female',
					'' => '(unknown)',
				);
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_Contact::ORG_ID:
				$label_map = function($ids) {
					$orgs = DAO_ContactOrg::getIds($ids);
					return array_column($orgs, 'name', 'id');
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
				break;
				
			case SearchFields_Contact::LANGUAGE:
			case SearchFields_Contact::TIMEZONE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Contact::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Contact::getFields();
		$date = DevblocksPlatform::services()->date();
		
		$timezones = $date->getTimezones();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Contact::FULLTEXT_CONTACT),
				),
			'alias' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_ALIAS),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Contact::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Contact::CREATED_AT),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_EMAIL_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Contact::PRIMARY_EMAIL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CONTACT],
					]
				),
			'firstName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::FIRST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:contact by:firstName~25 query:(firstName:{{term}}*) format:dictionaries',
						'key' => 'firstName',
						'limit' => 25,
					]
				),
			'gender' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::GENDER, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => [
						'female',
						'male',
						'unknown',
					],
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Contact::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONTACT, 'q' => ''],
					]
				),
			'lang' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::LANGUAGE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'lastLogin' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Contact::LAST_LOGIN_AT),
				),
			'lastName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::LAST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:contact by:lastName~25 query:(lastName:{{term}}*) format:dictionaries',
						'key' => 'lastName',
						'limit' => 25,
					]
				),
			'mobile' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_Contact::MOBILE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX],
				],
			'org' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_ORG_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'org.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Contact::ORG_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'phone' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_Contact::PHONE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX],
				],
			'timezone' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::TIMEZONE),
					'examples' => array(
						['type' => 'list', 'values' => array_combine($timezones, $timezones), 'label_delimiter' => '/', 'key_delimiter' => '/'],
					)
				),
			'title' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:contact by:title~25 query:(title:{{term}}*) format:dictionaries',
						'key' => 'title',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Contact::UPDATED_AT),
				),
			'username' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_Contact::USERNAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX],
				],
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Contact::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CONTACT, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Contact::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['text']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'alias':
				return DevblocksSearchCriteria::getContextAliasParamFromTokens(SearchFields_Contact::VIRTUAL_ALIAS, $tokens);
			
			case 'email':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Contact::VIRTUAL_EMAIL_SEARCH);
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				
			case 'gender':
				$field_key = SearchFields_Contact::GENDER;
				$oper = null;
				$value = null;
				
				if(false == CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value))
					return false;
				
				foreach($value as &$v) {
					if(substr(DevblocksPlatform::strLower($v), 0, 1) == 'm') {
						$v = 'M';
					} else if(substr(DevblocksPlatform::strLower($v), 0, 1) == 'f') {
						$v = 'F';
					} else {
						$v = '';
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value
				);
				
			case 'org':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Contact::VIRTUAL_ORG_SEARCH);
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Contact::VIRTUAL_WATCHERS, $tokens);
				
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/contact/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Contact::GENDER:
				$label_map = SearchFields_Contact::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Contact::ORG_ID:
				$label_map = SearchFields_Contact::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
				$label_map = SearchFields_Contact::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Contact::VIRTUAL_ALIAS:
				echo sprintf("%s %s <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.alias')),
					DevblocksPlatform::strEscapeHtml($param->operator),
					DevblocksPlatform::strEscapeHtml(json_encode($param->value))
				);
				break;
			
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Contact::VIRTUAL_EMAIL_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.email')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Contact::VIRTUAL_ORG_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.organization')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Contact::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Contact::FIRST_NAME:
			case SearchFields_Contact::LAST_NAME:
			case SearchFields_Contact::TITLE:
			case SearchFields_Contact::USERNAME:
			case SearchFields_Contact::GENDER:
			case SearchFields_Contact::DOB:
			case SearchFields_Contact::LOCATION:
			case SearchFields_Contact::ORG_NAME:
			case SearchFields_Contact::PHONE:
			case SearchFields_Contact::PRIMARY_EMAIL_ADDRESS:
			case SearchFields_Contact::MOBILE:
			case SearchFields_Contact::AUTH_SALT:
			case SearchFields_Contact::AUTH_PASSWORD:
			case SearchFields_Contact::LANGUAGE:
			case SearchFields_Contact::TIMEZONE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Contact::ID:
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
			case SearchFields_Contact::ORG_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Contact::CREATED_AT:
			case SearchFields_Contact::UPDATED_AT:
			case SearchFields_Contact::LAST_LOGIN_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Contact::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Contact::FULLTEXT_CONTACT:
				$scope = DevblocksPlatform::importGPC($_POST['scope'] ?? null, 'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array', []);
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

class Context_Contact extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextBroadcast, IDevblocksContextMerge, IDevblocksContextAutocomplete {
	const ID = CerberusContexts::CONTEXT_CONTACT;
	const URI = 'contact';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Contact::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=contact&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Contact();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CONTACT
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->primary_email_id,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
		);
		
		$properties['org'] = array(
			'label' => mb_ucfirst($translate->_('common.organization')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->org_id,
			'params' => array('context' => CerberusContexts::CONTEXT_ORG),
		);
		
		$properties['gender'] = array(
			'label' => mb_ucfirst($translate->_('common.gender')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getGenderAsString(),
		);
		
		$properties['title'] = array(
			'label' => mb_ucfirst($translate->_('common.title')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->title,
		);
		
		$properties['location'] = array(
			'label' => mb_ucfirst($translate->_('common.location')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->location,
		);
		
		$properties['language'] = array(
			'label' => mb_ucfirst($translate->_('common.language')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->language,
		);
		
		$properties['timezone'] = array(
			'label' => mb_ucfirst($translate->_('common.timezone')),
			'type' => 'timezone',
			'value' => $model->timezone,
		);
			
		$properties['phone'] = array(
			'label' => mb_ucfirst($translate->_('common.phone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->phone,
		);
			
		$properties['mobile'] = array(
			'label' => mb_ucfirst($translate->_('common.mobile')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->mobile,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['last_login'] = array(
			'label' => mb_ucfirst($translate->_('common.last_login')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->last_login_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(false == ($contact = DAO_Contact::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($contact->getName());
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $contact->id,
			'name' => $contact->getName(),
			'permalink' => $url,
			'updated' => $contact->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'email__label',
			'org__label',
			'location',
			'language',
			'timezone',
			'phone',
			'mobile',
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$list = array();
		
		$models = DAO_Contact::autocomplete($term);
		
		if(stristr('none',$term) || stristr('empty',$term) || stristr('no contact',$term)) {
			$empty = new stdClass();
			$empty->label = '(no contact)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the contact');
			$list[] = $empty;
		}
		
		// Efficiently load all of the referenced orgs in one query
		$orgs = DAO_ContactOrg::getIds(DevblocksPlatform::extractArrayValues($models, 'org_id'));

		if(is_array($models))
		foreach($models as $contact_id => $contact){
			$entry = new stdClass();
			$entry->label = $contact->getName();
			$entry->value = sprintf("%d", $contact_id);
			$entry->icon = $url_writer->write('c=avatars&type=contact&id=' . $contact->id, true) . '?v=' . $contact->updated_at;
			
			$meta = array();
			$meta['role'] = $contact->title;

			if($contact->org_id && isset($orgs[$contact->org_id])) {
				$org = $orgs[$contact->org_id];
				$meta['role'] .= (!empty($meta['role']) ? ' at ' : '') . $org->name;
			}
			
			$entry->meta = $meta;
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($contact, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Contact:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT);

		// Polymorph
		if(is_numeric($contact)) {
			$contact = DAO_Contact::get($contact);
		} elseif($contact instanceof Model_Contact) {
			// It's what we want already.
		} elseif(is_array($contact)) {
			$contact = Cerb_ORMHelper::recastArrayToModel($contact, 'Model_Contact');
		} else {
			$contact = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'dob' => $prefix.$translate->_('common.dob'),
			'first_name' => $prefix.$translate->_('common.name.first'),
			'gender' => $prefix.$translate->_('common.gender'),
			'id' => $prefix.$translate->_('common.id'),
			'language' => $prefix.$translate->_('common.language'),
			'last_login_at' => $prefix.$translate->_('common.last_login'),
			'last_name' => $prefix.$translate->_('common.name.last'),
			'location' => $prefix.$translate->_('common.location'),
			'mobile' => $prefix.$translate->_('common.mobile'),
			'name' => $prefix.$translate->_('common.name'),
			'phone' => $prefix.$translate->_('common.phone'),
			'timezone' => $prefix.$translate->_('common.timezone'),
			'title' => $prefix.$translate->_('common.title'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'username' => $prefix.$translate->_('common.username'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'dob' => Model_CustomField::TYPE_SINGLE_LINE,
			'first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'gender' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'language' => Model_CustomField::TYPE_SINGLE_LINE,
			'last_login_at' => Model_CustomField::TYPE_DATE,
			'last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'location' => Model_CustomField::TYPE_SINGLE_LINE,
			'mobile' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'timezone' => Model_CustomField::TYPE_SINGLE_LINE,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'username' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = Context_Contact::ID;
		$token_values['_type'] = Context_Contact::URI;
		
		$token_values['_types'] = $token_types;
		
		if($contact) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $contact->getName();
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'contact', $contact->id), true) . '?v=' . $contact->updated_at;
			$token_values['dob'] = $contact->dob;
			$token_values['id'] = $contact->id;
			$token_values['first_name'] = $contact->first_name;
			$token_values['gender'] = $contact->gender;
			$token_values['language'] = $contact->language;
			$token_values['last_login_at'] = $contact->last_login_at;
			$token_values['last_name'] = $contact->last_name;
			$token_values['location'] = $contact->location;
			$token_values['mobile'] = $contact->mobile;
			$token_values['phone'] = $contact->phone;
			$token_values['name'] = $contact->getName();
			$token_values['timezone'] = $contact->timezone;
			$token_values['title'] = $contact->title;
			$token_values['username'] = $contact->username;
			$token_values['updated_at'] = $contact->updated_at;
			
			$token_values['email_id'] = $contact->primary_email_id;
			
			$token_values['org_id'] = $contact->org_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($contact, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=contact&id=%d-%s",$contact->id, DevblocksPlatform::strToPermalink($contact->getName())), true);
		}
		
		$context_stack = CerberusContexts::getStack();
		
		// Address
		// Only link address placeholders if the contact isn't nested under an address already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_ADDRESS, $context_stack)) {
			$merge_token_labels = array();
			$merge_token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);
			
				CerberusContexts::merge(
					'email_',
					$prefix.'Email:',
					$merge_token_labels,
					$merge_token_values,
					$token_labels,
					$token_values
				);
				
		} else {
			$token_values['email__context'] = CerberusContexts::CONTEXT_ADDRESS;
		}
		
		// Org
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, null, $merge_token_labels, $merge_token_values, '', true);
		
			CerberusContexts::merge(
				'org_',
				$prefix.'Org:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'dob' => DAO_Contact::DOB,
			'email_id' => DAO_Contact::PRIMARY_EMAIL_ID,
			'first_name' => DAO_Contact::FIRST_NAME,
			'gender' => DAO_Contact::GENDER,
			'id' => DAO_Contact::ID,
			'image' => '_image',
			'language' => DAO_Contact::LANGUAGE,
			'last_login_at' => DAO_Contact::LAST_LOGIN_AT,
			'last_name' => DAO_Contact::LAST_NAME,
			'links' => '_links',
			'location' => DAO_Contact::LOCATION,
			'mobile' => DAO_Contact::MOBILE,
			'org_id' => DAO_Contact::ORG_ID,
			'phone' => DAO_Contact::PHONE,
			'timezone' => DAO_Contact::TIMEZONE,
			'title' => DAO_Contact::TITLE,
			'username' => DAO_Contact::USERNAME,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['email'] = [
			'key' => 'email',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'Email address (e.g. `customer@example.com`); alternative to `email_id`',
			'type' => 'string',
		];
		
		$keys['org'] = [
			'key' => 'org',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'Organization (e.g. `Fiaflux Software`); alternative to `org_id`',
			'type' => 'string',
		];
		
		$keys['dob']['notes'] = "Date of birth: `YYYY-MM-DD`";
		$keys['email_id']['notes'] = "ID of this contact's primary [email address](/docs/records/types/address/)";
		$keys['first_name']['notes'] = "Given name";
		$keys['gender']['notes'] = "Gender: `F` (female), `M` (male), or blank";
		$keys['language']['notes'] = "Language: `en_US`";
		$keys['last_login_at']['notes'] = "Date of their last [community portal](/docs/portals/) login";
		$keys['last_name']['notes'] = "Surname";
		$keys['location']['notes'] = "Location (e.g. `Los Angeles, California, USA`)";
		$keys['mobile']['notes'] = "Mobile number";
		$keys['org_id']['notes'] = "ID of this contact's [organization](/docs/records/types/org/)";
		$keys['phone']['notes'] = "Phone number";
		$keys['timezone']['notes'] = "Timezone (e.g. `America/Los_Angeles`)";
		$keys['title']['notes'] = "Job title / Position";
		$keys['username']['notes'] = "Username for public display";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'email':
				if(false == ($address = DAO_Address::lookupAddress($value, true))) {
					$error = sprintf("Failed to lookup address: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Contact::PRIMARY_EMAIL_ID] = $address->id;
				break;
			
			case 'image':
				$out_fields[DAO_Contact::_IMAGE] = $value;
				break;
				
			case 'org':
				if(!($org_id = DAO_ContactOrg::lookup($value, true))) {
					$error = sprintf("Failed to lookup org: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Contact::ORG_ID] = $org_id;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['emails'] = [
			'label' => 'Email Addresses',
			'type' => 'Records',
		];
		
		$lazy_keys['last_recipient_message'] = [
			'label' => 'Latest Message Received To',
			'type' => 'Record',
		];
		
		$lazy_keys['last_sender_message'] = [
			'label' => 'Latest Message Sent From',
			'type' => 'Record',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CONTACT;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'emails':
				$values['emails'] = array();
				
				$addresses = DAO_Address::getWhere(sprintf("%s = %d",
					Cerb_ORMHelper::escape(DAO_Address::CONTACT_ID),
					$context_id
				));
				
				foreach($addresses as $model_id => $model) {
					$values['emails'][$model_id] = $model->email;
				}
				break;
			
			default:
				if($token == 'last_recipient_message' || DevblocksPlatform::strStartsWith($token, 'last_recipient_message_')) {
					$values['last_recipient_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
					$values['last_recipient_message_id'] = intval(DAO_Message::getLatestIdByRecipientContactId($dictionary['id']));
					
				} else if($token == 'last_sender_message' || DevblocksPlatform::strStartsWith($token, 'last_sender_message_')) {
					$values['last_sender_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
					$values['last_sender_message_id'] = intval(DAO_Message::getLatestIdBySenderContactId($dictionary['id']));
					
				} else {
					$defaults = $this->_lazyLoadDefaults($token, $dictionary);
					$values = array_merge($values, $defaults);
				}
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
		$view->name = 'Contact';
		/*
		$view->addParams(array(
			SearchFields_Contact::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Contact::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Contact::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Contact';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Contact::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CONTACT;
		
		$contact = null;
		
		$tpl->assign('view_id', $view_id);
		
		$custom_fields = DAO_CustomField::getByContext($context, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if($context_id) {
			if(false == ($contact = DAO_Contact::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('model', $contact);
		}
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		// Aliases
		$tpl->assign('aliases', DAO_ContextAlias::get($context, $context_id));
		
		// Languages
		$translate = DevblocksPlatform::getTranslationService();
		$locales = $translate->getLocaleStrings();
		$tpl->assign('languages', $locales);
		
		// Timezones
		$date = DevblocksPlatform::services()->date();
		$tpl->assign('timezones', $date->getTimezones());
		
		if(!$context_id || $edit) {
			if($contact) {
				if(!Context_Contact::isWriteableByActor($contact, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if(!$context_id && $edit) {
				$tokens = explode(' ', trim($edit));
				
				$model = new Model_Contact();
				
				foreach($tokens as $token) {
					list($k,$v) = array_pad(explode(':', $token, 2), 2, null);
					
					if($v)
					switch($k) {
						case 'email':
							$model->primary_email_id = intval($v);
							break;
							
						case 'org':
							$model->org_id = intval($v);
							break;
					}
				}
				
				$tpl->assign('model', $model);
			}
			$tpl->display('devblocks:cerberusweb.core::internal/contact/peek_edit.tpl');
		} else {
			Page_Profiles::renderCard($context, $context_id, $contact);
		}
	}
	
	function mergeGetKeys() {
		$keys = [
			'dob',
			'first_name',
			'gender',
			'language',
			'last_login_at',
			'last_name',
			'location',
			'mobile',
			'phone',
			'timezone',
			'title',
			'username',
			'email__label',
			'org__label',
		];
		
		return $keys;
	}
	
	function broadcastRecipientFieldsGet() {
		$results = $this->_broadcastRecipientFieldsGet(CerberusContexts::CONTEXT_CONTACT, 'Contact', [
			'email_address',
			'org_email_address',
		]);
		
		asort($results);
		return $results;
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_CONTACT);
		return $token_values;
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = $this->_broadcastRecipientFieldsToEmails($fields, $dict);
		return $emails;
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'created_at' => array(
				'label' => 'Created',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Contact::CREATED_AT,
			),
			'dob' => array(
				'label' => 'Date of Birth',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::DOB,
			),
			'first_name' => array(
				'label' => 'First Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::FIRST_NAME,
			),
			'primary_email_id' => array(
				'label' => 'Email',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ADDRESS,
				'param' => SearchFields_Contact::PRIMARY_EMAIL_ID,
				//'force_match' => true,
			),
			'gender' => array(
				'label' => 'Gender',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::GENDER,
			),
			'language' => array(
				'label' => 'Language',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::LANGUAGE,
			),
			'last_login_at' => array(
				'label' => 'Last Login Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Contact::LAST_LOGIN_AT,
			),
			'last_name' => array(
				'label' => 'Last Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::LAST_NAME,
			),
			'location' => array(
				'label' => 'Location',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::LOCATION,
			),
			'mobile' => array(
				'label' => 'Mobile',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::MOBILE,
			),
			'org_id' => array(
				'label' => 'Org',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ORG,
				'param' => SearchFields_Contact::ORG_ID,
			),
			'phone' => array(
				'label' => 'Phone',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::PHONE,
			),
			'timezone' => array(
				'label' => 'Timezone',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::TIMEZONE,
			),
			'title' => array(
				'label' => 'Title',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Contact::TITLE,
			),
			'updated_at' => array(
				'label' => 'Updated',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Contact::UPDATED_AT,
			),
		);
	
		$fields = SearchFields_Contact::getFields();
		self::_getImportCustomFields($fields, $keys);
		
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
			case 'gender':
				if(0 == strcasecmp(substr($value,0,1),'M'))
					$value = 'M';
				elseif(0 == strcasecmp(substr($value,0,1),'F'))
					$value = 'F';
				else
					$value = '';
				break;
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Create
			$meta['object_id'] = DAO_Contact::create($fields);
	
		} else {
			// Update
			DAO_Contact::update($meta['object_id'], $fields);
		}

		if(isset($fields['primary_email_id']) 
				&& $fields['primary_email_id']
				&& false != ($address = DAO_Address::get($fields['primary_email_id']))) {
					$address_fields = array();
					
					// Address->Contact
					if(!$address->contact_id && isset($meta['object_id']) && $meta['object_id'])
						$address_fields[DAO_Address::CONTACT_ID] = intval($meta['object_id']);
					
					// Address->Org
					if(!$address->contact_org_id && isset($fields['org_id']) && $fields['org_id'])
						$address_fields[DAO_Address::CONTACT_ORG_ID] = intval($fields['org_id']);
					
					if(!empty($address_fields))
						DAO_Address::update($fields['primary_email_id'], $address_fields);
		}
		
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
		
		// Aliases
		DAO_ContextAlias::set(CerberusContexts::CONTEXT_CONTACT, $meta['object_id'], DevblocksPlatform::parseCrlfString($fields[DAO_Contact::FIRST_NAME] . ' ' . $fields[DAO_Contact::LAST_NAME])); //  . "\n" . $aliases
	}
};
