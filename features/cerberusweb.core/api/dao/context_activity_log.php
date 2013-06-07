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

class DAO_ContextActivityLog extends Cerb_ORMHelper {
	const ID = 'id';
	const ACTIVITY_POINT = 'activity_point';
	const ACTOR_CONTEXT = 'actor_context';
	const ACTOR_CONTEXT_ID = 'actor_context_id';
	const TARGET_CONTEXT = 'target_context';
	const TARGET_CONTEXT_ID = 'target_context_id';
	const CREATED = 'created';
	const ENTRY_JSON = 'entry_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO context_activity_log () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		@$target_context = $fields[DAO_ContextActivityLog::TARGET_CONTEXT];
		@$target_context_id = $fields[DAO_ContextActivityLog::TARGET_CONTEXT_ID];

		if(is_null($target_context))
			$fields[DAO_ContextActivityLog::TARGET_CONTEXT] = '';
		
		if(is_null($target_context_id))
			$fields[DAO_ContextActivityLog::TARGET_CONTEXT_ID] = 0;
		
		parent::_update($ids, 'context_activity_log', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_activity_log', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextActivityLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, activity_point, actor_context, actor_context_id, target_context, target_context_id, created, entry_json ".
			"FROM context_activity_log ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContextActivityLog	 */
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
	 * @return Model_ContextActivityLog[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContextActivityLog();
			$object->id = $row['id'];
			$object->activity_point = $row['activity_point'];
			$object->actor_context = $row['actor_context'];
			$object->actor_context_id = $row['actor_context_id'];
			$object->target_context = $row['target_context'];
			$object->target_context_id = $row['target_context_id'];
			$object->created = $row['created'];
			$object->entry_json = $row['entry_json'];
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
		
		$db->Execute(sprintf("DELETE FROM context_activity_log WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM context_activity_log WHERE target_context = %s AND target_context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextActivityLog::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_activity_log.id as %s, ".
			"context_activity_log.activity_point as %s, ".
			"context_activity_log.actor_context as %s, ".
			"context_activity_log.actor_context_id as %s, ".
			"context_activity_log.target_context as %s, ".
			"context_activity_log.target_context_id as %s, ".
			"context_activity_log.created as %s, ".
			"context_activity_log.entry_json as %s ",
				SearchFields_ContextActivityLog::ID,
				SearchFields_ContextActivityLog::ACTIVITY_POINT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::CREATED,
				SearchFields_ContextActivityLog::ENTRY_JSON
			);
			
		$join_sql = "FROM context_activity_log ";
		
		$has_multiple_values = false;
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_ContextActivityLog', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'context_activity_log',
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

		$from_context = CerberusContexts::CONTEXT_ACTIVITY_LOG;
		$from_index = 'context_activity_log.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				switch($param_key) {
					case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
						$context_field = 'actor_context';
						break;
					case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
						$context_field = 'target_context';
						break;
				}

				if(is_array($param->value)) {
					$wheres = array();
					foreach($param->value as $context_pair) {
						@list($context, $context_id) = explode(':', $context_pair);
						if(!empty($context_id)) {
							$wheres[] = sprintf("(%s = %s AND %s_id = %d)",
								$context_field,
								Cerb_ORMHelper::qstr($context),
								$context_field,
								$context_id
							);
						} else {
							$wheres[] = sprintf("(%s = %s)",
								$context_field,
								Cerb_ORMHelper::qstr($context)
							);
						}
					}
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= ' AND ' . implode(' OR ', $wheres);
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
			($has_multiple_values ? 'GROUP BY context_activity_log.id ' : '').
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
			$object_id = intval($row[SearchFields_ContextActivityLog::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT context_activity_log.id) " : "SELECT COUNT(context_activity_log.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContextActivityLog implements IDevblocksSearchFields {
	const ID = 'c_id';
	const ACTIVITY_POINT = 'c_activity_point';
	const ACTOR_CONTEXT = 'c_actor_context';
	const ACTOR_CONTEXT_ID = 'c_actor_context_id';
	const TARGET_CONTEXT = 'c_target_context';
	const TARGET_CONTEXT_ID = 'c_target_context_id';
	const CREATED = 'c_created';
	const ENTRY_JSON = 'c_entry_json';
	
	const VIRTUAL_ACTOR = '*_actor';
	const VIRTUAL_TARGET = '*_target';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'context_activity_log', 'id', $translate->_('common.id'), null),
			self::ACTIVITY_POINT => new DevblocksSearchField(self::ACTIVITY_POINT, 'context_activity_log', 'activity_point', $translate->_('dao.context_activity_log.activity_point'), Model_CustomField::TYPE_SINGLE_LINE),
			self::ACTOR_CONTEXT => new DevblocksSearchField(self::ACTOR_CONTEXT, 'context_activity_log', 'actor_context', $translate->_('dao.context_activity_log.actor_context'), null),
			self::ACTOR_CONTEXT_ID => new DevblocksSearchField(self::ACTOR_CONTEXT_ID, 'context_activity_log', 'actor_context_id', $translate->_('dao.context_activity_log.actor_context_id'), null),
			self::TARGET_CONTEXT => new DevblocksSearchField(self::TARGET_CONTEXT, 'context_activity_log', 'target_context', $translate->_('dao.context_activity_log.target_context'), null),
			self::TARGET_CONTEXT_ID => new DevblocksSearchField(self::TARGET_CONTEXT_ID, 'context_activity_log', 'target_context_id', $translate->_('dao.context_activity_log.target_context_id'), null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'context_activity_log', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::ENTRY_JSON => new DevblocksSearchField(self::ENTRY_JSON, 'context_activity_log', 'entry_json', $translate->_('dao.context_activity_log.entry'), null),
				
			self::VIRTUAL_ACTOR => new DevblocksSearchField(self::VIRTUAL_ACTOR, '*', 'actor', 'Actor', null),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', 'Target', null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContextActivityLog {
	public $id;
	public $activity_point;
	public $actor_context;
	public $actor_context_id;
	public $target_context;
	public $target_context_id;
	public $created;
	public $entry_json;
};

class View_ContextActivityLog extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'context_activity_log';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Activity Log';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_ContextActivityLog::CREATED,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
			SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
			SearchFields_ContextActivityLog::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
			SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
			SearchFields_ContextActivityLog::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContextActivityLog::search(
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
		return $this->_getDataAsObjects('DAO_ContextActivityLog', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContextActivityLog', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);

		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ContextActivityLog::ACTIVITY_POINT:
					$pass = true;
					break;
					
				case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
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
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$label_map = array();
				$translate = DevblocksPlatform::getTranslationService();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				if(is_array($activities))
				foreach($activities as $k => $data) {
					@$string_id = $data['params']['label_key'];
					if(!empty($string_id)) {
						$label_map[$k] = $translate->_($string_id);
					}
				}
				$counts = $this->_getSubtotalCountForStringColumn('DAO_ContextActivityLog', $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_ContextActivityLog', CerberusContexts::CONTEXT_ACTIVITY_LOG, $column, DAO_ContextActivityLog::ACTOR_CONTEXT, DAO_ContextActivityLog::ACTOR_CONTEXT_ID);
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_ContextActivityLog', CerberusContexts::CONTEXT_ACTIVITY_LOG, $column, DAO_ContextActivityLog::TARGET_CONTEXT, DAO_ContextActivityLog::TARGET_CONTEXT_ID);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_ContextActivityLog', $column, 'context_activity_log.id');
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

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/activity_log/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContextActivityLog::ENTRY_JSON:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ContextActivityLog::ID:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_ContextActivityLog::CREATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$options = array();
				
				foreach($activities as $activity_id => $activity) {
					if(isset($activity['params']['label_key']))
						$options[$activity_id] = $activity['params']['label_key'];
				}
				
				$tpl->assign('options', $options);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				$this->_renderVirtualContextLinks($param, 'Actor', 'Actors');
				break;
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$this->_renderVirtualContextLinks($param, 'Target', 'Targets');
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$strings = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($contexts[$v])) {
						if(isset($contexts[$v]->name))
							$string = $contexts[$v]->name;
					}
					
					$strings[] = $string;
				}
				
				return implode(' or ', $strings);
				break;
				
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$strings = array();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$translate = DevblocksPlatform::getTranslationService();
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($activities[$v])) {
						@$string_id = $activities[$v]['params']['label_key'];
						if(!empty($string_id))
							$string = $translate->_($string_id);
					}
					
					$strings[] = $string;
				}
				
				return implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContextActivityLog::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextActivityLog::ENTRY_JSON:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContextActivityLog::ID:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContextActivityLog::CREATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				@$contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$contexts);
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
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
				case 'example':
					//$change_fields[DAO_ContextActivityLog::EXAMPLE] = 'some value';
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContextActivityLog::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContextActivityLog::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_ContextActivityLog::update($batch_ids, $change_fields);

			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_ContextActivityLog extends Extension_DevblocksContext {
	function getRandom() {
		return null;
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::getUrlService();
		
		$entry = DAO_ContextActivityLog::get($context_id);
		
		return array(
			'id' => $entry->id,
			'name' => CerberusContexts::formatActivityLogEntry(json_decode($entry->entry_json, true), 'text'),
			'permalink' => null,
		);
	}
	
	function getContext($entry, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Activity:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($entry)) {
			$entry = DAO_ContextActivityLog::get($entry);
		} elseif($entry instanceof Model_ContextActivityLog) {
			// It's what we want already.
		} else {
			$entry = null;
		}
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'created|date' => $prefix.$translate->_('common.created'),
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_ACTIVITY_LOG;

		// Address token values
		if(null != $entry) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = CerberusContexts::formatActivityLogEntry(json_decode($entry->entry_json,true),'text');
			$token_values['id'] = $entry->id;
			$token_values['created'] = $entry->created;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_ACTIVITY_LOG;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
		}
		
		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Activity Log';
		
		$view->addParamsDefault(array(
			//SearchFields_ContextActivityLog::IS_BANNED => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::IS_BANNED,'=',0),
			//SearchFields_ContextActivityLog::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::IS_DEFUNCT,'=',0),
		), true);
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_ContextActivityLog::CREATED;
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
		$view->name = 'Activity Log';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
// 				new DevblocksSearchCriteria(SearchFields_ContextActivityLog::CONTEXT_LINK,'=',$context),
// 				new DevblocksSearchCriteria(SearchFields_ContextActivityLog::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};