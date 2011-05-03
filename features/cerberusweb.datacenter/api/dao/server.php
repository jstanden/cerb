<?php
class Context_Server extends Extension_DevblocksContext {
	function getMeta($context_id) {
		$server = DAO_Server::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $server->id,
			'name' => $server->name,
			'permalink' => $url_writer->write(sprintf("c=datacenter&tab=server&id=%d",$context_id), true),
		);
	}
    
	function getContext($server, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Server:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.server');
		
		// Polymorph
		if(is_numeric($server)) {
			$server = DAO_Server::get($server);
		} elseif($server instanceof Model_Server) {
			// It's what we want already.
		} else {
			$server = null;
		}
			
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('common.name'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Custom token values
		if(null != $server) {
			$token_values['id'] = $server->id;
			$token_values['name'] = $server->name;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.datacenter.server', $server->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $server)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $server)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Addy
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, @$id_map['address_id'], $merge_token_labels, $merge_token_values, null, true);
//
//		CerberusContexts::merge(
//			'contact_',
//			'Contact:',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);
		
		return true;		
	}

	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$view->renderSortBy = SearchFields_Server::NAME;
		$view->renderSortAsc = true;
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
		//$view->name = 'Sites';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Server::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Server::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class DAO_Server extends C4_ORMHelper {
	const CACHE_ALL = 'cerberus_cache_servers_all';
	
	const ID = 'id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO server () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'server', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('server', $fields, $where);
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($servers = $cache->load(self::CACHE_ALL))) {
    	    $servers = self::getWhere();
    	    $cache->save($servers, self::CACHE_ALL);
	    }
	    
	    return $servers;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Server[]
	 */
	static function getWhere($where=null, $sortBy='name', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name ".
			"FROM server ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Server	 */
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
	 * @return Model_Server[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Server();
			$object->id = $row['id'];
			$object->name = $row['name'];
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
		
		$db->Execute(sprintf("DELETE FROM server WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Server::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"server.id as %s, ".
			"server.name as %s ",
				SearchFields_Server::ID,
				SearchFields_Server::NAME
			);
			
		$join_sql = "FROM server ".
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.datacenter.server' AND context_link.to_context_id = server.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'server.id',
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
				case SearchFields_Server::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					$from_context = 'cerberusweb.contexts.datacenter.server';
					$from_index = 'server.id';
					
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
		
		$result = array(
			'primary_table' => 'server',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			($has_multiple_values ? 'GROUP BY server.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
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
			$object_id = intval($row[SearchFields_Server::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT server.id) " : "SELECT COUNT(server.id) ").
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

class SearchFields_Server implements IDevblocksSearchFields {
	const ID = 's_id';
	const NAME = 's_name';
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'server', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'server', 'name', $translate->_('common.name')),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.server');

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

class Model_Server {
	public $id;
	public $name;
};

class View_Server extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'server';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Servers');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Server::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Server::ID,
		);
		// Filter cols
		$this->addColumnsHidden(array(
			SearchFields_Server::VIRTUAL_WATCHERS,
		));
		
		// Filter params
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Server::search(
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
		return $this->_doGetDataSample('DAO_Server', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Server::VIRTUAL_WATCHERS:
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
//			case SearchFields_Server::EXAMPLE:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_Server', $column);
//				break;

			case SearchFields_Server::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Server', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Server', $column, 's.id');
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
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.server');
		$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.datacenter::datacenter/servers/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.datacenter::datacenter/servers/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Server::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Server::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Server::VIRTUAL_WATCHERS:
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Server::VIRTUAL_WATCHERS:
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
		return SearchFields_Server::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Server::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Server::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
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
				
			case SearchFields_Server::VIRTUAL_WATCHERS:
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
					//$change_fields[DAO_Server::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_Server::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Server::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_Server::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields('cerberusweb.contexts.datacenter.server', $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
