<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class ChCustomFieldSource_TimeEntry extends Extension_CustomFieldSource {
	const ID = 'timetracking.fields.source.time_entry';
};

// Workspace Sources

class ChWorkspaceSource_TimeEntry extends Extension_WorkspaceSource {
	const ID = 'timetracking.workspace.source.time_entry';
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
	const ID = 'timetracking.source.ticket';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getSourceName() {
		$translate = DevblocksPlatform::getTranslationService();
		return ucwords($translate->_('common.ticket'));
	}
	
	function getLinkText($source_id) {
		$translate = DevblocksPlatform::getTranslationService();
		return vsprintf($translate->_('timetracking.ui.source.ticket.link_text'), $source_id);
	}
	
	function getLink($source_id) {
		$url = DevblocksPlatform::getUrlService();
		return $url->write('c=display&id=' . $source_id);
	}
};
endif;

if (class_exists('Extension_AppPreBodyRenderer',true)):
	class ChTimeTrackingPreBodyRenderer extends Extension_AppPreBodyRenderer {
		function render() {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			
			$tpl->assign('current_timestamp', time());
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/prebody.tpl');
		}
	};
endif;

if (class_exists('Extension_TicketToolbarItem',true)):
	class ChTimeTrackingTicketToolbarTimer extends Extension_TicketToolbarItem {
		function render(Model_Ticket $ticket) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			
			$tpl->assign('ticket', $ticket); /* @var $ticket Model_Ticket */
			
//			if(null != ($first_wrote_address_id = $ticket->first_wrote_address_id)
//				&& null != ($first_wrote_address = DAO_Address::get($first_wrote_address_id))) {
//				$tpl->assign('tt_first_wrote', $first_wrote_address);
//			}
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/ticket_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class ChTimeTrackingReplyToolbarTimer extends Extension_ReplyToolbarItem {
		function render(Model_Message $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			
			$tpl->assign('message', $message); /* @var $message Model_Message */
			
//			if(null != ($first_wrote_address_id = $ticket->first_wrote_address_id)
//				&& null != ($first_wrote_address = DAO_Address::get($first_wrote_address_id))) {
//				$tpl->assign('tt_first_wrote', $first_wrote_address);
//			}
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/reply_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_LogMailToolbarItem',true)):
	class ChTimeTrackingLogMailToolbarTimer extends Extension_LogMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/logmail_toolbar_timer.tpl');
		}
	};
endif;

if (class_exists('Extension_SendMailToolbarItem',true)):
	class ChTimeTrackingSendMailToolbarTimer extends Extension_SendMailToolbarItem {
		function render() { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			
			$tpl->display('file:' . $tpl_path . 'timetracking/renderers/sendmail_toolbar_timer.tpl');
		}
	};
endif;

class DAO_TimeTrackingEntry extends C4_ORMHelper {
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
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('timetracking_entry', $fields, $where);
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
	 * @param resource $rs
	 * @return Model_TimeTrackingEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_TimeTrackingEntry();
			$object->id = $row['id'];
			$object->time_actual_mins = $row['time_actual_mins'];
			$object->log_date = $row['log_date'];
			$object->worker_id = $row['worker_id'];
			$object->activity_id = $row['activity_id'];
			$object->debit_org_id = $row['debit_org_id'];
			$object->notes = $row['notes'];
			$object->source_extension_id = $row['source_extension_id'];
			$object->source_id = $row['source_id'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM timetracking_entry");
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Entries
		$db->Execute(sprintf("DELETE FROM timetracking_entry WHERE id IN (%s)", $ids_list));
		
		// Custom fields
		DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_TimeEntry::ID, $ids);
		
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
		$fields = SearchFields_TimeTrackingEntry::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
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

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'tt.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY tt.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_TimeTrackingEntry::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT tt.id) " : "SELECT COUNT(tt.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
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
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'tt', 'id', $translate->_('timetracking_entry.id')),
			self::TIME_ACTUAL_MINS => new DevblocksSearchField(self::TIME_ACTUAL_MINS, 'tt', 'time_actual_mins', $translate->_('timetracking_entry.time_actual_mins')),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'tt', 'log_date', $translate->_('timetracking_entry.log_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'tt', 'worker_id', $translate->_('timetracking_entry.worker_id')),
			self::ACTIVITY_ID => new DevblocksSearchField(self::ACTIVITY_ID, 'tt', 'activity_id', $translate->_('timetracking_entry.activity_id')),
			self::DEBIT_ORG_ID => new DevblocksSearchField(self::DEBIT_ORG_ID, 'tt', 'debit_org_id', $translate->_('timetracking_entry.debit_org_id')),
			self::NOTES => new DevblocksSearchField(self::NOTES, 'tt', 'notes', $translate->_('timetracking_entry.notes')),
			self::SOURCE_EXTENSION_ID => new DevblocksSearchField(self::SOURCE_EXTENSION_ID, 'tt', 'source_extension_id', $translate->_('timetracking_entry.source_extension_id')),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 'tt', 'source_id', $translate->_('timetracking_entry.source_id')),
			
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', $translate->_('contact_org.name')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class C4_TimeTrackingEntryView extends C4_AbstractView {
	const DEFAULT_ID = 'timetracking_entries';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('timetracking.activity.tab');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_TimeTrackingEntry::LOG_DATE,
			SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID,
			SearchFields_TimeTrackingEntry::ORG_NAME,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TimeTrackingEntry::search(
			$this->view_columns,
			$this->params,
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
		$tpl->assign('activities', $activities);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);		
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.timetracking/templates/timetracking/time/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_TimeTrackingEntry::NOTES:
			case SearchFields_TimeTrackingEntry::ORG_NAME:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
			case SearchFields_TimeTrackingEntry::SOURCE_ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
				$tpl->assign('billable_activities', $billable_activities);
				
				$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
				$tpl->assign('nonbillable_activities', $nonbillable_activities);

				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.timetracking/templates/timetracking/criteria/activity.tpl');
				break;
			case SearchFields_TimeTrackingEntry::SOURCE_EXTENSION_ID:
				$sources = DAO_TimeTrackingEntry::getSources();
				$tpl->assign('sources', $sources);
				
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.timetracking/templates/timetracking/criteria/source.tpl');
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
//				case 'xxxx':
//					$change_fields[DAO_TimeTrackingEntry::XXX] = $v;
//					break;
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
			list($objects,$null) = DAO_TimeTrackingEntry::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_TimeTrackingEntry::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_TimeTrackingEntry::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_TimeEntry::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
	
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
	 * @param resource $rs
	 * @return Model_TimeTrackingActivity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_TimeTrackingActivity();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->rate = $row['rate'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
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

class ChTimeTrackingEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'ticket.merge':
            	$new_ticket_id = $event->params['new_ticket_id'];
            	$old_ticket_ids = $event->params['old_ticket_ids'];
            	
            	$fields = array(
            		DAO_TimeTrackingEntry::SOURCE_ID => $new_ticket_id,
            	);
            	 DAO_TimeTrackingEntry::updateWhere($fields,sprintf("%s = '%s' AND %s IN (%s)",
            		DAO_TimeTrackingEntry::SOURCE_EXTENSION_ID,
            		ChTimeTrackingTicketSource::ID,
            		DAO_TimeTrackingEntry::SOURCE_ID,
            		implode(',', $old_ticket_ids)
            	));
            	break;
        }
    }
};

