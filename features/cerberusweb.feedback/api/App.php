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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerb Development Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 \* - Jeff Standen, Darren Sugita, Dan Hildebrandt
 *	 Webgroup Media LLC - Developers of Cerb
 */

class DAO_FeedbackEntry extends Cerb_ORMHelper {
	const ID = 'id';
	const LOG_DATE = 'log_date';
	const WORKER_ID = 'worker_id';
	const QUOTE_TEXT = 'quote_text';
	const QUOTE_MOOD = 'quote_mood';
	const QUOTE_ADDRESS_ID = 'quote_address_id';
	const SOURCE_URL = 'source_url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO feedback_entry () ".
			"VALUES ()"
		);
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
			parent::_update($batch_ids, 'feedback_entry', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.feedback_entry.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_FEEDBACK, $batch_ids);
			}
		}
	}
	
	/**
	 * @param string $where
	 * @return Model_FeedbackEntry[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, log_date, worker_id, quote_text, quote_mood, quote_address_id, source_url ".
			"FROM feedback_entry ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FeedbackEntry	 */
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
	 * @return Model_FeedbackEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_FeedbackEntry();
			$object->id = $row['id'];
			$object->log_date = $row['log_date'];
			$object->worker_id = $row['worker_id'];
			$object->quote_text = $row['quote_text'];
			$object->quote_mood = $row['quote_mood'];
			$object->quote_address_id = $row['quote_address_id'];
			$object->source_url = $row['source_url'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM feedback_entry");
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Entries
		$db->Execute(sprintf("DELETE FROM feedback_entry WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_FEEDBACK,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('feedback_entry');
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_FeedbackEntry::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns,$fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"f.id as %s, ".
			"f.log_date as %s, ".
			"f.worker_id as %s, ".
			"f.quote_text as %s, ".
			"f.quote_mood as %s, ".
			"f.quote_address_id as %s, ".
			"f.source_url as %s, ".
			"a.email as %s ",
				SearchFields_FeedbackEntry::ID,
				SearchFields_FeedbackEntry::LOG_DATE,
				SearchFields_FeedbackEntry::WORKER_ID,
				SearchFields_FeedbackEntry::QUOTE_TEXT,
				SearchFields_FeedbackEntry::QUOTE_MOOD,
				SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID,
				SearchFields_FeedbackEntry::SOURCE_URL,
				SearchFields_FeedbackEntry::ADDRESS_EMAIL
			 );
		
		$join_sql =
			"FROM feedback_entry f ".
			"LEFT JOIN address a ON (f.quote_address_id=a.id) ".
		
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.feedback' AND context_link.to_context_id = f.id) " : " ")
		;

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'f.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Virtuals
		foreach($params as $param) {
			if(!is_a($param, 'DevblocksSearchCriteria'))
				continue;
			
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					$from_context = CerberusContexts::CONTEXT_FEEDBACK;
					$from_index = 'f.id';
					
					self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $join_sql, $where_sql);
					break;
			}
		}
		
		$result = array(
			'primary_table' => 'f',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 * Enter description here...
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
			($has_multiple_values ? 'GROUP BY f.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_FeedbackEntry::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT f.id) " : "SELECT COUNT(f.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
};

class Model_FeedbackEntry {
	const MOOD_NEUTRAL = 0;
	const MOOD_PRAISE = 1;
	const MOOD_CRITICISM = 2;
	
	public $id;
	public $log_date;
	public $worker_id;
	public $quote_text;
	public $quote_mood;
	public $quote_address_id;
	public $source_url;
};

class SearchFields_FeedbackEntry {
	// Feedback_Entry
	const ID = 'f_id';
	const LOG_DATE = 'f_log_date';
	const WORKER_ID = 'f_worker_id';
	const QUOTE_TEXT = 'f_quote_text';
	const QUOTE_MOOD = 'f_quote_mood';
	const QUOTE_ADDRESS_ID = 'f_quote_address_id';
	const SOURCE_URL = 'f_source_url';
	
	const ADDRESS_EMAIL = 'a_email';
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'f', 'id', $translate->_('feedback_entry.id')),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'f', 'log_date', $translate->_('feedback_entry.log_date'), Model_CustomField::TYPE_DATE),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'f', 'worker_id', $translate->_('feedback_entry.worker_id')),
			self::QUOTE_TEXT => new DevblocksSearchField(self::QUOTE_TEXT, 'f', 'quote_text', $translate->_('feedback_entry.quote_text'), Model_CustomField::TYPE_SINGLE_LINE),
			self::QUOTE_MOOD => new DevblocksSearchField(self::QUOTE_MOOD, 'f', 'quote_mood', $translate->_('feedback_entry.quote_mood'), Model_CustomField::TYPE_SINGLE_LINE),
			self::QUOTE_ADDRESS_ID => new DevblocksSearchField(self::QUOTE_ADDRESS_ID, 'f', 'quote_address_id'),
			self::SOURCE_URL => new DevblocksSearchField(self::SOURCE_URL, 'f', 'source_url', $translate->_('feedback_entry.source_url'), Model_CustomField::TYPE_SINGLE_LINE),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'a', 'email', $translate->_('feedback_entry.quote_address'), Model_CustomField::TYPE_SINGLE_LINE),

			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', mb_convert_case($translate->_('common.watchers'), MB_CASE_TITLE), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_FEEDBACK,
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

