<?php
class DAO_Calendar extends Cerb_ORMHelper {
	const CACHE_ALL = 'cerb_calendars_all';
	
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO calendar () VALUES ()";
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
			parent::_update($batch_ids, 'calendar', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.calendar.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CALENDAR, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('calendar', $fields, $where);
		self::clearCache();
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Calendar[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, params_json, updated_at ".
			"FROM calendar ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_Calendar[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($calendars = $cache->load(self::CACHE_ALL))) {
			$calendars = DAO_Calendar::getWhere(
				array(),
				DAO_Calendar::NAME,
				true
			);
			$cache->save($calendars, self::CACHE_ALL);
		}
		
		return $calendars;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Calendar
	 */
	static function get($id) {
		$calendars = self::getAll();

		if(isset($calendars[$id]))
			return $calendars[$id];
			
		return null;
	}
	
	static function getReadableByActor($actor) {
		$calendars = DAO_Calendar::getAll();
		
		foreach($calendars as $calendar_id => $calendar) { /* @var $calendar Model_Calendar */
			if(!CerberusContexts::isReadableByActor($calendar->owner_context, $calendar->owner_context_id, $actor)) {
				unset($calendars[$calendar_id]);
				continue;
			}
		}
		
		return $calendars;
	}
	
	static function getWriteableByActor($actor) {
		$calendars = DAO_Calendar::getAll();
		
		foreach($calendars as $calendar_id => $calendar) { /* @var $calendar Model_Calendar */
			@$manual_disabled = $calendar->params['manual_disabled'];
			
			if(!empty($manual_disabled) || !CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $actor)) {
				unset($calendars[$calendar_id]);
				continue;
			}
		}
		
		return $calendars;
	}
	
