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

class DAO_CalendarRecurringProfile extends Cerb_ORMHelper {
	const ID = 'id';
	const EVENT_NAME = 'event_name';
	const CALENDAR_ID = 'calendar_id';
	const IS_AVAILABLE = 'is_available';
	const TZ = 'tz';
	const EVENT_START = 'event_start';
	const EVENT_END = 'event_end';
	const RECUR_END = 'recur_end';
	const PATTERNS = 'patterns';

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
		$sql = "SELECT id, event_name, calendar_id, is_available, tz, event_start, event_end, recur_end, patterns ".
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
	
	static function getByCalendar($calendar_id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::CALENDAR_ID,
			$calendar_id
		));
		
		return $objects;
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
			$object->calendar_id = $row['calendar_id'];
			$object->is_available = $row['is_available'];
			$object->tz = $row['tz'];
			$object->event_start = $row['event_start'];
			$object->event_end = $row['event_end'];
			$object->recur_end = $row['recur_end'];
			$object->patterns = $row['patterns'];
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
	
	static function deleteByCalendarIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM calendar_recurring_profile WHERE calendar_id IN (%s)", $ids_list));
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
			"calendar_recurring_profile.calendar_id as %s, ".
			"calendar_recurring_profile.is_available as %s, ".
			"calendar_recurring_profile.tz as %s, ".
			"calendar_recurring_profile.event_start as %s, ".
			"calendar_recurring_profile.event_end as %s, ".
			"calendar_recurring_profile.recur_end as %s, ".
			"calendar_recurring_profile.patterns as %s ",
				SearchFields_CalendarRecurringProfile::ID,
				SearchFields_CalendarRecurringProfile::EVENT_NAME,
				SearchFields_CalendarRecurringProfile::CALENDAR_ID,
				SearchFields_CalendarRecurringProfile::IS_AVAILABLE,
				SearchFields_CalendarRecurringProfile::TZ,
				SearchFields_CalendarRecurringProfile::EVENT_START,
				SearchFields_CalendarRecurringProfile::EVENT_END,
				SearchFields_CalendarRecurringProfile::RECUR_END,
				SearchFields_CalendarRecurringProfile::PATTERNS
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
	const CALENDAR_ID = 'c_calendar_id';
	const IS_AVAILABLE = 'c_is_available';
	const TZ = 'c_tz';
	const EVENT_START = 'c_event_start';
	const EVENT_END = 'c_event_end';
	const RECUR_END = 'c_recur_end';
	const PATTERNS = 'c_patterns';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar_recurring_profile', 'id', null),
			self::EVENT_NAME => new DevblocksSearchField(self::EVENT_NAME, 'calendar_recurring_profile', 'event_name', null),
			self::CALENDAR_ID => new DevblocksSearchField(self::CALENDAR_ID, 'calendar_recurring_profile', 'calendar_id', null),
			self::IS_AVAILABLE => new DevblocksSearchField(self::IS_AVAILABLE, 'calendar_recurring_profile', 'is_available', null),
			self::TZ => new DevblocksSearchField(self::TZ, 'calendar_recurring_profile', 'tz', null),
			self::EVENT_START => new DevblocksSearchField(self::EVENT_START, 'calendar_recurring_profile', 'event_start', null),
			self::EVENT_END => new DevblocksSearchField(self::EVENT_END, 'calendar_recurring_profile', 'event_end', null),
			self::RECUR_END => new DevblocksSearchField(self::RECUR_END, 'calendar_recurring_profile', 'recur_end', null),
			self::PATTERNS => new DevblocksSearchField(self::PATTERNS, 'calendar_recurring_profile', 'patterns', null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_CalendarRecurringProfile {
	public $id;
	public $event_name;
	public $calendar_id;
	public $is_available;
	public $tz;
	public $event_start;
	public $event_end;
	public $recur_end;
	public $patterns;
	
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
					DAO_CalendarEvent::CALENDAR_ID => $this->calendar_id,
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
