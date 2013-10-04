<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class DAO_Comment extends Cerb_ORMHelper {
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED = 'created';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const COMMENT = 'comment';

	static function create($fields, $also_notify_worker_ids=array(), $file_ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO comment () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		/*
		 * Attachments
		 */
		
		if(!empty($file_ids))
		foreach($file_ids as $file_id) {
			DAO_AttachmentLink::create(intval($file_id), CerberusContexts::CONTEXT_COMMENT, $id);
		}
		
		/*
		 * Log the activity of a new comment being created
		 */
		
		$context = DevblocksPlatform::getExtension($fields[self::CONTEXT], true); /* @var $context Extension_DevblocksContext */
		$meta = $context->getMeta($fields[self::CONTEXT_ID]);
		
		$entry = array(
			//{{actor}} commented on {{object}} {{target}}: {{content}}
			'message' => 'activities.comment.create',
			'variables' => array(
				'object' => mb_convert_case($context->manifest->name, MB_CASE_LOWER),
				'target' => $meta['name'],
				'content' => $fields[self::COMMENT],
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%d", $fields[self::CONTEXT], $fields[self::CONTEXT_ID]),
				)
		);
		CerberusContexts::logActivity('comment.create', $fields[self::CONTEXT], $fields[self::CONTEXT_ID], $entry, null, null, $also_notify_worker_ids);
		
		/*
		 * Send a new comment event
		 */
		
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'comment.create',
				array(
					'comment_id' => $id,
					'fields' => $fields,
				)
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
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, created, owner_context, owner_context_id, comment ".
			"FROM comment ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
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
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Comment();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->created = $row['created'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->comment = $row['comment'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static public function count($from_context, $from_context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne(sprintf("SELECT count(*) FROM comment ".
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
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM comment WHERE context = %s AND context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Comments
		$db->Execute(sprintf("DELETE FROM comment WHERE id IN (%s)", $ids_list));
		
		// Search index
		Search_CommentContent::delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
	
	public static function random() {
		return self::_getRandom('comment');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Comment::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
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
		
		$has_multiple_values = false;
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_Comment', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'comment',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_COMMENT;
		$from_index = 'comment.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Comment::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(comment.owner_context = %s AND comment.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(comment.owner_context = %s)",
							Cerb_ORMHelper::qstr($context)
						);
					}
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(comment.context = %s AND comment.context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(comment.context = %s)",
							Cerb_ORMHelper::qstr($context)
						);
					}
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
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
			//($has_multiple_values ? 'GROUP BY comment.id ' : '').
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
			$object_id = intval($row[SearchFields_Comment::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT comment.id) " : "SELECT COUNT(comment.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		$tables = $db->metaTables();

		// Attachments
		$sql = "DELETE QUICK attachment_link FROM attachment_link LEFT JOIN comment ON (attachment_link.context_id=comment.id) WHERE attachment_link.context = 'cerberusweb.contexts.comment' AND comment.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' comment attachment_links.');
		
		// Search indexes
		if(isset($tables['fulltext_comment_content'])) {
			$sql = "DELETE QUICK fulltext_comment_content FROM fulltext_comment_content LEFT JOIN comment ON fulltext_comment_content.id = comment.id WHERE comment.id IS NULL";
			$db->Execute($sql);
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_comment_content records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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

class SearchFields_Comment implements IDevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const CREATED = 'c_created';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const COMMENT = 'c_comment';
	
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_TARGET = '*_target';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'comment', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'comment', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'comment', 'context_id', null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'comment', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'comment', 'owner_context', null),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'comment', 'owner_context_id', null),
			self::COMMENT => new DevblocksSearchField(self::COMMENT, 'comment', 'comment', $translate->_('common.comment'), Model_CustomField::TYPE_MULTI_LINE),
				
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', $translate->_('common.target'), null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_CommentContent {
	const ID = 'cerberusweb.search.schema.comment_content';
	
	public static function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($search = DevblocksPlatform::getSearchService())) {
			$logger->error("[Search] The search engine is misconfigured.");
			return;
		}
		
		$ns = 'comment_content';
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
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
					$content = $search->truncateOnWhitespace($content, 10000);
					$search->index($ns, $id, $content, true);
				}

				// Record our progress every 10th index
				if(++$count % 10 == 0) {
					if(!empty($id))
						DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $id);
				}
			}
			
			flush();
			
			// Record our index every batch
			if(!empty($id))
				DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $id);
		}
	}
	
	public static function delete($ids) {
		if(false == ($search = DevblocksPlatform::getSearchService())) {
			$logger->error("[Search] The search engine is misconfigured.");
			return;
		}
		
		$ns = 'comment_content';
		return $search->delete($ns, $ids);
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
	
	function getLinksAndAttachments() {
		return DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_COMMENT, $this->id);
	}
};

