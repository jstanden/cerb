<?php
class DAO_Calendar extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	
	const CACHE_ALL = 'cerb_calendars_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setNotEmpty(true)
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		
		return $validation->getFields();
	}
	
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO calendar () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CALENDAR, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'calendar', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.calendar.update',
						array(
							'fields' => $fields,
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
	
	static function getByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
		
		return self::getWhere(sprintf("%s = %s AND %s IN (%s)",
			self::OWNER_CONTEXT,
			Cerb_ORMHelper::qstr($context),
			self::OWNER_CONTEXT_ID,
			implode(',', $context_ids)
		));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Calendar[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, params_json, updated_at ".
			"FROM calendar ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_Calendar[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($calendars = $cache->load(self::CACHE_ALL))) {
			$calendars = DAO_Calendar::getWhere(
				array(),
				DAO_Calendar::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($calendars))
				return false;
			
			$cache->save($calendars, self::CACHE_ALL);
		}
		
		return $calendars;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Calendar
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$calendars = self::getAll();

		if(isset($calendars[$id]))
			return $calendars[$id];
			
		return null;
	}
	
	static function getReadableByActor($actor) {
		$calendars = DAO_Calendar::getAll();
		return CerberusContexts::filterModelsByActorReadable('Context_Calendar', $calendars, $actor);
	}
	
	static function getWriteableByActor($actor) {
		$calendars = DAO_Calendar::getAll();

		$calendars = array_filter($calendars, function($calendar) {
			@$manual_disabled = $calendar->params['manual_disabled'];
			
			if(!empty($manual_disabled))
				return false;
			
			return true;
		});
		
		return CerberusContexts::filterModelsByActorWriteable('Context_Calendar', $calendars, $actor);
	}
	
	static function getOwnedByWorker($worker) {
		$calendars = DAO_Calendar::getAll();

		if(is_array($calendars))
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
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('calendar');
	}
	
	static public function count($owner_context, $owner_context_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneSlave(sprintf("SELECT count(*) FROM calendar ".
			"WHERE owner_context = %s AND owner_context_id = %d",
			$db->qstr($owner_context),
			$owner_context_id
		));
	}
	
	static function deleteByContext($context, $context_ids) {
		$calendars = DAO_Calendar::getByContext($context, $context_ids);
		
		if(is_array($calendars) && !empty($calendars))
			return self::delete(array_keys($calendars));
		
		return;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		// Sanitize
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int', array('unique','nonzero'));
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM calendar WHERE id IN (%s)", $ids_list));
		
		// Delete linked records
		DAO_CalendarEvent::deleteByCalendarIds($ids);
		DAO_CalendarRecurringProfile::deleteByCalendarIds($ids);
		
		// Delete worker prefs
		DAO_Worker::updateWhere(array(DAO_Worker::CALENDAR_ID => 0), sprintf("%s IN (%s)", DAO_Worker::CALENDAR_ID, $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
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
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Calendar', $sortBy);
		
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
			
		$join_sql = "FROM calendar ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Calendar');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
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
			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
	/**
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
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
			
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Calendar::ID]);
			$results[$object_id] = $row;
		}
		
		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(calendar.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_Calendar extends DevblocksSearchFields {
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
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'calendar.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CALENDAR => new DevblocksSearchFieldContextKeys('calendar.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CALENDAR, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'calendar.owner_context', 'calendar.owner_context_id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_CALENDAR, self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'calendar', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'calendar', 'owner_context', $translate->_('common.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'calendar', 'owner_context_id', $translate->_('common.owner_context_id'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'calendar', 'params_json', $translate->_('dao.calendar.params_json'), null, false),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'calendar', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
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
		$db = DevblocksPlatform::services()->database();
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
		
		$results = $db->GetArraySlave($sql);
		
		foreach($results as $row) {
			// If the event spans multiple days, split them up into distinct events
			$ts_pointer = strtotime('midnight', $row['date_start']);
			$day_range = array();
			
			while($ts_pointer <= $row['date_end']) {
				$day_range[] = $ts_pointer;
				$ts_pointer = strtotime('tomorrow', $ts_pointer);
			}
			
			foreach($day_range as $epoch) {
				$day_start = strtotime('midnight', $epoch);
				$day_end = strtotime('23:59:59', $epoch);
				
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$event_start = $row['date_start'];
				$event_end = $row['date_end'];
				
				// If the day segment is outside of our desired range, skip
				//if($epoch < $date_from || $epoch > $date_to)
				//	continue;
				
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

		if(is_array($events))
		foreach($events as $ts => $schedule) {
			if(is_array($schedule))
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
					if(isset($mins_bits[$x]) && $mins_bits[$x] != '0')
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
	
	function getMinutes() {
		return $this->_mins;
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
		$left = intval($for);
		$offset = intval($at);
		
		while($left) {
			$bit = $this->_mins[$offset];

			// If we're starting in busy space, advance to available
			if(!$bit)
				$offset = strpos($this->_mins, '1', $offset);
			
			if(false === $offset) {
				return false;
			}
			
			$next_block = strpos($this->_mins, '0', $offset);

			// If there isn't another busy block, use the last available minute as our end
			if(false === $next_block)
				$next_block = strlen($next_block) - 1;
			
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
		if(is_array($calendar_events))
		foreach($calendar_events as $ts => $day_schedule) {
			if(is_array($day_schedule))
			foreach($day_schedule as $idx => $event) {
				if(empty($event['is_available']))
					continue;
				
				$at = ceil(($event['ts'] - $this->_start)/60);
				$for = max(1, ceil(($event['ts_end'] - $event['ts'])/60));
				$mins = substr($this->_mins, $at, $for);
				
				if(false === strpos($mins, '1'))
					unset($calendar_events[$ts][$idx]);
			}
		}
	}
	
	// [TODO] Find 5 mins between two dates
};

class View_Calendar extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Calendar');
		
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
		$context = CerberusContexts::CONTEXT_CALENDAR;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Calendar::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Calendar::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Calendar::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_Calendar::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Calendar::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Calendar::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Calendar::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CALENDAR, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Calendar::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Calendar::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Calendar::VIRTUAL_WATCHERS),
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner');
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CALENDAR, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_Calendar::VIRTUAL_OWNER);
					
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/calendar/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
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
};

class Context_Calendar extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CALENDAR, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CALENDAR, $models);
	}
	
	function getRandom() {
		return DAO_Calendar::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=calendar&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$calendar = DAO_Calendar::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($calendar->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $calendar->id,
			'name' => $calendar->name,
			'permalink' => $url,
			'updated' => $calendar->updated_at,
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
		} elseif(is_array($calendar)) {
			$calendar = Cerb_ORMHelper::recastArrayToModel($calendar, 'Model_Calendar');
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
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
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
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($calendar, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=calendar&id=%d-%s",$calendar->id, DevblocksPlatform::strToPermalink($calendar->name)), true);
			
			// Owner
			$token_values['owner__context'] = $calendar->owner_context;
			$token_values['owner_id'] = $calendar->owner_context_id;
		}
		
		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_Calendar::ID,
			'name' => DAO_Calendar::NAME,
			'owner__context' => DAO_Calendar::OWNER_CONTEXT,
			'owner_id' => DAO_Calendar::OWNER_CONTEXT_ID,
			'updated_at' => DAO_Calendar::UPDATED_AT,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_Calendar::PARAMS_JSON] = $json;
				break;
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
			
			case 'scope':
				// [TODO] Handle 'Start on Monday'
				$month = null;
				$year = null;
				
				// Overload month from dictionary?
				if(isset($dictionary['__scope_month'])) {
					$month = intval($dictionary['__scope_month']);
				}
				
				// Overload year from dictionary?
				if(isset($dictionary['__scope_year'])) {
					$year = intval($dictionary['__scope_year']);
				}
				
				$calendar_scope = DevblocksCalendarHelper::getCalendar($month, $year);
				$values['scope'] = $calendar_scope;
				break;
				
			case 'weeks':
				if(!isset($dictionary['scope'])) {
					$values = self::lazyLoadContextValues('scope', $dictionary);
					@$month = $values['scope']['month'];
					@$year = $values['scope']['year'];

					unset($values['scope']['calendar_weeks']);
					
				} else {
					@$month = $dictionary['scope']['month'];
					@$year = $dictionary['scope']['year'];
				}
				
				$calendar_scope = DevblocksCalendarHelper::getCalendar($month, $year);
				
				$values['weeks'] = $calendar_scope['calendar_weeks'];
				
				break;
			
			case 'events':
			case 'events_occluded':
				if(!isset($dictionary['scope'])) {
					$values = self::lazyLoadContextValues('scope', $dictionary);
					@$calendar_scope = $values['scope'];
					
				} else {
					@$calendar_scope = $dictionary['scope'];
				}

				$calendar = DAO_Calendar::get($context_id);

				$calendar_events = $calendar->getEvents($calendar_scope['date_range_from'], $calendar_scope['date_range_to']);
				$events = array();
				
				if("events_occluded" == $token) {
					$availability = $calendar->computeAvailability($calendar_scope['date_range_from'], $calendar_scope['date_range_to'], $calendar_events);
					$availability->occludeCalendarEvents($calendar_events);
				}
				
				if(is_array($calendar_events))
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
			case 'weeks_events_occluded':
				if(!isset($dictionary['weeks'])) {
					$values = self::lazyLoadContextValues('weeks', $dictionary);
					$calendar_scope = $values['scope'];
					
				} else {
					$values['weeks'] = $dictionary['weeks'];
					$calendar_scope = $dictionary['scope'];
				}
				
				@$month = $calendar_scope['month'];
				@$year = $calendar_scope['year'];
				
				$calendar = DAO_Calendar::get($context_id);
				
				$calendar_events = $calendar->getEvents($calendar_scope['date_range_from'], $calendar_scope['date_range_to']);
				
				if("weeks_events_occluded" == $token) {
					$availability = $calendar->computeAvailability($calendar_scope['date_range_from'], $calendar_scope['date_range_to'], $calendar_events);
					$availability->occludeCalendarEvents($calendar_events);
				}
				
				foreach($values['weeks'] as $week_idx => $week) {
					foreach($week as $day_ts => $day) {
						$values['weeks'][$week_idx][$day_ts]['events'] = array();
						
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
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
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
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Calendar';
		$view->renderSortBy = SearchFields_Calendar::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Calendar';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Calendar::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_CALENDAR;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($context_id)) {
			$calendar = DAO_Calendar::get($context_id);
			$tpl->assign('model', $calendar);
		}

		if(empty($context_id) || $edit) {
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Owner
			
			$owners_menu = Extension_DevblocksContext::getOwnerTree();
			$tpl->assign('owners_menu', $owners_menu);
			
			// Datasources
			
			$datasource_extensions = Extension_CalendarDatasource::getAll(false);
			$tpl->assign('datasource_extensions', $datasource_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/calendar/peek_edit.tpl');
			
		} else {
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $calendar, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			// Counts
			$activity_counts = array(
				//'comments' => DAO_Comment::count($context, $context_id),
				'events' => DAO_CalendarEvent::countByCalendar($context_id),
				'events_recurring' => DAO_CalendarRecurringProfile::countByCalendar($context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Interactions
			$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
			$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
			$tpl->assign('interactions_menu', $interactions_menu);
	
			$tpl->display('devblocks:cerberusweb.core::internal/calendar/peek.tpl');
		}
	}
};
