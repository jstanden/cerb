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

	static function create($fields, $also_notify_worker_ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO comment () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
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
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'comment.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		$result = array(
			'primary_table' => 'comment',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
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
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'comment', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'comment', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'comment', 'context_id', null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'comment', 'created', $translate->_('common.created')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'comment', 'owner_context', null),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'comment', 'owner_context_id', null),
			self::COMMENT => new DevblocksSearchField(self::COMMENT, 'comment', 'comment', $translate->_('common.comment')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		//}
		
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

class View_Comment extends C4_AbstractView {
	const DEFAULT_ID = 'comment';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Comments');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Comment::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Comment::ID,
			SearchFields_Comment::CONTEXT,
			SearchFields_Comment::CONTEXT_ID,
			SearchFields_Comment::CREATED,
			SearchFields_Comment::OWNER_CONTEXT,
			SearchFields_Comment::COMMENT,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Comment::search(
			array(),
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// [TODO] Set your template path
		$tpl->display('devblocks:/path/to/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::CREATED:
			case SearchFields_Comment::COMMENT:
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			default:
				echo '';
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
		return SearchFields_Comment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_Comment::ID:
			case SearchFields_Comment::CONTEXT:
			case SearchFields_Comment::CONTEXT_ID:
			case SearchFields_Comment::CREATED:
			case SearchFields_Comment::COMMENT:
			case 'placeholder_string':
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
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
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_Comment::EXAMPLE] = 'some value';
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Comment::search(
				array(),
				$this->params,
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
			
			DAO_Comment::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_Comment::ID, $custom_fields, $batch_ids);
			
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
			'comment' => $prefix.$translate->_('common.content'),
			'created|date' => $prefix.$translate->_('common.created'),
			'owner_context' => $prefix.'Author Context',
			'author_label' => $prefix.'Author Label',
			'author_type' => $prefix.'Author Type',
			'author_url' => $prefix.'Author URL',
			'context' => $prefix.'Record Context',
			'record_label' => $prefix.'Record Label',
			'record_type' => $prefix.'Record Type Label',
			'record_url' => $prefix.'Record URL',
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_COMMENT;
		
		if($comment) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $comment->comment;
			$token_values['id'] = $comment->id;
			$token_values['context'] = $comment->context;
			$token_values['context_id'] = $comment->context_id;
			$token_values['created'] = $comment->created;
			$token_values['owner_context'] = $comment->owner_context;
			$token_values['owner_context_id'] = $comment->owner_context_id;
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
			//SearchFields_Task::VIRTUAL_WATCHERS => new DevblocksSearchCriteria(SearchFields_Task::VIRTUAL_WATCHERS,'in',array($active_worker->id)),
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