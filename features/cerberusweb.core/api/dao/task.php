<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Task extends C4_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const UPDATED_DATE = 'updated_date';
	const DUE_DATE = 'due_date';
	const IS_COMPLETED = 'is_completed';
	const COMPLETED_DATE = 'completed_date';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('task_seq');
		
		$sql = sprintf("INSERT INTO task (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		// New task
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'task.create',
                array(
                    'task_id' => $id,
                	'fields' => $fields,
                )
            )
	    );
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'task', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('task', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Task[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, due_date, updated_date, is_completed, completed_date ".
			"FROM task ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Task	 */
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
	 * @return Model_Task[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Task();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->updated_date = $row['updated_date'];
			$object->due_date = $row['due_date'];
			$object->is_completed = $row['is_completed'];
			$object->completed_date = $row['completed_date'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE id IN (%s)", $ids_list));
		
		// Context links
		DAO_ContextLink::delete(CerberusContexts::CONTEXT_TASK, $ids);
		
		// Custom fields
		DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_Task::ID, $ids);
		
		// Notes
		DAO_Comment::deleteByContext(CerberusContexts::CONTEXT_TASK, $ids);
		
		return true;
	}

    /**
     * Enter description here...
     *
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
		$fields = SearchFields_Task::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.updated_date as %s, ".
			"t.due_date as %s, ".
			"t.is_completed as %s, ".
			"t.completed_date as %s, ".
			"t.title as %s ",
//			"o.name as %s ".
			    SearchFields_Task::ID,
			    SearchFields_Task::UPDATED_DATE,
			    SearchFields_Task::DUE_DATE,
			    SearchFields_Task::IS_COMPLETED,
			    SearchFields_Task::COMPLETED_DATE,
			    SearchFields_Task::TITLE
			 );
		
		$join_sql = 
			"FROM task t ".

			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.task' AND context_link.to_context_id = t.id) " : " ")
			;

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			't.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Virtuals
		foreach($params as $param_key => $param) {
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_Task::VIRTUAL_WORKERS:
					if(empty($param->value)) { // empty
						$where_sql .= "AND (SELECT GROUP_CONCAT(context_link.to_context_id) FROM context_link WHERE context_link.from_context = 'cerberusweb.contexts.task' AND context_link.from_context_id = t.id AND context_link.to_context = 'cerberusweb.contexts.worker') IS NULL ";
					} else {
						$where_sql .= sprintf("AND (SELECT GROUP_CONCAT(context_link.to_context_id) FROM context_link WHERE context_link.from_context = 'cerberusweb.contexts.task' AND context_link.from_context_id = t.id AND context_link.to_context = 'cerberusweb.contexts.worker' AND context_link.to_context_id IN (%s)) IS NOT NULL ",
							implode(',', $param->value)
						);
					}
					break;
			}
		}
		
		// Build it
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY t.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Task::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT t.id) " : "SELECT COUNT(t.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }	
	
};

class SearchFields_Task implements IDevblocksSearchFields {
	// Task
	const ID = 't_id';
	const UPDATED_DATE = 't_updated_date';
	const DUE_DATE = 't_due_date';
	const IS_COMPLETED = 't_is_completed';
	const COMPLETED_DATE = 't_completed_date';
	const TITLE = 't_title';
	
	const VIRTUAL_WORKERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', $translate->_('task.id')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 't', 'updated_date', $translate->_('task.updated_date')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', $translate->_('task.title')),
			self::IS_COMPLETED => new DevblocksSearchField(self::IS_COMPLETED, 't', 'is_completed', $translate->_('task.is_completed')),
			self::DUE_DATE => new DevblocksSearchField(self::DUE_DATE, 't', 'due_date', $translate->_('task.due_date')),
			self::COMPLETED_DATE => new DevblocksSearchField(self::COMPLETED_DATE, 't', 'completed_date', $translate->_('task.completed_date')),
			
			self::VIRTUAL_WORKERS => new DevblocksSearchField(self::VIRTUAL_WORKERS, '*', 'workers', $translate->_('common.owners')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
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

class Model_Task {
	public $id;
	public $title;
	public $created;
	public $due_date;
	public $is_completed;
	public $completed_date;
	public $updated_date;
};

class View_Task extends C4_AbstractView {
	const DEFAULT_ID = 'tasks';
	const DEFAULT_TITLE = 'Tasks';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Task::DUE_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Task::UPDATED_DATE,
			SearchFields_Task::DUE_DATE,
		);
		$this->columnsHidden = array(
			SearchFields_Task::ID,
			SearchFields_Task::CONTEXT_LINK,
			SearchFields_Task::CONTEXT_LINK_ID,
			SearchFields_Task::VIRTUAL_WORKERS,
		);
		
		$this->paramsHidden = array(
			SearchFields_Task::ID,
			SearchFields_Task::CONTEXT_LINK,
			SearchFields_Task::CONTEXT_LINK_ID,
		);
		$this->paramsDefault = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		return $this->_objects = DAO_Task::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());

		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/tasks/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/tasks/view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = APP_PATH . '/features/cerberusweb.core/templates/';
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_Task::TITLE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Task::IS_COMPLETED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::DUE_DATE:
			case SearchFields_Task::COMPLETED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Task::VIRTUAL_WORKERS:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__context_worker.tpl');
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Task::VIRTUAL_WORKERS:
				if(empty($param->value)) {
					echo "Workers <b>are not assigned</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Worker is %s", implode(' or ', $strings));
				}
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = !is_null($param_key) ? $param_key : $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Task::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Task::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Task::IS_COMPLETED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Task::VIRTUAL_WORKERS:
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
		@set_time_limit(600); // [TODO] Temp!
	  
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
				case 'due':
					@$date = strtotime($v);
					$change_fields[DAO_Task::DUE_DATE] = intval($date);
					break;
				case 'status':
					if(1==intval($v)) { // completed
						$change_fields[DAO_Task::IS_COMPLETED] = 1;
						$change_fields[DAO_Task::COMPLETED_DATE] = time();
					} else { // active
						$change_fields[DAO_Task::IS_COMPLETED] = 0;
						$change_fields[DAO_Task::COMPLETED_DATE] = 0;
					}
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Task::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Task::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Task::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Task::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};

class Context_Task extends Extension_DevblocksContext {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=tasks&action=display&id='.$context_id, true);
    }

	function getContext($task, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Task:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);

		// Polymorph
		if(is_numeric($task)) {
			$task = DAO_Task::get($task);
		} elseif($task instanceof Model_Task) {
			// It's what we want already.
		} else {
			$task = null;
		}
		
		// Token labels
		$token_labels = array(
			'completed|date' => $prefix.$translate->_('task.completed_date'),
			'due|date' => $prefix.$translate->_('task.due_date'),
			'id' => $prefix.$translate->_('common.id'),
			'is_completed' => $prefix.$translate->_('task.is_completed'),
			'title' => $prefix.$translate->_('task.title'),
			'updated|date' => $prefix.$translate->_('task.updated_date'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		if($task) {
			$token_values['completed'] = $task->completed_date;
			$token_values['due'] = $task->due_date;
			$token_values['id'] = $task->id;
			$token_values['is_completed'] = $task->is_completed;
			$token_values['title'] = $task->title;
			$token_values['updated'] = $task->updated_date;
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Task::ID, $task->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $task)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $task)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		// Assignee
//		@$assignee_id = $task->worker_id;
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
		$defaults->class_name = 'View_Task';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tasks';
		$view->view_columns = array(
			SearchFields_Task::UPDATED_DATE,
			SearchFields_Task::DUE_DATE,
		);
		$view->addParams(array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
			//SearchFields_Task::VIRTUAL_WORKERS => new DevblocksSearchCriteria(SearchFields_Task::VIRTUAL_WORKERS,'in',array($active_worker->id)),
		), true);
		$view->renderSortBy = SearchFields_Task::UPDATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context, $context_id) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_Task';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tasks';
		$view->addParams(array(
			new DevblocksSearchCriteria(SearchFields_Task::CONTEXT_LINK,'=',$context),
			new DevblocksSearchCriteria(SearchFields_Task::CONTEXT_LINK_ID,'=',$context_id),
		), true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
