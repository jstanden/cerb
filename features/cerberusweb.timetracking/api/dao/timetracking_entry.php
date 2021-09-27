<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class DAO_TimeTrackingEntry extends Cerb_ORMHelper {
	const ACTIVITY_ID = 'activity_id';
	const ID = 'id';
	const IS_CLOSED = 'is_closed';
	const LOG_DATE = 'log_date';
	const TIME_ACTUAL_MINS = 'time_actual_mins';
	const TIME_ACTUAL_SECS = 'time_actual_secs';
	const WORKER_ID = 'worker_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ACTIVITY_ID)
			->id()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_CLOSED)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::LOG_DATE)
			->timestamp()
			;
		// smallint(5) unsigned
		$validation
			->addField(self::TIME_ACTUAL_MINS)
			->uint(2)
			->setNotEmpty(true)
			->setRequired(true)
			;
		// int unsigned
		$validation
			->addField(self::TIME_ACTUAL_SECS)
			->uint(4)
			->setNotEmpty(true)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER))
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::LOG_DATE]))
			$fields[self::LOG_DATE] = time();
		
		$sql = sprintf("INSERT INTO timetracking_entry () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_TIMETRACKING, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TIMETRACKING, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'timetracking_entry', $fields);
			
			// Send events
			if($check_deltas) {
				// Local events
				self::processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.timetracking.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TIMETRACKING, $batch_ids);
			}
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::WORKER_ID])) {
			$error = "A 'worker_id' is required.";
			return false;
		}
		
		// If given secs and not mins
		if(array_key_exists(self::TIME_ACTUAL_SECS, $fields) && !array_key_exists(self::TIME_ACTUAL_MINS, $fields))
			$fields[self::TIME_ACTUAL_MINS] = ceil($fields[self::TIME_ACTUAL_SECS] / 60);
		
		// If given mins and not secs
		if(array_key_exists(self::TIME_ACTUAL_MINS, $fields) && !array_key_exists(self::TIME_ACTUAL_SECS, $fields))
			$fields[self::TIME_ACTUAL_SECS] = $fields[self::TIME_ACTUAL_MINS] * 60;
		
		if(isset($fields[self::WORKER_ID])) {
			@$worker_id = $fields[self::WORKER_ID];
			
			if(!$worker_id) {
				$error = "Invalid 'worker_id' value.";
				return false;
			}
			
			if(!CerberusContexts::isOwnableBy(CerberusContexts::CONTEXT_WORKER, $worker_id, $actor)) {
				$error = "You do not have permission to create time entries for this worker.";
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_closed':
					$change_fields[DAO_TimeTrackingEntry::IS_CLOSED] = $v;
					break;
					
				case 'activity_id':
					$change_fields[DAO_TimeTrackingEntry::ACTIVITY_ID] = $v;
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TIMETRACKING, $ids);
		
		if(!empty($change_fields)) {
			DAO_TimeTrackingEntry::update($ids, $change_fields, false);
			DAO_TimeTrackingEntry::processUpdateEvents($ids, $change_fields);
		}

		// Custom Fields
		if(!empty($custom_fields))
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TIMETRACKING, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_TIMETRACKING, $do['behavior'], $ids);
		
		// Watchers
		if(isset($do['watchers']))
			C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_TIMETRACKING, $do['watchers'], $ids);
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TIMETRACKING, $ids);
		
		$update->markCompleted();
		return true;
	}
	
	static function processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_TimeTrackingEntry::IS_CLOSED,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_TIMETRACKING, $ids)))
			return;
		
		if(false == ($models = DAO_TimeTrackingEntry::getIds($ids)))
			return;
		
		foreach($models as $id => $model) {
			if(!isset($before_models[$id]))
				continue;
			
			$before_model = (object) $before_models[$id];
			
			/*
			 * Activity Log: Time tracking status change
			 */
			
			@$is_closed = $change_fields[DAO_TimeTrackingEntry::IS_CLOSED];
			
			if($is_closed == $before_model->is_closed)
				unset($change_fields[DAO_TimeTrackingEntry::IS_CLOSED]);
			
			if(isset($change_fields[DAO_TimeTrackingEntry::IS_CLOSED])) {
				
				$status_to = null;
				$activity_point = null;
				
				if($model->is_closed) {
					$status_to = 'closed';
					$activity_point = 'timetracking.status.closed';
					
				} else {
					$status_to = 'open';
					$activity_point = 'timetracking.status.open';
					
				}
				
				if(!empty($status_to) && !empty($activity_point)) {
					/*
					 * Log activity (timetracking.status.*)
					 */
					$entry = array(
						//{{actor}} changed time tracking {{target}} to status {{status}}
						'message' => 'activities.timetracking.status',
						'variables' => array(
							'target' => sprintf("%s", $model->getSummary()),
							'status' => $status_to,
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TIMETRACKING, $model->id, $model->getSummary()),
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TIMETRACKING, $model->id, $entry);
				}
				
			} //foreach
		}
		
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('timetracking_entry', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_TimeTrackingEntry[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, time_actual_mins, time_actual_secs, log_date, worker_id, activity_id, is_closed ".
			"FROM timetracking_entry ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TimeTrackingEntry	 */
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
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_TimeTrackingEntry[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TimeTrackingEntry();
			$object->id = $row['id'];
			$object->time_actual_mins = intval($row['time_actual_mins']);
			$object->time_actual_secs = intval($row['time_actual_secs']);
			$object->log_date = $row['log_date'];
			$object->worker_id = $row['worker_id'];
			$object->activity_id = $row['activity_id'];
			$object->is_closed = $row['is_closed'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader("SELECT count(id) FROM timetracking_entry");
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Entries
		$db->ExecuteMaster(sprintf("DELETE FROM timetracking_entry WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_TIMETRACKING,
					'context_ids' => $ids
				)
			)
		);

		return true;
	}

	static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_TIMETRACKING,
					'context_table' => 'timetracking_entry',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function random() {
		return self::_getRandom('timetracking_entry');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
			$fields = SearchFields_TimeTrackingEntry::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_TimeTrackingEntry', $sortBy);

		$select_sql = sprintf("SELECT ".
			"tt.id as %s, ".
			"tt.time_actual_mins as %s, ".
			"tt.time_actual_secs as %s, ".
			"tt.log_date as %s, ".
			"tt.worker_id as %s, ".
			"tt.activity_id as %s, ".
			"tt.is_closed as %s ",
			SearchFields_TimeTrackingEntry::ID,
			SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS,
			SearchFields_TimeTrackingEntry::TIME_ACTUAL_SECS,
			SearchFields_TimeTrackingEntry::LOG_DATE,
			SearchFields_TimeTrackingEntry::WORKER_ID,
			SearchFields_TimeTrackingEntry::ACTIVITY_ID,
			SearchFields_TimeTrackingEntry::IS_CLOSED
		);
		
		$join_sql =
			"FROM timetracking_entry tt ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_TimeTrackingEntry');
		
		$result = array(
			'primary_table' => 'tt',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 *
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_TimeTrackingEntry::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class Model_TimeTrackingEntry {
	public $id;
	public $time_actual_mins;
	public $time_actual_secs;
	public $log_date;
	public $worker_id;
	public $activity_id;
	public $is_closed;
	
	function getSummary() {
		$translate = DevblocksPlatform::getTranslationService();
		$out = '';
		
		$activity = '';
		if(!empty($this->activity_id))
			$activity = DAO_TimeTrackingActivity::get($this->activity_id); // [TODO] Cache?

		$time_increment = DevblocksPlatform::strSecsToString($this->time_actual_secs, 2);
		
		$who = 'A worker';
		if(null != ($worker = DAO_Worker::get($this->worker_id)))
			$who = $worker->getName();

		if(!empty($activity)) {
			$out = vsprintf($translate->_('timetracking.ui.tracked_desc'), array(
				$who,
				$time_increment,
				$activity->name
			));
			
		} else {
			$out = vsprintf("%s tracked %s", array(
				$who,
				$time_increment
			));
			
		}

		return $out;
	}
};

class SearchFields_TimeTrackingEntry extends DevblocksSearchFields {
	// TimeTracking_Entry
	const ID = 'tt_id';
	const TIME_ACTUAL_MINS = 'tt_time_actual_mins';
	const TIME_ACTUAL_SECS = 'tt_time_actual_secs';
	const LOG_DATE = 'tt_log_date';
	const WORKER_ID = 'tt_worker_id';
	const ACTIVITY_ID = 'tt_activity_id';
	const IS_CLOSED = 'tt_is_closed';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_owners';
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'tt.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_TIMETRACKING => new DevblocksSearchFieldContextKeys('tt.id', self::ID),
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('tt.worker_id', self::WORKER_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_TIMETRACKING, self::getPrimaryKey());
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_TIMETRACKING, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TIMETRACKING), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_TIMETRACKING, self::getPrimaryKey());
			
			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'tt.worker_id');
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'activity':
				$key = 'activity.id';
				break;
				
			case 'closed':
				$key = 'isClosed';
				break;
				
			case 'worker':
				$key = 'worker.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$models = DAO_TimeTrackingActivity::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				break;
				
			case SearchFields_TimeTrackingEntry::ID:
				$models = DAO_TimeTrackingEntry::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_TIMETRACKING);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
				
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				return parent::_getLabelsForKeyBooleanValues();
				break;
				
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
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
			self::ID => new DevblocksSearchField(self::ID, 'tt', 'id', $translate->_('common.id'), null, true),
			self::TIME_ACTUAL_MINS => new DevblocksSearchField(self::TIME_ACTUAL_MINS, 'tt', 'time_actual_mins', $translate->_('timetracking.ui.entry_panel.time_spent'), Model_CustomField::TYPE_NUMBER, true),
			self::TIME_ACTUAL_SECS => new DevblocksSearchField(self::TIME_ACTUAL_SECS, 'tt', 'time_actual_secs', $translate->_('timetracking.ui.entry_panel.time_spent'), Model_CustomField::TYPE_NUMBER, true),
			self::LOG_DATE => new DevblocksSearchField(self::LOG_DATE, 'tt', 'log_date', $translate->_('timetracking_entry.log_date'), Model_CustomField::TYPE_DATE, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'tt', 'worker_id', $translate->_('timetracking_entry.worker_id'), Model_CustomField::TYPE_WORKER, true),
			self::ACTIVITY_ID => new DevblocksSearchField(self::ACTIVITY_ID, 'tt', 'activity_id', $translate->_('timetracking_entry.activity_id'), null, true),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'tt', 'is_closed', $translate->_('common.is_closed'), Model_CustomField::TYPE_CHECKBOX, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'owners', $translate->_('common.watchers'), 'WS', false),
			self::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(self::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, false),
				
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class View_TimeTracking extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
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
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TimeTrackingEntry::ID,
			SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT,
			SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK,
			SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET,
			SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS,
			SearchFields_TimeTrackingEntry::VIRTUAL_WORKER_SEARCH,
		));
		
		$this->addParamsDefault(array(
			SearchFields_TimeTrackingEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::IS_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_TimeTrackingEntry::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_TimeTrackingEntry');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_TimeTrackingEntry', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TimeTrackingEntry', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				case SearchFields_TimeTrackingEntry::IS_CLOSED:
				case SearchFields_TimeTrackingEntry::WORKER_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_')) {
						$pass = $this->_canSubtotalCustomField($field_key);
					}
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_TIMETRACKING;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_TimeTrackingEntry::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, DevblocksSearchCriteria::OPER_IN, 'options[]');
				break;
				
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$label_map = [];
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in', 'worker_id[]');
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	// [TODO] activity
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_TimeTrackingEntry::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT),
				),
			'activity.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_TIMETRACKING_ACTIVITY,
					],
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::ACTIVITY_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TIMETRACKING_ACTIVITY, 'q' => ''],
					]
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::LOG_DATE),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_TIMETRACKING],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TIMETRACKING, 'q' => ''],
					]
				),
			'isClosed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::IS_CLOSED),
				),
			'timeSpent' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER_SECONDS,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::TIME_ACTUAL_SECS),
					'examples' => array(
						'30',
						'"< 1 hour"',
						'">= 1 hour"',
						'[30,60,90]',
						'![0]',
						'1...60',
						'"1 min ... 1 hour"',
					),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'worker.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::WORKER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TIMETRACKING, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['comments']['examples'] = $ft_examples;
		}
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'secs':
			case 'mins':
			case 'timeSpent':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens, 1);
				
				$field_key = SearchFields_TimeTrackingEntry::TIME_ACTUAL_SECS;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS, $tokens);
				
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_TimeTrackingEntry::VIRTUAL_WORKER_SEARCH);
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$activities = DAO_TimeTrackingActivity::getWhere(); // [TODO] getAll cache
		$tpl->assign('activities', $activities);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.timetracking::timetracking/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
				
			case SearchFields_TimeTrackingEntry::VIRTUAL_WORKER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;

			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_SECS:
				$strings = [];
				$sep = ' or ';
				
				if($param->operator == DevblocksSearchCriteria::OPER_BETWEEN)
					$sep = ' and ';
				
				foreach($values as $value) {
					if(empty($value)) {
						$strings[] = 'never';
					} else {
						$strings[] = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value, 2));
					}
				}
				
				echo implode($sep, $strings);
				break;
				
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
				$strings = [];
				$sep = ' or ';
				
				if($param->operator == DevblocksSearchCriteria::OPER_BETWEEN)
					$sep = ' and ';
				
				foreach($values as $value) {
					if(empty($value)) {
						$strings[] = 'never';
					} else {
						$strings[] = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value*60, 2));
					}
				}
				
				echo implode($sep, $strings);
				break;
				
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				$label_map = SearchFields_TimeTrackingEntry::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_TimeTrackingEntry::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TimeTrackingEntry::ID:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_MINS:
			case SearchFields_TimeTrackingEntry::TIME_ACTUAL_SECS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			case SearchFields_TimeTrackingEntry::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			case SearchFields_TimeTrackingEntry::LOG_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			case SearchFields_TimeTrackingEntry::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			case SearchFields_TimeTrackingEntry::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
			case SearchFields_TimeTrackingEntry::ACTIVITY_ID:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
			case SearchFields_TimeTrackingEntry::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};

