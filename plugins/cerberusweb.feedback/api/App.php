<?php
// Classes
$path = realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_FeedbackEntryView'
));

class ChFeedbackPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChFeedbackPatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = realpath(dirname(__FILE__) . '/../patches');
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.feedback',0,$file_prefix.'/1.0.0.php',''));
	}
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChFeedbackTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return realpath(dirname(__FILE__) . '/../strings.xml');
		}
	};
endif;

class DAO_FeedbackEntry extends DevblocksORMHelper {
	const ID = 'id';
	const LOG_DATE = 'log_date';
	const LIST_ID = 'list_id';
	const WORKER_ID = 'worker_id';
	const QUOTE_TEXT = 'quote_text';
	const QUOTE_MOOD = 'quote_mood';
	const QUOTE_ADDRESS_ID = 'quote_address_id';

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
		
		$sql = "SELECT id, log_date, list_id, worker_id, quote_text, quote_mood, quote_address_id ".
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
		
		while(!$rs->EOF) {
			$object = new Model_FeedbackEntry();
			$object->id = $rs->fields['id'];
			$object->log_date = $rs->fields['log_date'];
			$object->list_id = $rs->fields['list_id'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->quote_text = $rs->fields['quote_text'];
			$object->quote_mood = $rs->fields['quote_mood'];
			$object->quote_address_id = $rs->fields['quote_address_id'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM feedback_entry WHERE id IN (%s)", $ids_list));
		
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_FeedbackEntry::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"f.id as %s, ".
			"f.log_date as %s, ".
			"f.list_id as %s, ".
			"f.worker_id as %s, ".
			"f.quote_text as %s, ".
			"f.quote_mood as %s, ".
			"f.quote_address_id as %s, ".
			"a.email as %s ",
			    SearchFields_FeedbackEntry::ID,
			    SearchFields_FeedbackEntry::LOG_DATE,
			    SearchFields_FeedbackEntry::LIST_ID,
			    SearchFields_FeedbackEntry::WORKER_ID,
			    SearchFields_FeedbackEntry::QUOTE_TEXT,
			    SearchFields_FeedbackEntry::QUOTE_MOOD,
			    SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID,
			    SearchFields_FeedbackEntry::ADDRESS_EMAIL
			 );
		
		$join_sql = 
			"FROM feedback_entry f ".
			"LEFT JOIN address a ON (f.quote_address_id=a.id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
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
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
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
	public $list_id;
	public $worker_id;
	public $quote_text;
	public $quote_mood;
	public $quote_address_id;
};

class SearchFields_FeedbackEntry {
	// Feedback_Entry
	const ID = 'f_id';
	const LOG_DATE = 'f_log_date';
	const LIST_ID = 'f_list_id';
	const WORKER_ID = 'f_worker_id';
	const QUOTE_TEXT = 'f_quote_text';
	const QUOTE_MOOD = 'f_quote_mood';
	const QUOTE_ADDRESS_ID = 'f_quote_address_id';
	
	const ADDRESS_EMAIL = 'a_email';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'f', 'id', null, $translate->_('feedback_entry.id')),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'f', 'log_date', null, $translate->_('feedback_entry.log_date')),
			self::LIST_ID => new DevblocksSearchField(self::LIST_ID, 'f', 'list_id', null, $translate->_('feedback_entry.list_id')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'f', 'worker_id', null, $translate->_('feedback_entry.worker_id')),
			self::QUOTE_TEXT => new DevblocksSearchField(self::QUOTE_TEXT, 'f', 'quote_text', null, $translate->_('feedback_entry.quote_text')),
			self::QUOTE_MOOD => new DevblocksSearchField(self::QUOTE_MOOD, 'f', 'quote_mood', null, $translate->_('feedback_entry.quote_mood')),
			self::QUOTE_ADDRESS_ID => new DevblocksSearchField(self::QUOTE_ADDRESS_ID, 'f', 'quote_address_id', null, null),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'a', 'email', null, $translate->_('feedback_entry.quote_address')),
		);
	}
};

