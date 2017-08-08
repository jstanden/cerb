<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class DAO_CrmOpportunity extends Cerb_ORMHelper {
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
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
			$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
		
		$sql = sprintf("INSERT INTO crm_opportunity () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// New opportunity
		$eventMgr = DevblocksPlatform::services()->event();
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
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'crm_opportunity', $fields);
			
			// Send events
			if($check_deltas) {
				// Local events
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.crm_opportunity.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_OPPORTUNITY, $batch_ids);
			}
		}
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
		
		$change_fields = array();
		$custom_fields = array();
		$deleted = false;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'status':
					switch(DevblocksPlatform::strLower($v)) {
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
							$deleted = true;
							break;
					}
					break;
					
				case 'closed_date':
					$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = intval($v);
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		if(!$deleted) {
			// Fields
			if(!empty($change_fields))
				DAO_CrmOpportunity::update($ids, $change_fields);
			
			// Custom Fields
			if(!empty($custom_fields))
				C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $custom_fields, $ids);
			
			// Scheduled behavior
			if(isset($do['behavior']))
				C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_OPPORTUNITY, $do['behavior'], $ids);
			
			// Watchers
			if(isset($do['watchers']))
				C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $do['watchers'], $ids);
			
			// Broadcast
			if(isset($do['broadcast']))
				C4_AbstractView::_doBulkBroadcast(CerberusContexts::CONTEXT_OPPORTUNITY, $do['broadcast'], $ids, 'email_address');
			
		} else {
			DAO_CrmOpportunity::delete($ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	static function _processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_CrmOpportunity::IS_CLOSED,
			DAO_CrmOpportunity::IS_WON,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_OPPORTUNITY, $ids)))
			return;
		
		if(false == ($models = DAO_CrmOpportunity::getIds($ids)))
			return;
		
		// [TODO] These can be merged with 'Record changed' now
		foreach($models as $id => $model) {
			if(!isset($before_models[$id]))
				continue;
			
			$before_model = (object) $before_models[$id];
			
			/*
			 * Opp status changed
			 */
			
			@$is_closed = $change_fields[DAO_CrmOpportunity::IS_CLOSED];
			@$is_won = $change_fields[DAO_CrmOpportunity::IS_WON];
			
			if($is_closed == $before_model->is_closed)
				unset($change_fields[DAO_CrmOpportunity::IS_CLOSED]);
			
			if($is_won == $before_model->is_won)
				unset($change_fields[DAO_CrmOpportunity::IS_WON]);
			
			if(
				isset($change_fields[DAO_CrmOpportunity::IS_CLOSED])
				|| isset($change_fields[DAO_CrmOpportunity::IS_WON])
			) {
				
				if(!$model->is_closed) {
					$activity_point = 'opp.status.open';
					$status_to = 'open';
					
				} else {
					if($model->is_won) {
						$activity_point = 'opp.status.closed_won';
						$status_to = 'closed/won';
						
					} else {
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
						'target' => sprintf("%s", $model->name),
						'status' => $status_to,
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_OPPORTUNITY, $model->id, $model->name),
						)
				);
				CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_OPPORTUNITY, $model->id, $entry);
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
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, name, amount, primary_email_id, created_date, updated_date, closed_date, is_won, is_closed ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity
	 */
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
	 * @param resource $rs
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneSlave("SELECT count(id) FROM crm_opportunity");
	}
	
	static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
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
		$db = DevblocksPlatform::services()->database();
		
		$ids_list = implode(',', $ids);
		
		// Opps
		$db->ExecuteMaster(sprintf("DELETE FROM crm_opportunity WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
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
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CrmOpportunity', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.name as %s, ".
			"o.amount as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
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
				SearchFields_CrmOpportunity::CREATED_DATE,
				SearchFields_CrmOpportunity::UPDATED_DATE,
				SearchFields_CrmOpportunity::CLOSED_DATE,
				SearchFields_CrmOpportunity::IS_CLOSED,
				SearchFields_CrmOpportunity::IS_WON
			);

		// [TODO] Get rid of the left join
		$join_sql =
			"FROM crm_opportunity o ".
			"INNER JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) ".
			'';
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CrmOpportunity');

		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
		
		array_walk_recursive(
			$params,
			array('DAO_CrmOpportunity', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'o',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$from_context = 'cerberusweb.contexts.opportunity';
		$from_index = 'o.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
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
		$db = DevblocksPlatform::services()->database();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(o.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_CrmOpportunity extends DevblocksSearchFields {
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

	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_EMAIL_SEARCH = '*_email_search';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'o.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_OPPORTUNITY => new DevblocksSearchFieldContextKeys('o.id', self::ID),
			//CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('o.primary_email_id', self::PRIMARY_EMAIL_ID),
			//CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('a.contact_org_id', self::ORG_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_OPPORTUNITY, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_OPPORTUNITY, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_EMAIL_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ADDRESS, 'o.primary_email_id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_OPPORTUNITY, self::getPrimaryKey());
				break;
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
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
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', $translate->_('common.id'), null, true),
			
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', $translate->_('crm.opportunity.primary_email_id'), null, true),
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id', $translate->_('address.contact_org_id'), Model_CustomField::TYPE_NUMBER, true),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', $translate->_('common.organization'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'o', 'amount', $translate->_('crm.opportunity.amount'), Model_CustomField::TYPE_NUMBER, true),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', $translate->_('crm.opportunity.closed_date'), Model_CustomField::TYPE_DATE, true),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', $translate->_('crm.opportunity.is_won'), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', $translate->_('crm.opportunity.is_closed'), Model_CustomField::TYPE_CHECKBOX, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_EMAIL_SEARCH => new DevblocksSearchField(self::VIRTUAL_EMAIL_SEARCH, '*', 'email_search', null, null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
				
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
	
	/**
	 * 
	 * @return Model_Address
	 */
	function getPrimaryEmail() {
		return DAO_Address::get($this->primary_email_id);
	}
};

class View_CrmOpportunity extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
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
		);
		$this->addColumnsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT,
			SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK,
			SearchFields_CrmOpportunity::VIRTUAL_EMAIL_SEARCH,
			SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET,
			SearchFields_CrmOpportunity::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsDefault(array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		));
		$this->addParamsHidden(array(
			SearchFields_CrmOpportunity::ID,
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_ID,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::VIRTUAL_EMAIL_SEARCH,
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CrmOpportunity');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CrmOpportunity', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CrmOpportunity', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Strings
				case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				case SearchFields_CrmOpportunity::IS_CLOSED:
				case SearchFields_CrmOpportunity::IS_WON:
				case SearchFields_CrmOpportunity::ORG_NAME:
					$pass = true;
					break;
					
				case SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_OPPORTUNITY;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
			case SearchFields_CrmOpportunity::ORG_NAME:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
			
			case SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_CrmOpportunity::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CrmOpportunity::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'amount' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CrmOpportunity::AMOUNT),
				),
			'closedDate' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CrmOpportunity::CLOSED_DATE),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_CrmOpportunity::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CrmOpportunity::CREATED_DATE),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CrmOpportunity::VIRTUAL_EMAIL_SEARCH),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					],
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CrmOpportunity::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_OPPORTUNITY, 'q' => ''],
					]
				),
			'isClosed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_CrmOpportunity::IS_CLOSED),
				),
			'isWon' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_CrmOpportunity::IS_WON),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CrmOpportunity::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CrmOpportunity::UPDATED_DATE),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_CrmOpportunity::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_OPPORTUNITY, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'email':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_CrmOpportunity::VIRTUAL_EMAIL_SEARCH);
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
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
			case SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_EMAIL_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.email')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;

			case SearchFields_CrmOpportunity::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
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
				
			case SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_OPPORTUNITY);
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
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
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
				
			case SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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
};