class ChTimeTrackingAjaxController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
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
				if(null != ($ticket = DAO_Ticket::get($source_id))) {
					
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
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		$tpl->assign('view_id', $view_id);
		
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_TimeEntry::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('file:' . $tpl_path . 'timetracking/rpc/time_entry_panel.tpl');
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
				// Check privs
				if(($active_worker->hasPriv('timetracking.actions.create') && $active_worker->id==$entry->worker_id)
					|| $active_worker->hasPriv('timetracking.actions.update_all'))
						DAO_TimeTrackingEntry::delete($id);
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
			$id = DAO_TimeTrackingEntry::create($fields);
			
			// Procedurally create a comment
			$translate = DevblocksPlatform::getTranslationService();
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
							"== %s ==\n".
							"%s %s\n".
							"%s %d\n".
							"%s %s (%s)\n".
							"%s %s\n".
							"%s %s\n",
							$translate->_('timetracking.ui.timetracking'),
							$translate->_('timetracking.ui.worker'),
							$active_worker->getName(),
							$translate->_('timetracking.ui.comment.time_spent'),
							$time_actual_mins,
							$translate->_('timetracking.ui.comment.activity'),
							(!empty($activity) ? $activity->name : ''),
							((!empty($activity) && $activity->rate > 0.00) ? $translate->_('timetracking.ui.billable') : $translate->_('timetracking.ui.non_billable')),
							$translate->_('timetracking.ui.comment.organization'),
							(!empty($org) ? $org->name : $translate->_('timetracking.ui.comment.not_set')),
							$translate->_('timetracking.ui.comment.notes'),
							$notes
						);
						//timetracking.ui.billable timetracking.ui.non_billable
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
			
		} else { // modify
			DAO_TimeTrackingEntry::update($id, $fields);
		}
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_TimeEntry::ID, $id, $field_ids);
	}
	
	function clearEntryAction() {
		$this->_destroyTimer();
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
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $path . 'timetracking/time/bulk.tpl');
	}
	
	function doBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Time Tracking fields
