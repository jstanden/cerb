<?php
// Classes
$path = realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/App.php', array(
    'C4_TimeTrackingEntryView'
));

class ChTimeTrackingPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

abstract class Extension_TimeTrackingSource extends DevblocksExtension {
	function __construct($manifest) {
		parent::DevblocksExtension($manifest);
	}

	function getSourceName() {
		return NULL;
	}
	
	function getLinkText($source_id) {
		return NULL;
	}
	
	function getLink($source_id) {
		return NULL;
	}
};

if (class_exists('Extension_TimeTrackingSource',true)):
class ChTimeTrackingTicketSource extends Extension_TimeTrackingSource {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getSourceName() {
		return "Ticket";
	}
	
	function getLinkText($source_id) {
		return "Ticket #" . $source_id;
	}
	
	function getLink($source_id) {
		$url = DevblocksPlatform::getUrlService();
		return $url->write('c=display&id=' . $source_id);
	}
};
endif;

class ChTimeTrackingPatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = realpath(dirname(__FILE__) . '/../patches');
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.timetracking',2,$file_prefix.'/1.0.0.php',''));
	}
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChTimeTrackingTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return realpath(dirname(__FILE__) . '/../strings.xml');
		}
	};
endif;

if (class_exists('Extension_AppPreBodyRenderer',true)):
	class ChTimeTrackingPreBodyRenderer extends Extension_AppPreBodyRenderer {
		function render() {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('current_timestamp', time());
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/prebody.tpl.php');
		}
	};
endif;

if (class_exists('Extension_TicketToolbarItem',true)):
	class ChTimeTrackingTicketToolbarTimer extends Extension_TicketToolbarItem {
		function render(CerberusTicket $ticket) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('ticket', $ticket); /* @var $ticket CerberusTicket */
			
//			if(null != ($first_wrote_address_id = $ticket->first_wrote_address_id)
//				&& null != ($first_wrote_address = DAO_Address::get($first_wrote_address_id))) {
//				$tpl->assign('tt_first_wrote', $first_wrote_address);
//			}
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/ticket_toolbar_timer.tpl.php');
		}
	};
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChTimeTrackingReplyToolbarTimer extends Extension_ReplyToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
//			if(null != ($first_wrote_address_id = $ticket->first_wrote_address_id)
//				&& null != ($first_wrote_address = DAO_Address::get($first_wrote_address_id))) {
//				$tpl->assign('tt_first_wrote', $first_wrote_address);
//			}
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/reply_toolbar_timer.tpl.php');
		}
	};
endif;

class DAO_TimeTrackingEntry extends DevblocksORMHelper {
	const ID = 'id';
	const TIME_ACTUAL_MINS = 'time_actual_mins';
	const LOG_DATE = 'log_date';
	const WORKER_ID = 'worker_id';
	const ACTIVITY_ID = 'activity_id';
	const DEBIT_ORG_ID = 'debit_org_id';
	const NOTES = 'notes';
	const SOURCE_EXTENSION_ID = 'source_extension_id';
	const SOURCE_ID = 'source_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('timetracking_entry_seq');
		
		$sql = sprintf("INSERT INTO timetracking_entry (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'timetracking_entry', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_TimeTrackingEntry[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, time_actual_mins, log_date, worker_id, activity_id, debit_org_id, notes, source_extension_id, source_id ".
			"FROM timetracking_entry ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TimeTrackingEntry	 */
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
	 * @return Model_TimeTrackingEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_TimeTrackingEntry();
			$object->id = $rs->fields['id'];
			$object->time_actual_mins = $rs->fields['time_actual_mins'];
			$object->log_date = $rs->fields['log_date'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->activity_id = $rs->fields['activity_id'];
			$object->debit_org_id = $rs->fields['debit_org_id'];
			$object->notes = $rs->fields['notes'];
			$object->source_extension_id = $rs->fields['source_extension_id'];
			$object->source_id = $rs->fields['source_id'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM timetracking_entry");
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM timetracking_entry WHERE id IN (%s)", $ids_list));
		
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_TimeTrackingEntry::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"tt.id as %s, ".
			"tt.time_actual_mins as %s, ".
			"tt.log_date as %s, ".
			"tt.worker_id as %s, ".
			"tt.activity_id as %s, ".
			"tt.debit_org_id as %s, ".
			"tt.notes as %s, ".
			"tt.source_extension_id as %s, ".
			"tt.source_id as %s, ".
			"o.name as %s ",
			    SearchFields_TimeTrackingEntry::ID,
			    SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS,
			    SearchFields_TimeTrackingEntry::LOG_DATE,
			    SearchFields_TimeTrackingEntry::WORKER_ID,
			    SearchFields_TimeTrackingEntry::ACTIVITY_ID,
			    SearchFields_TimeTrackingEntry::DEBIT_ORG_ID,
			    SearchFields_TimeTrackingEntry::NOTES,
			    SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID,
			    SearchFields_TimeTrackingEntry::SOURCE_ID,
			    SearchFields_TimeTrackingEntry::ORG_NAME
			 );
		
		$join_sql = 
			"FROM timetracking_entry tt ".
			"LEFT JOIN contact_org o ON (o.id=tt.debit_org_id) "
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
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_TimeTrackingEntry::ID]);
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
    