class Context_Opportunity extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport {
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getDaoClass() {
		return 'DAO_CrmOpportunity';
	}
	
	function getSearchClass() {
		return 'SearchFields_CrmOpportunity';
	}
	
	function getViewClass() {
		return 'View_CrmOpportunity';
	}
	
	function getRandom() {
		return DAO_CrmOpportunity::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=opportunity&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$opp = DAO_CrmOpportunity::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
		$url = $this->profileGetUrl($context_id);
		
		$friendly = DevblocksPlatform::strToPermalink($opp->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $opp->id,
			'name' => $opp->name,
			'permalink' => $url,
			'updated' => $opp->updated_date,
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
	
	function getDefaultProperties() {
		return array(
			'status',
			'email__label',
			'amount',
			'created',
			'updated',
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
		} elseif(is_array($opp)) {
			$opp = Cerb_ORMHelper::recastArrayToModel($opp, 'Model_CrmOpportunity');
		} else {
			$opp = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'amount' => $prefix.$translate->_('crm.opportunity.amount'),
			'created' => $prefix.$translate->_('common.created'),
			'is_closed' => $prefix.$translate->_('crm.opportunity.is_closed'),
			'is_won' => $prefix.$translate->_('crm.opportunity.is_won'),
			'status' => $prefix.$translate->_('common.status'),
			'title' => $prefix.$translate->_('common.title'),
			'updated' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'amount' => Model_CustomField::TYPE_NUMBER,
			'created' => Model_CustomField::TYPE_DATE,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'is_won' => Model_CustomField::TYPE_CHECKBOX,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_OPPORTUNITY;
		$token_values['_types'] = $token_types;
		
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
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($opp, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&what=opportunity&id=%d-%s",$opp->id, DevblocksPlatform::strToPermalink($opp->name)), true);
			
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
			$prefix.'Lead:',
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

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
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Opportunities';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_OPPORTUNITY;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($context_id)) {
			$opp = DAO_CrmOpportunity::get($context_id);
			$tpl->assign('opp', $opp);
		}

		if(empty($context_id) || $edit) {
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.crm::crm/opps/peek_edit.tpl');
			
		} else {
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $opp, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			// Counts
			$activity_counts = array(
				'comments' => DAO_Comment::count($context, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Interactions
			$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
			$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
			$tpl->assign('interactions_menu', $interactions_menu);
			
			$tpl->display('devblocks:cerberusweb.crm::crm/opps/peek.tpl');
		}
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
		
		$fields = SearchFields_CrmOpportunity::getFields();
		self::_getImportCustomFields($fields, $keys);
		
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