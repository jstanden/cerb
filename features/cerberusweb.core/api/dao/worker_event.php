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
class DAO_WorkerEvent extends DevblocksORMHelper {
	const CACHE_COUNT_PREFIX = 'workerevent_count_';
	
	const ID = 'id';
	const CREATED_DATE = 'created_date';
	const WORKER_ID = 'worker_id';
	const MESSAGE = 'message';
	const IS_READ = 'is_read';
	const URL = 'url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO worker_event () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// Invalidate the worker notification count cache
		if(isset($fields[self::WORKER_ID])) {
			$cache = DevblocksPlatform::getCacheService();
			self::clearCountCache($fields[self::WORKER_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'worker_event', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('worker_event', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_WorkerEvent[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, created_date, worker_id, message, is_read, url ".
			"FROM worker_event ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkerEvent	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getUnreadCountByWorker($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$cache = DevblocksPlatform::getCacheService();
		
	    if(null === ($count = $cache->load(self::CACHE_COUNT_PREFIX.$worker_id))) {
			$sql = sprintf("SELECT count(*) ".
				"FROM worker_event ".
				"WHERE worker_id = %d ".
				"AND is_read = 0",
				$worker_id
			);
			
			$count = $db->GetOne($sql);
			$cache->save($count, self::CACHE_COUNT_PREFIX.$worker_id);
	    }
		
		return intval($count);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WorkerEvent[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkerEvent();
			$object->id = $row['id'];
			$object->created_date = $row['created_date'];
			$object->worker_id = $row['worker_id'];
			$object->message = $row['message'];
			$object->url = $row['url'];
			$object->is_read = $row['is_read'];
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
		
		$db->Execute(sprintf("DELETE FROM worker_event WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$db->Execute("DELETE QUICK FROM worker_event WHERE is_read = 1");
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_event records.');
	}
	
	static function clearCountCache($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_COUNT_PREFIX.$worker_id);
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
		$fields = SearchFields_WorkerEvent::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"we.id as %s, ".
			"we.created_date as %s, ".
			"we.worker_id as %s, ".
			"we.message as %s, ".
			"we.is_read as %s, ".
			"we.url as %s ",
			    SearchFields_WorkerEvent::ID,
			    SearchFields_WorkerEvent::CREATED_DATE,
			    SearchFields_WorkerEvent::WORKER_ID,
			    SearchFields_WorkerEvent::MESSAGE,
			    SearchFields_WorkerEvent::IS_READ,
			    SearchFields_WorkerEvent::URL
		);
			
		$join_sql = "FROM worker_event we "
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
		;
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		$has_multiple_values = false;
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY we.id ' : '').
			$sort_sql;
		
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_WorkerEvent::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT we.id) " : "SELECT COUNT(we.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
	
};

class SearchFields_WorkerEvent implements IDevblocksSearchFields {
	// Worker Event
	const ID = 'we_id';
	const CREATED_DATE = 'we_created_date';
	const WORKER_ID = 'we_worker_id';
	const MESSAGE = 'we_message';
	const IS_READ = 'we_is_read';
	const URL = 'we_url';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'we', 'id', $translate->_('worker_event.id')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'we', 'created_date', $translate->_('worker_event.created_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'we', 'worker_id', $translate->_('worker_event.worker_id')),
			self::MESSAGE => new DevblocksSearchField(self::MESSAGE, 'we', 'message', $translate->_('worker_event.message')),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'we', 'is_read', $translate->_('worker_event.is_read')),
			self::URL => new DevblocksSearchField(self::URL, 'we', 'url', $translate->_('common.url')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_WorkerEvent {
	public $id;
	public $created_date;
	public $worker_id;
	public $message;
	public $is_read;
	public $url;
};

class View_WorkerEvent extends C4_AbstractView {
	const DEFAULT_ID = 'worker_events';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Worker Events';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_WorkerEvent::MESSAGE,
			SearchFields_WorkerEvent::CREATED_DATE,
		);
		$this->columnsHidden = array(
			SearchFields_WorkerEvent::ID,
		);
		
		$this->paramsHidden = array(
			SearchFields_WorkerEvent::ID,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WorkerEvent::search(
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/home/tabs/my_events/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WorkerEvent::MESSAGE:
			case SearchFields_WorkerEvent::URL:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_WorkerEvent::ID:
//			case SearchFields_WorkerEvent::MESSAGE_ID:
//			case SearchFields_WorkerEvent::TICKET_ID:
//				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
//				break;
			case SearchFields_WorkerEvent::IS_READ:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_WorkerEvent::CREATED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__context_worker.tpl');
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
			case SearchFields_WorkerEvent::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_WorkerEvent::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkerEvent::MESSAGE:
			case SearchFields_WorkerEvent::URL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_WorkerEvent::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_WorkerEvent::IS_READ:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
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
//		// Make sure we have checked items if we want a checked list
//		if(0 == strcasecmp($filter,"checks") && empty($ids))
//			return;
//	
//		if(is_array($do))
//		foreach($do as $k => $v) {
//			switch($k) {
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
//			$this->getParams(),
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
//			unset($batch_ids);
//		}
//
//		unset($ids);
//	}

};