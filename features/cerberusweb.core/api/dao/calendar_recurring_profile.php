<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class DAO_CalendarRecurringProfile extends Cerb_ORMHelper {
	const CALENDAR_ID = 'calendar_id';
	const EVENT_END = 'event_end';
	const EVENT_NAME = 'event_name';
	const EVENT_START = 'event_start';
	const ID = 'id';
	const IS_AVAILABLE = 'is_available';
	const PATTERNS = 'patterns';
	const RECUR_END = 'recur_end';
	const RECUR_START = 'recur_start';
	const TZ = 'tz';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CALENDAR_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CALENDAR))
			;
		$validation
			->addField(self::EVENT_END)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::EVENT_NAME)
			->string()
			->setNotEmpty(true)
			->setRequired(true)
			;
		$validation
			->addField(self::EVENT_START)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_AVAILABLE)
			->bit()
			;
		$validation
			->addField(self::PATTERNS)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::RECUR_END)
			->timestamp()
			;
		$validation
			->addField(self::RECUR_START)
			->timestamp()
			;
		$validation
			->addField(self::TZ)
			->string()
			->setMaxLength(128)
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO calendar_recurring_profile () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		if(
			isset($fields[self::EVENT_NAME]) 
			&& isset($fields[self::CALENDAR_ID])
			&& false == ($calendar = DAO_Calendar::get($fields[DAO_CalendarRecurringProfile::CALENDAR_ID]))
			) {
		
			/*
			 * Log the activity of a new recurring event being created
			 */
			
			$entry = array(
				//{{actor}} created recurring event {{event}} on calendar {{target}}
				'message' => 'activities.calendar_event_recurring.created',
				'variables' => array(
					'event' => $fields[DAO_CalendarRecurringProfile::EVENT_NAME],
					'target' => $calendar->name,
					),
				'urls' => array(
					'event' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id),
					'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_CALENDAR, $calendar->id),
					)
			);
			CerberusContexts::logActivity('calendar_event_recurring.created', CerberusContexts::CONTEXT_CALENDAR, $calendar->id, $entry, null, null);
		}
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'calendar_recurring_profile', $fields);
			
			// Send events
			if(!empty($check_deltas)) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.calendar_recurring_profile.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $batch_ids);
			}
		}
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
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, event_name, is_available, calendar_id, tz, event_start, event_end, recur_start, recur_end, patterns ".
			"FROM calendar_recurring_profile ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CalendarRecurringProfile
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
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
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CalendarRecurringProfile();
			$object->id = $row['id'];
			$object->event_name = $row['event_name'];
			$object->is_available = $row['is_available'];
			$object->calendar_id = $row['calendar_id'];
			$object->tz = $row['tz'];
			$object->event_start = $row['event_start'];
			$object->event_end = $row['event_end'];
			$object->recur_start = $row['recur_start'];
			$object->recur_end = $row['recur_end'];
			$object->patterns = $row['patterns'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('calendar_recurring_profile');
	}
	
	static function countByCalendar($calendar_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneSlave(sprintf("SELECT count(id) FROM calendar_recurring_profile ".
			"WHERE calendar_id = %d",
			$calendar_id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM calendar_recurring_profile WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function deleteByCalendarIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM calendar_recurring_profile WHERE calendar_id IN (%s)", $ids_list));
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CalendarRecurringProfile::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CalendarRecurringProfile', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"calendar_recurring_profile.id as %s, ".
			"calendar_recurring_profile.event_name as %s, ".
			"calendar_recurring_profile.is_available as %s, ".
			"calendar_recurring_profile.calendar_id as %s, ".
			"calendar_recurring_profile.tz as %s, ".
			"calendar_recurring_profile.event_start as %s, ".
			"calendar_recurring_profile.event_end as %s, ".
			"calendar_recurring_profile.recur_start as %s, ".
			"calendar_recurring_profile.recur_end as %s, ".
			"calendar_recurring_profile.patterns as %s ",
				SearchFields_CalendarRecurringProfile::ID,
				SearchFields_CalendarRecurringProfile::EVENT_NAME,
				SearchFields_CalendarRecurringProfile::IS_AVAILABLE,
				SearchFields_CalendarRecurringProfile::CALENDAR_ID,
				SearchFields_CalendarRecurringProfile::TZ,
				SearchFields_CalendarRecurringProfile::EVENT_START,
				SearchFields_CalendarRecurringProfile::EVENT_END,
				SearchFields_CalendarRecurringProfile::RECUR_START,
				SearchFields_CalendarRecurringProfile::RECUR_END,
				SearchFields_CalendarRecurringProfile::PATTERNS
			);
			
		$join_sql = "FROM calendar_recurring_profile ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CalendarRecurringProfile');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
	
		array_walk_recursive(
			$params,
			array('DAO_CalendarRecurringProfile', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'calendar_recurring_profile',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		$from_index = 'calendar_recurring_profile.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
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
			$object_id = intval($row[SearchFields_CalendarRecurringProfile::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(calendar_recurring_profile.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_CalendarRecurringProfile extends DevblocksSearchFields {
	const ID = 'c_id';
	const EVENT_NAME = 'c_event_name';
	const IS_AVAILABLE = 'c_is_available';
	const CALENDAR_ID = 'c_calendar_id';
	const TZ = 'c_tz';
	const EVENT_START = 'c_event_start';
	const EVENT_END = 'c_event_end';
	const RECUR_START = 'c_recur_start';
	const RECUR_END = 'c_recur_end';
	const PATTERNS = 'c_patterns';

	const VIRTUAL_CALENDAR_SEARCH = '*_calendar_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'calendar_recurring_profile.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING => new DevblocksSearchFieldContextKeys('calendar_recurring_profile.id', self::ID),
			CerberusContexts::CONTEXT_CALENDAR => new DevblocksSearchFieldContextKeys('calendar_recurring_profile.calendar_id', self::CALENDAR_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CALENDAR_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CALENDAR, 'calendar_recurring_profile.calendar_id');
				break;
			
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, self::getPrimaryKey());
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
			self::ID => new DevblocksSearchField(self::ID, 'calendar_recurring_profile', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::EVENT_NAME => new DevblocksSearchField(self::EVENT_NAME, 'calendar_recurring_profile', 'event_name', $translate->_('dao.calendar_recurring_profile.event_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::IS_AVAILABLE => new DevblocksSearchField(self::IS_AVAILABLE, 'calendar_recurring_profile', 'is_available', $translate->_('dao.calendar_recurring_profile.is_available'), Model_CustomField::TYPE_CHECKBOX, true),
			self::CALENDAR_ID => new DevblocksSearchField(self::CALENDAR_ID, 'calendar_recurring_profile', 'calendar_id', $translate->_('common.calendar'), null, true),
			self::TZ => new DevblocksSearchField(self::TZ, 'calendar_recurring_profile', 'tz', $translate->_('dao.calendar_recurring_profile.tz'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EVENT_START => new DevblocksSearchField(self::EVENT_START, 'calendar_recurring_profile', 'event_start', $translate->_('dao.calendar_recurring_profile.event_start'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EVENT_END => new DevblocksSearchField(self::EVENT_END, 'calendar_recurring_profile', 'event_end', $translate->_('dao.calendar_recurring_profile.event_end'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::RECUR_START => new DevblocksSearchField(self::RECUR_START, 'calendar_recurring_profile', 'recur_start', $translate->_('dao.calendar_recurring_profile.recur_start'), Model_CustomField::TYPE_DATE, true),
			self::RECUR_END => new DevblocksSearchField(self::RECUR_END, 'calendar_recurring_profile', 'recur_end', $translate->_('dao.calendar_recurring_profile.recur_end'), Model_CustomField::TYPE_DATE, true),
			self::PATTERNS => new DevblocksSearchField(self::PATTERNS, 'calendar_recurring_profile', 'patterns', $translate->_('dao.calendar_recurring_profile.patterns'), Model_CustomField::TYPE_MULTI_LINE, true),

			self::VIRTUAL_CALENDAR_SEARCH => new DevblocksSearchField(self::VIRTUAL_CALENDAR_SEARCH, '*', 'calendar_search', null, null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
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

class Model_CalendarRecurringProfile {
	public $id;
	public $event_name;
	public $calendar_id;
	public $is_available;
	public $tz;
	public $event_start;
	public $event_end;
	public $recur_start;
	public $recur_end;
	public $patterns;
	
	private $_calendar_model = null;
	
	/**
	 * @return Model_Calendar
	 */
	function getCalendar() {
		if(is_null($this->_calendar_model))
			$this->_calendar_model = DAO_Calendar::get($this->calendar_id);
			
		return $this->_calendar_model;
	}
	
	function generateRecurringEvents($date_from, $date_to) {
		$calendar_events = array();

		// Commencement date for recurring event
		if($this->recur_start && $this->recur_start > $date_to)
			return array();
		
		// Termination date for recurring event
		if($this->recur_end && $this->recur_end < $date_from)
			return array();
		
		$day = strtotime('today', $date_from);
		$end_day = strtotime('today', $date_to);
		
		while($day <= $date_to) {
			$passed = false;
			
			$patterns = DevblocksPlatform::parseCrlfString($this->patterns);

			// Translate convenience placeholders
			foreach($patterns as $idx => $pattern) {
				if(0 == strcasecmp($pattern, 'weekdays')) {
					unset($patterns[$idx]);
					array_push($patterns, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');
					continue;
				}
				
				if(0 == strcasecmp($pattern, 'weekends')) {
					unset($patterns[$idx]);
					array_push($patterns, 'Saturday', 'Sunday');
					continue;
				}
			}
			
			foreach($patterns as $pattern) {
				if($passed)
					continue;
				
				if(strlen($pattern) <= 4 && in_array(substr($pattern,-2),array('st','nd','rd','th')))
					$pattern = substr($pattern,0,-2);
				
				if(is_numeric($pattern)) {
					$passed = ($day == mktime(0,0,0,date('n', $day),$pattern,date('Y', $day)));
					
				} else {
					if(preg_match('#of every month$#i', $pattern))
						$pattern = str_replace('of every month', 'of this month', $pattern);
					
					@$pattern_day = strtotime('today', strtotime($pattern, $day));
					$passed = ($pattern_day == $day);
				}
				
				if($passed) {
					$timezone = new DateTimeZone($this->tz);
					$datetime = new DateTime(date('Y-m-d', $day), $timezone);
					
					$datetime->modify($this->event_start ?: 'midnight');
					$event_start_local = $datetime->getTimestamp();
					
					$datetime->modify($this->event_end ?: 'midnight');
					$event_end_local = $datetime->getTimestamp();
					
					// If the generated event starts before the recurring event begins, skip
					if($this->recur_start && $event_start_local < $this->recur_start)
						continue;
					
					// If the generated event starts after the recurring event ends, skip
					if($this->recur_end && $event_start_local > $this->recur_end)
						continue;
					
					$calendar_events[] = array(
						'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING,
						'context_id' => $this->id,
						'label' => $this->event_name,
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

class View_CalendarRecurringProfile extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'calendarrecurringprofile';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Calendar Recurring Events');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CalendarRecurringProfile::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CalendarRecurringProfile::CALENDAR_ID,
			SearchFields_CalendarRecurringProfile::IS_AVAILABLE,
			SearchFields_CalendarRecurringProfile::EVENT_START,
			SearchFields_CalendarRecurringProfile::EVENT_END,
			SearchFields_CalendarRecurringProfile::TZ,
			SearchFields_CalendarRecurringProfile::RECUR_START,
			SearchFields_CalendarRecurringProfile::RECUR_END,
			SearchFields_CalendarRecurringProfile::PATTERNS,
		);

		$this->addColumnsHidden(array(
			SearchFields_CalendarRecurringProfile::VIRTUAL_CALENDAR_SEARCH,
			SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK,
			SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET,
			SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CalendarRecurringProfile::VIRTUAL_CALENDAR_SEARCH,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CalendarRecurringProfile::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CalendarRecurringProfile');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CalendarRecurringProfile', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CalendarRecurringProfile', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				case SearchFields_CalendarRecurringProfile::TZ:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				$calendars = DAO_Calendar::getAll();
				$label_map = array();
				
				if(is_array($calendars))
				foreach($calendars as $calendar)
					$label_map[$calendar->id] = $calendar->name;
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, DevblocksSearchCriteria::OPER_IN, 'context_id[]');
				break;
			
			case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_CalendarRecurringProfile::TZ:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_CalendarRecurringProfile::getFields();
		$date = DevblocksPlatform::services()->date();
		
		$timezones = $date->getTimezones();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::EVENT_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'calendar' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::VIRTUAL_CALENDAR_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CALENDAR, 'q' => ''],
					]
				),
			'calendar.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::CALENDAR_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CALENDAR, 'q' => ''],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::EVENT_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'status' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::IS_AVAILABLE),
					'examples' => array(
						'available',
						'busy',
					),
				),
			'timezone' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::TZ),
					'examples' => array(
						['type' => 'list', 'values' => array_combine($timezones, $timezones), 'label_delimiter' => '/', 'key_delimiter' => '/'],
					)
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');

		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'calendar':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_CalendarRecurringProfile::VIRTUAL_CALENDAR_SEARCH);
				break;
				
			case 'status':
				$field_key = SearchFields_CalendarRecurringProfile::IS_AVAILABLE;
				$oper = null;
				$term = null;
				$value = 0;
				
				if(false == CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $term, false))
					return false;
				
				// Normalize status labels
				switch(substr(DevblocksPlatform::strLower($term), 0, 1)) {
					case 'a':
					case 'y':
					case '1':
						$value = 1;
						break;
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					$value
				);
				break;
				
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS, $tokens);
				break;
				
			default:
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

		// Calendars
		$calendars = DAO_Calendar::getAll();
		$tpl->assign('calendars', $calendars);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/calendar_recurring_profile/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CalendarRecurringProfile::EVENT_NAME:
			case SearchFields_CalendarRecurringProfile::TZ:
			case SearchFields_CalendarRecurringProfile::EVENT_START:
			case SearchFields_CalendarRecurringProfile::EVENT_END:
			case SearchFields_CalendarRecurringProfile::PATTERNS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::RECUR_START:
			case SearchFields_CalendarRecurringProfile::RECUR_END:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				$tpl->assign('context', CerberusContexts::CONTEXT_CALENDAR);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__chooser.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
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
			case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				$label_map = array();
				$calendars = DAO_Calendar::getAll();
				foreach($calendars as $calendar)
					$label_map[$calendar->id] = $calendar->name;
				
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CALENDAR_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.calendar')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CalendarRecurringProfile::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CalendarRecurringProfile::EVENT_NAME:
			case SearchFields_CalendarRecurringProfile::TZ:
			case SearchFields_CalendarRecurringProfile::EVENT_START:
			case SearchFields_CalendarRecurringProfile::EVENT_END:
			case SearchFields_CalendarRecurringProfile::PATTERNS:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CalendarRecurringProfile::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CalendarRecurringProfile::RECUR_START:
			case SearchFields_CalendarRecurringProfile::RECUR_END:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				@$context_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array()), 'integer', array('nonzero','unique'));
				$criteria = new DevblocksSearchCriteria($field,$oper,$context_ids);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
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

class Context_CalendarRecurringProfile extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $models, 'calendar_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $models, 'calendar_owner_');
	}
	
	function getRandom() {
		return DAO_CalendarRecurringProfile::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=calendar_recurring_profile&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$calendar_recurring_profile = DAO_CalendarRecurringProfile::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($calendar_recurring_profile->event_name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $calendar_recurring_profile->id,
			'name' => $calendar_recurring_profile->event_name,
			'permalink' => $url,
			'updated' => 0, // [TODO]
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
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
			'calendar__label',
			'patterns',
			'is_available',
			'event_start',
			'event_end',
			'tz',
		);
	}
	
	function getContext($calendar_recurring_profile, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Calendar Recurring Event:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING);

		// Polymorph
		if(is_numeric($calendar_recurring_profile)) {
			$calendar_recurring_profile = DAO_CalendarRecurringProfile::get($calendar_recurring_profile);
		} elseif($calendar_recurring_profile instanceof Model_CalendarRecurringProfile) {
			// It's what we want already.
		} elseif(is_array($calendar_recurring_profile)) {
			$calendar_recurring_profile = Cerb_ORMHelper::recastArrayToModel($calendar_recurring_profile, 'Model_CalendarRecurringProfile');
		} else {
			$calendar_recurring_profile = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'event_start' => $prefix.$translate->_('dao.calendar_recurring_profile.event_start'),
			'event_end' => $prefix.$translate->_('dao.calendar_recurring_profile.event_end'),
			'recur_start' => $prefix.$translate->_('dao.calendar_recurring_profile.recur_start'),
			'recur_end' => $prefix.$translate->_('dao.calendar_recurring_profile.recur_end'),
			'is_available' => $prefix.$translate->_('dao.calendar_recurring_profile.is_available'),
			'tz' => $prefix.$translate->_('dao.calendar_recurring_profile.tz'),
			'patterns' => $prefix.$translate->_('dao.calendar_recurring_profile.patterns'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'event_start' => Model_CustomField::TYPE_SINGLE_LINE,
			'event_end' => Model_CustomField::TYPE_SINGLE_LINE,
			'recur_start' => Model_CustomField::TYPE_SINGLE_LINE,
			'recur_end' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_available' => Model_CustomField::TYPE_CHECKBOX,
			'tz' => Model_CustomField::TYPE_SINGLE_LINE,
			'patterns' => Model_CustomField::TYPE_MULTI_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		$token_values['_types'] = $token_types;
		
		if($calendar_recurring_profile) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $calendar_recurring_profile->event_name;
			$token_values['id'] = $calendar_recurring_profile->id;
			$token_values['name'] = $calendar_recurring_profile->event_name;
			$token_values['calendar_id'] = $calendar_recurring_profile->calendar_id;
			$token_values['event_start'] = $calendar_recurring_profile->event_start;
			$token_values['event_end'] = $calendar_recurring_profile->event_end;
			$token_values['recur_start'] = $calendar_recurring_profile->recur_start;
			$token_values['recur_end'] = $calendar_recurring_profile->recur_end;
			$token_values['is_available'] = $calendar_recurring_profile->is_available;
			$token_values['tz'] = $calendar_recurring_profile->tz;
			$token_values['patterns'] = $calendar_recurring_profile->patterns;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($calendar_recurring_profile, $token_values);
			
			// Calendar
			$merge_token_labels = array();
			$merge_token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_CALENDAR, null, $merge_token_labels, $merge_token_values, '', true);
	
			CerberusContexts::merge(
				'calendar_',
				$prefix.'Calendar:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=calendar_recurring_event&id=%d-%s",$calendar_recurring_profile->id, DevblocksPlatform::strToPermalink($calendar_recurring_profile->event_name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'calendar_id' => DAO_CalendarRecurringProfile::CALENDAR_ID,
			'event_end' => DAO_CalendarRecurringProfile::EVENT_END,
			'event_start' => DAO_CalendarRecurringProfile::EVENT_START,
			'id' => DAO_CalendarRecurringProfile::ID,
			'is_available' => DAO_CalendarRecurringProfile::IS_AVAILABLE,
			'name' => DAO_CalendarRecurringProfile::EVENT_NAME,
			'patterns' => DAO_CalendarRecurringProfile::PATTERNS,
			'recur_end' => DAO_CalendarRecurringProfile::RECUR_END,
			'recur_start' => DAO_CalendarRecurringProfile::RECUR_START,
			'tz' => DAO_CalendarRecurringProfile::TZ,
		];
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
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
		$view->name = 'Calendar Recurring Profile';
		$view->renderSortBy = SearchFields_CalendarRecurringProfile::EVENT_NAME;
		$view->renderSortAsc = true;
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
		$view->name = 'Calendar Recurring Profile';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($context_id)) {
			$model = DAO_CalendarRecurringProfile::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(!isset($model)) {
				$model = new Model_CalendarRecurringProfile();
				$model->is_available = 0;
				$model->tz = DevblocksPlatform::getTimezone();
				
				if(false != ($view = C4_AbstractViewLoader::getView($view_id))) {
					switch(get_class($view)) {
						case 'View_CalendarRecurringProfile':
							$filters = $view->findParam(SearchFields_CalendarRecurringProfile::CALENDAR_ID, $view->getParams());
							
							if(!empty($filters)) {
								$filter = array_shift($filters);
								if(is_numeric($filter->value))
									$model->calendar_id = $filter->value;
							}
							break;
					}
				}
			}
			$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Timezones
			$date = DevblocksPlatform::services()->date();
			$tpl->assign('timezones', $date->getTimezones());
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/calendar_recurring_profile/peek_edit.tpl');
			
		} else {
			// Counts
			$activity_counts = array(
				'comments' => DAO_Comment::count($context, $context_id),
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
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			// Interactions
			$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
			$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
			$tpl->assign('interactions_menu', $interactions_menu);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::internal/calendar_recurring_profile/peek.tpl');
		}
	}
};
