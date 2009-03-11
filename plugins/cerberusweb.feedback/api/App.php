<?php
class ChCustomFieldSource_FeedbackEntry extends Extension_CustomFieldSource {
	const ID = 'feedback.fields.source.feedback_entry';
};

// Workspace Sources

class ChWorkspaceSource_FeedbackEntry extends Extension_WorkspaceSource {
	const ID = 'feedback.workspace.source.feedback_entry';
};

if (class_exists('Extension_ActivityTab')):
class ChFeedbackActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_FEEDBACK = 'activity_feedback';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_FEEDBACK))) {
			$view = new C4_FeedbackEntryView();
			$view->id = self::VIEW_ACTIVITY_FEEDBACK;
			$view->renderSortBy = SearchFields_FeedbackEntry::LOG_DATE;
			$view->renderSortAsc = 0;
			
			$view->name = "Feedback";
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/feedback');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_FeedbackEntryView::getFields());
		$tpl->assign('view_searchable_fields', C4_FeedbackEntryView::getSearchFields());
		
		$tpl->display($tpl_path . 'activity_tab/index.tpl');		
	}
}
endif;

class DAO_FeedbackEntry extends C4_ORMHelper {
	const ID = 'id';
	const LOG_DATE = 'log_date';
	const WORKER_ID = 'worker_id';
	const QUOTE_TEXT = 'quote_text';
	const QUOTE_MOOD = 'quote_mood';
	const QUOTE_ADDRESS_ID = 'quote_address_id';
	const SOURCE_URL = 'source_url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('feedback_entry_seq');
		
		$sql = sprintf("INSERT INTO feedback_entry (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'feedback_entry', $fields);
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
	 * @param ADORecordSet $rs
	 * @return Model_FeedbackEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FeedbackEntry();
			$object->id = $rs->fields['id'];
			$object->log_date = $rs->fields['log_date'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->quote_text = $rs->fields['quote_text'];
			$object->quote_mood = $rs->fields['quote_mood'];
			$object->quote_address_id = $rs->fields['quote_address_id'];
			$object->source_url = $rs->fields['source_url'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
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
		
		// Custom fields
		DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_FeedbackEntry::ID, $ids);
		
		return true;
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
		$fields = SearchFields_FeedbackEntry::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			unset($sortBy);

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns,$fields);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
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
			"LEFT JOIN address a ON (f.quote_address_id=a.id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
		list($select_sql, $join_sql) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			'f.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$group_sql = "GROUP BY f.id ";
		
		$sql = $select_sql . $join_sql . $where_sql . $group_sql . $sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_FeedbackEntry::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT COUNT(DISTINCT f.id) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
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
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'f', 'id', null, $translate->_('feedback_entry.id')),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'f', 'log_date', null, $translate->_('feedback_entry.log_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'f', 'worker_id', null, $translate->_('feedback_entry.worker_id')),
			self::QUOTE_TEXT => new DevblocksSearchField(self::QUOTE_TEXT, 'f', 'quote_text', null, $translate->_('feedback_entry.quote_text')),
			self::QUOTE_MOOD => new DevblocksSearchField(self::QUOTE_MOOD, 'f', 'quote_mood', null, $translate->_('feedback_entry.quote_mood')),
			self::QUOTE_ADDRESS_ID => new DevblocksSearchField(self::QUOTE_ADDRESS_ID, 'f', 'quote_address_id', null, null),
			self::SOURCE_URL => new DevblocksSearchField(self::SOURCE_URL, 'f', 'source_url', null, $translate->_('feedback_entry.source_url')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'a', 'email', null, $translate->_('feedback_entry.quote_address')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		return $columns;
	}
};

class C4_FeedbackEntryView extends C4_AbstractView {
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

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FeedbackEntry::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.feedback/templates/feedback/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_FeedbackEntry::QUOTE_TEXT:
			case SearchFields_FeedbackEntry::SOURCE_URL:
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_FeedbackEntry::ID:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_FeedbackEntry::LOG_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_FeedbackEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				// [TODO] Translations
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.feedback/templates/feedback/criteria/quote_mood.tpl');
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
			case SearchFields_FeedbackEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Nobody";
					} else {
						if(!isset($workers[$val]))
							continue;
						$strings[] = $workers[$val]->getName();
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				$strings = array();

				// [TODO] Translations
				foreach($values as $val) {
					switch($val) {
						case 0:
							$strings[] = "Neutral";
							break;
						case 1:
							$strings[] = "Praise";
							break;
						case 2:
							$strings[] = "Criticism";
							break;
					}
				}
				echo implode(", ", $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_FeedbackEntry::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_FeedbackEntry::ID]);
		unset($fields[SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_FeedbackEntry::ID]);
		unset($fields[SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_FeedbackEntry::LOG_DATE => new DevblocksSearchCriteria(SearchFields_FeedbackEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FeedbackEntry::QUOTE_TEXT:
			case SearchFields_FeedbackEntry::SOURCE_URL:
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_FeedbackEntry::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_FeedbackEntry::LOG_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_FeedbackEntry::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				@$moods = DevblocksPlatform::importGPC($_REQUEST['moods'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$moods);
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
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
				$this->params,
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
			self::_doBulkSetCustomFields(ChCustomFieldSource_FeedbackEntry::ID, $custom_fields, $batch_ids);

			unset($batch_ids);
		}

		unset($ids);
	}	
};

class ChFeedbackController extends DevblocksControllerExtension {
	private $plugin_path = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->plugin_path = dirname(dirname(__FILE__)) . '/';
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
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
	
	function showEntryAction() {
		@$active_worker = CerberusApplication::getActiveWorker(); 
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $this->plugin_path . '/templates/');
		$tpl->cache_lifetime = "0";

		// Editing
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
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
				if(null != ($message = DAO_Ticket::getMessage($msg_id))) {
					$model->id = 0;
					$model->log_date = time();
					$model->quote_address_id = $message->address_id;
					$model->quote_mood = 0;
					$model->quote_text = $quote;
					$model->worker_id = $active_worker->id;
					$model->source_url = $url;
					
					$tpl->assign('model', $model);
				}
			}
		} elseif(!empty($id)) { // Were we given a model ID to load?
			if(null != ($model = DAO_FeedbackEntry::get($id)))
				$tpl->assign('model', $model);
		}

