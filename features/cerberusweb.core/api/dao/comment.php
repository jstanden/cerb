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

class DAO_Comment extends Cerb_ORMHelper {
	const COMMENT = 'comment';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED = 'created';
	const ID = 'id';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::COMMENT)
			->string()
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
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
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
		parent::_update($ids, 'comment', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('comment', $fields, $where);
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
		$sql = "SELECT id, context, context_id, created, owner_context, owner_context_id, comment ".
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
	
	static function getByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return array();

		return self::getWhere(sprintf("%s = %s AND %s IN (%s)",
			self::CONTEXT,
			Cerb_ORMHelper::qstr($context),
			self::CONTEXT_ID,
			implode(',', $context_ids)
		));
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
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Comment', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"comment.id as %s, ".
			"comment.context as %s, ".
			"comment.context_id as %s, ".
			"comment.created as %s, ".
			"comment.owner_context as %s, ".
			"comment.owner_context_id as %s, ".
			"comment.comment as %s ",
				SearchFields_Comment::ID,
				SearchFields_Comment::CONTEXT,
				SearchFields_Comment::CONTEXT_ID,
				SearchFields_Comment::CREATED,
				SearchFields_Comment::OWNER_CONTEXT,
				SearchFields_Comment::OWNER_CONTEXT_ID,
				SearchFields_Comment::COMMENT
			);
			
		$join_sql = "FROM comment ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Comment');
		
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
		
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
};

class SearchFields_Comment extends DevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const CREATED = 'c_created';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
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
	
	public $_email_record = null;
	
	public function getOwnerMeta() {
		if(null != ($ext = Extension_DevblocksContext::get($this->owner_context))) {
			$meta = $ext->getMeta($this->owner_context_id);
			$meta['context'] = $this->owner_context;
			$meta['context_ext'] = $ext;
			return $meta;
		}
	}
	
	public function getAuthorDictionary() {
		$values = $labels = [];
		$models = CerberusContexts::getModels($this->owner_context, [$this->owner_context_id]);
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $this->owner_context);
		
		if(!isset($dicts[$this->owner_context_id]))
			return false;
		
		return $dicts[$this->owner_context_id];
	}
	
	public function getTargetDictionary() {
		$values = $labels = [];
		$models = CerberusContexts::getModels($this->context, [$this->context_id]);
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $this->context);
		
		if(!isset($dicts[$this->context_id]))
			return false;
		
		return $dicts[$this->context_id];
	}
	
	function getAttachments() {
		return DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_COMMENT, $this->id);
	}
	
	function getTimeline($is_ascending=true) {
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
		
		$this->addParamsHidden(array(
			SearchFields_Comment::CONTEXT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::ID,
			SearchFields_Comment::OWNER_CONTEXT,
			SearchFields_Comment::OWNER_CONTEXT_ID,
			SearchFields_Comment::VIRTUAL_ATTACHMENTS_SEARCH,
			SearchFields_Comment::VIRTUAL_OWNER,
			SearchFields_Comment::VIRTUAL_TARGET,
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
				if('cf_' == substr($column,0,3)) {
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
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Comment::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_COMMENT, 'q' => ''],
					]
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// author.*
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('author', $fields);
		
		// on.*
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('on', $fields);
		
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

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::COMMENT:
			case SearchFields_Comment::OWNER_CONTEXT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::OWNER_CONTEXT_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Comment::CREATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_COMMENT);
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
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Comment::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				@$options = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['target_context'],'array',array());
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
	
	function getMeta($context_id) {
		$comment = DAO_Comment::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
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
		
		$translate = DevblocksPlatform::getTranslationService();
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
			$token_values['_label'] = $comment->comment;
			$token_values['id'] = $comment->id;
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
			'target__context' => DAO_Comment::CONTEXT,
			'target_id' => DAO_Comment::CONTEXT_ID,
		];
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
			case 'attachments':
				$results = DAO_Attachment::getByContextIds($context, $context_id);
				$objects = [];
				
				foreach($results as $attachment_id => $attachment) {
					$object = [
						'id' => $attachment_id,
						'file_name' => $attachment->name,
						'file_size' => $attachment->storage_size,
						'file_type' => $attachment->mime_type,
					];
					$objects[$attachment_id] = $object;
				}
				
				$values['attachments'] = $objects;
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
		$view->name = DevblocksPlatform::translate('common.comments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		$view->renderSortBy = SearchFields_Comment::CREATED;
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

		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = CerberusContexts::CONTEXT_COMMENT;
		
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
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/comments/peek_edit.tpl');
			
		} else {
			if(empty($model)) {
				$tpl->assign('error_message', "This comment no longer exists.");
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				return;
			}
			
			// Counts
			$activity_counts = array(
				//'comments' => DAO_Comment::count($context, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($model) {
				$timeline = $model->getTimeline();
				$start_index = null;
				
				// Find the current model in thetimeline
				foreach($timeline as $idx => $object) {
					if($object instanceof Model_Comment && $object->id == $model->id) {
						$start_index = $idx;
						break;
					}
				}
				
				$timeline_json = Page_Profiles::getTimelineJson($timeline, true, $start_index);
				$tpl->assign('timeline_json', $timeline_json);
			}
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::internal/comments/peek.tpl');
		}
	}
};