// [TODO] Rename this for consistency  -- View_
class View_FeedbackEntry extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'feedback_entries';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('common.search_results');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_FeedbackEntry::LOG_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_FeedbackEntry::LOG_DATE,
			SearchFields_FeedbackEntry::ADDRESS_EMAIL,
			SearchFields_FeedbackEntry::SOURCE_URL,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_FeedbackEntry::ID,
			SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID,
			SearchFields_FeedbackEntry::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_FeedbackEntry::ID,
			SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID,
		));
		$this->addParamsDefault(array(
			SearchFields_FeedbackEntry::LOG_DATE => new DevblocksSearchCriteria(SearchFields_FeedbackEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FeedbackEntry::search(
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
		return $this->_getDataAsObjects('DAO_FeedbackEntry', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_FeedbackEntry', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Booleans
				case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				case SearchFields_FeedbackEntry::QUOTE_MOOD:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
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
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_FeedbackEntry', $column);
				break;
				
			case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_FeedbackEntry', $column);
				break;
				
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				$translate = DevblocksPlatform::getTranslationService();
				
				$label_map = array(
					'1' => $translate->_('feedback.mood.praise'),
					'0' => $translate->_('feedback.mood.neutral'),
					'2' => $translate->_('feedback.mood.criticism'),
				);
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_FeedbackEntry', $column, $label_map, 'in', 'moods[]');
				break;

			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_FeedbackEntry', $column, 'f.id');
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.feedback::feedback/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_FeedbackEntry::QUOTE_TEXT:
			case SearchFields_FeedbackEntry::SOURCE_URL:
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_FeedbackEntry::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_FeedbackEntry::LOG_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_FeedbackEntry::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				$translate = DevblocksPlatform::getTranslationService();
				
				$options = array(
					'1' => $translate->_('feedback.mood.praise'),
					'0' => $translate->_('feedback.mood.neutral'),
					'2' => $translate->_('feedback.mood.criticism'),
				);
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
				
			case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_FeedbackEntry::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;

			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				$translate = DevblocksPlatform::getTranslationService();
				$strings = array();
				
				$options = array(
					'1' => $translate->_('feedback.mood.praise'),
					'0' => $translate->_('feedback.mood.neutral'),
					'2' => $translate->_('feedback.mood.criticism'),
				);
				
				foreach($values as $val) {
					if(isset($options[$val]))
						$strings[] = $options[$val];
				}
				echo implode(" or ", $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_FeedbackEntry::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FeedbackEntry::QUOTE_TEXT:
			case SearchFields_FeedbackEntry::SOURCE_URL:
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_FeedbackEntry::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_FeedbackEntry::LOG_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_FeedbackEntry::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
				
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_FeedbackEntry::VIRTUAL_WATCHERS:
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
			list($objects,$null) = DAO_FeedbackEntry::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_FeedbackEntry::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_FeedbackEntry::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_FEEDBACK, $custom_fields, $batch_ids);

			unset($batch_ids);
		}

		unset($ids);
	}
};

class ChFeedbackController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}

	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
				break;
		}
	}
	
	function saveEntryAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
			
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		@$mood = DevblocksPlatform::importGPC($_POST['mood'],'integer',0);
		@$quote = DevblocksPlatform::importGPC($_POST['quote'],'string','');
		@$url = DevblocksPlatform::importGPC($_POST['url'],'string','');
		@$source_extension_id = DevblocksPlatform::importGPC($_POST['source_extension_id'],'string','');
		@$source_id = DevblocksPlatform::importGPC($_POST['source_id'],'integer',0);
		
		// Translate email string into addy id, if exists
		$address_id = 0;
		if(!empty($email)) {
			if(null != ($author_address = DAO_Address::lookupAddress($email, true)))
				$address_id = $author_address->id;
		}

		// Delete entries
		if(!empty($id) && !empty($do_delete)) {
			if(null != ($entry = DAO_FeedbackEntry::get($id))) {
				// Only superusers and owners can delete entries
				if($active_worker->is_superuser || $active_worker->id == $entry->worker_id) {
					DAO_FeedbackEntry::delete($id);
				}
			}
			
			return;
		}
		
		// New or modify
		$fields = array(
			DAO_FeedbackEntry::QUOTE_MOOD => intval($mood),
			DAO_FeedbackEntry::QUOTE_TEXT => $quote,
			DAO_FeedbackEntry::QUOTE_ADDRESS_ID => intval($address_id),
			DAO_FeedbackEntry::SOURCE_URL => $url,
		);

		// Only on new
		if(empty($id)) {
			$fields[DAO_FeedbackEntry::LOG_DATE] = time();
			$fields[DAO_FeedbackEntry::WORKER_ID] = $active_worker->id;
		}
		
		if(empty($id)) { // create
			$id = DAO_FeedbackEntry::create($fields);
			
			// Post-create actions
			if(!empty($source_extension_id) && !empty($source_id))
			switch($source_extension_id) {
				case 'feedback.source.ticket':
					$comment_text = sprintf(
						"== Capture Feedback ==\n".
						"Author: %s\n".
						"Mood: %s\n".
						"\n".
						"%s\n",
						(!empty($author_address) ? $author_address->email : 'Anonymous'),
						(empty($mood) ? 'Neutral' : (1==$mood ? 'Praise' : 'Criticism')),
						$quote
					);
					$fields = array(
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
						DAO_Comment::COMMENT => $comment_text,
						DAO_Comment::CREATED => time(),
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
						DAO_Comment::CONTEXT_ID => intval($source_id),
					);
					DAO_Comment::create($fields);
					break;
			}
			
		} else { // modify
			DAO_FeedbackEntry::update($id, $fields);
		}
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_FEEDBACK, $id, $field_ids);
		
		// Context Link (if given)
		@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
		@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
		if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
			DAO_ContextLink::setLink(CerberusContexts::CONTEXT_FEEDBACK, $id, $link_context, $link_context_id);
		}
	}
	
	function showBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.feedback::feedback/bulk.tpl');
	}
	
	function doBulkUpdateAction() {
		@set_time_limit(600); // 10m
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Feedback fields
//		@$list_id = trim(DevblocksPlatform::importGPC($_POST['list_id'],'integer',0));

		$do = array();
		
		// Do: List
//		if(0 != strlen($list_id))
//			$do['list_id'] = $list_id;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
};

