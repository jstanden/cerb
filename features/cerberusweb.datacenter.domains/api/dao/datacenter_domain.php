<?php
class Context_Domain extends Extension_DevblocksContext {
	function getRandom() {
		return DAO_Domain::random();
	}
	
	function getMeta($context_id) {
		$domain = DAO_Domain::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$friendly = DevblocksPlatform::strToPermalink($domain->name);
		
		return array(
			'id' => $domain->id,
			'name' => $domain->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=datacenter.domains&tab=domain&id=%d-%s",$context_id, $friendly), true),
		);
	}
    
	function getContext($id_map, &$token_labels, &$token_values, $prefix=null) {
		if(is_array($id_map)) {
			$domain = $id_map['id'];
		} else {
			$domain = $id_map;
		}
		
		if(is_null($prefix))
			$prefix = 'Domain:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.domain');
		
		// Polymorph
		if(is_numeric($domain)) {
			$domain = DAO_Domain::get($domain);
		} elseif($domain instanceof Model_Domain) {
			// It's what we want already.
		} else {
			$domain = null;
		}
			
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('common.created'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $domain) {
			$token_values['id'] = $domain->id;
			$token_values['created'] = $domain->created;
			if(!empty($domain->name))
				$token_values['name'] = $domain->name;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=datacenter.domains&tab=domain&id=%d-%s",$domain->id, DevblocksPlatform::strToPermalink($domain->name)), true);
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.datacenter.domain', $domain->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $domain)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $domain)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Addy
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, @$id_map['address_id'], $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'contact_',
			'Contact:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Server
		$server_id = (null != $domain && !empty($domain->server_id)) ? $domain->server_id : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext('cerberusweb.contexts.datacenter.server', $server_id, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'server_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
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
		
		$view->renderSortBy = SearchFields_Domain::NAME;
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
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Domain::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Domain::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class DAO_Domain extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const SERVER_ID = 'server_id';
	const CREATED = 'created';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO datacenter_domain () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'datacenter_domain', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('datacenter_domain', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Domain[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, server_id, created ".
			"FROM datacenter_domain ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Domain	 */
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
	 * @return Model_Domain[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Domain();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->server_id = $row['server_id'];
			$object->created = $row['created'];
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
		
		$db->Execute(sprintf("DELETE FROM datacenter_domain WHERE id IN (%s)", $ids_list));
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => 'cerberusweb.contexts.datacenter.domain',
                	'context_ids' => $ids
                )
            )
	    );
		
		return true;
	}
	
	public static function maint() {
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.maint',
                array(
                	'context' => 'cerberusweb.contexts.datacenter.domain',
                	'context_table' => 'datacenter_domain',
                	'context_key' => 'id',
                )
            )
	    );
	}
	
	public static function random() {
		return self::_getRandom('datacenter_domain');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Domain::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"datacenter_domain.id as %s, ".
			"datacenter_domain.name as %s, ".
			"datacenter_domain.server_id as %s, ".
			"datacenter_domain.created as %s ",
				SearchFields_Domain::ID,
				SearchFields_Domain::NAME,
				SearchFields_Domain::SERVER_ID,
				SearchFields_Domain::CREATED
			);
			
		$join_sql = "FROM datacenter_domain ".
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.datacenter.domain' AND context_link.to_context_id = datacenter_domain.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN comment ON (comment.context = 'cerberusweb.contexts.datacenter.domain' AND comment.context_id = datacenter_domain.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN fulltext_comment_content ftcc ON (ftcc.id=comment.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'datacenter_domain.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Virtuals
		foreach($params as $param) {
			if(!is_a($param, 'DevblocksSearchCriteria'))
				continue;
			
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_Domain::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					$from_context = 'cerberusweb.contexts.datacenter.domain';
					$from_index = 'datacenter_domain.id';
					
					self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $join_sql, $where_sql);
					break;
			}
		}
		
		$result = array(
			'primary_table' => 'datacenter_domain',
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
			($has_multiple_values ? 'GROUP BY datacenter_domain.id ' : '').
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
			$object_id = intval($row[SearchFields_Domain::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT datacenter_domain.id) " : "SELECT COUNT(datacenter_domain.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_Domain implements IDevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const SERVER_ID = 'w_server_id';
	const CREATED = 'w_created';
	
	// Virtuals
	const VIRTUAL_WATCHERS = '*_workers';

	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'datacenter_domain', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'datacenter_domain', 'name', $translate->_('common.name')),
			self::SERVER_ID => new DevblocksSearchField(self::SERVER_ID, 'datacenter_domain', 'server_id', $translate->_('cerberusweb.datacenter.common.server')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'datacenter_domain', 'created', $translate->_('common.created')),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_comment_content'])) {
			$columns[self::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'));
		}
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.domain');

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

class Model_Domain {
	public $id;
	public $name;
	public $server_id;
	public $created;
};

class View_Domain extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'datacenter_domain';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('cerberusweb.datacenter.domains');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Domain::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Domain::SERVER_ID,
			SearchFields_Domain::CREATED,
		);
		
		// Filter columns
		$this->addColumnsHidden(array(
			SearchFields_Domain::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Domain::VIRTUAL_WATCHERS,
		));
		
		// Filter params
		//$this->addParamsHidden(array(
		//));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Domain::search(
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
		return $this->_doGetDataSample('DAO_Domain', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Booleans
				case SearchFields_Domain::SERVER_ID:
					$pass = true;
					break;
					
				// Booleans
				case SearchFields_Domain::VIRTUAL_WATCHERS:
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
			case SearchFields_Domain::SERVER_ID:
				$servers = DAO_Server::getAll();
				$label_map = array();
				foreach($servers as $server_id => $server)
					$label_map[$server_id] = $server->name;
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Domain', $column, $label_map, 'in', 'server_id[]');
				break;

			case SearchFields_Domain::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Domain', $column);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Domain', $column, 'd.id');
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
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.datacenter.domain');
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.datacenter.domains::domain/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Domain::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Domain::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Domain::CREATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Domain::SERVER_ID:
				$servers = DAO_Server::getAll();
				$tpl->assign('servers', $servers);
				
				$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/filter/server.tpl');
				break;
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			case SearchFields_Domain::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
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
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}	
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Domain::SERVER_ID:
				$servers = DAO_Server::getAll();
				$strings = array();

				if(empty($values)) {
					echo "(blank)";
					break;
				}
				
				foreach($values as $val) {
					if(empty($val))
						$strings[] = "";
					elseif(!isset($servers[$val]))
						continue;
					else
						$strings[] = $servers[$val]->name;
				}
				echo implode(", ", $strings);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Domain::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Domain::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Domain::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Domain::CREATED:
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
				
			case SearchFields_Domain::SERVER_ID:
				@$server_ids = DevblocksPlatform::importGPC($_REQUEST['server_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$server_ids);
				break;
				
			case SearchFields_Domain::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Domain::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
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
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();
		$deleted = false;

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
					//$change_fields[DAO_Domain::EXAMPLE] = 'some value';
					break;
				case 'delete':
					$deleted = true;
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
			list($objects,$null) = DAO_Domain::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Domain::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		// Broadcast?
		if(isset($do['broadcast'])) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			
			$params = $do['broadcast'];
			if(
				!isset($params['worker_id']) 
				|| empty($params['worker_id'])
				|| !isset($params['subject']) 
				|| empty($params['subject'])
				|| !isset($params['message']) 
				|| empty($params['message'])
				)
				break;

			$is_queued = (isset($params['is_queued']) && $params['is_queued']) ? true : false; 
			$next_is_closed = (isset($params['next_is_closed'])) ? intval($params['next_is_closed']) : 0; 
			
			if(is_array($ids))
			foreach($ids as $domain_id) {
				$addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.domain', $domain_id);
				
				foreach($addresses as $address_id => $address) {
					try {
						CerberusContexts::getContext('cerberusweb.contexts.datacenter.domain', array('id'=>$domain_id,'address_id'=>$address_id), $tpl_labels, $tpl_tokens);
						$subject = $tpl_builder->build($params['subject'], $tpl_tokens);
						$body = $tpl_builder->build($params['message'], $tpl_tokens);
						
						$json_params = array(
							'to' => $tpl_tokens['contact_address'],
							'group_id' => $params['group_id'],
							'next_is_closed' => $next_is_closed,
						);
						
						$fields = array(
							DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
							DAO_MailQueue::TICKET_ID => 0,
							DAO_MailQueue::WORKER_ID => $params['worker_id'],
							DAO_MailQueue::UPDATED => time(),
							DAO_MailQueue::HINT_TO => $tpl_tokens['contact_address'],
							DAO_MailQueue::SUBJECT => $subject,
							DAO_MailQueue::BODY => $body,
							DAO_MailQueue::PARAMS_JSON => json_encode($json_params),
						);
						
						if($is_queued) {
							$fields[DAO_MailQueue::IS_QUEUED] = 1;
						}
						
						$draft_id = DAO_MailQueue::create($fields);
						
					} catch (Exception $e) {
						// [TODO] ...
					}
				}
			}
		}		
		
		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			if(!$deleted) {
				DAO_Domain::update($batch_ids, $change_fields);
	
				// Custom Fields
				self::_doBulkSetCustomFields('cerberusweb.contexts.datacenter.domain', $custom_fields, $batch_ids);
				
				// Scheduled behavior
				if(isset($do['behavior']) && is_array($do['behavior'])) {
					$behavior_id = $do['behavior']['id'];
					@$behavior_when = strtotime($do['behavior']['when']) or time();
					
					if(!empty($batch_ids) && !empty($behavior_id))
					foreach($batch_ids as $batch_id) {
						DAO_ContextScheduledBehavior::create(array(
							DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
							DAO_ContextScheduledBehavior::CONTEXT => 'cerberusweb.contexts.datacenter.domain',
							DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
							DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
						));
					}
				}
			} else {
				DAO_Domain::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