	static function getOwnedByWorker($worker) {
		$calendars = DAO_Calendar::getAll();

		foreach($calendars as $calendar_id => $calendar) { /* @var $calendar Model_Calendar */
			if($calendar->owner_context == CerberusContexts::CONTEXT_WORKER && $calendar->owner_context_id == $worker->id)
				continue;
			
			unset($calendars[$calendar_id]);
		}
		
		return $calendars;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Calendar[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Calendar();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->updated_at = $row['updated_at'];
			
			if(!empty($row['params_json']) && false !== ($params_json = json_decode($row['params_json'], true)))
				$object->params = $params_json;
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM calendar WHERE id IN (%s)", $ids_list));
		
		// Delete linked records
		DAO_CalendarEvent::deleteByCalendarIds($ids);
		DAO_CalendarRecurringProfile::deleteByCalendarIds($ids);
		
		// Delete worker prefs
		DAO_WorkerPref::deleteByKeyValues('availability_calendar_id', $ids);
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CALENDAR,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Calendar::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"calendar.id as %s, ".
			"calendar.name as %s, ".
			"calendar.owner_context as %s, ".
			"calendar.owner_context_id as %s, ".
			"calendar.params_json as %s, ".
			"calendar.updated_at as %s ",
				SearchFields_Calendar::ID,
				SearchFields_Calendar::NAME,
				SearchFields_Calendar::OWNER_CONTEXT,
				SearchFields_Calendar::OWNER_CONTEXT_ID,
				SearchFields_Calendar::PARAMS_JSON,
				SearchFields_Calendar::UPDATED_AT
			);
			
		$join_sql = "FROM calendar ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.calendar' AND context_link.to_context_id = calendar.id) " : " ").
			'';
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'calendar.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_Calendar', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'calendar',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_CALENDAR;
		$from_index = 'calendar.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Calendar::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(calendar.owner_context = %s AND calendar.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(calendar.owner_context = %s)",
							Cerb_ORMHelper::qstr($context)
						);
					}
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
				break;
				
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
		}
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
			($has_multiple_values ? 'GROUP BY calendar.id ' : '').
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
			$object_id = intval($row[SearchFields_Calendar::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT calendar.id) " : "SELECT COUNT(calendar.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_Calendar implements IDevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const PARAMS_JSON = 'c_params_json';
	const UPDATED_AT = 'c_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER),
			self::NAME => new DevblocksSearchField(self::NAME, 'calendar', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'calendar', 'owner_context', $translate->_('common.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'calendar', 'owner_context_id', $translate->_('common.owner_context_id')),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'calendar', 'params_json', $translate->_('dao.calendar.params_json'), null),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'calendar', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_CALENDAR,
		));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Calendar {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $params;
	public $updated_at;
	
	function isReadableByActor($actor) {
		return CerberusContexts::isReadableByActor($this->owner_context, $this->owner_context_id, $actor);
	}
	
	function isWriteableByActor($actor) {
		return CerberusContexts::isWriteableByActor($this->owner_context, $this->owner_context_id, $actor);
	}
	
	function getCreateContexts() {
		$context_extensions = Extension_DevblocksContext::getAll(false, array('create'));
		$contexts = array();
			
		if(!isset($this->params['manual_disabled']) || empty($this->params['manual_disabled'])) {
			$contexts[CerberusContexts::CONTEXT_CALENDAR_EVENT] = $context_extensions[CerberusContexts::CONTEXT_CALENDAR_EVENT];
			$contexts[CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING] = $context_extensions[CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING];
		}
		
		if(isset($this->params['series']))
		foreach($this->params['series'] as $series) {
			if(isset($series['datasource']))
			switch($series['datasource']) {
				case 'calendar.datasource.worklist':
					if(null != (@$worklist_context = $series['worklist_model']['context']))
						if(isset($context_extensions[$worklist_context]))
							$contexts[$worklist_context] = $context_extensions[$worklist_context];
					break;
			}
		}
		
		return $contexts;
	}
	
	function getEvents($date_from, $date_to) {
		if(isset($this->params['manual_disabled']) && !empty($this->params['manual_disabled'])) {
			$calendar_events = array();
		} else {
			$calendar_events = $this->_getSelfEvents($date_from, $date_to);
		}
		
		// Load data from each extension
		
		if(isset($this->params['series']))
		foreach($this->params['series'] as $series_idx => $series) {
			$series_prefix = sprintf("[series][%d]",
				$series_idx
			);
		
			if(!isset($series['datasource']))
				continue;
		
			if(null == ($datasource_extension = Extension_CalendarDatasource::get($series['datasource'])))
				continue;
			
			$series_events = $datasource_extension->getData($this, $series, $series_prefix, $date_from, $date_to);
			
			foreach($series_events as $time => $events) {
				if(!isset($calendar_events[$time]))
					$calendar_events[$time] = array();
				
				foreach($events as $event)
					$calendar_events[$time][] = $event;
			}
		}

		// Sort days by timestamp
		ksort($calendar_events);
		
		// Sort daily events by start time
		foreach($calendar_events as $ts => $events) {
			DevblocksPlatform::sortObjects($calendar_events[$ts], '[ts]');
		}
		
		return $calendar_events;
	}
	
	private function _getSelfEvents($date_from, $date_to) {
		$calendar_events = array();
		
		@$color_available = $this->params['color_available'] ?: '#A0D95B';
		@$color_busy = $this->params['color_busy'] ?: '#C8C8C8';
		
		// Generate recurring events
		$recurrings = DAO_CalendarRecurringProfile::getByCalendar($this->id);

		// Get recurring events
		if(is_array($recurrings))
		foreach($recurrings as $recurring) {
			$events = $recurring->generateRecurringEvents($date_from, $date_to);

			if(is_array($events))
			foreach($events as $event) {
				$epoch = strtotime('today', $event['ts']);
				$event['color'] = $event['is_available'] ? $color_available : $color_busy;
				$calendar_events[$epoch][] = $event;
			}
		}
		
		// Get manual events from the database
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf(
			"SELECT id, name, is_available, date_start, date_end ".
			"FROM calendar_event ".
			"WHERE calendar_id = %d ".
			"AND date_start <= %d AND date_end >= %d ".
			"ORDER BY is_available DESC, date_start ASC",
			$this->id,
			$date_to,
			$date_from
		);
		
		$results = $db->GetArray($sql);

		foreach($results as $row) {
			$day_range = range(strtotime('midnight', $row['date_start']), strtotime('midnight', $row['date_end']), 86400);
			
			foreach($day_range as $epoch) {
				// If the day segment is outside of our desired range, skip
				if($epoch < $date_from || $epoch > $date_to)
					continue;

				$day_start = strtotime('midnight', $epoch);
				$day_end = strtotime('23:59:59', $epoch);
				
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$event_start = $row['date_start'];
				$event_end = $row['date_end'];

				// Segment multi-day events with day-based start/end times
				
				if($event_start < $day_start)
					$event_start = $day_start;
				
				if($event_end > $day_end)
					$event_end = $day_end;
				
				$calendar_events[$epoch][] = array(
					'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT,
					'context_id' => $row['id'],
					'label' => $row['name'],
					'color' => $row['is_available'] ? $color_available : $color_busy,
					'ts' => $event_start,
					'ts_end' => $event_end,
					'is_available' => intval($row['is_available']),
					'link' => sprintf("ctx://%s:%d",
						CerberusContexts::CONTEXT_CALENDAR_EVENT,
						$row['id']
					),
				);
			}
		}
		
		return $calendar_events;
	}
	
	function computeAvailability($date_from, $date_to, $events) {
		$mins_len = ceil(($date_to - $date_from)/60);
		
		$mins_bits = str_pad('', $mins_len, '*', STR_PAD_LEFT);

		foreach($events as $ts => $schedule) {
			foreach($schedule as $event) {
				$start = $event['ts'];
				$end = @$event['ts_end'] ?: $start;

				$start_mins = intval(ceil(($start - $date_from)/60));
				$end_mins = intval(ceil(($end - $date_from)/60));
				
				if($start_mins < 0)
					$start_mins = 0;
				
				if($end_mins > $mins_len)
					$end_mins = $mins_len - 1;
				
				for($x = $start_mins; $x <= $end_mins; $x++) {
					// Only set mins as available that aren't marked as busy already.
					if($mins_bits[$x] != '0')
						$mins_bits[$x] = $event['is_available'];
				}
			}
		}
		
		return new Model_CalendarAvailability($date_from, $date_to, str_replace('*', '0', $mins_bits));
	}
};

class Model_CalendarAvailability {
	private $_start = null;
	private $_end = null;
	private $_mins = array();
	
	function __construct($start, $end, $mins) {
		$this->_start = $start;
		$this->_end = $end;
		$this->_mins = $mins;
	}
	
	function isAvailableAtFor($at, $for_mins) {
		$from_pos = ceil(($at - $this->_start)/60);
		
		$mins = substr($this->_mins, $from_pos, $for_mins);
		
		return (false === strpos($mins, '0')) ? true : false;
	}
	
	function isAvailableBetween($from, $to) {
		$from_pos = ceil(($from - $this->_start)/60);
		$to_pos = ceil(($to - $this->_start)/60);
		
		$mins = substr($this->_mins, $from_pos, $to_pos-$from_pos);

		return (false === strpos($mins, '0')) ? true : false;
	}
	
	function scheduleInRelativeTime($starting_at, $for) {
		$at = ceil(($starting_at - $this->_start)/60);
		$for = ceil((strtotime($for) - time())/60);
		$left = $for;
		$offset = $at;
		
		while($left) {
			$bit = $this->_mins[$offset];

			// If we're starting in busy space, advance to available
			if(!$bit)
				$offset = strpos($this->_mins, '1', $offset);
			
			if(false === $offset) {
				return false;
			}

			$next_block = strpos($this->_mins, '0', $offset);
			
			if(false === $next_block) {
				return false;
			}
			
			// If we have enough time in the current availability block to schedule a time
			if($next_block - $offset >= $left) {
				$sched = $this->_start + (($offset + $left) * 60);
				return $sched;
				
			} else {
				$left -= $next_block - $offset;
				$offset = $next_block;
				
			}
		}
	}
	
	function getAsCalendarEvents($calendar_properties) {
		$calendar_events = array();
		
		if(isset($calendar_properties['calendar_weeks']))
		foreach($calendar_properties['calendar_weeks'] as $week_idx => $days) {
			foreach($days as $ts => $day) {
				$at = ceil(($ts - $this->_start)/60);
				$for = ceil((strtotime('11:59:59pm', $ts) - $ts)/60);
				$mins = substr($this->_mins, $at, $for);
				$until = strlen($mins);
				
				$offset = 0;
				
				while($offset < $until) {
					$bit = $mins[$offset];
					$next_block = strpos($mins, ($bit ? '0' : '1'), $offset);
					
					// We are busy all day
					if(false === $next_block) {
						if($offset > 0 && $bit) {
							$event_start = $ts + ($offset * 60);
							$event_end = strtotime('11:59:59pm', $event_start);

							// If this event is at least a minute long
							if($event_end - $event_start > 59) {
								$calendar_events[$ts][] = array(
									'label' => sprintf("%s - 11:59pm",
										date('h:ia', $event_start)
									),
									'ts' => $event_start,
									'ts_end' => $event_end,
									'is_available' => ($bit) ? 1 : 0,
									'color' => ($bit) ? '#A0D95B' : '#C8C8C8',
								);
							}
						}
						$offset = strlen($mins);
						
					} else {
						if($bit) {
							$event_start = $ts + ($offset * 60);
							$event_end = $ts + (($next_block-1) * 60);
							
							// If this event is at least a minute long
							if($event_end - $event_start > 59) {
								$calendar_events[$ts][] = array(
									'label' => sprintf("%s - %s",
										date('h:ia', $event_start),
										date('h:ia', $event_end)
									),
									'ts' => $event_start,
									'ts_end' => $event_end,
									'is_available' => ($bit) ? 1 : 0,
									'color' => ($bit) ? '#A0D95B' : '#C8C8C8',
								);
							}
						}
						$offset = $next_block;
						
					}
				}
			}
		}
		
		return $calendar_events;
	}
	
	function occludeCalendarEvents(&$calendar_events) {
		foreach($calendar_events as $ts => $day_schedule) {
			foreach($day_schedule as $idx => $event) {
				if(empty($event['is_available']))
					continue;
				
				//var_dump($event['label']);
				$at = ceil(($event['ts'] - $this->_start)/60);
				$for = ceil(($event['ts_end'] - $event['ts'])/60);
				$mins = substr($this->_mins, $at, $for);
				
				if(false === strpos($mins, '1'))
					unset($calendar_events[$ts][$idx]);
			}
		}
	}
	
	// [TODO] Find 5 mins between two dates
};

class View_Calendar extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'view_calendars';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Calendars');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Calendar::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Calendar::NAME,
			SearchFields_Calendar::UPDATED_AT,
			SearchFields_Calendar::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Calendar::OWNER_CONTEXT,
			SearchFields_Calendar::OWNER_CONTEXT_ID,
			SearchFields_Calendar::PARAMS_JSON,
			SearchFields_Calendar::VIRTUAL_CONTEXT_LINK,
			SearchFields_Calendar::VIRTUAL_HAS_FIELDSET,
			SearchFields_Calendar::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Calendar::OWNER_CONTEXT,
			SearchFields_Calendar::OWNER_CONTEXT_ID,
			SearchFields_Calendar::PARAMS_JSON,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Calendar::search(
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
		return $this->_getDataAsObjects('DAO_Calendar', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Calendar', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_Calendar::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Calendar::VIRTUAL_OWNER:
				case SearchFields_Calendar::VIRTUAL_WATCHERS:
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
//			case SearchFields_Calendar::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Calendar', $column);
//				break;

//			case SearchFields_Calendar::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_Calendar', $column);
//				break;
				
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_Calendar', CerberusContexts::CONTEXT_CALENDAR, $column);
				break;

			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Calendar', CerberusContexts::CONTEXT_CALENDAR, $column);
				break;
				
			case SearchFields_Calendar::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_Calendar', CerberusContexts::CONTEXT_CALENDAR, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Calendar', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Calendar', $column, 'calendar.id');
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

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/calendar/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Calendar::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Calendar::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Calendar::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_CALENDAR);
				break;
				
			case SearchFields_Calendar::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
				
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
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

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Calendar::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
			
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Calendar::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Calendar::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Calendar::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Calendar::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Calendar::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
				
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
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
			$this->addParam($criteria, $field);
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
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_Calendar::EXAMPLE] = 'some value';
					break;
					
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
			list($objects,$null) = DAO_Calendar::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Calendar::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_Calendar::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_CALENDAR, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Calendar extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	function getRandom() {
		//return DAO_Calendar::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=calendar&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$calendar = DAO_Calendar::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($calendar->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $calendar->id,
			'name' => $calendar->name,
			'permalink' => $url,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'owner__label',
			'updated_at',
		);
	}
	
	function getContext($calendar, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Calendar:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR);

		// Polymorph
		if(is_numeric($calendar)) {
			$calendar = DAO_Calendar::get($calendar);
		} elseif($calendar instanceof Model_Calendar) {
			// It's what we want already.
		} else {
			$calendar = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' =>'context_url',
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CALENDAR;
		$token_values['_types'] = $token_types;
		
		if($calendar) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $calendar->name;
			$token_values['id'] = $calendar->id;
			$token_values['name'] = $calendar->name;
			$token_values['updated_at'] = $calendar->updated_at;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=calendar&id=%d-%s",$calendar->id, DevblocksPlatform::strToPermalink($calendar->name)), true);
			
			// Owner
			$token_values['owner__context'] = $calendar->owner_context;
			$token_values['owner_id'] = $calendar->owner_context_id;
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CALENDAR;
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
			
			case 'calendar_scope':
				// This can be preset in scope to select a specific month/year
				$calendar_scope = DevblocksCalendarHelper::getCalendar(null, null);
				$values['scope'] = $calendar_scope;
				break;
				
			case 'weeks':
				if(!isset($dictionary['calendar_scope'])) {
					$values = self::lazyLoadContextValues('calendar_scope', $dictionary);
					@$month = $values['scope']['month'];
					@$year = $values['scope']['year'];
					
				} else {
					@$month = $dictionary['calendar_scope']['month'];
					@$year = $dictionary['calendar_scope']['year'];
				}
				
				$calendar_scope = DevblocksCalendarHelper::getCalendar($month, $year);
				
				$values['weeks'] = $calendar_scope['calendar_weeks'];
				
				break;
			
			case 'events':
				if(!isset($dictionary['calendar_scope'])) {
					$values = self::lazyLoadContextValues('calendar_scope', $dictionary);
					unset($values['scope']['calendar_weeks']);
					@$calendar_scope = $values['scope'];
					
				} else {
					@$calendar_scope = $dictionary['calendar_scope'];
				}

				$calendar = DAO_Calendar::get($context_id);

				$calendar_events = $calendar->getEvents($calendar_scope['date_range_from'], $calendar_scope['date_range_to']);
				$events = array();
				
				foreach($calendar_events as $day_ts => $day_events) {
					foreach($day_events as $event) {
						if(isset($event['context'])) {
							$event['event__context'] = $event['context'];
							$event['event_id'] = $event['context_id'];
						}
						unset($event['context']);
						unset($event['context_id']);
						unset($event['link']);
						
						$events[] = $event;
					}
				}
				
				$values['events'] = $events;
				break;
				
			case 'weeks_events':
				if(!isset($dictionary['weeks'])) {
					$values = self::lazyLoadContextValues('weeks', $dictionary);
					@$calendar_scope = $values['scope'];
					
				} else {
					@$calendar_scope = $dictionary['calendar_scope'];
					
				}
				
				@$month = $calendar_scope['month'];
				@$year = $calendar_scope['year'];
				
				$calendar = DAO_Calendar::get($context_id);
				
				$calendar_events = $calendar->getEvents($calendar_scope['date_range_from'], $calendar_scope['date_range_to']);
				
				foreach($values['weeks'] as $week_idx => $week) {
					foreach($week as $day_ts => $day) {
						if(isset($calendar_events[$day_ts])) {
							$events = $calendar_events[$day_ts];
							
							array_walk($events, function(&$event) {
								if(isset($event['context'])) {
									$event['event__context'] = $event['context'];
									$event['event_id'] = $event['context_id'];
								}
								unset($event['context']);
								unset($event['context_id']);
								unset($event['link']);
							});
							
							$values['weeks'][$week_idx][$day_ts]['events'] = $events;
						}
					}
				}
				
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
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Calendar';
		$view->renderSortBy = SearchFields_Calendar::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
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
		$view->name = 'Calendar';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Calendar::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Calendar::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($calendar = DAO_Calendar::get($context_id))) {
			$tpl->assign('model', $calendar);
			
		} else {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
		
			$calendar = new Model_Calendar();
			$calendar->id = 0;
			$calendar->owner_context = !empty($owner_context) ? $owner_context : '';
			$calendar->owner_context_id = $owner_context_id;
			
			$tpl->assign('model', $calendar);
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Owners
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$virtual_attendants = DAO_VirtualAttendant::getAll();
		$tpl->assign('virtual_attendants', $virtual_attendants);

		$owner_groups = array();
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
		
		$owner_roles = array();
		foreach($roles as $k => $v) { /* @var $v Model_WorkerRole */
			if($active_worker->is_superuser)
				$owner_roles[$k] = $v;
		}
		$tpl->assign('owner_roles', $owner_roles);
		
		// Datasources
		
		$datasource_extensions = Extension_CalendarDatasource::getAll(false);
		$tpl->assign('datasource_extensions', $datasource_extensions);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/peek.tpl');
	}
};
