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
	const IS_AVAILABLE = 'is_available';
	const CALENDAR_ID = 'calendar_id';
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
			parent::_update($batch_ids, 'calendar_recurring_profile', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.calendar_recurring_profile.update',
						array(
							'objects' => $object_changes,
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
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, event_name, is_available, calendar_id, tz, event_start, event_end, recur_end, patterns ".
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
	 * @return Model_CalendarRecurringProfile
	 */
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
			$object->is_available = $row['is_available'];
			$object->calendar_id = $row['calendar_id'];
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
		$eventMgr = DevblocksPlatform::getEventService();
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
			"calendar_recurring_profile.is_available as %s, ".
			"calendar_recurring_profile.calendar_id as %s, ".
			"calendar_recurring_profile.tz as %s, ".
			"calendar_recurring_profile.event_start as %s, ".
			"calendar_recurring_profile.event_end as %s, ".
			"calendar_recurring_profile.recur_end as %s, ".
			"calendar_recurring_profile.patterns as %s ",
				SearchFields_CalendarRecurringProfile::ID,
				SearchFields_CalendarRecurringProfile::EVENT_NAME,
				SearchFields_CalendarRecurringProfile::IS_AVAILABLE,
				SearchFields_CalendarRecurringProfile::CALENDAR_ID,
				SearchFields_CalendarRecurringProfile::TZ,
				SearchFields_CalendarRecurringProfile::EVENT_START,
				SearchFields_CalendarRecurringProfile::EVENT_END,
				SearchFields_CalendarRecurringProfile::RECUR_END,
				SearchFields_CalendarRecurringProfile::PATTERNS
			);
			
		$join_sql = "FROM calendar_recurring_profile ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.calendar_event.recurring' AND context_link.to_context_id = calendar_recurring_profile.id) " : " ").
			'';
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'calendar_recurring_profile.id',
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
			array('DAO_CalendarRecurringProfile', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'calendar_recurring_profile',
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
			
		$from_context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		$from_index = 'calendar_recurring_profile.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
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
	const IS_AVAILABLE = 'c_is_available';
	const CALENDAR_ID = 'c_calendar_id';
	const TZ = 'c_tz';
	const EVENT_START = 'c_event_start';
	const EVENT_END = 'c_event_end';
	const RECUR_END = 'c_recur_end';
	const PATTERNS = 'c_patterns';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar_recurring_profile', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER),
			self::EVENT_NAME => new DevblocksSearchField(self::EVENT_NAME, 'calendar_recurring_profile', 'event_name', $translate->_('dao.calendar_recurring_profile.event_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::IS_AVAILABLE => new DevblocksSearchField(self::IS_AVAILABLE, 'calendar_recurring_profile', 'is_available', $translate->_('dao.calendar_recurring_profile.is_available'), Model_CustomField::TYPE_CHECKBOX),
			self::CALENDAR_ID => new DevblocksSearchField(self::CALENDAR_ID, 'calendar_recurring_profile', 'calendar_id', $translate->_('common.calendar')),
			self::TZ => new DevblocksSearchField(self::TZ, 'calendar_recurring_profile', 'tz', $translate->_('dao.calendar_recurring_profile.tz'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EVENT_START => new DevblocksSearchField(self::EVENT_START, 'calendar_recurring_profile', 'event_start', $translate->_('dao.calendar_recurring_profile.event_start'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EVENT_END => new DevblocksSearchField(self::EVENT_END, 'calendar_recurring_profile', 'event_end', $translate->_('dao.calendar_recurring_profile.event_end'), Model_CustomField::TYPE_SINGLE_LINE),
			self::RECUR_END => new DevblocksSearchField(self::RECUR_END, 'calendar_recurring_profile', 'recur_end', $translate->_('dao.calendar_recurring_profile.recur_end'), Model_CustomField::TYPE_DATE),
			self::PATTERNS => new DevblocksSearchField(self::PATTERNS, 'calendar_recurring_profile', 'patterns', $translate->_('dao.calendar_recurring_profile.patterns'), Model_CustomField::TYPE_MULTI_LINE),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING,
		));
		
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
					// This is a B.S. workaround for a PHP bug
					// If we can do things the right way
					if(version_compare(PHP_VERSION, '5.3.6', '>=')) {
						$timezone = new DateTimeZone($this->tz);
						$datetime = new DateTime(date('Y-m-d', $day), $timezone);
						
						$datetime->modify($this->event_start ?: 'midnight');
						$event_start_local = $datetime->getTimestamp();
						
						$datetime->modify($this->event_end ?: 'midnight');
						$event_end_local = $datetime->getTimestamp();
						
					// If we have to hack around the PHP bug with DateTime::modify($absolute_time)
					} else {
						$datetime_date = date('Y-m-d', $day);
						$previous_timezone = date_default_timezone_get();
						date_default_timezone_set($this->tz);
						$datetime = strtotime($datetime_date);
						
						@$event_start_local = strtotime($this->event_start ?: 'midnight', $datetime);
						@$event_end_local = strtotime($this->event_end ?: 'midnight', $datetime);
						date_default_timezone_set($previous_timezone);
					}
					
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

class View_CalendarRecurringProfile extends C4_AbstractView implements IAbstractView_Subtotals {
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
			SearchFields_CalendarRecurringProfile::RECUR_END,
			SearchFields_CalendarRecurringProfile::PATTERNS,
		);

		$this->addColumnsHidden(array(
			SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK,
			SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET,
			SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CalendarRecurringProfile::CALENDAR_ID:
				$calendars = DAO_Calendar::getAll();
				$label_map = array();
				
				if(is_array($calendars))
				foreach($calendars as $calendar)
					$label_map[$calendar->id] = $calendar->name;
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CalendarRecurringProfile', $column, $label_map, DevblocksSearchCriteria::OPER_IN, 'context_id[]');
				break;
			
			case SearchFields_CalendarRecurringProfile::IS_AVAILABLE:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_CalendarRecurringProfile', $column);
				break;

			case SearchFields_CalendarRecurringProfile::TZ:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CalendarRecurringProfile', $column);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_CalendarRecurringProfile', CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $column);
				break;

			case SearchFields_CalendarRecurringProfile::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_CalendarRecurringProfile', CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $column);
				break;
				
			case SearchFields_CalendarRecurringProfile::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_CalendarRecurringProfile', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_CalendarRecurringProfile', $column, 'calendar_recurring_profile.id');
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
		
		$tpl = DevblocksPlatform::getTemplateService();
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
					//$change_fields[DAO_CalendarRecurringProfile::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_CalendarRecurringProfile::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CalendarRecurringProfile::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_CalendarRecurringProfile::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_CalendarRecurringProfile extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	function getRandom() {
		//return DAO_CalendarRecurringProfile::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=calendar_recurring_profile&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$calendar_recurring_profile = DAO_CalendarRecurringProfile::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($calendar_recurring_profile->event_name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $calendar_recurring_profile->id,
			'name' => $calendar_recurring_profile->event_name,
			'permalink' => $url,
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
		} else {
			$calendar_recurring_profile = null;
		}
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'event_start' => $prefix.$translate->_('dao.calendar_recurring_profile.event_start'),
			'event_end' => $prefix.$translate->_('dao.calendar_recurring_profile.event_end'),
			'recur_end' => $prefix.$translate->_('dao.calendar_recurring_profile.recur_end'),
			'is_available' => $prefix.$translate->_('dao.calendar_recurring_profile.is_available'),
			'tz' => $prefix.$translate->_('dao.calendar_recurring_profile.tz'),
			'patterns' => $prefix.$translate->_('dao.calendar_recurring_profile.patterns'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		
		if($calendar_recurring_profile) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $calendar_recurring_profile->event_name;
			$token_values['id'] = $calendar_recurring_profile->id;
			$token_values['name'] = $calendar_recurring_profile->event_name;
			$token_values['calendar_id'] = $calendar_recurring_profile->calendar_id;
			$token_values['event_start'] = $calendar_recurring_profile->event_start;
			$token_values['event_end'] = $calendar_recurring_profile->event_end;
			$token_values['recur_end'] = $calendar_recurring_profile->recur_end;
			$token_values['is_available'] = $calendar_recurring_profile->is_available;
			$token_values['tz'] = $calendar_recurring_profile->tz;
			$token_values['patterns'] = $calendar_recurring_profile->patterns;

			// Calendar
			$merge_token_labels = array();
			$merge_token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_CALENDAR, null, $merge_token_labels, $merge_token_values, '', true);
	
			CerberusContexts::merge(
				'calendar_',
				'Calendar:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=calendar_recurring_event&id=%d-%s",$calendar_recurring_profile->id, DevblocksPlatform::strToPermalink($calendar_recurring_profile->event_name)), true);
		}
		
		return true;
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
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
		$view->name = 'Calendar Recurring Profile';
		$view->renderSortBy = SearchFields_CalendarRecurringProfile::EVENT_NAME;
		$view->renderSortAsc = true;
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
		$view->name = 'Calendar Recurring Profile';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CalendarRecurringProfile::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CalendarRecurringProfile::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($calendar_recurring_profile = DAO_CalendarRecurringProfile::get($context_id))) {
			$tpl->assign('model', $calendar_recurring_profile);
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		if(empty($context_id) || is_null($calendar_recurring_profile)) {
			@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'],'integer');
			
			$model = new Model_CalendarRecurringProfile();
			$model->id = 0;
			$model->calendar_id = $calendar_id;
			$model->is_available = 0;
			$model->tz = @$_SESSION['timezone'] ?: date_default_timezone_get();
			$tpl->assign('model', $model);
		}

		// Calendars
		if(empty($context_id)) {
			$active_worker = CerberusApplication::getActiveWorker();
			$calendars = DAO_Calendar::getWriteableByWorker($active_worker);
			$tpl->assign('calendars', $calendars);
		}
		
		// Timezones
		
		$date = DevblocksPlatform::getDateService();
		$tpl->assign('timezones', $date->getTimezones());
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar_recurring_profile/peek.tpl');
	}
	
	/*
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_CalendarRecurringProfile::EVENT_NAME,
				'required' => true,
			),
		);
	
		$cfields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING);
	
		foreach($cfields as $cfield_id => $cfield) {
			$keys['cf_' . $cfield_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'param' => 'cf_' . $cfield_id,
			);
		}
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_CalendarRecurringProfile::EVENT_NAME])) {
				$fields[DAO_CalendarRecurringProfile::EVENT_NAME] = 'New ' . $this->manifest->name;
			}
	
			// Create
			$meta['object_id'] = DAO_CalendarRecurringProfile::create($fields);
	
		} else {
			// Update
			DAO_CalendarRecurringProfile::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
	*/
};