    static function getSources() {
    	// Pull this off an extension point
    	return DevblocksPlatform::getExtensions('timetracking.source', true);
    }
};

class Model_TimeTrackingEntry {
	public $id;
	public $time_actual_mins;
	public $log_date;
	public $worker_id;
	public $activity_id;
	public $debit_org_id;
	public $notes;
	public $source_extension_id;
	public $source_id;
};

class SearchFields_TimeTrackingEntry {
	// TimeTracking_Entry
	const ID = 'tt_id';
	const TIME_ACTUAL_MINS = 'tt_time_actual_mins';
	const LOG_DATE = 'tt_log_date';
	const WORKER_ID = 'tt_worker_id';
	const ACTIVITY_ID = 'tt_activity_id';
	const DEBIT_ORG_ID = 'tt_debit_org_id';
	const NOTES = 'tt_notes';
	const SOURCE_EXTENSION_ID = 'tt_source_extension_id';
	const SOURCE_ID = 'tt_source_id';
	
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'tt', 'id', null, $translate->_('timetracking_entry.id')),
			self::TIME_ACTUAL_MINS => new DevblocksSearchField(self::TIME_ACTUAL_MINS, 'tt', 'time_actual_mins', null, $translate->_('timetracking_entry.time_actual_mins')),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'tt', 'log_date', null, $translate->_('timetracking_entry.log_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'tt', 'worker_id', null, $translate->_('timetracking_entry.worker_id')),
			self::ACTIVITY_ID => new DevblocksSearchField(self::ACTIVITY_ID, 'tt', 'activity_id', null, $translate->_('timetracking_entry.activity_id')),
			self::DEBIT_ORG_ID => new DevblocksSearchField(self::DEBIT_ORG_ID, 'tt', 'debit_org_id', null, $translate->_('timetracking_entry.debit_org_id')),
			self::NOTES => new DevblocksSearchField(self::NOTES, 'tt', 'notes', null, $translate->_('timetracking_entry.notes')),
			self::SOURCE_EXTENSION_ID => new DevblocksSearchField(self::SOURCE_EXTENSION_ID, 'tt', 'source_extension_id', null, $translate->_('timetracking_entry.source_extension_id')),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 'tt', 'source_id', null, $translate->_('timetracking_entry.source_id')),
			
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', null, $translate->_('contact_org.name')),
		);
	}
};

class C4_TimeTrackingEntryView extends C4_AbstractView {
	const DEFAULT_ID = 'timetracking_entries';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Time Tracking Entries';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_TimeTrackingEntry::LOG_DATE,
			SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID,
			SearchFields_TimeTrackingEntry::NOTES,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TimeTrackingEntry::search(
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
		
		$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
		$tpl->assign('activities', $activities);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);		
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.timetracking/templates/timetracking/time/view.tpl.php');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_TimeTrackingEntry::NOTES:
			case SearchFields_TimeTrackingEntry::ORG_NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl.php');
				break;
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
			case SearchFields_TimeTrackingEntry::SOURCE_ID:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl.php');
				break;
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl.php');
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl.php');
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
				$tpl->assign('billable_activities', $billable_activities);
				
