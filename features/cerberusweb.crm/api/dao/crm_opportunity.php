<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class DAO_CrmOpportunity extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const AMOUNT = 'amount';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_DATE = 'closed_date';
	const IS_WON = 'is_won';
	const IS_CLOSED = 'is_closed';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO crm_opportunity () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// New opportunity
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'opportunity.create',
                array(
                    'opp_id' => $id,
                	'fields' => $fields,
                )
            )
	    );
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		/*
		 * Make a diff for the requested objects in batches
		 */
    	$chunks = array_chunk($ids, 25, true);
    	while($batch_ids = array_shift($chunks)) {
	    	$objects = DAO_CrmOpportunity::getWhere(sprintf("id IN (%s)", implode(',', $batch_ids)));
	    	$object_changes = array();
	    	
	    	foreach($objects as $object_id => $object) {
	    		$pre_fields = get_object_vars($object);
	    		$changes = array();
	    		
	    		foreach($fields as $field_key => $field_val) {
	    			// Make sure the value of the field actually changed
	    			if($pre_fields[$field_key] != $field_val) {
	    				$changes[$field_key] = array('from' => $pre_fields[$field_key], 'to' => $field_val);
	    			}
	    		}
	    		
	    		// If we had changes
	    		if(!empty($changes)) {
	    			$object_changes[$object_id] = array(
	    				'model' => array_merge($pre_fields, $fields),
	    				'changes' => $changes,
	    			);
	    		}
	    	}
	    	
	    	parent::_update($ids, 'crm_opportunity', $fields);
	    	
	    	if(!empty($object_changes)) {
		    	// Handle local events
		    	self::_processUpdateEvents($object_changes);
		    	
		        /*
		         * Trigger an event about the changes
		         */
			    $eventMgr = DevblocksPlatform::getEventService();
			    $eventMgr->trigger(
			        new Model_DevblocksEvent(
			            'dao.crm_opportunity.update',
		                array(
		                    'objects' => $object_changes,
		                )
		            )
			    );
			    
			    // Log the context update
	    		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
	    	}
    	}
    	
	}	
	
	static function _processUpdateEvents($objects) {
    	if(is_array($objects))
    	foreach($objects as $object_id => $object) {
    		@$model = $object['model'];
    		@$changes = $object['changes'];
    		
    		if(empty($model) || empty($changes))
    			continue;
    			
    		if(!empty($changes[DAO_CrmOpportunity::IS_CLOSED]) 
    			|| !empty($changes[DAO_CrmOpportunity::IS_WON])) {
    			
    			// We only care about things that are closed.
    			if(empty($model[DAO_CrmOpportunity::IS_CLOSED])) {
    				$activity_point = 'opp.status.open';
    				$status_to = 'open';
    				
    			} else {
	    			if(!empty($model[DAO_CrmOpportunity::IS_WON])) {
	    				$activity_point = 'opp.status.closed_won';
	    				$status_to = 'closed/won';
	    				
	    			} else { // closed_lost
	    				$activity_point = 'opp.status.closed_lost';
	    				$status_to = 'closed/lost';
	    			}
    			}
    			
				/*
				 * Log activity (opp.status.*)
				 */
				$entry = array(
					//{{actor}} changed opportunity {{target}} to status {{status}}
					'message' => 'activities.opp.status',
					'variables' => array(
						'target' => sprintf("%s", $model[DAO_CrmOpportunity::NAME]),
						'status' => $status_to,
						),
					'urls' => array(
						'target' => 'c=crm&p=opps&id='.$model[DAO_CrmOpportunity::ID],
						)
				);
				CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_OPPORTUNITY, $object_id, $entry);
    		}
    	}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('crm_opportunity', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOpportunity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, amount, primary_email_id, created_date, updated_date, closed_date, is_won, is_closed ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity
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
	
	/**
	 * @param resource $rs
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CrmOpportunity();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->amount = doubleval($row['amount']);
			$object->primary_email_id = intval($row['primary_email_id']);
			$object->created_date = $row['created_date'];
			$object->updated_date = $row['updated_date'];
			$object->closed_date = $row['closed_date'];
			$object->is_won = $row['is_won'];
			$object->is_closed = $row['is_closed'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM crm_opportunity");
	}
	
	static function maint() {
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.maint',
                array(
                	'context' => CerberusContexts::CONTEXT_OPPORTUNITY,
                	'context_table' => 'crm_opportunity',
                	'context_key' => 'id',
                )
            )
	    );
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Opps
		$db->Execute(sprintf("DELETE QUICK FROM crm_opportunity WHERE id IN (%s)", $ids_list));

		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => CerberusContexts::CONTEXT_OPPORTUNITY,
                	'context_ids' => $ids
                )
            )
	    );
		
		return true;
	}

	public static function random() {
		return self::_getRandom('crm_opportunity');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CrmOpportunity::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.name as %s, ".
			"o.amount as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"o.created_date as %s, ".
			"o.updated_date as %s, ".
			"o.closed_date as %s, ".
			"o.is_closed as %s, ".
			"o.is_won as %s ",
			    SearchFields_CrmOpportunity::ID,
			    SearchFields_CrmOpportunity::NAME,
			    SearchFields_CrmOpportunity::AMOUNT,
			    SearchFields_CrmOpportunity::ORG_ID,
			    SearchFields_CrmOpportunity::ORG_NAME,
			    SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			    SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			    SearchFields_CrmOpportunity::EMAIL_FIRST_NAME,
			    SearchFields_CrmOpportunity::EMAIL_LAST_NAME,
			    SearchFields_CrmOpportunity::EMAIL_NUM_SPAM,
			    SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM,
			    SearchFields_CrmOpportunity::CREATED_DATE,
			    SearchFields_CrmOpportunity::UPDATED_DATE,
			    SearchFields_CrmOpportunity::CLOSED_DATE,
			    SearchFields_CrmOpportunity::IS_CLOSED,
			    SearchFields_CrmOpportunity::IS_WON
			);
			
		$join_sql = 
			"FROM crm_opportunity o ".
			"INNER JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) ".
			
			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.opportunity' AND context_link.to_context_id = o.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN comment ON (comment.context = 'cerberusweb.contexts.opportunity' AND comment.context_id = o.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN fulltext_comment_content ftcc ON (ftcc.id=comment.id) " : " ")
			;
			
		$cfield_index_map = array(
			CerberusContexts::CONTEXT_OPPORTUNITY => 'o.id',
			CerberusContexts::CONTEXT_ADDRESS => 'a.id',
			CerberusContexts::CONTEXT_ORG => 'a.contact_org_id',
		);
			
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			$cfield_index_map,
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		// Translate virtual fields
		
		array_walk_recursive(
			$params,
			array('DAO_CrmOpportunity', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);

		$result = array(
			'primary_table' => 'o',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}	
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$param_key = $param->field;
		settype($param_key, 'string');
		switch($param_key) {
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				$from_context = 'cerberusweb.contexts.opportunity';
				$from_index = 'o.id';
				
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
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
			($has_multiple_values ? 'GROUP BY o.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT o.id) " : "SELECT COUNT(o.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_CrmOpportunity implements IDevblocksSearchFields {
	// Table
	const ID = 'o_id';
	const PRIMARY_EMAIL_ID = 'o_primary_email_id';
	const NAME = 'o_name';
	const AMOUNT = 'o_amount';
	const CREATED_DATE = 'o_created_date';
	const UPDATED_DATE = 'o_updated_date';
	const CLOSED_DATE = 'o_closed_date';
	const IS_WON = 'o_is_won';
	const IS_CLOSED = 'o_is_closed';
	
	const ORG_ID = 'org_id';
	const ORG_NAME = 'org_name';

	const EMAIL_ADDRESS = 'a_email';
	const EMAIL_FIRST_NAME = 'a_first_name';
	const EMAIL_LAST_NAME = 'a_last_name';
	const EMAIL_NUM_SPAM = 'a_num_spam';
	const EMAIL_NUM_NONSPAM = 'a_num_nonspam';

	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_WATCHERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', $translate->_('crm.opportunity.id')),
			
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', $translate->_('crm.opportunity.primary_email_id')),
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', $translate->_('crm.opportunity.email_address'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EMAIL_FIRST_NAME => new DevblocksSearchField(self::EMAIL_FIRST_NAME, 'a', 'first_name', $translate->_('address.first_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EMAIL_LAST_NAME => new DevblocksSearchField(self::EMAIL_LAST_NAME, 'a', 'last_name', $translate->_('address.last_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EMAIL_NUM_SPAM => new DevblocksSearchField(self::EMAIL_NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam'), Model_CustomField::TYPE_NUMBER),
			self::EMAIL_NUM_NONSPAM => new DevblocksSearchField(self::EMAIL_NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam'), Model_CustomField::TYPE_NUMBER),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', $translate->_('crm.opportunity.org_name'), Model_CustomField::TYPE_SINGLE_LINE),
			
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', $translate->_('crm.opportunity.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'o', 'amount', $translate->_('crm.opportunity.amount'), Model_CustomField::TYPE_NUMBER),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', $translate->_('crm.opportunity.created_date'), Model_CustomField::TYPE_DATE),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', $translate->_('crm.opportunity.updated_date'), Model_CustomField::TYPE_DATE),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', $translate->_('crm.opportunity.closed_date'), Model_CustomField::TYPE_DATE),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', $translate->_('crm.opportunity.is_won'), Model_CustomField::TYPE_CHECKBOX),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', $translate->_('crm.opportunity.is_closed'), Model_CustomField::TYPE_CHECKBOX),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
		);
		
		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_comment_content'])) {
			$columns[self::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT');
		}
		
		// Custom Fields: opp + addy + org
		$fields = 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY) + 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS) + 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG)
		;
		
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};	

class Model_CrmOpportunity {
	public $id;
	public $name;
	public $amount;
	public $primary_email_id;
	public $created_date;
	public $updated_date;
	public $closed_date;
	public $is_won;
	public $is_closed;
};

class View_CrmOpportunity extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'crm_opportunities';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Opportunities';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::AMOUNT,
			SearchFields_CrmOpportunity::UPDATED_DATE,
			SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM,
		);
		$this->addColumnsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::CONTEXT_LINK,
			SearchFields_CrmOpportunity::CONTEXT_LINK_ID,
			SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT,
			SearchFields_CrmOpportunity::VIRTUAL_WATCHERS
		));
		
		$this->addParamsDefault(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		));
		$this->addParamsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::CONTEXT_LINK,
			SearchFields_CrmOpportunity::CONTEXT_LINK_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CrmOpportunity::search(
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
		return $this->_getDataAsObjects('DAO_CrmOpportunity', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CrmOpportunity', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Strings
				case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
				case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
				case SearchFields_CrmOpportunity::IS_CLOSED:
				case SearchFields_CrmOpportunity::IS_WON:
				case SearchFields_CrmOpportunity::ORG_NAME:
					$pass = true;
					break;
					
				case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
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
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
			case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CrmOpportunity', $column);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_CrmOpportunity', $column);
				break;
			
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_CrmOpportunity', $column);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_CrmOpportunity', $column, 'a.id');
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
		
		$custom_fields = 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY) + 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS) + 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG)
			; 
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.crm::crm/opps/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}	
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
			case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
			case SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM:
			case SearchFields_CrmOpportunity::EMAIL_NUM_SPAM:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
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
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CrmOpportunity::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::EMAIL_FIRST_NAME:
			case SearchFields_CrmOpportunity::EMAIL_LAST_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
			case SearchFields_CrmOpportunity::EMAIL_NUM_NONSPAM:
			case SearchFields_CrmOpportunity::EMAIL_NUM_SPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT:
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
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
				case 'status':
					switch(strtolower($v)) {
						case 'open':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 0;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = 0;
							break;
						case 'won':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 1;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
						case 'lost':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
						case 'deleted':
							if($active_worker->hasPriv('crm.opp.actions.delete'))
								$deleted = true;
							break;
					}
					break;
				case 'closed_date':
					$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = intval($v);
					break;
//				case 'worker_id':
//					$change_fields[DAO_CrmOpportunity::WORKER_ID] = intval($v);
//					break;
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
			list($objects, $null) = DAO_CrmOpportunity::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CrmOpportunity::ID,
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
			foreach($ids as $opp_id) {
				try {
					CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $tpl_labels, $tpl_tokens);
					$subject = $tpl_builder->build($params['subject'], $tpl_tokens);
					$body = $tpl_builder->build($params['message'], $tpl_tokens);
					
					$json_params = array(
						'to' => $tpl_tokens['email_address'],
						'group_id' => $params['group_id'],
						'next_is_closed' => $next_is_closed,
					);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
						DAO_MailQueue::TICKET_ID => 0,
						DAO_MailQueue::WORKER_ID => $params['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $tpl_tokens['email_address'],
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
		
		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) {
				DAO_CrmOpportunity::update($batch_ids, $change_fields);
				
				// Custom Fields
				self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $custom_fields, $batch_ids);
				
				// Scheduled behavior
				if(isset($do['behavior']) && is_array($do['behavior'])) {
					$behavior_id = $do['behavior']['id'];
					@$behavior_when = strtotime($do['behavior']['when']) or time();
					@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
					
					if(!empty($batch_ids) && !empty($behavior_id))
					foreach($batch_ids as $batch_id) {
						DAO_ContextScheduledBehavior::create(array(
							DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
							DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
							DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
							DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
							DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
						));
					}
				}
				
				// Watchers
				if(isset($do['watchers']) && is_array($do['watchers'])) {
					$watcher_params = $do['watchers'];
					foreach($batch_ids as $batch_id) {
						if(isset($watcher_params['add']) && is_array($watcher_params['add']))
							CerberusContexts::addWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_id, $watcher_params['add']);
						if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
							CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_id, $watcher_params['remove']);
					}
				}
				
			} else {
				DAO_CrmOpportunity::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};

class Context_Opportunity extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextImport {
	function getRandom() {
		return DAO_CrmOpportunity::random();
	}
	
	function getMeta($context_id) {
		$opp = DAO_CrmOpportunity::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$friendly = DevblocksPlatform::strToPermalink($opp->name);
		
		return array(
			'id' => $opp->id,
			'name' => $opp->name,
			'permalink' => $url_writer->write(sprintf("c=profiles&a=opportunity&id=%d-%s",$context_id,$friendly), true),
		);
	}
    
	function getContext($opp, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Opportunity:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);

		// Polymorph
		if(is_numeric($opp)) {
			$opp = DAO_CrmOpportunity::get($opp);
		} elseif($opp instanceof Model_CrmOpportunity) {
			// It's what we want already.
		} else {
			$opp = null;
		}
		
		// Token labels
		$token_labels = array(
			'amount' => $prefix.$translate->_('crm.opportunity.amount'),
			'created|date' => $prefix.$translate->_('crm.opportunity.created_date'),
			'is_closed' => $prefix.$translate->_('crm.opportunity.is_closed'),
			'is_won' => $prefix.$translate->_('crm.opportunity.is_won'),
			'status' => $prefix.$translate->_('common.status'),
			'title' => $prefix.$translate->_('crm.opportunity.name'),
			'updated|date' => $prefix.$translate->_('crm.opportunity.updated_date'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_OPPORTUNITY;
		
		// Opp token values
		if($opp) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $opp->name;
			$token_values['id'] = $opp->id;
			$token_values['amount'] = $opp->amount;
			$token_values['created'] = $opp->created_date;
			$token_values['is_closed'] = $opp->is_closed;
			$token_values['is_won'] = $opp->is_won;
			$token_values['title'] = $opp->name;
			$token_values['updated'] = $opp->updated_date;
			
			// Status
			if($opp->is_closed) {
				$token_values['status'] = ($opp->is_won) ? 'closed_won' : 'closed_lost';
			} else {
				$token_values['status'] = 'open';
			}
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=crm&tab=opps&id=%d-%s",$opp->id, DevblocksPlatform::strToPermalink($opp->name)), true);
			
			// Lead
			@$address_id = $opp->primary_email_id;
			$token_values['email_id'] = $address_id;
		}
		
		// Lead
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'email_',
			'Lead:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_OPPORTUNITY;
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
		$view->name = 'Opportunities';
		$view->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
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
		$view->name = 'Opportunities';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('email', $email);
		
		if(!empty($context_id) && null != ($opp = DAO_CrmOpportunity::get($context_id))) {
			$tpl->assign('opp', $opp);
				
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $context_id);
			if(isset($custom_field_values[$opp->id]))
				$tpl->assign('custom_field_values', $custom_field_values[$opp->id]);
		}
		
		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY, $context_id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/peek.tpl');
	}
	
	function importGetKeys() {
		// [TODO] Translate
		
		$keys = array(
			'amount' => array(
				'label' => 'Amount',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_CrmOpportunity::AMOUNT,
			),
			'closed_date' => array(
				'label' => 'Closed Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_CrmOpportunity::CLOSED_DATE,
			),
			'created_date' => array(
				'label' => 'Created Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_CrmOpportunity::CREATED_DATE,
			),
			'is_closed' => array(
				'label' => 'Is Closed',
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'param' => SearchFields_CrmOpportunity::IS_CLOSED,
			),
			'is_won' => array(
				'label' => 'Is Won',
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'param' => SearchFields_CrmOpportunity::IS_WON,
			),
			'name' => array(
				'label' => 'Name',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_CrmOpportunity::NAME,
			),
			'primary_email_id' => array(
				'label' => 'Email',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ADDRESS,
				'param' => SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
				'required' => true,
			),
			'updated_date' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_CrmOpportunity::UPDATED_DATE,
			),
		);
		
		$cfields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
		
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
		// Default these fields
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();

		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have an opp name
			if(!isset($fields[DAO_CrmOpportunity::NAME])) {
				$fields[DAO_CrmOpportunity::NAME] = 'New ' . $this->manifest->name;
			}
			
			// Default the created date to now
			if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
				$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
			
			// Create
			$meta['object_id'] = DAO_CrmOpportunity::create($fields);
			
		} else {
			// Update
			DAO_CrmOpportunity::update($meta['object_id'], $fields);
		}
		
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
};