if (class_exists('Extension_MessageToolbarItem',true)):
	class ChFeedbackMessageToolbarFeedback extends Extension_MessageToolbarItem {
		function render(Model_Message $message) {
			$tpl = DevblocksPlatform::getTemplateService();
			
			$tpl->assign('message', $message); /* @var $message Model_Message */
			
			$tpl->display('devblocks:cerberusweb.feedback::feedback/renderers/message_toolbar_feedback.tpl');
		}
	};
endif;

class Context_Feedback extends Extension_DevblocksContext implements IDevblocksContextPeek {
	static function searchInboundLinks($from_context, $from_context_id) {
		list($results, $null) = DAO_FeedbackEntry::search(
			array(
				SearchFields_FeedbackEntry::ID,
			),
			array(
				new DevblocksSearchCriteria(SearchFields_FeedbackEntry::CONTEXT_LINK,'=',$from_context),
				new DevblocksSearchCriteria(SearchFields_FeedbackEntry::CONTEXT_LINK_ID,'=',$from_context_id),
			),
			-1,
			0,
			SearchFields_FeedbackEntry::LOG_DATE,
			true,
			false
		);
		
		return $results;
	}
	
	function getRandom() {
		return DAO_FeedbackEntry::random();
	}
	
	function getMeta($context_id) {
		$feedback = DAO_FeedbackEntry::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $feedback->id,
			'name' => '', //$feedback->title, // [TODO]
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
		);
	}
	
	function getContext($feedback, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Feedback:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);

		// Polymorph
		if(is_numeric($feedback)) {
			$feedback = DAO_FeedbackEntry::get($feedback);
		} elseif($feedback instanceof Model_FeedbackEntry) {
			// It's what we want already.
		} else {
			$feedback = null;
		}
		
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('feedback_entry.log_date'),
			'id' => $prefix.$translate->_('feedback_entry.id'),
			'quote_mood' => $prefix.$translate->_('feedback_entry.quote_mood'),
			'quote_text' => $prefix.$translate->_('feedback_entry.quote_text'),
			'url' => $prefix.$translate->_('feedback_entry.source_url'),
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_FEEDBACK;
		
		if($feedback) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = trim(strtr($feedback->quote_text,"\r\n",' '));
			$token_values['id'] = $feedback->id;
			$token_values['created'] = $feedback->log_date;
			$token_values['quote_text'] = $feedback->quote_text;
			$token_values['url'] = $feedback->source_url;

			$mood = $feedback->quote_mood;
			$token_values['quote_mood_id'] = $mood;
			$token_values['quote_mood'] = ($mood ? (2==$mood ? 'criticism' : 'praise' ) : 'neutral');
			
			// Author
			@$address_id = $feedback->quote_address_id;
			$token_values['author_id'] = $address_id;
			
			// Created by worker
			@$assignee_id = $feedback->worker_id;
			$token_values['worker_id'] = $assignee_id;
		}

		// Author
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'author_',
			'Author:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Created by (Worker)
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'worker_',
			'Worker:',
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
		
		$context = CerberusContexts::CONTEXT_FEEDBACK;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
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
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Feedback';
		
		$view->view_columns = array(
			SearchFields_FeedbackEntry::LOG_DATE,
			SearchFields_FeedbackEntry::ADDRESS_EMAIL,
			SearchFields_FeedbackEntry::SOURCE_URL,
		);
		
		$view->addParamsDefault(array(
			//SearchFields_FeedbackEntry::IS_BANNED => new DevblocksSearchCriteria(SearchFields_FeedbackEntry::IS_BANNED,'=',0),
		), true);
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_FeedbackEntry::LOG_DATE;
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
		$view->name = 'Feedback';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_FeedbackEntry::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_FeedbackEntry::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$id = $context_id; // [TODO] Rename below and remove
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		// Creating
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['msg_id'],'integer',0);
		@$quote = DevblocksPlatform::importGPC($_REQUEST['quote'],'string','');
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		@$source_ext_id = DevblocksPlatform::importGPC($_REQUEST['source_ext_id'],'string','');
		@$source_id = DevblocksPlatform::importGPC($_REQUEST['source_id'],'integer',0);
		
		/*
		 * This treats procedurally created model objects
		 * the same as existing objects
		 */
		if(empty($id)) {
			$model = new Model_FeedbackEntry();
			
			if(!empty($msg_id)) {
				if(null != ($message = DAO_Message::get($msg_id))) {
					$model->id = 0;
					$model->log_date = time();
					$model->quote_address_id = $message->address_id;
					$model->quote_mood = 0;
					$model->quote_text = $quote;
					$model->worker_id = $active_worker->id;
					$model->source_url = $url;
				}
			}
		} elseif(!empty($id)) { // Were we given a model ID to load?
			if(null == ($model = DAO_FeedbackEntry::get($id))) {
				$id = null;
				$model = new Model_Feedback();
			}
		}

		// Author (if not anonymous)
		if(!empty($model->quote_address_id)) {
			if(null != ($address = DAO_Address::get($model->quote_address_id))) {
				$tpl->assign('address', $address);
			}
		}

		if(empty($model->source_url) && !empty($url))
			$model->source_url = $url;
		
		if(!empty($source_ext_id)) {
			$tpl->assign('source_extension_id', $source_ext_id);
			$tpl->assign('source_id', $source_id);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK, false);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_FEEDBACK, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->assign('model', $model);
		
		$tpl->display('devblocks:cerberusweb.feedback::feedback/ajax/peek.tpl');
	}
};