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

class DAO_Snippet extends Cerb_ORMHelper {
	const CONTENT = 'content';
	const CONTEXT = 'context';
	const ID = 'id';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PROMPTS_KATA = 'prompts_kata';
	const TITLE = 'title';
	const TOTAL_USES = 'total_uses';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// longtext
		$validation
			->addField(self::CONTENT)
			->string()
			->setMaxLength('32 bits')
			->setRequired(true)
			;
		// varchar(255)
		$validation
			->addField(self::CONTEXT)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(128)
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		// int(11)
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		// mediumtext
		$validation
			->addField(self::PROMPTS_KATA)
			->string()
			->setMaxLength('24 bits')
			;
		// varchar(255)
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->setNotEmpty(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::TOTAL_USES)
			->uint(4)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
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
		
		$sql = sprintf("INSERT INTO snippet () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_SNIPPET, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[DAO_Snippet::UPDATED_AT]))
			$fields[DAO_Snippet::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_SNIPPET;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_SNIPPET, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'snippet', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.snippet.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_SNIPPET, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('snippet', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_SNIPPET;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$owner_context = $fields[self::OWNER_CONTEXT];
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID]);
		
		// Verify that the actor can use this new owner
		if($owner_context) {
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
				return false;
			}
		}
		
		// Verify prompts KATA
		if(array_key_exists(self::PROMPTS_KATA, $fields)) {
			$kata = DevblocksPlatform::services()->kata();
			
			if(false === $kata->validate($fields[self::PROMPTS_KATA], CerberusApplication::kataSchemas()->snippetPrompts(), $error))
					return false;
		}
		
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
				case 'owner':
					list($context, $context_id) = array_pad(explode(':', $v), 2, null);
					
					if(empty($context))
						break;
					
					$change_fields[DAO_Snippet::OWNER_CONTEXT] = $context;
					$change_fields[DAO_Snippet::OWNER_CONTEXT_ID] = $context_id;
					break;
					
				case 'status':
					switch($v) {
						case 'delete':
							$deleted = true;
							break;
					}
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		if(!$deleted) {
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_SNIPPET, $ids);
			
			// Fields
			if(!empty($change_fields))
				DAO_Snippet::update($ids, $change_fields, false);
			
