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

class DAO_CalendarRecurringProfile extends C4_ORMHelper {
	const ID = 'id';
	const EVENT_NAME = 'event_name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const IS_AVAILABLE = 'is_available';
	const DATE_START = 'date_start';
	const DATE_END = 'date_end';
	const PARAMS_JSON = 'params_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO calendar_recurring_profile () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'calendar_recurring_profile', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('calendar_recurring_profile', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CalendarRecurringProfile[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, event_name, owner_context, owner_context_id, is_available, date_start, date_end, params_json ".
			"FROM calendar_recurring_profile ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CalendarRecurringProfile	 */
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
	 * @return Model_CalendarRecurringProfile[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CalendarRecurringProfile();
			$object->id = $row['id'];
			$object->event_name = $row['event_name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->is_available = $row['is_available'];
			$object->date_start = $row['date_start'];
			$object->date_end = $row['date_end'];
			
			if(!empty($row['params_json'])) {
				@$json = json_decode($row['params_json'], true);
				$object->params = !empty($json) ? $json : array();
			}
			
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
		
		$db->Execute(sprintf("DELETE FROM calendar_recurring_profile WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.',
					'context_ids' => $ids
				)
			)
		);
		*/
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CalendarRecurringProfile::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"calendar_recurring_profile.id as %s, ".
			"calendar_recurring_profile.event_name as %s, ".
			"calendar_recurring_profile.owner_context as %s, ".
			"calendar_recurring_profile.owner_context_id as %s, ".
			"calendar_recurring_profile.is_available as %s, ".
			"calendar_recurring_profile.date_start as %s, ".
			"calendar_recurring_profile.date_end as %s, ".
			"calendar_recurring_profile.params_json as %s ",
				SearchFields_CalendarRecurringProfile::ID,
				SearchFields_CalendarRecurringProfile::EVENT_NAME,
				SearchFields_CalendarRecurringProfile::OWNER_CONTEXT,
				SearchFields_CalendarRecurringProfile::OWNER_CONTEXT_ID,
				SearchFields_CalendarRecurringProfile::IS_AVAILABLE,
				SearchFields_CalendarRecurringProfile::DATE_START,
				SearchFields_CalendarRecurringProfile::DATE_END,
				SearchFields_CalendarRecurringProfile::PARAMS_JSON
			);
			
		$join_sql = "FROM calendar_recurring_profile ";
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'calendar_recurring_profile',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
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
			($has_multiple_values ? 'GROUP BY calendar_recurring_profile.id ' : '').
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
			$object_id = intval($row[SearchFields_CalendarRecurringProfile::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT calendar_recurring_profile.id) " : "SELECT COUNT(calendar_recurring_profile.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_CalendarRecurringProfile implements IDevblocksSearchFields {
	const ID = 'c_id';
	const EVENT_NAME = 'c_event_name';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const IS_AVAILABLE = 'c_is_available';
	const DATE_START = 'c_date_start';
	const DATE_END = 'c_date_end';
	const PARAMS_JSON = 'c_params_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar_recurring_profile', 'id', null),
			self::EVENT_NAME => new DevblocksSearchField(self::EVENT_NAME, 'calendar_recurring_profile', 'event_name', null),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'calendar_recurring_profile', 'owner_context', null),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'calendar_recurring_profile', 'owner_context_id', null),
			self::IS_AVAILABLE => new DevblocksSearchField(self::IS_AVAILABLE, 'calendar_recurring_profile', 'is_available', null),
			self::DATE_START => new DevblocksSearchField(self::DATE_START, 'calendar_recurring_profile', 'date_start', null),
			self::DATE_END => new DevblocksSearchField(self::DATE_END, 'calendar_recurring_profile', 'date_end', null),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'calendar_recurring_profile', 'params_json', null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_CalendarRecurringProfile {
	public $id;
	public $event_name;
	public $owner_context;
	public $owner_context_id;
	public $is_available;
	public $date_start;
	public $date_end;
	public $params;
	
	function createRecurringEvents($epoch=null) {
		$params = $this->params;

		$until = strtotime("December 31 +1 month 23:59");
		
		$every_n = max(intval(@$params['options']['every_n']),1);
		$after_n = null;
		$end_params = isset($params['end']['options']) ? $params['end']['options'] : array();
		
		switch(@$params['end']['term']) {
			case 'after_n':
				if(isset($end_params['iterations'])) {
					$after_n = $end_params['iterations'];
				}
				break;
				
			case 'date':
				if(isset($end_params['on'])) {
					$until_date = $end_params['on'];
					$until = min($until_date, $until);
				}
				break;
		}
		
		$on_dates = array();
		$date_start = strtotime("today", $this->date_start);
		
		switch(@$params['freq']) {
			case 'daily':
				$on_dates = DevblocksCalendarHelper::getDailyDates($date_start, $every_n, $until, $after_n); 
				break;
			case 'weekly':
				$on_dates = DevblocksCalendarHelper::getWeeklyDates($date_start, $params['options']['day'], $until, $after_n); 
				break;	
			case 'monthly':
				$on_dates = DevblocksCalendarHelper::getMonthlyDates($date_start, $params['options']['day'], $until, $after_n); 
				break;	
			case 'yearly':
				$on_dates = DevblocksCalendarHelper::getYearlyDates($date_start, $params['options']['month'], $until, $after_n); 
				break;	
		}

		$time_start = date('H:i', $this->date_start);
		$time_end = date('H:i', $this->date_end);
		
		$event_start = new DateTime();
		$event_start->setTimestamp($this->date_start);
		
		$event_end = new DateTime();
		$event_end->setTimestamp($this->date_end);
		
		$event_interval = $event_end->diff($event_start);
		
		if(!empty($on_dates)) {
			if(is_array($on_dates))
			foreach($on_dates as $k => $on_date) {
				$date_start = strtotime($time_start, $on_date);
				
				if($date_start < $epoch) {
					unset($on_dates[$k]);
					continue;
				}
				
				$date_end = strtotime($time_end, strtotime($event_interval->format('%R%a days'), $date_start));					
				
				if($date_end < $date_start)
					$date_end = strtotime("+1 day", $date_end);
				
				$fields = array(
					DAO_CalendarEvent::NAME => $this->event_name,
					DAO_CalendarEvent::RECURRING_ID => $this->id,
					DAO_CalendarEvent::DATE_START => $date_start,
					DAO_CalendarEvent::DATE_END => $date_end,
					DAO_CalendarEvent::IS_AVAILABLE => $this->is_available,
					DAO_CalendarEvent::OWNER_CONTEXT => $this->owner_context,
					DAO_CalendarEvent::OWNER_CONTEXT_ID => $this->owner_context_id,
				);
				DAO_CalendarEvent::create($fields);
			}

			// Update the recurring profile with the last sched date
			
			if(isset($date_start) && isset($date_end)) {
				DAO_CalendarRecurringProfile::update($this->id, array(
					DAO_CalendarRecurringProfile::DATE_START => $date_start,
					DAO_CalendarRecurringProfile::DATE_END => $date_end,
				));
			}
			
			unset($on_dates);
		}
		
		return true;
	}	
};
