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

class DAO_ContactOrg extends Cerb_ORMHelper {
	const CITY = 'city';
	const COUNTRY = 'country';
	const CREATED = 'created';
	const EMAIL_ID = 'email_id';
	const ID = 'id';
	const NAME = 'name';
	const PHONE = 'phone';
	const POSTAL = 'postal';
	const PROVINCE = 'province';
	const STREET = 'street';
	const UPDATED = 'updated';
	const WEBSITE = 'website';
	
	const _IMAGE = '_image';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CITY)
			->string()
			;
		$validation
			->addField(self::COUNTRY)
			->string()
			;
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::EMAIL_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS, true))
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::PHONE)
			->string()
			;
		$validation
			->addField(self::POSTAL)
			->string()
			;
		$validation
			->addField(self::PROVINCE)
			->string()
			;
		$validation
			->addField(self::STREET)
			->string()
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		$validation
			->addField(self::WEBSITE)
			->url()
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
	
	/**
	 *
	 * @param array $fields
	 * @return integer
	 */
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO contact_org (created) ".
		"VALUES (%d)",
			time()
		);
		
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		return $id;
	}
	
	/**
	 *
	 * @param array $ids
	 * @param array $fields
	 * @return Model_ContactOrg
	 */
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		$context = CerberusContexts::CONTEXT_ORG;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ORG, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'contact_org', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.contact_org.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ORG, $batch_ids);
			}
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_ORG;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
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
				
				case 'country':
					$change_fields[DAO_ContactOrg::COUNTRY] = $v;
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
			DAO_ContactOrg::delete($ids);
			
		} else {
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ORG, $ids);
			
			// Fields
			if(!empty($change_fields))
				DAO_ContactOrg::update($ids, $change_fields, false);
			
			// Custom Fields
			if(!empty($custom_fields))
				C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_ORG, $custom_fields, $ids);
			
			// Scheduled behavior
			if(isset($do['behavior']))
				C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_ORG, $do['behavior'], $ids);
			
			// Watchers
			if(isset($do['watchers']))
				C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_ORG, $do['watchers'], $ids);
			
			// Broadcast
			if(isset($do['broadcast']))
				C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_ORG, $do['broadcast'], $ids);
			
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ORG, $ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	static function mergeIds($from_ids, $to_id) {
		$db = DevblocksPlatform::services()->database();

		$context = CerberusContexts::CONTEXT_ORG;
		
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		self::_mergeIds($context, $from_ids, $to_id);
		
		// Merge email addresses
		$db->ExecuteMaster(sprintf("UPDATE address SET contact_org_id = %d WHERE contact_org_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge contacts
		$db->ExecuteMaster(sprintf("UPDATE contact SET org_id = %d WHERE org_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Merge tickets
		$db->ExecuteMaster(sprintf("UPDATE ticket SET org_id = %d WHERE org_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		));
		
		// Index immediately
		$search = Extension_DevblocksSearchSchema::get(Search_Org::ID);
		$search->indexIds(array($to_id));
		
		return true;
	}
	
	/**
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$id_list = implode(',', $ids);
		
		// Orgs
		$sql = sprintf("DELETE FROM contact_org WHERE id IN (%s)",
			$id_list
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;

		// Clear any associated addresses
		$sql = sprintf("UPDATE address SET contact_org_id = 0 WHERE contact_org_id IN (%s)",
			$id_list
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Clear search records
		$search = Extension_DevblocksSearchSchema::get(Search_Org::ID);
		$search->delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_ORG,
					'context_ids' => $ids
				)
			)
		);
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		// Search indexes
		if(isset($tables['fulltext_org'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_org WHERE id NOT IN (SELECT id FROM contact_org)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_org records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_ORG,
					'context_table' => 'contact_org',
					'context_key' => 'id',
				)
			)
		);
	}
	
	/**
	 * @param string $where
	 * @param string $sortBy
	 * @param bool $sortAsc
	 * @param integer $limit
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, street, city, province, postal, country, phone, website, created, updated, email_id ".
			"FROM contact_org ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);

		$objects = self::_getObjectsFromResultSet($rs);

		return $objects;
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContactOrg();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->street = $row['street'];
			$object->city = $row['city'];
			$object->province = $row['province'];
			$object->postal = $row['postal'];
			$object->country = $row['country'];
			$object->phone = $row['phone'];
			$object->website = $row['website'];
			$object->created = intval($row['created']);
			$object->updated = intval($row['updated']);
			$object->email_id = intval($row['email_id']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_ContactOrg
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$where = sprintf("%s = %d",
			self::ID,
			$id
		);
		$objects = self::getWhere($where);

		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}

	/**
	 *
	 * @param string $name
	 * @param boolean $create_if_null
	 * @return Model_ContactOrg
	 */
	static function lookup($name, $create_if_null=false) {
		$db = DevblocksPlatform::services()->database();
		
		@$orgs = self::getWhere(
			sprintf('%s = %s', self::NAME, $db->qstr($name))
		);
		
		if(empty($orgs)) {
			if($create_if_null) {
				$fields = array(
					self::NAME => $name
				);
				return self::create($fields);
			}
		} else {
			return key($orgs);
		}
		
		return NULL;
	}
	
	public static function random() {
		return self::_getRandom('contact_org');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContactOrg::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContactOrg', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"c.id as %s, ".
			"c.name as %s, ".
			"c.street as %s, ".
			"c.city as %s, ".
			"c.province as %s, ".
			"c.postal as %s, ".
			"c.country as %s, ".
			"c.phone as %s, ".
			"c.website as %s, ".
			"c.updated as %s, ".
			"c.created as %s, ".
			"c.email_id as %s ",
				SearchFields_ContactOrg::ID,
				SearchFields_ContactOrg::NAME,
				SearchFields_ContactOrg::STREET,
				SearchFields_ContactOrg::CITY,
				SearchFields_ContactOrg::PROVINCE,
				SearchFields_ContactOrg::POSTAL,
				SearchFields_ContactOrg::COUNTRY,
				SearchFields_ContactOrg::PHONE,
				SearchFields_ContactOrg::WEBSITE,
				SearchFields_ContactOrg::UPDATED,
				SearchFields_ContactOrg::CREATED,
				SearchFields_ContactOrg::EMAIL_ID
			);

		$join_sql =
			"FROM contact_org c ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContactOrg');

		$result = array(
			'primary_table' => 'c',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 *
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
			$object_id = intval($row[SearchFields_ContactOrg::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(c.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_ContactOrg extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const STREET = 'c_street';
	const CITY = 'c_city';
	const PROVINCE = 'c_province';
	const POSTAL = 'c_postal';
	const COUNTRY = 'c_country';
	const PHONE = 'c_phone';
	const WEBSITE = 'c_website';
	const CREATED = 'c_created';
	const UPDATED = 'c_updated';
	const EMAIL_ID = 'c_email_id';

	// Fulltexts
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	const FULLTEXT_ORG = 'ft_org';

	// Virtuals
	const VIRTUAL_ALIAS = '*_alias';
	const VIRTUAL_CONTACTS_SEARCH = '*_contacts_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_EMAIL_SEARCH = '*_email_search';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'c.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('c.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_ORG:
				return self::_getWhereSQLFromFulltextField($param, Search_Org::ID, self::getPrimaryKey());
				break;
				
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_ORG, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_ALIAS:
				return  self::_getWhereSQLFromAliasesField($param, CerberusContexts::CONTEXT_ORG, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_CONTACTS_SEARCH:
				$sql = "SELECT contact.org_id FROM contact WHERE contact.id IN (%s)";
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CONTACT, $sql, 'c.id');
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_ORG, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_EMAIL_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'c.email_id');
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_ORG)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_ORG, self::getPrimaryKey());
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
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'province':
				$key = 'state';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContactOrg::ID:
				$models = DAO_ContactOrg::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'c', 'name', $translate->_('common.name'),Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STREET => new DevblocksSearchField(self::STREET, 'c', 'street', $translate->_('contact_org.street'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CITY => new DevblocksSearchField(self::CITY, 'c', 'city', $translate->_('contact_org.city'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PROVINCE => new DevblocksSearchField(self::PROVINCE, 'c', 'province', $translate->_('contact_org.province'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::POSTAL => new DevblocksSearchField(self::POSTAL, 'c', 'postal', $translate->_('contact_org.postal'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::COUNTRY => new DevblocksSearchField(self::COUNTRY, 'c', 'country', $translate->_('contact_org.country'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'c', 'phone', $translate->_('common.phone'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::WEBSITE => new DevblocksSearchField(self::WEBSITE, 'c', 'website', $translate->_('common.website'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'c', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'c', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'c', 'email_id', $translate->_('common.email'), Model_CustomField::TYPE_NUMBER, true),

			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
			self::FULLTEXT_ORG => new DevblocksSearchField(self::FULLTEXT_ORG, 'ft', 'org', $translate->_('common.search.fulltext'), 'FT', false),

			self::VIRTUAL_ALIAS => new DevblocksSearchField(self::VIRTUAL_ALIAS, '*', 'alias', $translate->_('common.aliases'), null, false),
			self::VIRTUAL_CONTACTS_SEARCH => new DevblocksSearchField(self::VIRTUAL_CONTACTS_SEARCH, '*', 'contacts_search', null, null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_EMAIL_SEARCH => new DevblocksSearchField(self::VIRTUAL_EMAIL_SEARCH, '*', 'email_search', null, null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		$columns[self::FULLTEXT_ORG]->ft_schema = Search_Org::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Search_Org extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.org';
	
	public function getNamespace() {
		return 'org';
	}
	
	public function getAttributes() {
		return array();
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function query($query, $attributes=array(), $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		
		return $ids;
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
				$dict->name,
				$dict->street,
				$dict->city,
				$dict->province,
				$dict->postal,
				$dict->country,
				$dict->phone,
				$dict->website,
				$dict->email_address,
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
		
		if(false == ($models = DAO_ContactOrg::getIds($ids)))
			return;
		
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ORG, array());
		
		if(empty($dicts))
			return;
		
		foreach($dicts as $dict) {
			$this->_indexDictionary($dict, $engine);
		}
	}
	
	public function index($stop_time=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_ContactOrg::UPDATED,
				$ptr_time,
				DAO_ContactOrg::ID,
				$id
			);
			$models = DAO_ContactOrg::getWhere($where, array(DAO_ContactOrg::UPDATED, DAO_ContactOrg::ID), array(true, true), 100);

			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ORG, array());
			
			if(empty($dicts)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			// Loop dictionaries
			foreach($dicts as $dict) {
				$id = $dict->id;
				$ptr_time = $dict->updated;
				
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

class Model_ContactOrg {
	public $id;
	public $name;
	public $street;
	public $city;
	public $province;
	public $postal;
	public $country;
	public $phone;
	public $website;
	public $created;
	public $updated;
	public $email_id;
	
	// Primary
	function getEmail() {
		if(empty($this->email_id))
			return null;
		
		return DAO_Address::get($this->email_id);
	}
	
	function getEmailAsString() {
		if(empty($this->email_id))
			return null;
		
		if(false == ($addy = DAO_Address::get($this->email_id)))
			return null;
		
		return $addy->email;
	}
	
	function getMailingAddress() {
		$out = '';
		
		if(!empty($this->street))
			$out .= rtrim($this->street) . "\n";
		
		$parts = array();
		
		if(!empty($this->city))
			$parts[] = $this->city;
		
		if(!empty($this->state))
			$parts[] = $this->state;
		
		if(!empty($this->province))
			$parts[] = $this->province;
		
		if(!empty($this->postal))
			$parts[] = $this->postal;
		
		if(!empty($this->country))
			$parts[] = $this->country;
		
		$out .= implode(' ', $parts);
		
		return $out;
	}
	
	function getEmailsWithoutContacts($limit=0) {
		return DAO_Address::getWhere(
			sprintf("%s = %d && %s = 0",
				Cerb_ORMHelper::escape(DAO_Address::CONTACT_ORG_ID),
				$this->id,
				Cerb_ORMHelper::escape(DAO_Address::CONTACT_ID)
			),
			DAO_Address::NUM_NONSPAM,
			false,
			$limit
		);
	}
	
	function getImageUrl() {
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->write(sprintf('c=avatars&type=org&id=%d', $this->id)) . '?v=' . $this->updated;
	}
};

class View_ContactOrg extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'contact_orgs';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('addy_book.tab.organizations');
		$this->renderSortBy = 'c_name';
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContactOrg::EMAIL_ID,
			SearchFields_ContactOrg::UPDATED,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::WEBSITE,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContactOrg::FULLTEXT_COMMENT_CONTENT,
			SearchFields_ContactOrg::FULLTEXT_ORG,
			SearchFields_ContactOrg::VIRTUAL_ALIAS,
			SearchFields_ContactOrg::VIRTUAL_CONTACTS_SEARCH,
			SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK,
			SearchFields_ContactOrg::VIRTUAL_EMAIL_SEARCH,
			SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET,
			SearchFields_ContactOrg::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContactOrg::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ContactOrg');
		
		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ContactOrg', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContactOrg', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ContactOrg::COUNTRY:
				case SearchFields_ContactOrg::PROVINCE:
				case SearchFields_ContactOrg::POSTAL:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET:
				case SearchFields_ContactOrg::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_ORG;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_ContactOrg::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::FULLTEXT_ORG),
				),
			'alias' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContactOrg::VIRTUAL_ALIAS),
				),
			'city' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::CITY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::FULLTEXT_COMMENT_CONTENT),
				),
			'contacts' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContactOrg::VIRTUAL_CONTACTS_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CONTACT, 'q' => ''],
					]
				),
			'country' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::COUNTRY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:orgs by:country~50 query:(country:!"" country:{{term}}*) format:dictionaries',
						'key' => 'country',
					]
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ContactOrg::CREATED),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContactOrg::VIRTUAL_EMAIL_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContactOrg::EMAIL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_ORG],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContactOrg::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 2000,
					'options' => array('param_key' => SearchFields_ContactOrg::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:orgs by:name~25 query:(name:{{term}}*) format:dictionaries',
						'key' => 'name',
					]
				),
			'phone' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::PHONE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'postal' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::POSTAL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'state' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::PROVINCE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:orgs by:state~25 query:(state:{{term}}*) format:dictionaries',
						'key' => 'state',
					]
				),
			'street' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::STREET, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ContactOrg::UPDATED),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_ContactOrg::VIRTUAL_WATCHERS),
				),
			'website' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContactOrg::WEBSITE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ORG, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Org::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['text']['examples'] = $ft_examples;
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'alias':
				return DevblocksSearchCriteria::getContextAliasParamFromTokens(SearchFields_ContactOrg::VIRTUAL_ALIAS, $tokens);
				break;
			
			case 'contacts':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ContactOrg::VIRTUAL_CONTACTS_SEARCH);
				break;
				
			case 'email':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ContactOrg::VIRTUAL_EMAIL_SEARCH);
				break;
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
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

		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('custom_fields', $org_fields);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::contacts/orgs/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
		$tpl->clearAssign('custom_fields');
		$tpl->clearAssign('id');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ContactOrg::VIRTUAL_ALIAS:
				echo sprintf("%s %s <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.alias')),
					DevblocksPlatform::strEscapeHtml($param->operator),
					DevblocksPlatform::strEscapeHtml(json_encode($param->value))
				);
				break;
			
			case SearchFields_ContactOrg::VIRTUAL_CONTACTS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.contacts')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_EMAIL_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.email')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;

			case SearchFields_ContactOrg::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContactOrg::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContactOrg::NAME:
			case SearchFields_ContactOrg::STREET:
			case SearchFields_ContactOrg::CITY:
			case SearchFields_ContactOrg::PROVINCE:
			case SearchFields_ContactOrg::POSTAL:
			case SearchFields_ContactOrg::COUNTRY:
			case SearchFields_ContactOrg::PHONE:
			case SearchFields_ContactOrg::WEBSITE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContactOrg::CREATED:
			case SearchFields_ContactOrg::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ContactOrg::EMAIL_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ContactOrg::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_ContactOrg::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_ContactOrg::FULLTEXT_ORG:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class Context_Org extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextBroadcast, IDevblocksContextMerge, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.org';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	/**
	 * @see Extension_DevblocksContext::getDaoClass()
	 */
	function getDaoClass() {
		return 'DAO_ContactOrg';
	}
	

	/**
	 * @see Extension_DevblocksContext::getSearchClass()
	 */
	function getSearchClass() {
		return 'SearchFields_ContactOrg';
	}
	
	/**
	 * @see Extension_DevblocksContext::getViewClass()
	 */
	function getViewClass() {
		return 'View_ContactOrg';
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=org&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ContactOrg();
		
		$properties['_label'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->email_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['street'] = array(
			'label' => mb_ucfirst($translate->_('contact_org.street')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->street,
		);
		
		$properties['city'] = array(
			'label' => mb_ucfirst($translate->_('contact_org.city')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->city,
		);
		
		$properties['province'] = array(
			'label' => mb_ucfirst($translate->_('contact_org.province')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->province,
		);
		
		$properties['postal'] = array(
			'label' => mb_ucfirst($translate->_('contact_org.postal')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->postal,
		);
		
		$properties['country'] = array(
			'label' => mb_ucfirst($translate->_('contact_org.country')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->country,
		);
		
		$properties['phone'] = array(
			'label' => mb_ucfirst($translate->_('common.phone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->phone,
		);
		
		$properties['website'] = array(
			'label' => mb_ucfirst($translate->_('common.website')),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->website,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$org = DAO_ContactOrg::get($context_id);

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($org->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $context_id,
			'name' => $org->name,
			'permalink' => $url,
			'updated' => $org->updated,
		);
	}
	
	function getRandom() {
		return DAO_ContactOrg::random();
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'email__label',
			'phone',
			'website',
			'updated',
		);
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$list = [];
		
		if(stristr('none',$term) || stristr('empty',$term) || stristr('no organization',$term)) {
			$empty = new stdClass();
			$empty->label = '(no organization)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the organization');
			$list[] = $empty;
		}
		
		list($results,) = DAO_ContactOrg::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
			),
			25,
			0,
			SearchFields_ContactOrg::NAME,
			true,
			false
		);

		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_ContactOrg::NAME];
			$entry->value = $row[SearchFields_ContactOrg::ID];
			$entry->icon = $url_writer->write('c=avatars&type=org&id=' . $row[SearchFields_ContactOrg::ID], true) . '?v=' . $row[SearchFields_ContactOrg::UPDATED];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($org, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Org:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);

		// Polymorph
		if(is_numeric($org)) {
			$org = DAO_ContactOrg::get($org);
		} elseif($org instanceof Model_ContactOrg) {
			// It's what we want already.
		} elseif(is_array($org)) {
			$org = Cerb_ORMHelper::recastArrayToModel($org, 'Model_ContactOrg');
		} else {
			$org = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'city' => $prefix.$translate->_('contact_org.city'),
			'country' => $prefix.$translate->_('contact_org.country'),
			'created' => $prefix.$translate->_('common.created'),
			'phone' => $prefix.$translate->_('common.phone'),
			'postal' => $prefix.$translate->_('contact_org.postal'),
			'province' => $prefix.$translate->_('contact_org.province'),
			'street' => $prefix.$translate->_('contact_org.street'),
			'updated' => $prefix.$translate->_('common.updated'),
			'website' => $prefix.$translate->_('common.website'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'city' => Model_CustomField::TYPE_SINGLE_LINE,
			'country' => Model_CustomField::TYPE_SINGLE_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'phone' => 'phone',
			'postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'province' => Model_CustomField::TYPE_SINGLE_LINE,
			'street' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			'website' => Model_CustomField::TYPE_URL,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_ORG;
		$token_values['_types'] = $token_types;
		
		// Org token values
		if($org) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $org->name;
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'org', $org->id), true) . '?v=' . $org->updated;
			$token_values['id'] = $org->id;
			$token_values['name'] = $org->name;
			$token_values['created'] = $org->created;
			$token_values['city'] = $org->city;
			$token_values['country'] = $org->country;
			$token_values['phone'] = $org->phone;
			$token_values['postal'] = $org->postal;
			$token_values['province'] = $org->province;
			$token_values['street'] = $org->street;
			$token_values['updated'] = $org->updated;
			$token_values['website'] = $org->website;
			
			$token_values['email_id'] = $org->email_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($org, $token_values);
			
			// URL
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=org&id=%d-%s",$org->id, DevblocksPlatform::strToPermalink($org->name)), true);
		}
		
		$context_stack = CerberusContexts::getStack();
		
		// Email
		// Only link email placeholders if the email isn't nested under an org already
		if(1 == count($context_stack) || !in_array(CerberusContexts::CONTEXT_ADDRESS, $context_stack)) {
			$merge_token_labels = $merge_token_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);
	
			CerberusContexts::merge(
				'email_',
				$prefix.'Email:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		}

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'city' => DAO_ContactOrg::CITY,
			'country' => DAO_ContactOrg::COUNTRY,
			'created' => DAO_ContactOrg::CREATED,
			'email_id' => DAO_ContactOrg::EMAIL_ID,
			'id' => DAO_ContactOrg::ID,
			'image' => '_image',
			'links' => '_links',
			'name' => DAO_ContactOrg::NAME,
			'phone' => DAO_ContactOrg::PHONE,
			'postal' => DAO_ContactOrg::POSTAL,
			'province' => DAO_ContactOrg::PROVINCE,
			'street' => DAO_ContactOrg::STREET,
			'updated' => DAO_ContactOrg::UPDATED,
			'website' => DAO_ContactOrg::WEBSITE,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['city']['notes'] = "City";
		$keys['country']['notes'] = "Country";
		$keys['email_id']['notes'] = "Primary [email address](/docs/records/types/address/)";
		$keys['phone']['notes'] = "Phone";
		$keys['postal']['notes'] = "Postal code / ZIP";
		$keys['province']['notes'] = "State / Province";
		$keys['street']['notes'] = "Street address";
		$keys['website']['notes'] = "Website";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'image':
				$out_fields[DAO_ContactOrg::_IMAGE] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['last_recipient_message'] = [
			'label' => 'Latest Message Received',
			'type' => 'Record',
		];
		
		$lazy_keys['last_sender_message'] = [
			'label' => 'Latest Message Sent',
			'type' => 'Record',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_ORG;
		$context_id = $dictionary['id'];
		
		if(empty($context_id))
			return;
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		if($token == 'last_recipient_message' || DevblocksPlatform::strStartsWith($token, 'last_recipient_message_')) {
			$values['last_recipient_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
			$values['last_recipient_message_id'] = intval(DAO_Message::getLatestIdByRecipientOrgId($dictionary['id']));
			
		} else if($token == 'last_sender_message' || DevblocksPlatform::strStartsWith($token, 'last_sender_message_')) {
			$values['last_sender_message__context'] = CerberusContexts::CONTEXT_MESSAGE;
			$values['last_sender_message_id'] = intval(DAO_Message::getLatestIdBySenderOrgId($dictionary['id']));
			
		} else {
			$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
			$values = array_merge($values, $defaults);
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
		$view->name = 'Organizations';

		$view->view_columns = array(
			SearchFields_ContactOrg::PHONE,
			SearchFields_ContactOrg::COUNTRY,
			SearchFields_ContactOrg::WEBSITE,
		);
		
		$view->renderSortBy = SearchFields_ContactOrg::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Organizations';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ContactOrg::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_ORG;
		$contact = DAO_ContactOrg::get($context_id);
		
		if(empty($context_id) || $edit) {
			$tpl->assign('org', $contact);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Aliases
			$tpl->assign('aliases', DAO_ContextAlias::get($context, $context_id));
			
			$tpl->display('devblocks:cerberusweb.core::contacts/orgs/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $contact);
		}
	}
	
	function mergeGetKeys() {
		$keys = [
			'name',
			'street',
			'city',
			'province',
			'postal',
			'country',
			'phone',
			'website',
			'email__label',
		];
		
		return $keys;
	}
	
	function broadcastRecipientFieldsGet() {
		$results = $this->_broadcastRecipientFieldsGet(CerberusContexts::CONTEXT_ORG, 'Org', [
			'email_address',
		]);
		
		asort($results);
		return $results;
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_ORG);
		return $token_values;
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		$emails = $this->_broadcastRecipientFieldsToEmails($fields, $dict);
		return $emails;
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'city' => array(
				'label' => 'City',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::CITY,
			),
			'country' => array(
				'label' => 'Country',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::COUNTRY,
			),
			'created' => array(
				'label' => 'Created Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_ContactOrg::CREATED,
			),
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::NAME,
				'required' => true,
				'force_match' => true,
			),
			'phone' => array(
				'label' => 'Phone',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::PHONE,
			),
			'postal' => array(
				'label' => 'ZIP/Postal',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::POSTAL,
			),
			'province' => array(
				'label' => 'State/Province',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::PROVINCE,
			),
			'street' => array(
				'label' => 'Street',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::STREET,
			),
			'updated' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_ContactOrg::UPDATED,
			),
			'website' => array(
				'label' => 'Website',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_ContactOrg::WEBSITE,
			),
		);
	
		$fields = SearchFields_ContactOrg::getFields();
		self::_getImportCustomFields($fields, $keys);
		
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_ContactOrg::NAME])) {
				$fields[DAO_ContactOrg::NAME] = 'New ' . $this->manifest->name;
			}
	
			// Default the created date to now
			if(!isset($fields[DAO_ContactOrg::CREATED]))
				$fields[DAO_ContactOrg::CREATED] = time();
			
			// Default the updated date to now
			if(!isset($fields[DAO_ContactOrg::UPDATED]))
				$fields[DAO_ContactOrg::UPDATED] = time();
	
			// Create
			$meta['object_id'] = DAO_ContactOrg::create($fields);
	
		} else {
			// Update
			DAO_ContactOrg::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
		
		// Aliases
		DAO_ContextAlias::set(CerberusContexts::CONTEXT_ORG, $meta['object_id'], DevblocksPlatform::parseCrlfString($fields[DAO_ContactOrg::NAME])); //  . "\n" . $aliases
	}
};