				$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
				$tpl->assign('nonbillable_activities', $nonbillable_activities);

				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.timetracking/templates/timetracking/criteria/activity.tpl.php');
				break;
			case SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID:
				$sources = DAO_TimeTrackingEntry::getSources();
				$tpl->assign('sources', $sources);
				
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.timetracking/templates/timetracking/criteria/source.tpl.php');
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
			case SearchFields_TimeTrackingEntry::WORKER_ID:
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

			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "None";
					} else {
						if(!isset($activities[$val]))
							continue;
						$strings[] = $activities[$val]->name . ($activities[$val]->rate>0 ? ' ($)':'');
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID:
				$sources = DAO_TimeTrackingEntry::getSources();
				$strings = array();
				
				foreach($values as $val) {
					if(empty($val)) {
//						$strings[] = "None";
					} else {
						if(!isset($sources[$val]))
							continue;
						$strings[] = $sources[$val]->getSourceName();
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
		return SearchFields_TimeTrackingEntry::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_TimeTrackingEntry::ID]);
		unset($fields[SearchFields_TimeTrackingEntry::SOURCE_ID]);
		unset($fields[SearchFields_TimeTrackingEntry::DEBIT_ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_TimeTrackingEntry::ID]);
		unset($fields[SearchFields_TimeTrackingEntry::SOURCE_ID]);
		unset($fields[SearchFields_TimeTrackingEntry::DEBIT_ORG_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_TimeTrackingEntry::LOG_DATE => new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TimeTrackingEntry::NOTES:
			case SearchFields_TimeTrackingEntry::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
			case SearchFields_TimeTrackingEntry::SOURCE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				@$activity_ids = DevblocksPlatform::importGPC($_REQUEST['activity_ids'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$activity_ids);
				break;
			case SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID:
				@$source_ids = DevblocksPlatform::importGPC($_REQUEST['source_ids'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$source_ids);
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

class DAO_TimeTrackingActivity extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const RATE = 'rate';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO timetracking_activity (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'timetracking_activity', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_TimeTrackingActivity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, rate ".
			"FROM timetracking_activity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name ASC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TimeTrackingActivity	 */
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
	 * @return Model_TimeTrackingActivity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_TimeTrackingActivity();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->rate = $rs->fields['rate'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM timetracking_activity WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class Model_TimeTrackingActivity {
	public $id;
	public $name;
	public $rate;
};

//class ChTimeTrackingTab extends Extension_TicketTab {
//	function showTab() {
//		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
//		$tpl->assign('path', $tpl_path);
//		$tpl->cache_lifetime = "0";
//
////		$ticket = DAO_Ticket::getTicket($ticket_id);
//		$tpl->assign('ticket_id', $ticket_id);
//		
////		if(null == ($view = C4_AbstractViewLoader::getView('', 'ticket_opps'))) {
////			$view = new C4_CrmOpportunityView();
////			$view->id = 'ticket_opps';
////		}
////
////		if(!empty($address->contact_org_id)) { // org
////			@$org = DAO_ContactOrg::get($address->contact_org_id);
////			
////			$view->name = "Org: " . $org->name;
////			$view->params = array(
////				SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org->id) 
////			);
////		}
////		
////		C4_AbstractViewLoader::setView($view->id, $view);
////		
////		$tpl->assign('view', $view);
//		
//		$tpl->display('file:' . $tpl_path . 'timetracking/ticket_tab/index.tpl.php');
//	}
//	
//	function saveTab() {
//		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
//		
//		$ticket = DAO_Ticket::getTicket($ticket_id);
//		
//		if(isset($_SESSION['timetracking'])) {
//			@$time = intval($_SESSION['timetracking']);
////			echo "Ran for ", (time()-$time) , "secs <BR>";
//			unset($_SESSION['timetracking']);
//		} else {
//			$_SESSION['timetracking'] = time();
//		}
//		
//		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'timetracking')));
//	}
//};

//class ChTimeTrackingEventListener extends DevblocksEventListenerExtension {
//    function __construct($manifest) {
//        parent::__construct($manifest);
//    }
//
//    /**
//     * @param Model_DevblocksEvent $event
//     */
//    function handleEvent(Model_DevblocksEvent $event) {
//        switch($event->id) {
////            case 'cron.maint':
////            	DAO_TicketAuditLog::maint();
////            	break;
//            	
//            case 'ticket.reply.outbound':
//            	@$ticket_id = $event->params['ticket_id'];
//            	@$message_id = $event->params['message_id'];
//            	@$worker_id = $event->params['worker_id'];
//            	
//            	if(null == ($ticket = DAO_Ticket::getTicket($ticket_id)))
//            		return;
//
//            	$requester_list = array();
//            	$ticket_requesters = $ticket->getRequesters();
//            	
//            	if(is_array($ticket_requesters))
//            	foreach($ticket_requesters as $addy) { /* @var $addy Model_Address */
//            		$requester_list[] = $addy->email;
//            	}
//            	
//            	self::logToTimeTracking(sprintf("-- %s --\r\nReplied to %s on ticket: [#%s] %s",
//            		date('r', time()),
//            		implode(', ', $requester_list),
//            		$ticket->mask,
//            		$ticket->subject
//            	));
//            		
//            	break;
//        }
//    }
//    
//    // [TODO] Where does this static best belong?
//    static function logToTimeTracking($log) {
//    	if(!isset($_SESSION['timetracking_worklog']))
//        	$_SESSION['timetracking_worklog'] = array();
//        	
//        $_SESSION['timetracking_worklog'][] = $log;
//    }
//};

class ChTimeTrackingAjaxController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('timetracking','timetracking.controller.ajax');
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
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

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
	
	private function _startTimer() {
		if(!isset($_SESSION['timetracking_started'])) {
			$_SESSION['timetracking_started'] = time();	
		}
	}
	
	private function _stopTimer() {
		@$time = intval($_SESSION['timetracking_started']);
		
		// If a timer was running
		if(!empty($time)) {
			$elapsed = time() - $time;
			unset($_SESSION['timetracking_started']);
			@$_SESSION['timetracking_total'] = intval($_SESSION['timetracking_total']) + $elapsed;
		}

		@$total = $_SESSION['timetracking_total'];
		if(empty($total))
			return false;
		
		return $total;
	}
	
	private function _destroyTimer() {
		unset($_SESSION['timetracking_source_ext_id']);
		unset($_SESSION['timetracking_source_id']);
		unset($_SESSION['timetracking_started']);
		unset($_SESSION['timetracking_total']);
		unset($_SESSION['timetracking_link']);
	}
	
	function startTimerAction() {
		@$source_ext_id = urldecode(DevblocksPlatform::importGPC($_REQUEST['source_ext_id'],'string',''));
		@$source_id = intval(DevblocksPlatform::importGPC($_REQUEST['source_id'],'integer',0));
		
		if(!empty($source_ext_id) && !isset($_SESSION['timetracking_source_ext_id'])) {
			$_SESSION['timetracking_source_ext_id'] = $source_ext_id;
			$_SESSION['timetracking_source_id'] = $source_id;
		}
		
		$this->_startTimer();
	}
	
	function pauseTimerAction() {
		$total = $this->_stopTimer();
	}
	
	function getStopTimerPanelAction() {
		$total_secs = $this->_stopTimer();
		$this->_stopTimer();
		
		$object = new Model_TimeTrackingEntry();
		$object->id = 0;

		// Time
//		$tpl->assign('total_secs', $total_secs);
//		$tpl->assign('total_mins', ceil($total_secs/60));
		$object->time_actual_mins = ceil($total_secs/60);

		// Source
		@$source_ext_id = strtolower($_SESSION['timetracking_source_ext_id']);
		@$source_id = intval($_SESSION['timetracking_source_id']);
		
		$object->source_extension_id = $source_ext_id;
		$object->source_id = $source_id;
		
//		$tpl->assign('source_ext_id', $source_ext_id);
//		$tpl->assign('source_id', $source_id);
		
		switch($source_ext_id) {
			// Ticket
			case 'timetracking.source.ticket':
				if(null != ($ticket = DAO_Ticket::getTicket($source_id))) {
					
					// Timeslip Responsible Party
					if(null != ($address = DAO_Address::get($ticket->first_wrote_address_id))) {
//						$tpl->assign('performed_for', $address->email);

						// Timeslip Org
						if(!empty($address->contact_org_id)) { 
//							&& null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
//							$tpl->assign('org', $org->name);
							$object->debit_org_id = $address->contact_org_id;
						}
					}
					
					// Timeslip reference
//					$tpl->assign('reference', sprintf("Ticket #%s", 
//						$ticket->mask 
//						//((strlen($ticket->subject)>45) ? (substr($ticket->subject,0,45).'...') : $ticket->subject)
//					));
					
					// Timeslip note
					$object->notes = sprintf("Ticket #%s ",
						$ticket->mask
					);
//					$tpl->assign('note', sprintf("Replied to %s", 
//						$ticket->mask, 
//						(!empty($address->email) ? $address->email : '') 
//					));
				} 
				break;
		}		
		
		$this->showEntryAction($object);
	}
	
	function showEntryAction($model=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__).'/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		/*
		 * [IMPORTANT -- Yes, this is simply a line in the sand.]
		 * You're welcome to modify the code to meet your needs, but please respect 
		 * our licensing.  Buy a legitimate copy to help support the project!
		 * http://www.cerberusweb.com/
		 */
		$license = CerberusLicense::getInstance();
		if(empty($id) && (empty($license['key']) || (!empty($license['key']) && !empty($license['users'])))
			&& 10 <= DAO_TimeTrackingEntry::getItemCount()) {
			$tpl->display('file:' . $tpl_path . 'timetracking/rpc/trial.tpl.php');
			return;
		}
		
		/*
		 * This treats procedurally created model objects
		 * the same as existing objects
		 */ 
		if(!empty($id)) { // Were we given a model ID to load?
			if(null != ($model = DAO_TimeTrackingEntry::get($id)))
				$tpl->assign('model', $model);
		} elseif (!empty($model)) { // Were we passed a model object without an ID?
			$tpl->assign('model', $model);
		}

		/* @var $model Model_TimeTrackingEntry */
		
		// Source extension
		if(!empty($model->source_extension_id)) {
			if(null != ($source = DevblocksPlatform::getExtension($model->source_extension_id,true))) 
				$tpl->assign('source', $source);		
		}
		
		// Org Name
		if(!empty($model->debit_org_id)) {
			if(null != ($org = DAO_ContactOrg::get($model->debit_org_id)))
				$tpl->assign('org', $org);
		}
		
		// Activities
		// [TODO] Cache
		$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('billable_activities', $billable_activities);
		$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('nonbillable_activities', $nonbillable_activities);
		
		$tpl->display('file:' . $tpl_path . 'timetracking/rpc/time_entry_panel.tpl.php');
	}
	
//	function writeResponse(DevblocksHttpResponse $response) {
//		if(!$this->isVisible())
//			return;
//	}

	function saveEntryAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Make sure we're an active worker
		if(empty($active_worker) || empty($active_worker->id))
			return;
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
			
		@$activity_id = DevblocksPlatform::importGPC($_POST['activity_id'],'integer',0);
		@$time_actual_mins = DevblocksPlatform::importGPC($_POST['time_actual_mins'],'integer',0);
		@$notes = DevblocksPlatform::importGPC($_POST['notes'],'string','');
		@$org_str = DevblocksPlatform::importGPC($_POST['org'],'string','');
		@$source_extension_id = DevblocksPlatform::importGPC($_POST['source_extension_id'],'string','');
		@$source_id = DevblocksPlatform::importGPC($_POST['source_id'],'integer',0);
		
		// Translate org string into org id, if exists
		$org_id = 0;
		if(!empty($org_str)) {
			$org_id = DAO_ContactOrg::lookup($org_str, true);
		}

		// Delete entries
		if(!empty($id) && !empty($do_delete)) {
			if(null != ($entry = DAO_TimeTrackingEntry::get($id))) {
				// Only superusers and owners can delete entries
				if($active_worker->is_superuser || $active_worker->id == $entry->worker_id) {
					DAO_TimeTrackingEntry::delete($id);
				}
			}
			
			return;
		}
		
		// New or modify
		$fields = array(
			DAO_TimeTrackingEntry::ACTIVITY_ID => intval($activity_id),
			DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => intval($time_actual_mins),
			DAO_TimeTrackingEntry::NOTES => $notes,
			DAO_TimeTrackingEntry::DEBIT_ORG_ID => intval($org_id),
		);

		// Only on new
		if(empty($id)) {
			$fields[DAO_TimeTrackingEntry::LOG_DATE] = time();
			$fields[DAO_TimeTrackingEntry::SOURCE_EXTENSION_ID] = $source_extension_id;
			$fields[DAO_TimeTrackingEntry::SOURCE_ID] = intval($source_id);
			$fields[DAO_TimeTrackingEntry::WORKER_ID] = intval($active_worker->id);
		}
		
		if(empty($id)) { // create
			DAO_TimeTrackingEntry::create($fields);
		} else { // modify
			DAO_TimeTrackingEntry::update($id, $fields);
		}
		
		if(empty($id)) // only on new // [TODO] Cleanup
		switch($source_extension_id) {
			// If ticket, add a comment about the timeslip to the ticket
			case 'timetracking.source.ticket':
				$ticket_id = intval($source_id);
				
				if(null != ($worker_address = DAO_Address::lookupAddress($active_worker->email, false))) {
					if(!empty($activity_id)) {
						$activity = DAO_TimeTrackingActivity::get($activity_id);
					}
					
					if(!empty($org_id))
						$org = DAO_ContactOrg::get($org_id);
					
					$comment = sprintf(
						"== Time Tracking ==\n".
						"Worker: %s\n".
						"Time spent: %d min%s\n".
						"Activity: %s (%s)\n".
						"Organization: %s\n".
						"Notes: %s\n",
						$active_worker->getName(),
						$time_actual_mins,
						($time_actual_mins != 1 ? 's' : ''), // pluralize ([TODO] not I18N friendly)
						(!empty($activity) ? $activity->name : ''),
						((!empty($activity) && $activity->rate > 0.00) ? 'Billable' : 'Non-Billable'),
						(!empty($org) ? $org->name : '(not set)'),
						$notes
					);
					
					$fields = array(
						DAO_TicketComment::ADDRESS_ID => intval($worker_address->id),
						DAO_TicketComment::COMMENT => $comment,
						DAO_TicketComment::CREATED => time(),
						DAO_TicketComment::TICKET_ID => intval($ticket_id),
					);
					DAO_TicketComment::create($fields);
				}
				break;
		}
	}
	
	function clearEntryAction() {
		$this->_destroyTimer();
	}
};

if (class_exists('Extension_ActivityTab')):
class TimeTrackingActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_TIMETRACKING = 'activity_timetracking';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$core_path = realpath(APP_PATH . '/plugins/cerberusweb.core/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('core_path', $core_path);
		
		if(null == ($view = C4_AbstractViewLoader::getView('', self::VIEW_ACTIVITY_TIMETRACKING))) {
			$view = new C4_TimeTrackingEntryView();
			$view->id = self::VIEW_ACTIVITY_TIMETRACKING;
			$view->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
			$view->renderSortAsc = 0;
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('response_uri', 'activity/timetracking');
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_TimeTrackingEntryView::getFields());
		$tpl->assign('view_searchable_fields', C4_TimeTrackingEntryView::getSearchFields());
		
		$tpl->display($tpl_path . 'activity_tab/index.tpl.php');		
	}
}
endif;

class ChTimeTrackingConfigActivityTab extends Extension_ConfigTab {
	const ID = 'timetracking.config.tab.activities';
	
	function showTab() {
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('billable_activities', $billable_activities);
		
		$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('nonbillable_activities', $nonbillable_activities);
		
		$tpl->display('file:' . $tpl_path . 'config/activities/index.tpl.php');
	}
	
	function saveTab() {
		$settings = CerberusSettings::getInstance();
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$rate = floatval(DevblocksPlatform::importGPC($_REQUEST['rate'],'string',''));
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(empty($id)) { // Add
			$fields = array(
				DAO_TimeTrackingActivity::NAME => $name,
				DAO_TimeTrackingActivity::RATE => $rate,
			);
			$activity_id = DAO_TimeTrackingActivity::create($fields);
			
		} else { // Edit
			if($do_delete) { // Delete
				DAO_TimeTrackingActivity::delete($id);
				
			} else { // Modify
				$fields = array(
					DAO_TimeTrackingActivity::NAME => $name,
					DAO_TimeTrackingActivity::RATE => $rate,
				);
				DAO_TimeTrackingActivity::update($id, $fields);
			}
			
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','timetracking.activities')));
		exit;
	}
	
	function getActivityAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/../templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		if(!empty($id) && null != ($activity = DAO_TimeTrackingActivity::get($id)))
			$tpl->assign('activity', $activity);
		
		$tpl->display('file:' . $tpl_path . 'config/activities/edit_activity.tpl.php');
	}
	
};

if (class_exists('Extension_ReportGroup',true)):
class ChReportGroupTimeTracking extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};
endif;

if (class_exists('Extension_Report',true)):
class ChReportTimeSpentWorker extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_worker/index.tpl.php');
	}
	
	function getTimeSpentWorkerReportAction() {
		$db = DevblocksPlatform::getDatabaseService();

		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
				
		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
			
			$tpl->assign('invalidDate', true);
		}
		
		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);		
		
		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.worker_id, tte.notes, ".
				"tte.source_extension_id, tte.source_id, ".
				"tta.name activity_name, o.name org_name, o.id org_id ".
				"FROM timetracking_entry tte ".
				"INNER JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON o.id = tte.debit_org_id ".
				"INNER JOIN worker w ON tte.worker_id = w.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				(($sel_worker_id!=0) ? "AND tte.worker_id = ". $sel_worker_id. ' ' : '') .
				"ORDER BY w.first_name, w.last_name, w.id, tte.log_date ",
			$start_time,
			$end_time
		);
		//echo $sql;
		$rs = $db->Execute($sql);
	
		$time_entries = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$mins = intval($rs->fields['time_actual_mins']);
			$worker_id = intval($rs->fields['worker_id']);
			$org_id = intval($rs->fields['org_id']);
			$activity = $rs->fields['activity_name'];
			$org_name = $rs->fields['org_name'];
			$log_date = intval($rs->fields['log_date']);
			$notes = $rs->fields['notes'];
			
			
			if(!isset($time_entries[$worker_id]))
				$time_entries[$worker_id] = array();
				
			unset($time_entry);
			$time_entry['activity_name'] = $activity;
			$time_entry['org_name'] = $org_name;
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['source_extension_id'] = $rs->fields['source_extension_id'];
			$time_entry['source_id'] = intval($rs->fields['source_id']);			
			
			$time_entries[$worker_id]['entries'][] = $time_entry;
			@$time_entries[$worker_id]['total_mins'] = intval($time_entries[$worker_id]['total_mins']) + $mins;
			
			$rs->MoveNext();
		}
		//print_r($time_entries);
		$tpl->assign('time_entries', $time_entries);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_worker/html.tpl.php');
	}
	
	function getTimeSpentWorkerChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$groups = DAO_Group::getAll();
		
		$sql = sprintf("SELECT sum(tte.time_actual_mins) mins, tte.worker_id, w.first_name, w.last_name ".
				"FROM timetracking_entry tte ".
				"INNER JOIN worker w ON tte.worker_id = w.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				"GROUP BY tte.worker_id, w.first_name, w.last_name ".
				"ORDER BY w.first_name desc, w.last_name desc ",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}
		
	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$worker_name = $rs->fields['first_name'] . ' ' . $rs->fields['last_name'];
			
			echo $worker_name, "\t", $mins . "\n";
			
		    $rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class ChReportTimeSpentOrg extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_org/index.tpl.php');
	}
	
	function getTimeSpentOrgReportAction() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
				
		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
			
			$tpl->assign('invalidDate', true);
		}
		
		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);
		
		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.notes, tte.worker_id, ".
				"tte.source_extension_id, tte.source_id, ".
				"tta.name activity_name, o.name org_name, o.id org_id ".
				"FROM timetracking_entry tte ".
				"INNER JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON o.id = tte.debit_org_id ".
				"WHERE log_date > %d AND log_date <= %d ".
				"ORDER BY org_name, log_date ",
			$start_time,
			$end_time
		);
		//echo $sql;
		$rs = $db->Execute($sql);
	
		$time_entries = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$mins = intval($rs->fields['time_actual_mins']);
			$org_id = intval($rs->fields['org_id']);
			$activity = $rs->fields['activity_name'];
			$org_name = $rs->fields['org_name'];
			$log_date = intval($rs->fields['log_date']);
			$notes = $rs->fields['notes'];
			$worker_id = intval($rs->fields['worker_id']);
			
			if(!isset($time_entries[$org_id]))
				$time_entries[$org_id] = array();
			if(!isset($time_entries[$org_id]['entries']))
				$time_entries[$org_id]['entries'] = array();
				
				
			unset($time_entry);
			$time_entry['activity_name'] = $activity;
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['worker_id'] = $worker_id;
			$time_entry['source_extension_id'] = $rs->fields['source_extension_id'];
			$time_entry['source_id'] = intval($rs->fields['source_id']);

			$time_entries[$org_id]['entries'][] = $time_entry;
			@$time_entries[$org_id]['total_mins'] = intval($time_entries[$org_id]['total_mins']) + $mins;
			@$time_entries[$org_id]['org_name'] = $org_name;
			
			$rs->MoveNext();
		}
		//print_r($time_entries);
		$tpl->assign('time_entries', $time_entries);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_org/html.tpl.php');
	}
	
	function getTimeSpentOrgChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$groups = DAO_Group::getAll();
		
		$sql = sprintf("SELECT sum(tte.time_actual_mins) mins, o.id org_id, o.name org_name ".
				"FROM timetracking_entry tte ".
				"LEFT JOIN contact_org o ON tte.debit_org_id = o.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				"GROUP BY org_id, org_name ".
				"ORDER BY org_name desc ",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}
		
	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$org_name = $rs->fields['org_name'];
			if(empty($org_name)) $org_name = '(no org)';
			
			echo $org_name, "\t", $mins . "\n";
			
		    $rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class ChReportTimeSpentActivity extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_activity/index.tpl.php');
	}
	
	function getTimeSpentActivityReportAction() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
				
		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
			
			$tpl->assign('invalidDate', true);
		}
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);
		
		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.notes, tte.source_extension_id, tte.source_id,".
				"tta.id activity_id, tta.name activity_name, ".
				"o.name org_name, tte.worker_id ".
				"FROM timetracking_entry tte ".
				"LEFT JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON tte.debit_org_id = o.id " .
				"WHERE log_date > %d AND log_date <= %d ".
				"ORDER BY activity_name, log_date ",
			$start_time,
			$end_time
		);
		//echo $sql;
		$rs = $db->Execute($sql);
	
		$time_entries = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$mins = intval($rs->fields['time_actual_mins']);
			$activity = $rs->fields['activity_name'];
			$log_date = intval($rs->fields['log_date']);
			$activity_id = intval($rs->fields['activity_id']);
			$notes = $rs->fields['notes'];
			$worker_id = intval($rs->fields['worker_id']);
			$org_name = $rs->fields['org_name'];
			
			if(!isset($time_entries[$activity_id]))
				$time_entries[$activity_id] = array();
			if(!isset($time_entries[$activity_id]['entries']))
				$time_entries[$activity_id]['entries'] = array();
				
			unset($time_entry);
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['worker_name'] = $workers[$worker_id]->getName();
			$time_entry['org_name'] = $org_name;
			$time_entry['source_extension_id'] = $rs->fields['source_extension_id'];
			$time_entry['source_id'] = intval($rs->fields['source_id']);
			
			$time_entries[$activity_id]['entries'][] = $time_entry;
			@$time_entries[$activity_id]['total_mins'] = intval($time_entries[$activity_id]['total_mins']) + $mins;
			@$time_entries[$activity_id]['activity_name'] = $activity;
			
			$rs->MoveNext();
		}
		//print_r($time_entries);
		$tpl->assign('time_entries', $time_entries);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_activity/html.tpl.php');
	}
	
	function getTimeSpentActivityChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$groups = DAO_Group::getAll();
		
		$sql = sprintf("SELECT sum(tte.time_actual_mins) mins, tta.name activity_name ".
				"FROM timetracking_entry tte ".
				"LEFT JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				"GROUP BY activity_name ".
				"ORDER BY activity_name desc ",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}
		
	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$activity = $rs->fields['activity_name'];
			if(empty($activity)) $activity = '(no activity)';
			
			echo $activity, "\t", $mins . "\n";
			
		    $rs->MoveNext();
	    }
	}
};
endif;
?>