//		@$list_id = trim(DevblocksPlatform::importGPC($_POST['list_id'],'integer',0));

		$do = array();
		
		// Do: ...
//		if(0 != strlen($list_id))
//			$do['list_id'] = $list_id;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_TIMETRACKING))) {
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
		
		$tpl->display($tpl_path . 'activity_tab/index.tpl');		
	}
}
endif;

class ChTimeTrackingConfigActivityTab extends Extension_ConfigTab {
	const ID = 'timetracking.config.tab.activities';
	
	function showTab() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$billable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s!=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('billable_activities', $billable_activities);
		
		$nonbillable_activities = DAO_TimeTrackingActivity::getWhere(sprintf("%s=0",DAO_TimeTrackingActivity::RATE));
		$tpl->assign('nonbillable_activities', $nonbillable_activities);
		
		$tpl->display('file:' . $tpl_path . 'config/activities/index.tpl');
	}
	
	function saveTab() {
		$settings = DevblocksPlatform::getPluginSettingsService();
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		if(!empty($id) && null != ($activity = DAO_TimeTrackingActivity::get($id)))
			$tpl->assign('activity', $activity);
		
		$tpl->display('file:' . $tpl_path . 'config/activities/edit_activity.tpl');
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
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);		
		
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		$tpl->assign('sel_worker_id', $sel_worker_id);
		
		// Dates
		
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
		
		// Table
		
		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.worker_id, tte.notes, ".
				"tte.source_extension_id, tte.source_id, ".
				"tta.name activity_name, o.name org_name, o.id org_id ".
				"FROM timetracking_entry tte ".
				"LEFT JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON o.id = tte.debit_org_id ".
				"INNER JOIN worker w ON tte.worker_id = w.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				(($sel_worker_id!=0) ? "AND tte.worker_id = ". $sel_worker_id. ' ' : '') .
				"ORDER BY w.first_name, w.last_name, w.id, tte.log_date ",
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
	
		$time_entries = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$mins = intval($row['time_actual_mins']);
			$worker_id = intval($row['worker_id']);
			$org_id = intval($row['org_id']);
			$activity = $row['activity_name'];
			$org_name = $row['org_name'];
			$log_date = intval($row['log_date']);
			$notes = $row['notes'];
			
			if(!isset($time_entries[$worker_id]))
				$time_entries[$worker_id] = array();
				
			unset($time_entry);
			$time_entry['activity_name'] = $activity;
			$time_entry['org_name'] = $org_name;
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['source_extension_id'] = $row['source_extension_id'];
			$time_entry['source_id'] = intval($row['source_id']);
			
			$time_entries[$worker_id]['entries'][] = $time_entry;
			@$time_entries[$worker_id]['total_mins'] = intval($time_entries[$worker_id]['total_mins']) + $mins;
		}
		$tpl->assign('time_entries', $time_entries);		
		
		mysql_free_result($rs);
		
		// Chart
		
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

		$data = array();
		$iter = 0;
	    
	    while($row = mysql_fetch_assoc($rs)) {
	    	$mins = intval($row['mins']);
			$worker_name = $row['first_name'] . ' ' . $row['last_name'];

			$data[$iter++] = array('value'=>$worker_name,'mins'=>$mins);
	    }
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_worker/index.tpl');
	}
};
endif;

