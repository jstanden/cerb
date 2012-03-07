<?php
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
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

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
		@$timestamp_start = $this->date_start;
		@$timestamp_end = $this->date_end;

		$datetime = new DateTime('now');
		$datetime->modify("last day of next month");
		//$datetime->modify("+6 weeks");
		$datetime->setTime(23,59,59);
		//$datetime->modify("this day next year");
		
		$recur_until_date = $datetime->getTimestamp();
		//var_dump(date("Y-m-d h:i a", $recur_until_date));
		$until = $recur_until_date;
		
		$end_params = isset($params['end']['options']) ? $params['end']['options'] : array();
		
		switch(@$params['end']['term']) {
			case 'after_n':
				break;
			case 'date':
				if(isset($end_params['on'])) {
					// [TODO] This should contribute to the loop ending but not overload $until
					$until = $end_params['on'];
				}
				break;
		}
		
		$counter = 0;
		$done = false;
		
		$day_names = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		
		$on_dates = array();
		//$on_dates[] = array($timestamp_start, $timestamp_end);
		
		$every_n = max(intval(@$params['options']['every_n']),1); 
		
		while(!$done) {
			// [TODO] Advance start+end times
			switch(@$params['freq']) {
				case 'daily':
					$timestamp_start = strtotime(sprintf("+%d day",$every_n),$timestamp_start);
					$timestamp_end = strtotime(sprintf("+%d day",$every_n),$timestamp_end);
					
					if($timestamp_start >= $epoch && $timestamp_start <= $until)
						$on_dates[] = array($timestamp_start, $timestamp_end);
					break;
					
				case 'weekly':
					$days = $params['options']['day'];
					
					$current_day = (integer)date('w', $timestamp_start);
					$next_day = $days[$counter++ % count($days)];
					$is_end_of_week = isset($last_day) && ($next_day <= $last_day);

					if($is_end_of_week) {
						$timestamp_start = strtotime(sprintf("now +%d weeks", $every_n), $timestamp_start);
						$current_day = (integer)date('w', $timestamp_start);
						$is_end_of_week = false;
					}
					
					$when = sprintf("%s%s %s",
						(!isset($last_day) || $next_day < $last_day) ? ('last ') : ($is_end_of_week ? 'next ' : ''),
						$day_names[$next_day],
						date("H:i", $timestamp_start)
					);
					$timestamp_start = strtotime($when, $timestamp_start);
				
					if($timestamp_start >= $epoch && $timestamp_start <= $until)
						$on_dates[] = array($timestamp_start, $timestamp_end + ($timestamp_start - $this->date_start));
					
					$last_day = $next_day;
					
					break;
					
				case 'monthly':
					$days = $params['options']['day'];
					
					$current_day = (integer)date('j', $timestamp_start);
					$next_day = $days[$counter++ % count($days)];
					$days_in_month = (integer)date('t', $timestamp_start);
				
					if($next_day > $days_in_month) {
						while($next_day > $days_in_month) {
							$next_day = $days[$counter++ % count($days)];
						}
					}
				
					$timestamp_start = mktime(
						date('H', $timestamp_start),
						date('i', $timestamp_start),
						0,
						date('m', $timestamp_start) + (isset($last_day) && ($next_day < $last_day) ? $every_n : 0),
						$next_day,
						date('Y', $timestamp_start)
					);
					
					if($timestamp_start >= $epoch && $timestamp_start <= $until)
						$on_dates[] = array($timestamp_start, $timestamp_end + ($timestamp_start - $this->date_start));
				
					$last_day = $next_day;
					
					break;
					
				case 'yearly':
					$months = $params['options']['month'];
					
					$current_day = (integer)date('j', $timestamp_start);
					
					do {
						$next_month = $months[$counter++ % count($months)];
						
						$timestamp_month = mktime(
							date('H', $timestamp_start),
							date('i', $timestamp_start),
							0,
							$next_month,
							1,
							date('Y', $timestamp_start) + ((isset($last_month) && $next_month < $last_month) ? $every_n : 0)
						);
						
						$days_in_month = (integer)date('t', $timestamp_month);
						$last_month = $next_month;
						
					} while($current_day > $days_in_month);
				
					$timestamp_start = mktime(
						date('H', $timestamp_month),
						date('i', $timestamp_month),
						0,
						$next_month,
						$current_day,
						date('Y', $timestamp_month)
					);
					
					if($timestamp_start >= $epoch && $timestamp_start <= $until)
						$on_dates[] = array($timestamp_start, $timestamp_end + ($timestamp_start - $this->date_start));
					
					break;
			}
			
			// Have we passed the end of next year?  If so, finish
			if(!$done) {
				switch(@$params['end']['term']) {
					case 'after_n':
						$iters = isset($end_params['iterations']) ? intval($end_params['iterations']) : 1;
						if(count($on_dates) >= $iters)
							$done = true;
						break;
						
					default:
						if($timestamp_start >= $until)
							$done = true;
						break;
				}
			}
		}
		
		if(!empty($on_dates)) {
			foreach($on_dates as $v_pair) {
				if(!is_array($v_pair) || 2 != count($v_pair))
					continue;
				
				$fields = array(
					DAO_CalendarEvent::NAME => $this->event_name,
					DAO_CalendarEvent::RECURRING_ID => $this->id,
					DAO_CalendarEvent::DATE_START => $v_pair[0],
					DAO_CalendarEvent::DATE_END => $v_pair[1],
					DAO_CalendarEvent::IS_AVAILABLE => $this->is_available,
					DAO_CalendarEvent::OWNER_CONTEXT => $this->owner_context,
					DAO_CalendarEvent::OWNER_CONTEXT_ID => $this->owner_context_id,
				);
				DAO_CalendarEvent::create($fields);
			}
			
			// Update the recurring profile with the last sched date
			DAO_CalendarRecurringProfile::update($this->id, array(
				DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
				DAO_CalendarRecurringProfile::DATE_END => $timestamp_end + ($timestamp_start - $this->date_start),
			));
			
			unset($on_dates);
		}
		
		return true;
	}	
};