class C4_FeedbackEntryView extends C4_AbstractView {
	const DEFAULT_ID = 'feedback_entries';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Feedback Entries';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_FeedbackEntry::LOG_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_FeedbackEntry::LOG_DATE,
			SearchFields_FeedbackEntry::ADDRESS_EMAIL,
			SearchFields_FeedbackEntry::QUOTE_MOOD,
			SearchFields_FeedbackEntry::LIST_ID,
			SearchFields_FeedbackEntry::WORKER_ID,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FeedbackEntry::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$lists = DAO_FeedbackList::getWhere(); // [TODO] getAll cache
		$tpl->assign('lists', $lists);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.feedback/templates/feedback/page/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_FeedbackEntry::QUOTE_TEXT:
			case SearchFields_FeedbackEntry::ADDRESS_EMAIL:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_FeedbackEntry::ID:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl.php');
				break;
			case SearchFields_FeedbackEntry::LOG_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
			case SearchFields_FeedbackEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl.php');
				break;
			case SearchFields_FeedbackEntry::QUOTE_MOOD:
				// [TODO] Translations
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.feedback/templates/feedback/criteria/quote_mood.tpl.php');
				break;
			case SearchFields_FeedbackEntry::LIST_ID:
				$lists = DAO_FeedbackList::getWhere(); // [TODO] getAll cache
				$tpl->assign('lists', $lists);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.feedback/templates/feedback/criteria/list.tpl.php');
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
				
