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

class DAO_Comment extends Cerb_ORMHelper {
	const COMMENT = 'comment';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED = 'created';
	const ID = 'id';
	const IS_MARKDOWN = 'is_markdown';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::COMMENT)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength(65535)
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_MARKDOWN)
			->bit()
			;
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
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

	static function create($fields, $also_notify_worker_ids=[], $file_ids=[]) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		$db->ExecuteMaster("INSERT INTO comment () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		/*
		 * Attachments
		 */
		
		if(!empty($file_ids)) {
			DAO_Attachment::addLinks(CerberusContexts::CONTEXT_COMMENT, $id, $file_ids);
		}
		
		/*
		 * Log the activity of a new comment being created
		 */

		if(isset($fields[self::CONTEXT]) && isset($fields[self::CONTEXT_ID])) {
			$context = Extension_DevblocksContext::get($fields[self::CONTEXT]);
			
			$meta = $context->getMeta($fields[self::CONTEXT_ID]);
			
			$entry = [
				//{{actor}} {{common.commented}} on {{object}} {{target}}
				'message' => 'activities.comment.create',
				'variables' => array(
					'object' => mb_convert_case($context->manifest->name, MB_CASE_LOWER),
					'target' => $meta['name'],
					),
				'urls' => array(
					'common.commented' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_COMMENT, $id),
					'target' => sprintf("ctx://%s:%d", $fields[self::CONTEXT], $fields[self::CONTEXT_ID]),
					)
			];
			CerberusContexts::logActivity('comment.create', $fields[self::CONTEXT], $fields[self::CONTEXT_ID], $entry, null, null, $also_notify_worker_ids);
			
			/*
			 * Send a new comment event
			 */
			
			$eventMgr = DevblocksPlatform::services()->event();
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'comment.create',
					[
						'comment_id' => $id,
						'fields' => $fields,
					]
				)
			);
			
			/*
			 * Trigger global VA behavior
			 */
			
			Event_CommentCreatedByWorker::trigger($id);
			
			/*
			 * Trigger group-level VA behavior
			 */
			
			switch($context->id) {
				case CerberusContexts::CONTEXT_TICKET:
					@$ticket_id = $fields[self::CONTEXT_ID];
	
					// [TODO] This is inefficient
					if(!empty($ticket_id)) {
						@$ticket = DAO_Ticket::get($ticket_id);
						Event_CommentOnTicketInGroup::trigger($id, $ticket_id, $ticket->group_id);
					}
					break;
			}
		}
		
		return $id;
	}
	
	static function update($ids, $fields) {
		$context = CerberusContexts::CONTEXT_COMMENT;
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'comment', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('comment', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_COMMENT;
		
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
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Comment[]
	 */
	static function getWhere($where=null, $sortBy='created', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, created, owner_context, owner_context_id, comment, is_markdown ".
			"FROM comment ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	// [TODO] Cache
	static function getContextIdsByContextAndIds($context, $ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = array($ids);
		
		if(empty($ids))
			return array();
		
		$sql = sprintf("SELECT DISTINCT context_id FROM comment WHERE context = %s AND id IN (%s)",
			$db->qstr($context),
			implode(',', $ids)
		);
		$rows = $db->GetArraySlave($sql);
		
		$ids = array();
		
		foreach($rows as $row) {
			$ids[] = intval($row['context_id']);
		}
		
		return $ids;
	}
	
	static function getByContext($context, $context_ids, $limit=0) {
		if(!is_array($context_ids)) {
			if(0 == strlen($context_ids)) {
				$context_ids = [];
			} else {
				$context_ids = [$context_ids];
			}
		}
		
		if(empty($context_ids))
			return [];

		return self::getWhere(
			sprintf("%s = %s AND %s IN (%s)",
				self::CONTEXT,
				Cerb_ORMHelper::qstr($context),
				self::CONTEXT_ID,
				implode(',', $context_ids)
			),
			DAO_Comment::CREATED,
			false,
			$limit ? $limit : null
		);
	}

	/**
	 * @param integer $id
	 * @return Model_Comment	 */
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
	 * @param resource $rs
	 * @return Model_Comment[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Comment();
			$object->id = intval($row['id']);
			$object->context = $row['context'];
			$object->context_id = intval($row['context_id']);
			$object->created = intval($row['created']);
			$object->is_markdown = $row['is_markdown'] ? true : false;
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->comment = $row['comment'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static public function count($from_context, $from_context_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneSlave(sprintf("SELECT count(*) FROM comment ".
			"WHERE context = %s AND context_id = %d",
			$db->qstr($from_context),
			$from_context_id
		));
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
			
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM comment WHERE context = %s AND context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Comments
		$db->ExecuteMaster(sprintf("DELETE FROM comment WHERE id IN (%s)", $ids_list));
		
		// Search index
		$search = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID, true);
		$search->delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_COMMENT,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function random($context=null, $context_id=null) {
		if(!empty($context)) {
			$comments = DAO_Comment::getByContext($context, $context_id);
			
			if(!empty($comments))
				return array_shift($comments);
		}
		
		return self::_getRandom('comment');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Comment::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Comment', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"comment.id as %s, ".
			"comment.context as %s, ".
			"comment.context_id as %s, ".
			"comment.created as %s, ".
			"comment.owner_context as %s, ".
			"comment.owner_context_id as %s, ".
			"comment.is_markdown as %s, ".
			"comment.comment as %s ",
				SearchFields_Comment::ID,
				SearchFields_Comment::CONTEXT,
				SearchFields_Comment::CONTEXT_ID,
				SearchFields_Comment::CREATED,
				SearchFields_Comment::OWNER_CONTEXT,
				SearchFields_Comment::OWNER_CONTEXT_ID,
				SearchFields_Comment::IS_MARKDOWN,
				SearchFields_Comment::COMMENT
			);
			
		$join_sql = "FROM comment ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Comment');
		
		$result = array(
			'primary_table' => 'comment',
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
			$object_id = intval($row[SearchFields_Comment::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(comment.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$db->ExecuteMaster("DELETE FROM attachment_link WHERE context = 'cerberusweb.contexts.comment' AND context_id NOT IN (SELECT id FROM comment)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' comment attachment_link records.');

		// Search indexes
		if(isset($tables['fulltext_comment_content'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_comment_content WHERE id NOT IN (SELECT id FROM comment)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_comment_content records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_COMMENT,
					'context_table' => 'comment',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function handleFormPost($context, $context_id) {
		@$comment = DevblocksPlatform::importGPC(@$_POST['comment'],'string','');
		@$comment_enabled = DevblocksPlatform::importGPC(@$_POST['comment_enabled'],'bit',0);
		@$comment_is_markdown = DevblocksPlatform::importGPC(@$_POST['comment_is_markdown'],'bit',0);
		@$comment_file_ids = DevblocksPlatform::importGPC(@$_POST['comment_file_ids'],'array',[]);
		
		if(!$comment_enabled)
			return null;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if($comment_file_ids && !$comment)
			throw new Exception_DevblocksAjaxValidationError("A comment is required when attaching files.", "comment");
		
		if($context_id && $comment && $active_worker->hasPriv(sprintf("contexts.%s.comment", $context))) {
			$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
			
			$fields = [
				DAO_Comment::CREATED => time(),
				DAO_Comment::CONTEXT => $context,
				DAO_Comment::CONTEXT_ID => $context_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::IS_MARKDOWN => $comment_is_markdown,
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
				DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
			];
			$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			
			if($comment_file_ids) {
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_COMMENT, $comment_id, $comment_file_ids);
			}
			
			return $comment_id;
		}
		
		return null;
	}
};

class SearchFields_Comment extends DevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const CREATED = 'c_created';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const IS_MARKDOWN = 'c_is_markdown';
	const COMMENT = 'c_comment';
	
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	const VIRTUAL_ATTACHMENTS_SEARCH = '*_attachments_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_TARGET = '*_target';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'comment.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_COMMENT => new DevblocksSearchFieldContextKeys('comment.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromFulltextField($param, Search_CommentContent::ID, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_ATTACHMENTS_SEARCH:
				return self::_getWhereSQLFromAttachmentsField($param, CerberusContexts::CONTEXT_COMMENT, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_COMMENT, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_COMMENT)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'owner_context', 'owner_context_id');
				break;
				
			case self::VIRTUAL_TARGET:
				return self::_getWhereSQLFromContextAndID($param, 'context', 'context_id');
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
			case 'author':
				$key = 'author';
				$search_key = 'author';
				$owner_field = $search_fields[SearchFields_Comment::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_Comment::OWNER_CONTEXT_ID];
				
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
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('author'),
				];
				break;
				
			case 'on':
				$key = 'on';
				$search_key = 'on';
				$owner_field = $search_fields[SearchFields_Comment::CONTEXT];
				$owner_id_field = $search_fields[SearchFields_Comment::CONTEXT_ID];
				
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
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('on'),
				];
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Comment::ID:
				$models = DAO_Comment::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_COMMENT);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
				
			case 'author':
			case 'on':
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
			self::ID => new DevblocksSearchField(self::ID, 'comment', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'comment', 'context', null, null, true),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'comment', 'context_id', null, null, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'comment', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'comment', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'comment', 'owner_context_id', null, null, true),
			self::IS_MARKDOWN => new DevblocksSearchField(self::IS_MARKDOWN, 'comment', 'is_markdown', $translate->_('common.format.markdown'), Model_CustomField::TYPE_CHECKBOX, true),
			self::COMMENT => new DevblocksSearchField(self::COMMENT, 'comment', 'comment', $translate->_('common.comment'), Model_CustomField::TYPE_MULTI_LINE, true),
			
			self::VIRTUAL_ATTACHMENTS_SEARCH => new DevblocksSearchField(self::VIRTUAL_ATTACHMENTS_SEARCH, '*', 'attachments_search', null, null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.actor'), null, false),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', $translate->_('common.target'), null, false),
				
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
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

class Search_CommentContent extends Extension_DevblocksSearchSchema {
	const ID = 'cerberusweb.search.schema.comment_content';
	
	public function getNamespace() {
		return 'comment_content';
	}
	
	public function getAttributes() {
		return array(
			'context_crc32' => 'uint4',
		);
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the engine can tell us where the index left off
		if(isset($meta['max_id']) && $meta['max_id']) {
			$this->setParam('last_indexed_id', $meta['max_id']);
		
		// If the index has a delta, start from the current record
		} elseif($meta['is_indexed_externally']) {
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
				if(null != ($last_comments = DAO_Comment::getWhere('id is not null', 'id', false, 1))
					&& is_array($last_comments)
					&& null != ($last_comment = array_shift($last_comments))) {
						$this->setParam('last_indexed_id', $last_comment->id);
						$this->setParam('last_indexed_time', $last_comment->created);
				} else {
					$this->setParam('last_indexed_id', 0);
					$this->setParam('last_indexed_time', 0);
				}
				break;
		}
	}
	
	public function query($query, $attributes=array(), $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		return $ids;
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$where = sprintf("%s > %d", DAO_Comment::ID, $id);
			$comments = DAO_Comment::getWhere($where, 'id', true, 100);
	
			if(empty($comments)) {
				$done = true;
				continue;
			}
			
			$count = 0;
			
			if(is_array($comments))
			foreach($comments as $comment) { /* @var $comment Model_Comment */
				$id = $comment->id;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));

				$content = $comment->comment;
				
				if(!empty($content)) {
					$content = $engine->truncateOnWhitespace($content, 5000);
					
					$doc = array(
						'content' => $content,
					);
					
					if(false === ($engine->index($this, $id, $doc, array('context_crc32' => sprintf("%u", crc32($comment->context))))))
						return false;
				}

				// Record our progress every 25th index
				if(++$count % 25 == 0) {
					if(!empty($id))
						$this->setParam('last_indexed_id', $id);
				}
			}
			
			// Record our index every batch
			if(!empty($id))
				$this->setParam('last_indexed_id', $id);
		}
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Model_Comment {
	public $id;
	public $context;
	public $context_id;
	public $created;
	public $owner_context;
	public $owner_context_id;
	public $comment;
	public $is_markdown = false;
	
	public function getContent() {
		if($this->is_markdown) {
			return DevblocksPlatform::purifyHTML(
				DevblocksPlatform::parseMarkdown($this->comment),
				true,
				true
			);
		} else {
			return $this->comment;
		}
	}
	
	public function getOwnerMeta() {
		if(null != ($ext = Extension_DevblocksContext::get($this->owner_context))) {
			$meta = $ext->getMeta($this->owner_context_id);
			$meta['context'] = $this->owner_context;
			$meta['context_ext'] = $ext;
			return $meta;
		}
	}
	
	public function getActorDictionary() {
		$models = CerberusContexts::getModels($this->owner_context, [$this->owner_context_id]);
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $this->owner_context);
		
		if(!isset($dicts[$this->owner_context_id]))
			return false;
		
		return $dicts[$this->owner_context_id];
	}
	
	public function getTargetDictionary() {
		$models = CerberusContexts::getModels($this->context, [$this->context_id]);
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $this->context);
		
		if(!isset($dicts[$this->context_id]))
			return false;
		
		return $dicts[$this->context_id];
	}
	
	public function getTargetContext($as_instance=true) {
		if(false == ($context_ext = Extension_DevblocksContext::get($this->context, $as_instance)))
			return false;
		
		return $context_ext;
	}
	
	function getAttachments() {
		return DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_COMMENT, $this->id);
	}
	
	function getTimeline($is_ascending=true, $target_id=0, &$start_index=0) {
		// Load all the comments on the parent record
		$timeline = DAO_Comment::getByContext($this->context, $this->context_id);
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			$a_time = intval($a->created);
			$b_time = intval($b->created);
			
			if($a_time > $b_time) {
				return ($is_ascending) ? 1 : -1;
			} else if ($a_time < $b_time) {
				return ($is_ascending) ? -1 : 1;
			} else {
				return 0;
			}
		});
		
		if($target_id) {
			if(false !== ($pos = array_search($target_id, array_column($timeline, 'id'))))
				$start_index = $pos;
		}
		
		return $timeline;
	}
};

class View_Comment extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'comment';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translate('common.comments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Comment::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Comment::CREATED,
			SearchFields_Comment::VIRTUAL_TARGET,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Comment::COMMENT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Comment::OWNER_CONTEXT,
			SearchFields_Comment::OWNER_CONTEXT_ID,
			SearchFields_Comment::VIRTUAL_ATTACHMENTS_SEARCH,
			SearchFields_Comment::VIRTUAL_CONTEXT_LINK,
			SearchFields_Comment::VIRTUAL_HAS_FIELDSET,
			SearchFields_Comment::VIRTUAL_OWNER,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Comment::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Comment');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Comment', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Comment', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Comment::VIRTUAL_OWNER:
				case SearchFields_Comment::VIRTUAL_TARGET:
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
		$context = CerberusContexts::CONTEXT_COMMENT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_Comment::OWNER_CONTEXT, DAO_Comment::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_Comment::CONTEXT, DAO_Comment::CONTEXT_ID, 'target_context[]');
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
		$search_fields = SearchFields_Comment::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Comment::FULLTEXT_COMMENT_CONTENT),
				),
			'attachments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array(),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ATTACHMENT, 'q' => ''],
					]
				),
			'comment' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Comment::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Comment::CREATED),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Comment::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_COMMENT],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Comment::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_COMMENT, 'q' => ''],
					]
				),
			'isMarkdown' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Comment::IS_MARKDOWN),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Comment::VIRTUAL_CONTEXT_LINK);
		
		// author.*
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('author', $fields, 'search', SearchFields_Comment::VIRTUAL_OWNER);
		
		// on.*
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('on', $fields, 'search', SearchFields_Comment::VIRTUAL_TARGET);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_COMMENT, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['comment']['examples'] = $ft_examples;
		}
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'attachments':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Comment::VIRTUAL_ATTACHMENTS_SEARCH);
				break;
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
				
			default:
				if($field == 'author' || DevblocksPlatform::strStartsWith($field, 'author.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'author', SearchFields_Comment::VIRTUAL_OWNER);
					
				if($field == 'on' || DevblocksPlatform::strStartsWith($field, 'on.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'on', SearchFields_Comment::VIRTUAL_TARGET);
					
				if($field == 'links' || DevblocksPlatform::strStartsWith($field, 'links.'))
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
		
		// Data
		$results = $this->getData();
		$tpl->assign('results', $results);

		// If we're displaying VIRTUAL_TARGET, bulk load the contexts
		if(in_array(SearchFields_Comment::VIRTUAL_TARGET, $this->view_columns)) {
			$targets = array();
			
			foreach($results[0] as $result) {
				if(!isset($targets[$result[SearchFields_Comment::CONTEXT]]))
					$targets[$result[SearchFields_Comment::CONTEXT]] = [];
				
				$targets[$result[SearchFields_Comment::CONTEXT]][$result[SearchFields_Comment::CONTEXT_ID]] = null;
			}
			
			foreach($targets as $ctx => $ids) {
				if(false == ($ext = Extension_DevblocksContext::get($ctx)))
					continue;
				
				$models = $ext->getModelObjects(array_keys($ids));
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $ctx);
				$targets[$ctx] = $dicts;
			}
			
			$tpl->assign('targets', $targets);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_COMMENT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/comments/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Comment::VIRTUAL_ATTACHMENTS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.attachments')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Author', 'Authors', 'Author is');
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				$this->_renderVirtualContextLinks($param, 'On', 'On', 'On');
				break;
		}
	}

	function getFields() {
		return SearchFields_Comment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::COMMENT:
			case SearchFields_Comment::OWNER_CONTEXT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::OWNER_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Comment::CREATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Comment::IS_MARKDOWN:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				@$options = DevblocksPlatform::importGPC($_POST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				@$options = DevblocksPlatform::importGPC($_POST['target_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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

class Context_Comment extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.comment';
	
	// Anyone can read a comment
	public static function isReadableByActor($models, $actor) {
		return CerberusContexts::allowEverything($models);
	}
	
	// Only a superuser or the author of the comment can edit it
	public static function isWriteableByActor($models, $actor) {
		$context = CerberusContexts::CONTEXT_COMMENT;
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return false;
		
		if(CerberusContexts::isActorAnAdmin($actor)) {
			return CerberusContexts::allowEverything($models);
		}
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, $context)))
			return false;
		
		$results = array_fill_keys(array_keys($dicts), false);
		$workers = DAO_Worker::getAllActive();
			
		foreach($dicts as $id => $dict) {
			// If the actor is the author
			if($dict->author__context == $actor->_context && $dict->author_id == $actor->id)
				if(false != (@$worker = $workers[$actor->id]))
					// And they have permission to edit their own comments
					if($worker->hasPriv('contexts.cerberusweb.contexts.comment.update'))
						$results[$id] = true;
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return current($results);
		}
	}
	
	function getRandom() {
		return DAO_Comment::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=comment&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Comment();
		
		$properties['author'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.author'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		);
		
		$properties['created'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['is_markdown'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.comment.is_markdown'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_markdown,
		);
		
		$properties['target'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.target'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->context_id,
			'params' => [
				'context' => $model->context,
			],
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$comment = DAO_Comment::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		
		return array(
			'id' => $comment->id,
			'name' => '',
			'permalink' => $url,
			'updated' => $comment->created,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'created',
			'author__label',
			'target__label',
		);
	}

	function getContext($comment, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Comment:';
		
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_COMMENT);

		// Polymorph
		if(is_numeric($comment)) {
			$comment = DAO_Comment::get($comment);
		} elseif($comment instanceof Model_Comment) {
			// It's what we want already.
		} elseif(is_array($comment)) {
			$comment = Cerb_ORMHelper::recastArrayToModel($comment, 'Model_Comment');
		} else {
			$comment = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.DevblocksPlatform::translate('common.id', DevblocksPlatform::TRANSLATE_UPPER),
			'comment' => $prefix.DevblocksPlatform::translate('common.content', DevblocksPlatform::TRANSLATE_CAPITALIZE),
			'created' => $prefix.DevblocksPlatform::translate('common.created', DevblocksPlatform::TRANSLATE_CAPITALIZE),
			'author__label' => $prefix.DevblocksPlatform::translate('common.author', DevblocksPlatform::TRANSLATE_CAPITALIZE),
			'target__label' => $prefix.DevblocksPlatform::translate('common.target', DevblocksPlatform::TRANSLATE_CAPITALIZE),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'comment' => Model_CustomField::TYPE_MULTI_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'author__label' => 'context_url',
			'target__label' => 'context_url',
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_COMMENT;
		$token_values['_types'] = $token_types;
		
		if($comment) {
			$token_values['_loaded'] = true;
			$label = trim(mb_ereg_replace(' {2,}',' ', str_replace(
				["\t","\r","\n"],
				["  ","",""],
				$comment->comment
			)));
			$token_values['_label'] = mb_strlen($label) > 128 ? (mb_substr($label, 0, 128) . '...') : $label;
			$token_values['id'] = $comment->id;
			$token_values['is_markdown'] = $comment->is_markdown ? 1 : 0;
			$token_values['created'] = $comment->created;
			$token_values['author__context'] = $comment->owner_context;
			$token_values['author_id'] = $comment->owner_context_id;
			$token_values['target__context'] = $comment->context;
			$token_values['target_id'] = $comment->context_id;
			$token_values['comment'] = $comment->comment;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($comment, $token_values);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'author__context' => DAO_Comment::OWNER_CONTEXT,
			'author_id' => DAO_Comment::OWNER_CONTEXT_ID,
			'comment' => DAO_Comment::COMMENT,
			'created' => DAO_Comment::CREATED,
			'id' => DAO_Comment::ID,
			'is_markdown' => DAO_Comment::IS_MARKDOWN,
			'links' => '_links',
			'target__context' => DAO_Comment::CONTEXT,
			'target_id' => DAO_Comment::CONTEXT_ID,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['author__context']['notes'] = "The [record type](/docs/records/#record-type) of the comment's author";
		$keys['author_id']['notes'] = "The ID of the comment's author";
		$keys['comment']['notes'] = "The text of the comment";
		$keys['is_markdown']['notes'] = "`0`=plaintext, `1`=Markdown";
		$keys['target__context']['notes'] = "The [record type](/docs/records/#record-type) of the target record";
		$keys['target_id']['notes'] = "The ID of the target record";
		
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
		
		$context = CerberusContexts::CONTEXT_COMMENT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
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
		$view->name = DevblocksPlatform::translate('common.comments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		$view->renderSortBy = SearchFields_Comment::CREATED;
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
		$view->name = DevblocksPlatform::translate('common.comments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Comment::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		$context = CerberusContexts::CONTEXT_COMMENT;
		$model = null;
		
		if(!empty($context_id)) {
			$model = DAO_Comment::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(!isset($model)) {
				$model = new Model_Comment();
			}
			
			// Handle '$edit'
			if(empty($context_id) && !empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token, 2);
					
					if($v)
					switch($k) {
						case 'context':
							if(false != ($ext = Extension_DevblocksContext::get($v)))
								$model->context = $ext->id;
							break;
							
						case 'context.id':
							$model->context_id = intval($v);
							break;
					}
				}
			}
			
			$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$attachments = DAO_Attachment::getByContextIds($context, $context_id);
			$tpl->assign('attachments', $attachments);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/comments/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};