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

class DAO_Journal extends C4_ORMHelper {
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED = 'created';
	const ADDRESS_ID = 'address_id';
	const JOURNAL = 'journal';
	const ISPUBLIC = 'ispublic';
	const ISINTERNAL = 'isinternal';
	const STATE = 'state';

	static function create($fields, $also_notify_worker_ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("INSERT INTO `journal` () VALUES ()");
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		/*
		 * Log the activity of a new journal entry being created
		 */
		
		$context = DevblocksPlatform::getExtension($fields[self::CONTEXT], true); /* @var $context Extension_DevblocksContext */
		$meta = $context->getMeta($fields[self::CONTEXT_ID]);
		
		$entry = array(
			//{{actor}} wrote in journal of {{object}} {{target}}: {{content}}
			'message' => 'activities.journal.create',
			'variables' => array(
				'object' => mb_convert_case($context->manifest->name, MB_CASE_LOWER),
				'target' => $meta['name'],
				'content' => $fields[self::JOURNAL],
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%d", $fields[self::CONTEXT], $fields[self::CONTEXT_ID]),
				)
		);
		CerberusContexts::logActivity('journal.create', $fields[self::CONTEXT], $fields[self::CONTEXT_ID], $entry, null, null, $also_notify_worker_ids);
		
		/*
		 * Send a new journal event
		 */
		
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'journal.create',
				array(
					'journal_id' => $id,
					'fields' => $fields,
				)
			)
		);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'journal', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('journal', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Journal[]
	 */
	static function getWhere($where=null, $sortBy='created', $sortAsc=false, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, created, address_id, journal, ispublic, isinternal, state ".
			"FROM journal ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param boolean $includeInternals
	 * @return Model_Journal[]
	 */
	static function getPublic($includeInternals=false) {
		return ($includeInternals) 
			? self::getWhere(sprintf('%s = %u', self::ISPUBLIC, 1)) 
			: self::getWhere(sprintf('%s = %u AND %s = %u', self::ISPUBLIC, 1, self::ISINTERNAL, 0));
	}

	/**
	 * @param string $context
	 * @param integer $context_ids
	 * @return Model_Journal[]
	 */
	static function getByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return array();

		return self::getWhere(sprintf("%s = %s AND %s IN (%s)",
			self::CONTEXT,
			C4_ORMHelper::qstr($context),
			self::CONTEXT_ID,
			implode(',', $context_ids)
		));
	}

	/**
	 * @param integer $id
	 * @return Model_Journal	 */
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
	 * @return Model_Journal[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Journal();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->created = $row['created'];
			$object->address_id = $row['address_id'];
			$object->journal = $row['journal'];
			$object->ispublic = $row['ispublic'];
			$object->isinternal = $row['isinternal'];
			$object->state = $row['state'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static public function count($from_context, $from_context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne(sprintf("SELECT count(*) FROM journal ".
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
		
		$db->Execute(sprintf("DELETE FROM journal WHERE context = %s AND context_id IN (%s) ",
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
		
		// Journal
		$db->Execute(sprintf("DELETE FROM journal WHERE id IN (%s)", $ids_list));
		
		// Search index
		Search_JournalContent::delete($ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_JOURNAL,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('journal');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Journal::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"journal.id as %s, ".
			"journal.context as %s, ".
			"journal.context_id as %s, ".
			"journal.created as %s, ".
			"journal.address_id as %s, ".
			"journal.journal as %s, ".
			"journal.ispublic as %s, ".
			"journal.isinternal as %s, ".
			"journal.state as %s ",
				SearchFields_Journal::ID,
				SearchFields_Journal::CONTEXT,
				SearchFields_Journal::CONTEXT_ID,
				SearchFields_Journal::CREATED,
				SearchFields_Journal::ADDRESS_ID,
				SearchFields_Journal::JOURNAL,
				SearchFields_Journal::ISPUBLIC,
				SearchFields_Journal::ISINTERNAL,
				SearchFields_Journal::STATE
			);
			
		$join_sql = "FROM journal ";
		
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
			'primary_table' => 'journal',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	
	/**
	 * Search in journals
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
			//($has_multiple_values ? 'GROUP BY journal.id ' : '').
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
			$object_id = intval($row[SearchFields_Journal::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT journal.id) " : "SELECT COUNT(journal.id) ").
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
		$sql = "DELETE QUICK attachment_link FROM attachment_link LEFT JOIN journal ON (attachment_link.context_id=journal.id) WHERE attachment_link.context = 'cerberusweb.contexts.journal' AND journal.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' journal attachment_links.');
		
		// Search indexes
		if(isset($tables['fulltext_journal_content'])) {
			$sql = "DELETE QUICK fulltext_journal_content FROM fulltext_journal_content LEFT JOIN journal ON fulltext_journal_content.id = journal.id WHERE journal.id IS NULL";
			$db->Execute($sql);
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_journal_content records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_JOURNAL,
					'context_table' => 'journal',
					'context_key' => 'id',
				)
			)
		);
	}
};

class SearchFields_Journal implements IDevblocksSearchFields {
	const ID = 'j_id';
	const CONTEXT = 'j_context';
	const CONTEXT_ID = 'j_context_id';
	const CREATED = 'j_created';
	const ADDRESS_ID = 'j_address_id';
	const JOURNAL = 'j_journal';
	const ISPUBLIC = 'j_ispublic';
	const ISINTERNAL =  'j_isinternal';
	const STATE = 'j_state';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'journal', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'journal', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'journal', 'context_id', null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'journal', 'created', $translate->_('common.created')),
			self::ADDRESS_ID => new DevblocksSearchField(self::ADDRESS_ID, 'journal', 'address_id', $translate->_('common.email')),
			self::JOURNAL => new DevblocksSearchField(self::JOURNAL, 'journal', 'journal', $translate->_('common.journal')),
			self::ISPUBLIC => new DevblocksSearchField(self::ISPUBLIC, 'journal', 'ispublic', $translate->_('common.is_public')),
			self::ISINTERNAL => new DevblocksSearchField(self::ISINTERNAL, 'journal', 'isinternal', null),
			self::STATE => new DevblocksSearchField(self::STATE, 'journal', 'state', $translate->_('common.status'))
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

class Search_JournalContent {
	const ID = 'cerberusweb.search.schema.journal_content';
	
	public static function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($search = DevblocksPlatform::getSearchService())) {
			$logger->error("[Search] The search engine is misconfigured.");
			return;
		}
		
		$ns = 'journal_content';
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$where = sprintf("%s > %d", DAO_Journal::ID, $id);
			$journal = DAO_Journal::getWhere($where, 'id', true, 100);
	
			if(empty($journal)) {
				$done = true;
				continue;
			}
			
			$count = 0;
			
			if(is_array($journal))
			foreach($journal as $j) { /* @var $journal Model_Journal */
				$id = $j->id;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));

				$content = $j->journal;
				
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
		
		$ns = 'journal_content';
		return $search->delete($ns, $ids);
	}
};

class Model_Journal {
	public $id;
	public $context;
	public $context_id;
	public $created;
	public $address_id;
	public $journal;
	public $ispublic;
	public $isinternal;
	public $state;

	public $_email_record = null;
	
	public function getAddress() {
		// Cache repeated calls
		if(null == $this->_email_record) {
			$this->_email_record = DAO_Address::get($this->address_id);
		}
		
		return $this->_email_record;
	}
	
	function getLinksAndAttachments() {
		return DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_JOURNAL, $this->id);
	}
};

class View_Journal extends C4_AbstractView {
	const DEFAULT_ID = 'journal';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Journal');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Journal::CREATED;
		$this->renderSortAsc = FALSE;
		
		$this->addParamsDefault(array(
			new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT, DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_SERVER),
			new DevblocksSearchCriteria(SearchFields_Journal::ISPUBLIC, DevblocksSearchCriteria::OPER_EQ, '1'),
			new DevblocksSearchCriteria(SearchFields_Journal::ISINTERNAL, DevblocksSearchCriteria::OPER_EQ, '0')
		), TRUE);
		
		$this->getParams();

		$this->view_columns = array(
			SearchFields_Journal::ID,
			SearchFields_Journal::CONTEXT,
			SearchFields_Journal::CONTEXT_ID,
			SearchFields_Journal::CREATED,
			SearchFields_Journal::ADDRESS_ID,
			SearchFields_Journal::JOURNAL,
			SearchFields_Journal::ISPUBLIC,
			SearchFields_Journal::ISINTERNAL,
			SearchFields_Journal::STATE
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Journal::search(
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
			case SearchFields_Journal::CONTEXT:
			case SearchFields_Journal::JOURNAL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Journal::ID:
			case SearchFields_Journal::CONTEXT_ID:
			case SearchFields_Journal::ADDRESS_ID:
			case SearchFields_Journal::STATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Journal::ISINTERNAL:
			case SearchFields_Journal::ISPUBLIC:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Journal::CREATED:
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
		return SearchFields_Journal::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_Journal::CONTEXT:
			case SearchFields_Journal::JOURNAL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Journal::ID:
			case SearchFields_Journal::CONTEXT_ID:
			case SearchFields_Journal::ADDRESS_ID:
			case SearchFields_Journal::STATE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			case SearchFields_Journal::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_Journal::ISINTERNAL:
			case SearchFields_Journal::ISPUBLIC:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
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
					//$change_fields[DAO_JOURNAL::EXAMPLE] = 'some value';
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Journal::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Journal::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_Journal::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_Comment::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Journal extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;

			if(null == ($journal = DAO_Journal::get($context_id)))
				throw new Exception();
				
			if(null == ($defer_context = DevblocksPlatform::getExtension($journal->context, true)))
				throw new Exception();
				
			return $defer_context->authorize($journal->context_id, $worker);
			
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Journal::random();
	}
	
	function getMeta($context_id) {
		//$journal = DAO_Journal::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => '',
			'permalink' => '',
		);
	}

	function getContext($journal, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Journal:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_JOURNAL);

		// Polymorph
		if(is_numeric($journal)) {
			$journal = DAO_Journal::get($journal);
		} elseif($journal instanceof Model_Journal) {
			// It's what we want already.
		} else {
			$journal = null;
		}
		
		// Token labels
		$token_labels = array(
//			'completed|date' => $prefix.$translate->_('task.completed_date'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_JOURNAL;
		
		if($journal) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $journal->journal;
			$token_values['id'] = $journal->id;
			$token_values['context'] = $journal->context;
			$token_values['context_id'] = $journal->context_id;
			$token_values['created'] = $journal->created;
			$token_values['address_id'] = $journal->address_id;
			$token_values['journal'] = $journal->journal;
			$token_values['ispublic'] = $journal->ispublic;
			$token_values['isinternal'] = $journal->isinternal;
			$token_values['state'] = $journal->state;
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_JOURNAL;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
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
		$view->name = 'Journal';
//		$view->view_columns = array(
//			SearchFields_Message::UPDATED_DATE,
//		);
		$view->addParams(array(
//			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
			//SearchFields_Task::VIRTUAL_WATCHERS => new DevblocksSearchCriteria(SearchFields_Task::VIRTUAL_WATCHERS,'in',array($active_worker->id)),
		), true);
		$view->renderSortBy = SearchFields_Journal::CREATED;
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
		$view->name = 'Journal';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Journal::CONTEXT_LINK_ID,'=',$context_id),
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