			case SearchFields_FeedbackEntry::LIST_ID:
				$lists = DAO_FeedbackList::getWhere(); // [TODO] getAll cache
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($lists[$val]))
							continue;
						$strings[] = $lists[$val]->name;
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
			case SearchFields_FeedbackEntry::LIST_ID:
				@$list_ids = DevblocksPlatform::importGPC($_REQUEST['list_ids'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$list_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

//	function doBulkUpdate($filter, $do, $ids=array()) {
//		@set_time_limit(600); // [TODO] Temp!
//	  
//		$change_fields = array();
//
//		if(empty($do))
//		return;
//
//		if(is_array($do))
//		foreach($do as $k => $v) {
//			switch($k) {
//				case 'sla':
//					$change_fields[DAO_Address::SLA_ID] = intval($v);
//					break;
//				case 'banned':
//					$change_fields[DAO_Address::IS_BANNED] = intval($v);
//					break;
//			}
//		}
//
//		$pg = 0;
//
//		if(empty($ids))
//		do {
//			list($objects,$null) = DAO_Address::search(
//			$this->params,
//			100,
//			$pg++,
//			SearchFields_Address::ID,
//			true,
//			false
//			);
//			 
//			$ids = array_merge($ids, array_keys($objects));
//			 
//		} while(!empty($objects));
//
//		$batch_total = count($ids);
//		for($x=0;$x<=$batch_total;$x+=100) {
//			$batch_ids = array_slice($ids,$x,100);
//			DAO_Address::update($batch_ids, $change_fields);
//
//			// Cascade SLA changes
//			if(isset($do['sla'])) {
//				foreach($batch_ids as $id) {
//					DAO_Sla::cascadeAddressSla($id, $do['sla']);
//				}
//			}
//
//			unset($batch_ids);
//		}
//
//		unset($ids);
//	}
};

class DAO_FeedbackList extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO feedback_list (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'feedback_list', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_FeedbackList[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name ".
			"FROM feedback_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FeedbackList	 */
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
	 * @return Model_FeedbackList[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_FeedbackList();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM feedback_list WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_FeedbackList {
	public $id;
	public $name;
};

class ChFeedbackConfigTab extends Extension_ConfigTab {
	const ID = 'feedback.config.tab';
	
	function showTab() {
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$lists = DAO_FeedbackList::getWhere();
		$tpl->assign('lists', $lists);
		
		$tpl->display('file:' . $tpl_path . 'config/lists/index.tpl.php');
	}
	
	function saveTab() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(empty($id)) { // Add
			$fields = array(
				DAO_FeedbackList::NAME => $name,
			);
			$list_id = DAO_FeedbackList::create($fields);
			
		} else { // Edit
			if($do_delete) { // Delete
				DAO_FeedbackList::delete($id);
				
			} else { // Modify
				$fields = array(
					DAO_FeedbackList::NAME => $name,
				);
				DAO_FeedbackList::update($id, $fields);
			}
			
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','feedback')));
		exit;
	}
	
	function getListAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		if(!empty($id) && null != ($list = DAO_FeedbackList::get($id)))
			$tpl->assign('list', $list);
		
		$tpl->display('file:' . $tpl_path . 'config/lists/edit_list.tpl.php');
	}
	
};

class FeedbackPage extends CerberusPageExtension {
	private $plugin_path = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->plugin_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;
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
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = $this->plugin_path . '/templates/';
		$tpl->assign('path', $tpl_path);

		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		list($results,$count) = DAO_FeedbackEntry::search(
			array(
				new DevblocksSearchCriteria(SearchFields_FeedbackEntry::ID,DevblocksSearchCriteria::OPER_GTE,0),
			),
			-1,
			0,
			null,
			null,
			true
		);
		$tpl->assign('results', $results);
		
		$view = C4_AbstractViewLoader::getView('C4_FeedbackEntryView', C4_FeedbackEntryView::DEFAULT_ID);
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_FeedbackEntryView::getFields());
		$tpl->assign('view_searchable_fields', C4_FeedbackEntryView::getSearchFields());
		$tpl->display($tpl_path . 'feedback/page/index.tpl.php');
	}
	
	function showEntryAction() {
		@$active_worker = CerberusApplication::getActiveWorker(); 
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		// Editing
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		// Creating
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['msg_id'],'integer',0);
		@$quote = DevblocksPlatform::importGPC($_REQUEST['quote'],'string','');
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
					$model->list_id = 0;
					$model->log_date = time();
					$model->quote_address_id = $message->address_id;
					$model->quote_mood = 0;
					$model->quote_text = $quote;
					$model->worker_id = $active_worker->id;
					
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
		
		// Feedback lists
		$lists = DAO_FeedbackList::getWhere();
		$tpl->assign('lists', $lists);
		
		$tpl->display('file:' . $tpl_path . 'feedback/ajax/feedback_entry_panel.tpl.php');
	}
	
	function saveEntryAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
			
		@$list_id = DevblocksPlatform::importGPC($_POST['list_id'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		@$mood = DevblocksPlatform::importGPC($_POST['mood'],'integer',0);
		@$quote = DevblocksPlatform::importGPC($_POST['quote'],'string','');
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
				// [TODO] Only superusers and owners can delete entries
//				if($active_worker->is_superuser || $active_worker->id == $entry->worker_id) {
					DAO_FeedbackEntry::delete($id);
//				}
			}
			
			return;
		}
		
		// New or modify
		$fields = array(
			DAO_FeedbackEntry::LIST_ID => intval($list_id),
			DAO_FeedbackEntry::QUOTE_MOOD => intval($mood),
			DAO_FeedbackEntry::QUOTE_TEXT => $quote,
			DAO_FeedbackEntry::QUOTE_ADDRESS_ID => intval($address_id),
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
						
					@$list = DAO_FeedbackList::get($list_id);
					
					$comment_text = sprintf(
						"== Capture Feedback ==\n".
						"Author: %s\n".
						"List: %s\n".
						"Mood: %s\n".
						"\n".
						"%s\n",
						(!empty($author_address) ? $author_address->email : 'Anonymous'),
						(empty($list) ? '-inbox-' : $list->name),
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
	}
};

if (class_exists('Extension_MessageToolbarItem',true)):
	class ChFeedbackMessageToolbarFeedback extends Extension_MessageToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
//			$tpl->assign('toolbar_path', $tpl_path);
//			$tpl->cache_lifetime = "0";
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
			$tpl->display('file:' . $tpl_path . 'feedback/renderers/message_toolbar_feedback.tpl.php');
		}
	};
endif;


?>