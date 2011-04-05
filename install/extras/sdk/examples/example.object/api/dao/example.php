<?php
class DAO_ExampleObject extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const CREATED = 'created';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		$sql = "INSERT INTO example_object () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'example_object', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('example_object', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ExampleObject[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, created ".
			"FROM example_object ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ExampleObject	 */
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
	 * @return Model_ExampleObject[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ExampleObject();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->created = $row['created'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();

		// Context Links
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN example_object ON context_link.from_context_id=example_object WHERE context_link.from_context = %s AND example_object.id IS NULL",
			$db->qstr(Context_ExampleObject::ID)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' example_object context link sources.');
		
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN example_object ON context_link.to_context_id=example_object.id WHERE context_link.to_context = %s AND example_object IS NULL",
			$db->qstr(Context_ExampleObject::ID)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' example_object context link targets.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Context links
		DAO_ContextLink::delete(Context_ExampleObject::ID, $ids);
		
		// Custom fields
		DAO_CustomFieldValue::deleteByContextIds(Context_ExampleObject::ID, $ids);

		// Comments
		DAO_Comment::deleteByContext(Context_ExampleObject::ID, $ids);
		
		$db->Execute(sprintf("DELETE FROM example_object WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ExampleObject::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"example_object.id as %s, ".
			"example_object.name as %s, ".
			"example_object.created as %s ",
				SearchFields_ExampleObject::ID,
				SearchFields_ExampleObject::NAME,
				SearchFields_ExampleObject::CREATED
			);
			
		$join_sql = "FROM example_object ".
		
		// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? sprintf("INNER JOIN context_link ON (context_link.to_context = %s AND context_link.to_context_id = example_object.id) ", C4_ORMHelper::qstr(Context_ExampleObject::ID)) : " ")
			;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'example_object.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_ExampleObject::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					$from_context = Context_ExampleObject::ID; // [TODO]
					$from_index = 'example_object.id'; // [TODO]
					
					// Join and return anything
					if(DevblocksSearchCriteria::OPER_TRUE == $param->operator) {
						$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
					} elseif(empty($param->value)) { // empty
						// Either any watchers (1 or more); or no watchers
						if(DevblocksSearchCriteria::OPER_NIN == $param->operator || DevblocksSearchCriteria::OPER_NEQ == $param->operator) {
							$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
							$where_sql .= "AND context_watcher.to_context_id IS NOT NULL ";
						} else {
							$join_sql .= sprintf("LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker') ", $from_context, $from_index);
							$where_sql .= "AND context_watcher.to_context_id IS NULL ";
						}
					// Specific watchers
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_watcher ON (context_watcher.from_context = '%s' AND context_watcher.from_context_id = %s AND context_watcher.to_context = 'cerberusweb.contexts.worker' AND context_watcher.to_context_id IN (%s)) ",
							$from_context,
							$from_index,
							implode(',', $param->value)
						);
					}					
					break;
			}
		}		
		
		return array(
			'primary_table' => 'example_object',
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
			($has_multiple_values ? 'GROUP BY example_object.id ' : '').
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
			$object_id = intval($row[SearchFields_ExampleObject::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT example_object.id) " : "SELECT COUNT(example_object.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ExampleObject implements IDevblocksSearchFields {
	const ID = 'e_id';
	const NAME = 'e_name';
	const CREATED = 'e_created';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'example_object', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'example_object', 'name', $translate->_('common.name')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'example_object', 'created', $translate->_('common.created')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(Context_ExampleObject::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_ExampleObject {
	public $id;
	public $name;
	public $created;
};

class View_ExampleObject extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'example_objects';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('example.object.common.objects');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ExampleObject::CREATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_ExampleObject::CREATED,
		);
		$this->addColumnsHidden(array(
			SearchFields_ExampleObject::ID,
			SearchFields_ExampleObject::CONTEXT_LINK,
			SearchFields_ExampleObject::CONTEXT_LINK_ID,
			SearchFields_ExampleObject::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_ExampleObject::ID,
			SearchFields_ExampleObject::CONTEXT_LINK,
			SearchFields_ExampleObject::CONTEXT_LINK_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ExampleObject::search(
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
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ExampleObject', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ExampleObject::NAME:
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
	
	function getSubtotalCounts($column=null) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ExampleObject::NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_ExampleObject', $column);
				break;

//			case SearchFields_ExampleObject::EXAMPLE:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_ExampleObject', $column);
//				break;
				
			case SearchFields_ExampleObject::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_ExampleObject', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_ExampleObject', $column, 'e.id');
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:example.object::example_object/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->assign('view_template', 'devblocks:example.object::example_object/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ExampleObject::VIRTUAL_WATCHERS:
				if(empty($param->value)) {
					echo "There are no <b>watchers</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Watcher is %s", implode(' or ', $strings));
				}
				break;
		}
	}	
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ExampleObject::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ExampleObject::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_ExampleObject::CREATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_ExampleObject::VIRTUAL_WATCHERS:
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

	function getFields() {
		return SearchFields_ExampleObject::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ExampleObject::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_ExampleObject::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ExampleObject::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_ExampleObject::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $worker_ids);
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
		@set_time_limit(0);
	  
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
					//$change_fields[DAO_ExampleObject::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_ExampleObject::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ExampleObject::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));
		
		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields))
				DAO_ExampleObject::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(Context_ExampleObject::ID, $custom_fields, $batch_ids);
			
			// Watchers
			if(isset($do['watchers']) && is_array($do['watchers'])) {
				$watcher_params = $do['watchers'];
				foreach($batch_ids as $batch_id) {
					if(isset($watcher_params['add']) && is_array($watcher_params['add']))
						CerberusContexts::addWatchers(Context_ExampleObject::ID, $batch_id, $watcher_params['add']);
					if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
						CerberusContexts::removeWatchers(Context_ExampleObject::ID, $batch_id, $watcher_params['remove']);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_ExampleObject extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.example_object';
	
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=example.objects&action=profile&id='.$context_id, true);
    }

	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Example:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_ExampleObject::ID);

		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_ExampleObject::get($object);
		} elseif($object instanceof Model_ExampleObject) {
			// It's what we want already.
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		if($object) {
			$token_values['created'] = $object->created;
			$token_values['id'] = $object->id;
			$token_values['name'] = $object->name;
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(Context_ExampleObject::ID, $object->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $object)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $object)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		// Example link
		// [TODO] Use the following code if you want to link to another context
//		@$assignee_id = $object->worker_id;
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $assignee_id, $merge_token_labels, $merge_token_values, '', true);
//
//		CerberusContexts::merge(
//			'assignee_',
//			'Assignee:',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);			
		
		return true;
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
		$view->view_columns = array(
			SearchFields_ExampleObject::CREATED,
		);
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_ExampleObject::CREATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
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
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ExampleObject::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_ExampleObject::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};