class View_Comment extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'comment';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.comments'), MB_CASE_TITLE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Comment::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Comment::VIRTUAL_OWNER,
			SearchFields_Comment::VIRTUAL_TARGET,
			SearchFields_Comment::CREATED,
		);

		$this->addColumnsHidden(array(
			SearchFields_Comment::COMMENT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::OWNER_CONTEXT,
			SearchFields_Comment::OWNER_CONTEXT_ID,
			SearchFields_Comment::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Comment::CONTEXT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::ID,
			SearchFields_Comment::OWNER_CONTEXT,
			SearchFields_Comment::OWNER_CONTEXT_ID,
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Comment', CerberusContexts::CONTEXT_COMMENT, $column);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_Comment', CerberusContexts::CONTEXT_COMMENT, $column, DAO_Comment::OWNER_CONTEXT, DAO_Comment::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_Comment', CerberusContexts::CONTEXT_COMMENT, $column, DAO_Comment::CONTEXT, DAO_Comment::CONTEXT_ID, 'context_link[]');
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/comments/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Comment::COMMENT:
			case SearchFields_Comment::OWNER_CONTEXT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Comment::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Comment::CREATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Comment::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_COMMENT);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
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
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
				
			case SearchFields_Comment::VIRTUAL_TARGET:
				$this->_renderVirtualContextLinks($param, 'Target', 'Targets');
				break;
		}
	}

	function getFields() {
		return SearchFields_Comment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Comment::COMMENT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Comment::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Comment::CREATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::OWNER_CONTEXT:
				@$contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$contexts);
				break;
				
			case SearchFields_Comment::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Comment::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
				
			case SearchFields_COMMENT::VIRTUAL_TARGET:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
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
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
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
				case 'example':
					//$change_fields[DAO_Comment::EXAMPLE] = 'some value';
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Comment::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Comment::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_Comment::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_COMMENT, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Comment extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;

			if(null == ($comment = DAO_Comment::get($context_id)))
				throw new Exception();
				
			if(null == ($defer_context = DevblocksPlatform::getExtension($comment->context, true)))
				throw new Exception();
				
			return $defer_context->authorize($comment->context_id, $worker);
			
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Comment::random();
	}
	
	function getMeta($context_id) {
		//$comment = DAO_Comment::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => '',
			'permalink' => '',
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
		} else {
			$comment = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'comment' => $prefix.$translate->_('common.content'),
			'created' => $prefix.$translate->_('common.created'),
			'owner_context' => $prefix.'Author Context',
			'author_label' => $prefix.'Author Label',
			'author_type' => $prefix.'Author Type',
			'author_url' => $prefix.'Author URL',
			'context' => $prefix.'Record Context',
			'record_label' => $prefix.'Record Label',
			'record_type' => $prefix.'Record Type Label',
			'record_url' => $prefix.'Record URL',
			//'record_watchers' => $prefix.'Record Watchers',
			'record_watchers_emails' => $prefix.'Record Watchers Email List',
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'comment' => Model_CustomField::TYPE_MULTI_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'owner_context' => Model_CustomField::TYPE_SINGLE_LINE,
			'author_label' => Model_CustomField::TYPE_SINGLE_LINE,
			'author_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'author_url' => Model_CustomField::TYPE_URL,
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_label' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
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
			$token_values['context'] = $comment->context;
			$token_values['context_id'] = $comment->context_id;
			$token_values['created'] = $comment->created;
			$token_values['owner_context'] = $comment->owner_context;
			$token_values['owner_context_id'] = $comment->owner_context_id;
			$token_values['record__context'] = $comment->context;
			$token_values['record_id'] = $comment->context_id;
			$token_values['comment'] = $comment->comment;
		}
		
		return true;
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'record_type':
				$context_ext = $dictionary['context'];
				
				if(null == ($ext = Extension_DevblocksContext::get($context_ext)))
					break;
				
				$values['record_type'] = $ext->manifest->name;
				break;
				
			case 'record_label':
			case 'record_url':
				if(null == ($ext = Extension_DevblocksContext::get($dictionary['context'])))
					break;
				
				if(null == ($meta = $ext->getMeta($dictionary['context_id'])))
					break;
				
				$values['record_label'] = $meta['name'];
				$values['record_url'] = $meta['permalink'];
				break;
				
			case 'record_watchers':
			case 'record_watchers_emails':
				$watchers = CerberusContexts::getWatchers($dictionary['context'], $dictionary['context_id']);;
				
				$watchers_list = array();
				
				if(is_array($watchers))
				foreach($watchers as $watcher) {
					$watchers_list[] = $watcher->email;
				}
				
				$values['record_watchers'] = $watchers;
				$values['record_watchers_emails'] = implode(', ', $watchers_list);
				break;
				
			case 'author_type':
				$context_ext = $dictionary['owner_context'];
				
				if(null == ($ext = Extension_DevblocksContext::get($context_ext)))
					break;
				
				$values['author_type'] = $ext->manifest->name;
				break;
				
			case 'author_label':
			case 'author_url':
				if(null == ($ext = Extension_DevblocksContext::get($dictionary['owner_context'])))
					break;
				
				if(null == ($meta = $ext->getMeta($dictionary['owner_context_id'])))
					break;
				
				$values['author_label'] = $meta['name'];
				$values['author_url'] = $meta['permalink'];
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
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Comments';
//		$view->view_columns = array(
//			SearchFields_Message::UPDATED_DATE,
//		);
		$view->addParams(array(
//			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_Comment::CREATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Comments';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Comment::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Comment::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
//		$params = array();
		
//		if(isset($options['filter_open']))
//			$params[] = new DevblocksSearchCriteria(SearchFields_Message::IS_COMPLETED,'=',0);

//		$view->addParams($params, false);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};