			// Custom Fields
			if(!empty($custom_fields))
				C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_SNIPPET, $custom_fields, $ids);
			
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_SNIPPET, $ids);
		} else {
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SNIPPET, $ids);
		
			DAO_Snippet::delete($ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Snippet[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, title, context, owner_context, owner_context_id, content, total_uses, updated_at, prompts_kata ".
			"FROM snippet ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param integer $id
	 * @return Model_Snippet
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
	 * @param mysqli_result|false $rs
	 * @return Model_Snippet[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Snippet();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->context = $row['context'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->prompts_kata = $row['prompts_kata'];
			$object->content = $row['content'];
			$object->total_uses = intval($row['total_uses']);
			$object->updated_at = intval($row['updated_at']);
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		// Search indexes
		if(isset($tables['fulltext_snippet'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_snippet WHERE id NOT IN (SELECT id FROM snippet)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_snippet records.');
		}
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids))
			$ids = array($ids);
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
			
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM snippet WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_SNIPPET,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function deleteByOwner($owner_context, $owner_context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($owner_context_ids))
			$owner_context_ids = array($owner_context_ids);
		
		$owner_context_ids = DevblocksPlatform::sanitizeArray($owner_context_ids, 'integer');
		
		$snippets = DAO_Snippet::getWhere(sprintf("owner_context = %s AND owner_context_id IN (%s)",
			$db->qstr($owner_context),
			implode(',', $owner_context_ids)
		));
		
		if(is_array($snippets)) {
			DAO_Snippet::delete(array_keys($snippets));
		}
	}
	
	public static function random() {
		return self::_getRandom('snippet');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Snippet::getFields();
		$active_worker = CerberusApplication::getActiveWorker();
		
		switch($sortBy) {
			case SearchFields_Snippet::VIRTUAL_OWNER:
				$sortBy = SearchFields_Snippet::OWNER_CONTEXT;
				
				if(!in_array($sortBy, $columns))
					$columns[] = $sortBy;
				break;
		}
		
		list($tables, $wheres,) = parent::_parseSearchParams($params, $columns, 'SearchFields_Snippet', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"snippet.id as %s, ".
			"snippet.title as %s, ".
			"snippet.context as %s, ".
			"snippet.owner_context as %s, ".
			"snippet.owner_context_id as %s, ".
			"snippet.content as %s, ".
			"snippet.total_uses as %s, ".
			"snippet.updated_at as %s ",
				SearchFields_Snippet::ID,
				SearchFields_Snippet::TITLE,
				SearchFields_Snippet::CONTEXT,
				SearchFields_Snippet::OWNER_CONTEXT,
				SearchFields_Snippet::OWNER_CONTEXT_ID,
				SearchFields_Snippet::CONTENT,
				SearchFields_Snippet::TOTAL_USES,
				SearchFields_Snippet::UPDATED_AT
			);
		
		if(isset($tables['snippet_usage_metric'])) {
			$select_sql .= sprintf(", CAST((SELECT SUM(sum) FROM metric_value WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.snippet.uses') AND granularity = 86400 AND dim0_value_id=snippet.id AND dim1_value_id=%d) AS signed) AS %s ", $active_worker->id, SearchFields_Snippet::USE_HISTORY_MINE);
		}
		
		$join_sql = " FROM snippet ";
		
		$where_sql = ''.
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Snippet');
		
		$result = array(
			'primary_table' => 'snippet',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			SearchFields_Snippet::ID,
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

class SearchFields_Snippet extends DevblocksSearchFields {
	const ID = 's_id';
	const TITLE = 's_title';
	const CONTEXT = 's_context';
	const OWNER_CONTEXT = 's_owner_context';
	const OWNER_CONTEXT_ID = 's_owner_context_id';
	const CONTENT = 's_content';
	const TOTAL_USES = 's_total_uses';
	const UPDATED_AT = 's_updated_at';
	const PROMPTS_KATA = 's_prompts_kata';
	
	const USE_HISTORY_MINE = 'suh_my_uses';
	
	// Fulltexts
	const FULLTEXT_SNIPPET = 'ft_snippet';
	
	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_USABLE_BY = '*_usable_by';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'snippet.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_SNIPPET => new DevblocksSearchFieldContextKeys('snippet.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_SNIPPET, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_SNIPPET), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'snippet.owner_context', 'snippet.owner_context_id');
				
			case SearchFields_Snippet::FULLTEXT_SNIPPET:
				return self::_getWhereSQLFromFulltextField($param, Search_Snippet::ID, self::getPrimaryKey());
				
			case self::USE_HISTORY_MINE:
				return self::_getWhereSQLForMyUses($param, self::getPrimaryKey());
				
			case self::VIRTUAL_USABLE_BY:
				return self::_getWhereSQLForUsableBy($param, self::getPrimaryKey());
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static private function _getWhereSQLForMyUses($param, $pkey) {
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return '0';
		
		$query = trim($param->value, '()');
		
		$fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$schema = [
			'hits' => new DevblocksSearchField('hits', null, 'SUM(sum)', null, null, false),
			'date' => new DevblocksSearchField('date', null, 'bin', null, null, false),
		];
		
		$sql_wheres = [
			sprintf('dim1_value_id = %d', $active_worker)
		];
		$sql_havings = [];
		
		if(1 == count($fields) && 'text' == $fields[0]->key)
			$fields[0]->key = 'hits';
		
		foreach($fields as $field) {
			switch($field->key) {
				case 'hits':
					$param = DevblocksSearchCriteria::getNumberParamFromTokens('hits', $field->tokens);
					$sql_havings[] = $param->getWhereSQL($schema, null);
					break;
					
				case 'date':
					$param = DevblocksSearchCriteria::getDateParamFromTokens('date', $field->tokens);
					$sql_wheres[] = $param->getWhereSQL($schema, null);
					break;
			}
		}
		
		return sprintf(" id IN (SELECT dim0_value_id FROM metric_value WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.snippet.uses') AND granularity = 86400 AND %s GROUP BY dim0_value_id %s)",
			implode(' AND ', $sql_wheres),
			$sql_havings ? ('HAVING ' . implode(' AND ', $sql_havings)) : ''
		);
	}
	
	static private function _getWhereSQLForUsableBy($param, $pkey) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			if(!is_array($param->value))
				return '0';
			
			$actor_context = $param->value['context'];
			$actor_id = $param->value['id'];
			
			if(empty($actor_context) || empty($actor_id))
				return '0';
			
			$worker_group_ids = array_keys(DAO_Group::getByMembers($actor_id));
			$worker_role_ids = array_keys(DAO_WorkerRole::getReadableBy($actor_id));
			
			$sql = sprintf(
				"(".
				"(owner_context = %s AND owner_context_id = 0) ". // app
				"OR (owner_context = %s AND owner_context_id = %d) ". // worker
				"OR (owner_context = %s AND owner_context_id IN (%s)) ". // group
				"OR (owner_context = %s AND owner_context_id IN (%s)) ". // role
				")"
				,
				Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_APPLICATION),
				Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKER),
				$actor_id,
				Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_GROUP),
				implode(',', $worker_group_ids ?: [-1]),
				Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_ROLE),
				implode(',', $worker_role_ids ?: [-1])
			);
			
			return $sql;
		}
		
		return '0';
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_Snippet::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_Snippet::OWNER_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':',%s.%s,%s.%s)",
						Cerb_ORMHelper::escape($owner_field->db_table),
						Cerb_ORMHelper::escape($owner_field->db_column),
						Cerb_ORMHelper::escape($owner_id_field->db_table),
						Cerb_ORMHelper::escape($owner_id_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('owner'),
				];
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Snippet::CONTEXT:
				$label_map = parent::_getLabelsForKeyContextValues();
				if(in_array('', $values))
					$label_map[''] = DevblocksPlatform::translate('common.text.plain');
				return $label_map;
				break;
				
			case SearchFields_Snippet::ID:
				$models = DAO_Snippet::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				break;
				
			case 'owner':
				return self::_getLabelsForKeyContextAndIdValues($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'snippet', 'id', $translate->_('common.id'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'snippet', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'snippet', 'context', $translate->_('common.type'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'snippet', 'owner_context', $translate->_('dao.snippet.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'snippet', 'owner_context_id', $translate->_('dao.snippet.owner_context_id'), null, true),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'snippet', 'content', $translate->_('common.content'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::TOTAL_USES => new DevblocksSearchField(self::TOTAL_USES, 'snippet', 'total_uses', $translate->_('dao.snippet.total_uses'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'snippet', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::USE_HISTORY_MINE => new DevblocksSearchField(self::USE_HISTORY_MINE, 'snippet_usage_metric', 'uses', $translate->_('dao.snippet_use_history.uses.mine'), Model_CustomField::TYPE_NUMBER, true),
			
			self::FULLTEXT_SNIPPET => new DevblocksSearchField(self::FULLTEXT_SNIPPET, 'ft', 'snippet', $translate->_('common.search.fulltext'), 'FT', false),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
			self::VIRTUAL_USABLE_BY => new DevblocksSearchField(self::VIRTUAL_USABLE_BY, '*', 'usable_by', null, null, false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_SNIPPET]->ft_schema = Search_Snippet::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_Snippet extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.snippet';
	
	public function getNamespace() {
		return 'snippet';
	}
	
	public function getAttributes() {
		return [];
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
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_Snippet::UPDATED_AT,
				$ptr_time,
				DAO_Snippet::ID,
				$id
			);
			$snippets = DAO_Snippet::getWhere($where, array(DAO_Snippet::UPDATED_AT, DAO_Snippet::ID), array(true, true), 100);

			if(empty($snippets)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			foreach($snippets as $snippet) { /* @var $snippet Model_Snippet */
				$id = $snippet->id;
				$ptr_time = $snippet->updated_at;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array(
					'content' => implode("\n", array(
						$snippet->title,
						$snippet->content,
					))
				);
				
				if(false === ($engine->index($this, $id, $doc)))
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

class Model_Snippet {
	public $content;
	public $context;
	public $id;
	public $owner_context;
	public $owner_context_id;
	public $prompts_kata;
	public $title;
	public $total_uses;
	public $updated_at;
	
	public function getPrompts() {
		$kata = DevblocksPlatform::services()->kata();
		
		if(!$this->prompts_kata)
			return [];
		
		$error = null;
		
		$tree = $kata->parse($this->prompts_kata, $error);
		
		$prompts = $kata->formatTree($tree);
		
		foreach($prompts as $prompt_key => &$prompt) {
			list($prompt_type, $prompt_name) = array_pad(explode('/', $prompt_key, 2), 2, null);
			$prompt['type'] = $prompt_type;
			$prompt['name'] = $prompt_name;
			
			// Sanitize
			if(array_key_exists('default', $prompt) && !is_scalar($prompt['default'])) {
				$prompt['default'] = '';
			}
			
			if(array_key_exists('required', $prompt)) {
				$prompt['required'] = DevblocksPlatform::services()->string()->toBool($prompt['required']);
			} else {
				$prompt['required'] = true;
			}
		}
		
		return $prompts;
	}
	
	public function getContextLabel() {
		if(empty($this->context))
			return DevblocksPlatform::translateCapitalized('common.text.plain');
		
		if(false == ($context_mft = Extension_DevblocksContext::get($this->context, false)))
			return null;
		
		return $context_mft->name;
	}
	
	public function incrementUse($worker_id) {
		return DAO_Snippet::incrementUse($this->id, $worker_id);
	}
};

class View_Snippet extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'snippet';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.snippets');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Snippet::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Snippet::TITLE,
			SearchFields_Snippet::CONTEXT,
			SearchFields_Snippet::VIRTUAL_OWNER,
			SearchFields_Snippet::USE_HISTORY_MINE,
			SearchFields_Snippet::TOTAL_USES,
			SearchFields_Snippet::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Snippet::ID,
			SearchFields_Snippet::CONTENT,
			SearchFields_Snippet::OWNER_CONTEXT,
			SearchFields_Snippet::OWNER_CONTEXT_ID,
			SearchFields_Snippet::FULLTEXT_SNIPPET,
			SearchFields_Snippet::VIRTUAL_CONTEXT_LINK,
			SearchFields_Snippet::VIRTUAL_HAS_FIELDSET,
			SearchFields_Snippet::VIRTUAL_USABLE_BY,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Snippet::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Snippet');
		
		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Snippet', $ids);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Snippet::CONTEXT:
					$pass = true;
					break;
					
				case SearchFields_Snippet::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Snippet::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Snippet::VIRTUAL_OWNER:
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
		$context = CerberusContexts::CONTEXT_SNIPPET;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Snippet::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_Snippet::CONTEXT:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Snippet::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'contexts[]');
				break;
				
			case SearchFields_Snippet::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_Snippet::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_Snippet::OWNER_CONTEXT, DAO_Snippet::OWNER_CONTEXT_ID, 'owner_context[]');
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
		$search_fields = SearchFields_Snippet::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Snippet::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Snippet::CONTENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Snippet::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_SNIPPET],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Snippet::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_SNIPPET, 'q' => ''],
					]
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Snippet::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'myUses' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_Snippet::USE_HISTORY_MINE],
					'examples' => [
						'>10',
						'(hits:>10 date:"-30 days")',
					],
				),
			'totalUses' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Snippet::TOTAL_USES),
				),
			'type' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Snippet::CONTEXT),
					'examples' => array(
						'plaintext',
						'ticket',
						'[plaintext,ticket]',
						'![plaintext]',
					),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Snippet::UPDATED_AT),
				),
			'usableBy.worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Snippet::VIRTUAL_USABLE_BY),
					'examples' => [
						'me',
					]
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_Snippet::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Snippet::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_SNIPPET, $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Snippet::ID))) {
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
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
				
			case 'myUses':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Snippet::USE_HISTORY_MINE);
				break;
			
			case 'type':
				$field_key = SearchFields_Snippet::CONTEXT;
				$oper = null;
				$patterns = [];
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $patterns);
				
				$contexts = Extension_DevblocksContext::getAll(false);
				$values = [];
				
				if(is_array($patterns))
				foreach($patterns as $pattern) {
					if(in_array($pattern, array('plain', 'plaintext'))) {
						$values[''] = true;
						continue;
					}
					
					foreach($contexts as $context_id => $context) {
						if($context_id == $pattern || false !== stripos($context->name, $pattern))
							$values[$context_id] = true;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
			
			case 'usableBy.worker':
				$oper = $value = null;
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $value);
				$worker_id = intval($value);
				
				if(in_array(DevblocksPlatform::strLower($value),['me','self'])) {
					if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
						$worker_id = $active_worker->id;
					}
				}
				
				return new DevblocksSearchCriteria(
					SearchFields_Snippet::VIRTUAL_USABLE_BY,
					DevblocksSearchCriteria::OPER_CUSTOM,
					['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $worker_id]
				);
				break;
			
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_Snippet::VIRTUAL_OWNER);
				
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

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$placeholder_values = $this->getPlaceholderValues();
		
		// Are we translating snippet previews for certain contexts?
		if(isset($placeholder_values['dicts'])) {
			$tpl->assign('dicts', $placeholder_values['dicts']);

			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$tpl->assign('tpl_builder', $tpl_builder);
		}
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/snippets/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Snippet::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Snippet::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Snippet::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner is');
				break;
				
			case SearchFields_Snippet::VIRTUAL_USABLE_BY:
				if(!is_array($param->value) || !isset($param->value['context']))
					return;
				
				switch($param->value['context']) {
					case CerberusContexts::CONTEXT_WORKER:
						if(false == ($worker = DAO_Worker::get($param->value['id']))) {
							$worker_name = '(invalid worker)';
						} else {
							$worker_name = $worker->getName();
						}
						
						echo sprintf("Usable by %s <b>%s</b>",
							DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translate('common.worker', DevblocksPlatform::TRANSLATE_LOWER)),
							DevblocksPlatform::strEscapeHtml($worker_name)
						);
						break;
				}
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Snippet::CONTEXT:
				$label_map = SearchFields_CustomField::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Snippet::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Snippet::ID:
			case SearchFields_Snippet::TITLE:
			case SearchFields_Snippet::CONTENT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Snippet::TOTAL_USES:
			case SearchFields_Snippet::USE_HISTORY_MINE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Snippet::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Snippet::CONTEXT:
				$in_contexts = DevblocksPlatform::importGPC($_POST['contexts'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_Snippet::FULLTEXT_SNIPPET:
				$scope = DevblocksPlatform::importGPC($_POST['scope'] ?? null, 'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Snippet::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Snippet::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Snippet::VIRTUAL_OWNER:
				$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
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

class Context_Snippet extends Extension_DevblocksContext implements IDevblocksContextAutocomplete, IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.snippet';
	const URI = 'snippet';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_SNIPPET, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_SNIPPET, $models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=snippet&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_Snippet */
		
		if(is_null($model))
			$model = new Model_Snippet();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			]
		);
		
		$properties['context'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getContextLabel(),
		);
		
		$properties['total_uses'] = array(
			'label' => mb_ucfirst($translate->_('dao.snippet.total_uses')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->total_uses,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getRandom() {
		return DAO_Snippet::random();
	}
	
	function getMeta($context_id) {
		if(null == ($snippet = DAO_Snippet::get($context_id)))
			return [];
		
		return array(
			'id' => $context_id,
			'name' => $snippet->title,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
			'updated' => $snippet->updated_at,
		);
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
			'owner__label',
			'context',
			'total_uses',
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$as_worker = CerberusApplication::getActiveWorker();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'autocomplete_snippets';
		$defaults->class_name = 'View_Snippet';
		$defaults->is_ephemeral = true;
		
		if(false == ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults)))
			return [];
		
		// By owner
		$params = $view->getParamsFromQuickSearch($query . ' usableBy.worker:' . $as_worker->id);
		
		// Search by title
		$params[] = new DevblocksSearchCriteria(SearchFields_Snippet::TITLE,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%');
		
		$view->addParams($params, true);
		$view->view_columns = [
			SearchFields_Snippet::TITLE,
			SearchFields_Snippet::USE_HISTORY_MINE,
		];
		$view->renderSortBy = SearchFields_Snippet::USE_HISTORY_MINE;
		$view->renderSortAsc = false;
		$view->renderLimit = 25;
		$view->renderPage = 0;
		$view->renderTotal = false;
		$view->setAutoPersist(false);
		
		list($results,) = $view->getData();
		
		$list = [];

		if(is_array($results))
		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = sprintf("%s -- used %s",
				$row[SearchFields_Snippet::TITLE],
				((1 != $row[SearchFields_Snippet::USE_HISTORY_MINE]) ? (intval($row[SearchFields_Snippet::USE_HISTORY_MINE]) . ' times') : 'once')
			);
			$entry->value = $row[SearchFields_Snippet::ID];
			$entry->context = $row[SearchFields_Snippet::CONTEXT];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($snippet, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Snippet:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET);

		// Polymorph
		if(is_numeric($snippet)) {
			$snippet = DAO_Snippet::get($snippet);
		} elseif($snippet instanceof Model_Snippet) {
			// It's what we want already.
		} elseif(is_array($snippet)) {
			$snippet = Cerb_ORMHelper::recastArrayToModel($snippet, 'Model_Snippet');
		} else {
			$snippet = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'title' => $prefix.$translate->_('common.title'),
			'context' => $prefix.$translate->_('common.context'),
			'content' => $prefix.$translate->_('common.content'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'total_uses' => $prefix.$translate->_('dao.snippet.total_uses'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'owner__label' => 'context_url',
			'total_uses' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Snippet::ID;
		$token_values['_type'] = Context_Snippet::URI;
		
		$token_values['_types'] = $token_types;
		
		if($snippet) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $snippet->title;
			$token_values['content'] = $snippet->content;
			$token_values['context'] = $snippet->context;
			$token_values['id'] = $snippet->id;
			$token_values['owner__context'] = $snippet->owner_context;
			$token_values['owner_id'] = $snippet->owner_context_id;
			$token_values['title'] = $snippet->title;
			$token_values['total_uses'] = $snippet->total_uses;
			$token_values['updated_at'] = $snippet->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($snippet, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=snippet&id=%d-%s",$snippet->id, DevblocksPlatform::strToPermalink($snippet->title)), true);
		}

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'content' => DAO_Snippet::CONTENT,
			'context' => DAO_Snippet::CONTEXT,
			'id' => DAO_Snippet::ID,
			'links' => '_links',
			'owner__context' => DAO_Snippet::OWNER_CONTEXT,
			'owner_id' => DAO_Snippet::OWNER_CONTEXT_ID,
			'prompts_kata' => DAO_Snippet::PROMPTS_KATA,
			'title' => DAO_Snippet::TITLE,
			'total_uses' => DAO_Snippet::TOTAL_USES,
			'updated_at' => DAO_Snippet::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['content']['notes'] = "The [template](/docs/bots/scripting/) of the snippet";
		$keys['context']['notes'] = "The [record type](/docs/records/types/) to add the profile tab to";
		$keys['prompts_kata']['notes'] = "Prompted placeholders in [KATA](/docs/snippets/#prompts) format";
		$keys['title']['notes'] = "The name of the snippet";
		$keys['total_uses']['notes'] = "The total number of times this snippet has been used by all workers";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		
		switch($dict_key) {
			default:
				break;
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
		
		$context = CerberusContexts::CONTEXT_SNIPPET;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
				$values = array_merge($values, $defaults);
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
		$view->name = 'Snippets';
		
		// Restrict owners
		$view->setParamsRequiredQuery('usableBy.worker:' . $active_worker->id);
		
		$view->renderSortBy = SearchFields_Snippet::USE_HISTORY_MINE;
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
		$view->name = 'Snippets';

		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Snippet::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_SNIPPET;
		
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($model = DAO_Snippet::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$text = DevblocksPlatform::importGPC($_REQUEST['text'] ?? null, 'string', '');
			
			$model = new Model_Snippet();
			$model->id = 0;
			$model->content = $text;
			$model->prompts_kata = "# Add prompted placeholders here\n# (use Ctrl+Shift for autocompletion)\n";
		}
		
		if(!$context_id || $edit) {
			if($model && $model->id) {
				if(!Context_Snippet::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree();
			$tpl->assign('owners_menu', $owners_menu);
			
			// Contexts
			$contexts = Extension_DevblocksContext::getAll(false);
			$tpl->assign('contexts', $contexts);
			
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
			$tpl->assign('model', $model);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