if (class_exists('Extension_Report',true)):
class ChReportTimeSpentOrg extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);
		
		// Dates
		
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
		
		// Table
		
		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.notes, tte.worker_id, ".
				"tte.source_extension_id, tte.source_id, ".
				"tta.name activity_name, o.name org_name, o.id org_id ".
				"FROM timetracking_entry tte ".
				"LEFT JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON o.id = tte.debit_org_id ".
				"WHERE log_date > %d AND log_date <= %d ".
				"ORDER BY org_name, log_date ",
			$start_time,
			$end_time
		);
		//echo $sql;
		$rs = $db->Execute($sql);
	
		$time_entries = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$mins = intval($row['time_actual_mins']);
			$org_id = intval($row['org_id']);
			$activity = $row['activity_name'];
			$org_name = $row['org_name'];
			$log_date = intval($row['log_date']);
			$notes = $row['notes'];
			$worker_id = intval($row['worker_id']);
			
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
			$time_entry['source_extension_id'] = $row['source_extension_id'];
			$time_entry['source_id'] = intval($row['source_id']);

			$time_entries[$org_id]['entries'][] = $time_entry;
			@$time_entries[$org_id]['total_mins'] = intval($time_entries[$org_id]['total_mins']) + $mins;
			@$time_entries[$org_id]['org_name'] = $org_name;
		}
		$tpl->assign('time_entries', $time_entries);		
		
		mysql_free_result($rs);
		
		// Chart
		
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
		
		$data = array();
		$iter = 0;
	    
	    while($row = mysql_fetch_assoc($rs)) {
	    	$mins = intval($row['mins']);
			$org_name = $row['org_name'];
			if(empty($org_name)) $org_name = '(no org)';
			
			$data[$iter++] = array('value'=>$org_name,'mins'=>$mins);
	    }
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_org/index.tpl');
	}
};
endif;

if (class_exists('Extension_Report',true)):
class ChReportTimeSpentActivity extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);
		
		// Dates

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
		
		// Table
		
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
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$mins = intval($row['time_actual_mins']);
			$activity = $row['activity_name'];
			$log_date = intval($row['log_date']);
			$activity_id = intval($row['activity_id']);
			$notes = $row['notes'];
			$worker_id = intval($row['worker_id']);
			$org_name = $row['org_name'];
			
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
			$time_entry['source_extension_id'] = $row['source_extension_id'];
			$time_entry['source_id'] = intval($row['source_id']);
			
			$time_entries[$activity_id]['entries'][] = $time_entry;
			@$time_entries[$activity_id]['total_mins'] = intval($time_entries[$activity_id]['total_mins']) + $mins;
			@$time_entries[$activity_id]['activity_name'] = $activity;
		}
		$tpl->assign('time_entries', $time_entries);

		mysql_free_result($rs);
		
		// Chart
		
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

		$data = array();
		$iter = 0;
	    
	    while($row = mysql_fetch_assoc($rs)) {
	    	$mins = intval($row['mins']);
			$activity = $row['activity_name'];
			if(empty($activity)) $activity = '(no activity)';
			
			$data[$iter++] = array('value'=>$activity, 'mins'=>$mins);
	    }
	    $tpl->assign('data', $data);

	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/time_spent_activity/index.tpl');
	}
};
endif;