		// Author (if not anonymous)
		if(!empty($model->quote_address_id)) {
			if(null != ($address = DAO_Address::get($model->quote_address_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		if(!empty($source_ext_id)) {
			$tpl->assign('source_extension_id', $source_ext_id);
			$tpl->assign('source_id', $source_id);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_FeedbackEntry::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('file:' . $this->plugin_path . '/templates/feedback/ajax/feedback_entry_panel.tpl');
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
					// Create a ticket comment about the feedback (to prevent dupes)
					if(null == ($worker_address = DAO_Address::lookupAddress($active_worker->email)))
						break;
						
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
						DAO_TicketComment::ADDRESS_ID => $worker_address->id,
						DAO_TicketComment::COMMENT => $comment_text,
						DAO_TicketComment::CREATED => time(),
						DAO_TicketComment::TICKET_ID => intval($source_id),
					);
					DAO_TicketComment::create($fields);
					break;
			}
			
		} else { // modify
			DAO_FeedbackEntry::update($id, $fields);
		}
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_FeedbackEntry::ID, $id, $field_ids);
	}
	
	function showBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $path . 'feedback/bulk.tpl');
	}
	
	function doBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		// Feedback fields
//		@$list_id = trim(DevblocksPlatform::importGPC($_POST['list_id'],'integer',0));

		$do = array();
		
		// Do: List
//		if(0 != strlen($list_id))
//			$do['list_id'] = $list_id;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
};

if (class_exists('Extension_MessageToolbarItem',true)):
	class ChFeedbackMessageToolbarFeedback extends Extension_MessageToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
			$tpl->display('file:' . $tpl_path . 'feedback/renderers/message_toolbar_feedback.tpl');
		}
	};
endif;


?>
