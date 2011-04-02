<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2011, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

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
		parent::_update($ids, 'crm_opportunity', $fields);
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
	 * @return Model_CrmOpportunity	 */
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
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();

		// Context Links
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN crm_opportunity ON context_link.from_context_id=crm_opportunity.id WHERE context_link.from_context = %s AND crm_opportunity.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_OPPORTUNITY)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' opportunity context link sources.');
		
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN crm_opportunity ON context_link.to_context_id=crm_opportunity.id WHERE context_link.to_context = %s AND crm_opportunity.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_OPPORTUNITY)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' opportunity context link targets.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Opps
		$db->Execute(sprintf("DELETE QUICK FROM crm_opportunity WHERE id IN (%s)", $ids_list));

		// Context links
		DAO_ContextLink::delete(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		// Custom fields
		DAO_CustomFieldValue::deleteByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		// Notes
		DAO_Comment::deleteByContext(CerberusContexts::CONTEXT_OPPORTUNITY, $ids);
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CrmOpportunity::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
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
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.opportunity' AND context_link.to_context_id = o.id) " : " ")
			;
			
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'o.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
					$has_multiple_values = true;
					if(empty($param->value)) { // empty
						$join_sql .= "LEFT JOIN context_link AS context_watcher ON (context_watcher.from_context = 'cerberusweb.contexts.opportunity' AND context_watcher.from_context_id = o.id AND context_watcher.to_context = 'cerberusweb.contexts.worker') ";
						$where_sql .= "AND context_watcher.to_context_id IS NULL ";
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_watcher ON (context_watcher.from_context = 'cerberusweb.contexts.opportunity' AND context_watcher.from_context_id = o.id AND context_watcher.to_context = 'cerberusweb.contexts.worker' AND context_watcher.to_context_id IN (%s)) ",
							implode(',', $param->value)
						);
					}
					break;
			}
		}

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

	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_WATCHERS = '*_workers';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', $translate->_('crm.opportunity.id')),
			
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', $translate->_('crm.opportunity.primary_email_id')),
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', $translate->_('crm.opportunity.email_address')),
			self::EMAIL_FIRST_NAME => new DevblocksSearchField(self::EMAIL_FIRST_NAME, 'a', 'first_name', $translate->_('address.first_name')),
			self::EMAIL_LAST_NAME => new DevblocksSearchField(self::EMAIL_LAST_NAME, 'a', 'last_name', $translate->_('address.last_name')),
			self::EMAIL_NUM_SPAM => new DevblocksSearchField(self::EMAIL_NUM_SPAM, 'a', 'num_spam', $translate->_('address.num_spam')),
			self::EMAIL_NUM_NONSPAM => new DevblocksSearchField(self::EMAIL_NUM_NONSPAM, 'a', 'num_nonspam', $translate->_('address.num_nonspam')),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', $translate->_('crm.opportunity.org_name')),
			
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', $translate->_('crm.opportunity.name')),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'o', 'amount', $translate->_('crm.opportunity.amount')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', $translate->_('crm.opportunity.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', $translate->_('crm.opportunity.updated_date')),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', $translate->_('crm.opportunity.closed_date')),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', $translate->_('crm.opportunity.is_won')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', $translate->_('crm.opportunity.is_closed')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY);
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

class View_CrmOpportunity extends C4_AbstractView {
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
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CrmOpportunity', $size);
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
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
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
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
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
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
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
			$this->addParam($criteria);
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
			DAO_CrmOpportunity::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $custom_fields, $batch_ids);
			
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
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};

class Context_Opportunity extends Extension_DevblocksContext {
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=crm&tab=opps&id='.$context_id, true);
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
			'title' => $prefix.$translate->_('crm.opportunity.name'),
			'updated|date' => $prefix.$translate->_('crm.opportunity.updated_date'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Opp token values
		if($opp) {
			$token_values['id'] = $opp->id;
			$token_values['amount'] = $opp->amount;
			$token_values['created'] = $opp->created_date;
			$token_values['is_closed'] = $opp->is_closed;
			$token_values['is_won'] = $opp->is_won;
			$token_values['title'] = $opp->name;
			$token_values['updated'] = $opp->updated_date;
//			if(!empty($org->city))
//				$token_values['city'] = $org->city;

			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $opp)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $opp)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Person
		@$address_id = $opp->primary_email_id;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $address_id, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'email_',
			'Lead:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Assignee
//		@$assignee_id = $opp->worker_id;
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
		$view->name = 'Opportunities';
		$view->view_columns = array(
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
			//SearchFields_CrmOpportunity::WORKER_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::WORKER_ID,'=',$active_worker->id),
		), true);
		$view->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
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
};