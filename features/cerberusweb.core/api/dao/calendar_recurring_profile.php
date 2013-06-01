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
	
	function generateRecurringEvents($date_from, $date_to) {
		$calendar_events = array();
		
		// Termination date for recurring event
		if($this->recur_end && $this->recur_end < $date_from)
			return array();
		
		$day = strtotime('today', $date_from);
		$end_day = strtotime('today', $date_to);
		
		while($day <= $date_to) {
			$passed = false;
			
			$patterns = DevblocksPlatform::parseCrlfString($this->patterns);
			
			foreach($patterns as $pattern) {
				if($passed)
					continue;
				
				if(strlen($pattern) <= 4 && in_array(substr($pattern,-2),array('st','nd','rd','th')))
					$pattern = substr($pattern,0,-2);
				
				if(is_numeric($pattern)) {
					$passed = ($day == mktime(0,0,0,date('n', $day),$pattern,date('Y', $day)));
					
				} else {
					@$pattern_day = strtotime('today', strtotime($pattern, $day));
					$passed = ($pattern_day == $day);
				}
				
				if($passed) {
					$timezone = new DateTimeZone($this->tz);
					$datetime = new DateTime(date('Y-m-d', $day), $timezone);
					
					$datetime->modify($this->event_start);
					$event_start_local = $datetime->getTimestamp();
					
					$datetime->modify($this->event_end);
					$event_end_local = $datetime->getTimestamp();
					
					$calendar_events[] = array(
						'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING,
						'context' => null,
						'context_id' => $this->id,
						'label' => $this->event_name,
						//'color' => false ? $color_available : $color_busy,
						'ts' => $event_start_local,
						'ts_end' => $event_end_local,
						'is_available' => $this->is_available,
						'link' => sprintf("ctx://%s:%d",
							CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING,
							$this->id
						),
					);
				}
			}
			
			$day = strtotime('tomorrow', $day);
		}
		
		return $calendar_events;
	}
};
