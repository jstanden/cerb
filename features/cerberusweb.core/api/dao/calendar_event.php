<?php
class DAO_CalendarEvent extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const RECURRING_ID = 'recurring_id';
	const IS_AVAILABLE = 'is_available';
	const DATE_START = 'date_start';
	const DATE_END = 'date_end';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO calendar_event () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'calendar_event', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('calendar_event', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CalendarEvent[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, recurring_id, is_available, date_start, date_end ".
			"FROM calendar_event ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CalendarEvent	 */
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
	 * @return Model_CalendarEvent[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CalendarEvent();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->recurring_id = $row['recurring_id'];
			$object->is_available = $row['is_available'];
			$object->date_start = intval($row['date_start']);
			$object->date_end = intval($row['date_end']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function countByRecurringId($recurring_id) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne(sprintf("SELECT count(id) AS hits FROM calendar_event WHERE recurring_id = %d", $recurring_id));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM calendar_event WHERE id IN (%s)", $ids_list));
		
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
	
	static function deleteByRecurringIds($ids, $from_timestamp=0) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM calendar_event WHERE recurring_id IN (%s) AND date_start >= %d", $ids_list, $from_timestamp));
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CalendarEvent::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"calendar_event.id as %s, ".
			"calendar_event.name as %s, ".
			"calendar_event.owner_context as %s, ".
			"calendar_event.owner_context_id as %s, ".
			"calendar_event.recurring_id as %s, ".
			"calendar_event.is_available as %s, ".
			"calendar_event.date_start as %s, ".
			"calendar_event.date_end as %s ",
				SearchFields_CalendarEvent::ID,
				SearchFields_CalendarEvent::NAME,
				SearchFields_CalendarEvent::OWNER_CONTEXT,
				SearchFields_CalendarEvent::OWNER_CONTEXT_ID,
				SearchFields_CalendarEvent::RECURRING_ID,
				SearchFields_CalendarEvent::IS_AVAILABLE,
				SearchFields_CalendarEvent::DATE_START,
				SearchFields_CalendarEvent::DATE_END
			);
			
		$join_sql = "FROM calendar_event ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'calendar_event.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'calendar_event',
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
			($has_multiple_values ? 'GROUP BY calendar_event.id ' : '').
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
			$object_id = intval($row[SearchFields_CalendarEvent::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT calendar_event.id) " : "SELECT COUNT(calendar_event.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_CalendarEvent implements IDevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const RECURRING_ID = 'c_recurring_id';
	const IS_AVAILABLE = 'c_is_available';
	const DATE_START = 'c_date_start';
	const DATE_END = 'c_date_end';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'calendar_event', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'calendar_event', 'name', $translate->_('common.name')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'calendar_event', 'owner_context', $translate->_('common.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'calendar_event', 'owner_context_id', $translate->_('common.owner_context_id')),
			self::RECURRING_ID => new DevblocksSearchField(self::RECURRING_ID, 'calendar_event', 'recurring_id', $translate->_('dao.calendar_event.recurring_id')),
			self::IS_AVAILABLE => new DevblocksSearchField(self::IS_AVAILABLE, 'calendar_event', 'is_available', $translate->_('dao.calendar_event.is_available')),
			self::DATE_START => new DevblocksSearchField(self::DATE_START, 'calendar_event', 'date_start', $translate->_('dao.calendar_event.date_start')),
			self::DATE_END => new DevblocksSearchField(self::DATE_END, 'calendar_event', 'date_end', $translate->_('dao.calendar_event.date_end')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_CalendarEvent {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $recurring_id;
	public $is_available;
	public $date_start;
	public $date_end;
};

class View_CalendarEvent extends C4_AbstractView {
	const DEFAULT_ID = 'calendar_events';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Calendar Events');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_CalendarEvent::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CalendarEvent::NAME,
			SearchFields_CalendarEvent::DATE_START,
			SearchFields_CalendarEvent::DATE_END,
			SearchFields_CalendarEvent::IS_AVAILABLE,
			//SearchFields_CalendarEvent::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_CalendarEvent::ID,
			SearchFields_CalendarEvent::RECURRING_ID,
			SearchFields_CalendarEvent::OWNER_CONTEXT,
			SearchFields_CalendarEvent::OWNER_CONTEXT_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CalendarEvent::ID,
			SearchFields_CalendarEvent::RECURRING_ID,
			SearchFields_CalendarEvent::OWNER_CONTEXT,
			SearchFields_CalendarEvent::OWNER_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CalendarEvent::search(
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
		return $this->_getDataAsObjects('DAO_CalendarEvent', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CalendarEvent', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:cerberusweb.core::internal/calendar/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CalendarEvent::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CalendarEvent::ID:
			case SearchFields_CalendarEvent::RECURRING_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_CalendarEvent::IS_AVAILABLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CalendarEvent::DATE_START:
			case SearchFields_CalendarEvent::DATE_END:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
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
			case SearchFields_CalendarEvent::IS_AVAILABLE:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CalendarEvent::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CalendarEvent::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CalendarEvent::ID:
			case SearchFields_CalendarEvent::RECURRING_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CalendarEvent::DATE_START:
			case SearchFields_CalendarEvent::DATE_END:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CalendarEvent::IS_AVAILABLE:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
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
					//$change_fields[DAO_CalendarEvent::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_CalendarEvent::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CalendarEvent::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_CalendarEvent::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_CalendarEvent::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_CalendarEvent extends Extension_DevblocksContext {
	function getMeta($context_id) {
		$calendar_event = DAO_CalendarEvent::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$friendly = DevblocksPlatform::strToPermalink($calendar_event->name);
		
		return array(
			'id' => $calendar_event->id,
			'name' => $calendar_event->name,
			//'permalink' => $url_writer->writeNoProxy(sprintf("c=tasks&action=display&id=%d-%s",$task->id, $friendly), true),
			'permalink' => null,
		);
	}
	
	function getRandom() {
		// [TODO]
		//return DAO_CalendarEvent::random();
		return null;
	}
	
	function getContext($calendar_event, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Calendar Event:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALENDAR_EVENT);

		// Polymorph
		if(is_numeric($calendar_event)) {
			$calendar_event = DAO_CalendarEvent::get($calendar_event);
		} elseif($calendar_event instanceof Model_CalendarEvent) {
			// It's what we want already.
		} else {
			$calendar_event = null;
		}
		
		// Token labels
		$token_labels = array(
			'date_end|date' => $prefix.$translate->_('dao.calendar_event.date_end'),
			'date_start|date' => $prefix.$translate->_('dao.calendar_event.date_start'),
			'id' => $prefix.$translate->_('common.id'),
			'is_available' => $prefix.$translate->_('dao.calendar_event.is_available'),
			'name' => $prefix.$translate->_('common.name'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CALENDAR_EVENT;
		
		if($calendar_event) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $calendar_event->name;
			$token_values['date_end'] = $calendar_event->date_end;
			$token_values['date_start'] = $calendar_event->date_start;
			$token_values['id'] = $calendar_event->id;
			$token_values['is_available'] = $calendar_event->is_available;
			$token_values['name'] = $calendar_event->name;
			
			// URL
			
			//$url_writer = DevblocksPlatform::getUrlService();
			//$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=tasks&action=display&id=%d-%s",$task->id, DevblocksPlatform::strToPermalink($task->title)), true);
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
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
	
	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Calendar Events';
		$view->view_columns = array(
			SearchFields_CalendarEvent::IS_AVAILABLE,
			SearchFields_CalendarEvent::DATE_START,
			SearchFields_CalendarEvent::DATE_END,
		);
		$view->addParams(array(
			new DevblocksSearchCriteria(SearchFields_CalendarEvent::DATE_START,'between',array('now','+1 month')),
		), true);
		
		$required_params = array();

		// [TODO] This should still filter out on VAs 
		/*
		if(!empty($active_worker)) {
			$required_params['_owner'] = array(
				array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_CalendarEvent::OWNER_CONTEXT,'=',CerberusContexts::CONTEXT_WORKER),
					new DevblocksSearchCriteria(SearchFields_CalendarEvent::OWNER_CONTEXT_ID,'=',$active_worker->id),
				)
			);
		}
		*/
		
		$view->addParamsRequired($required_params, true);
		$view->renderSortBy = SearchFields_CalendarEvent::DATE_START;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = true;
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
		$view->name = 'Calendar Events';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CalendarEvent::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CalendarEvent::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