class Context_TimeTracking extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.timetracking';
	const URI = 'time_entry';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getDaoClass() {
		return 'DAO_TimeTrackingEntry';
	}
	
	function getSearchClass() {
		return 'SearchFields_TimeTrackingEntry';
	}
	
	function getViewClass() {
		return 'View_TimeTracking';
	}
	
	function getRandom() {
		return DAO_TimeTrackingEntry::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=time_tracking&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_TimeTrackingEntry();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['status'] = array(
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => ($model->is_closed) ? $translate->_('status.closed') : $translate->_('status.open'),
		);
		
		$properties['log_date'] = array(
			'label' => mb_ucfirst($translate->_('timetracking_entry.log_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->log_date,
		);
		
		$properties['worker_id'] = array(
			'label' => mb_ucfirst($translate->_('common.worker')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_WORKER),
			'value' => $model->worker_id,
		);
		
		$properties['time_spent'] = array(
			'label' => mb_ucfirst($translate->_('timetracking.ui.entry_panel.time_spent')),
			'type' => 'time_secs',
			'value' => $model->time_actual_secs,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($time_entry = DAO_TimeTrackingEntry::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		
		$summary = $time_entry->getSummary();
		$friendly = DevblocksPlatform::strToPermalink($summary);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $time_entry->id,
			'name' => $summary,
			'permalink' => $url,
			'updated' => $time_entry->log_date, // [TODO]
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
	
	// [TODO] Include the activity
	// [TODO] 'time_mins' type (mins) doesn't render on cards/profiles properly
	function getDefaultProperties() {
		return array(
			'worker__label',
			'log_date',
			'is_closed',
		);
	}
	
	function getContext($timeentry, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Time Entry:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING);
		
		// Polymorph
		if(is_numeric($timeentry)) {
			$timeentry = DAO_TimeTrackingEntry::get($timeentry);
		} elseif($timeentry instanceof Model_TimeTrackingEntry) {
			// It's what we want already.
		} elseif(is_array($timeentry)) {
			$timeentry = Cerb_ORMHelper::recastArrayToModel($timeentry, 'Model_TimeTrackingEntry');
		} else {
			$timeentry = null;
		}
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'is_closed' => $prefix.$translate->_('common.is_closed'),
			'log_date' => $prefix.$translate->_('timetracking_entry.log_date'),
			'mins' => $prefix.$translate->_('timetracking.ui.entry_panel.time_spent'),
			'summary' => $prefix.$translate->_('common.summary'),
				
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'log_date' => Model_CustomField::TYPE_DATE,
			'mins' => 'time_mins',
			'summary' => Model_CustomField::TYPE_SINGLE_LINE,
				
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		$blank = [];
		
		$token_values['_context'] = Context_TimeTracking::ID;
		$token_values['_type'] = Context_TimeTracking::URI;
		
		$token_values['_types'] = $token_types;
		
		if(null != $timeentry) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $timeentry->getSummary();
			$token_values['log_date'] = $timeentry->log_date;
			$token_values['id'] = $timeentry->id;
			$token_values['mins'] = $timeentry->time_actual_mins;
			$token_values['secs'] = $timeentry->time_actual_secs;
			$token_values['summary'] = $timeentry->getSummary();
			$token_values['is_closed'] = $timeentry->is_closed;
			$token_values['activity_id'] = $timeentry->activity_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($timeentry, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=time_tracking&id=%d-%s",$timeentry->id, DevblocksPlatform::strToPermalink($timeentry->getSummary())), true);
			
			// Worker
			$token_values['worker_id'] = $timeentry->worker_id ?? 0;
		}
		
		// Worker
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, null, true);

			// Clear dupe labels
			CerberusContexts::scrubTokensWithRegexp(
				$merge_token_labels,
				$blank, // ignore
				array(
					"#^address_contact_first_name$#",
					"#^address_contact_full_name$#",
					"#^address_contact_last_name$#",
				)
			);
		
			CerberusContexts::merge(
				'worker_',
				$prefix,
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'activity_id' => DAO_TimeTrackingEntry::ACTIVITY_ID,
			'id' => DAO_TimeTrackingEntry::ID,
			'is_closed' => DAO_TimeTrackingEntry::IS_CLOSED,
			'links' => '_links',
			'log_date' => DAO_TimeTrackingEntry::LOG_DATE,
			'mins' => DAO_TimeTrackingEntry::TIME_ACTUAL_MINS,
			'secs' => DAO_TimeTrackingEntry::TIME_ACTUAL_SECS,
			'worker_id' => DAO_TimeTrackingEntry::WORKER_ID,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['activity_id']['notes'] = "The ID of the [activity](/docs/records/types/timetracking_activity/) for the work";
		$keys['is_closed']['notes'] = "Is this time entry archived?";
		$keys['log_date']['notes'] = "The date/time of the work";
		$keys['mins']['notes'] = "The number of minutes worked (alternative to `secs`)";
		$keys['secs']['notes'] = "The number of seconds worked (alternative to `mins`)";
		$keys['worker_id']['notes'] = "The ID of the [worker](/docs/records/types/worker/) who completed the work";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'mins':
				$out_fields[DAO_TimeTrackingEntry::TIME_ACTUAL_MINS] = intval($value);
				
				if(!array_key_exists(DAO_TimeTrackingEntry::TIME_ACTUAL_SECS, $out_fields))
					$out_fields[DAO_TimeTrackingEntry::TIME_ACTUAL_SECS] = intval($value) * 60;
				break;
				
			case 'secs':
				$out_fields[DAO_TimeTrackingEntry::TIME_ACTUAL_SECS] = intval($value);
				
				if(!array_key_exists(DAO_TimeTrackingEntry::TIME_ACTUAL_MINS, $out_fields))
					$out_fields[DAO_TimeTrackingEntry::TIME_ACTUAL_MINS] = ceil(intval($value) / 60);
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Time Tracking';
		$view->view_columns = array(
			SearchFields_TimeTrackingEntry::LOG_DATE,
		);
		$view->addParams(array(
			SearchFields_TimeTrackingEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Time Tracking';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_TimeTrackingEntry::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}

		if(!$context_id || $edit) {
			if($model) {
				if(!Context_TimeTracking::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Activities
			$activities = DAO_TimeTrackingActivity::getAll();
			$tpl->assign('activities', $activities);
			
			// Default model
			if(!isset($model)) {
				$model = new Model_TimeTrackingEntry();
				$model->log_date = time();
	
				// Initial time
				
				@$total_secs = DevblocksPlatform::importGPC($_REQUEST['secs'],'integer',0);
				$model->time_actual_mins = ceil($total_secs / 60);
				$model->time_actual_secs = $total_secs;
				
				// If we're linking a context during creation
				
				@$link_context = DevblocksPlatform::strLower($_SESSION['timetracking_context']);
				@$link_context_id = intval($_SESSION['timetracking_context_id']);
				
				/* If the session was empty, don't set these since they may have been
				 * previously set by the abstract context peek code.
				 */
				
				if(!empty($link_context)) {
					$tpl->assign('link_context', $link_context);
					$tpl->assign('link_context_id', $link_context_id);
				}
			}
			
			$tpl->assign('model', $model);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.timetracking::timetracking/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};