<?php
class Context_DatacenterSite extends Extension_DevblocksContext {
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write(sprintf("c=datacenter.sites&tab=site&id=%d",$context_id), true);
    }
    
	function getContext($id_map, &$token_labels, &$token_values, $prefix=null) {
		if(is_array($id_map)) {
			$site = $id_map['id'];
		} else {
			$site = $id_map;
		}
		
		if(is_null($prefix))
			$prefix = 'Site:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_DatacenterSite::ID);
		
		// Polymorph
		if(is_numeric($site)) {
			$site = DAO_DatacenterSite::get($site);
		} elseif($site instanceof Model_DatacenterSite) {
			// It's what we want already.
		} else {
			$site = null;
		}
			
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('common.created'),
			'domain' => $prefix.$translate->_('dao.datacenter_site.domain'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Address token values
		if(null != $site) {
			$token_values['id'] = $site->id;
			$token_values['created'] = $site->created;
			if(!empty($site->domain))
				$token_values['domain'] = $site->domain;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_DatacenterSite::ID, $site->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $site)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $site)
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
		
		// [TODO] Server
		$server_id = (null != $site && !empty($site->server_id)) ? $site->server_id : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext('cerberusweb.contexts.datacenter.server', $server_id, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'server_',
			'Server:',
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
		$defaults->class_name = 'View_DatacenterSite';
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Sites';
		
//		$view->view_columns = array(
//			SearchFields_DatacenterSite::FIRST_NAME,
//			SearchFields_DatacenterSite::LAST_NAME,
//			SearchFields_DatacenterSite::ORG_NAME,
//		);
		
//		$view->addParamsDefault(array(
//			SearchFields_DatacenterSite::IS_BANNED => new DevblocksSearchCriteria(SearchFields_DatacenterSite::IS_BANNED,'=',0),
//		));
//		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_DatacenterSite::DOMAIN;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context, $context_id, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = 'View_DatacenterSite';
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Sites';
		
		$params = array(
			new DevblocksSearchCriteria(SearchFields_DatacenterSite::CONTEXT_LINK,'=',$context),
			new DevblocksSearchCriteria(SearchFields_DatacenterSite::CONTEXT_LINK_ID,'=',$context_id),
		);
		
		if(isset($options['filter_open']))
			true; // Do nothing
		
		$view->addParams($params, true);
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class DAO_DatacenterSite extends C4_ORMHelper {
	const ID = 'id';
	const DOMAIN = 'domain';
	const SERVER_ID = 'server_id';
	const CREATED = 'created';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO datacenter_site () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'datacenter_site', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('datacenter_site', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_DatacenterSite[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, domain, server_id, created ".
			"FROM datacenter_site ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_DatacenterSite	 */
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
	 * @return Model_DatacenterSite[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_DatacenterSite();
			$object->id = $row['id'];
			$object->domain = $row['domain'];
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
		
		$db->Execute(sprintf("DELETE FROM datacenter_site WHERE id IN (%s)", $ids_list));
		
		return true;
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
		$fields = SearchFields_DatacenterSite::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"datacenter_site.id as %s, ".
			"datacenter_site.domain as %s, ".
			"datacenter_site.server_id as %s, ".
			"datacenter_site.created as %s ",
				SearchFields_DatacenterSite::ID,
				SearchFields_DatacenterSite::DOMAIN,
				SearchFields_DatacenterSite::SERVER_ID,
				SearchFields_DatacenterSite::CREATED
			);
			
		$join_sql = "FROM datacenter_site ".
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.datacenter.site' AND context_link.to_context_id = datacenter_site.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'datacenter_site.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
			
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY datacenter_site.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_DatacenterSite::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT datacenter_site.id) " : "SELECT COUNT(datacenter_site.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_DatacenterSite implements IDevblocksSearchFields {
	const ID = 'w_id';
	const DOMAIN = 'w_domain';
	const SERVER_ID = 'w_server_id';
	const CREATED = 'w_created';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'datacenter_site', 'id', $translate->_('common.id')),
			self::DOMAIN => new DevblocksSearchField(self::DOMAIN, 'datacenter_site', 'domain', $translate->_('dao.datacenter_site.domain')),
			self::SERVER_ID => new DevblocksSearchField(self::SERVER_ID, 'datacenter_site', 'server_id', $translate->_('cerberusweb.datacenter.common.server')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'datacenter_site', 'created', $translate->_('common.created')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_DatacenterSite::ID);

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

class Model_DatacenterSite {
	public $id;
	public $domain;
	public $server_id;
	public $created;
};

class View_DatacenterSite extends C4_AbstractView {
	const DEFAULT_ID = 'datacenter_site';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = 'Sites'; // $translate->_('On-Demand');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DatacenterSite::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_DatacenterSite::CREATED,
		);
		
		// Filter columns
		//$this->addColumnsHidden(array(
		//));
		
		// Filter params
		//$this->addParamsHidden(array(
		//));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_DatacenterSite::search(
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

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_DatacenterSite::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.datacenter.sites::site/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.datacenter.sites::site/view.tpl');
				break;
		}
		
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_DatacenterSite::DOMAIN:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_DatacenterSite::ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_DatacenterSite::CREATED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_DatacenterSite::SERVER_ID:
				$servers = DAO_Server::getAll();
				$tpl->assign('servers', $servers);
				
				$tpl->display('devblocks:cerberusweb.datacenter.sites::site/filter/server.tpl');
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
			case SearchFields_DatacenterSite::SERVER_ID:
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
		return SearchFields_DatacenterSite::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_DatacenterSite::DOMAIN:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_DatacenterSite::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_DatacenterSite::CREATED:
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
				
			case SearchFields_DatacenterSite::SERVER_ID:
				@$server_ids = DevblocksPlatform::importGPC($_REQUEST['server_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$server_ids);
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
					//$change_fields[DAO_DatacenterSite::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_DatacenterSite::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_DatacenterSite::ID,
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
			foreach($ids as $site_id) {
				$addresses = Context_Address::searchInboundLinks('cerberusweb.contexts.datacenter.site', $site_id);
				
				foreach($addresses as $address_id => $address) {
					try {
						CerberusContexts::getContext('cerberusweb.contexts.datacenter.site', array('id'=>$site_id,'address_id'=>$address_id), $tpl_labels, $tpl_tokens);
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
			
			DAO_DatacenterSite::update($batch_ids, $change_fields);

			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_DatacenterSite::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

