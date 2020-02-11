<?php
class DAO_FeedItem extends Cerb_ORMHelper {
	const CREATED_DATE = 'created_date';
	const FEED_ID = 'feed_id';
	const GUID = 'guid';
	const ID = 'id';
	const IS_CLOSED = 'is_closed';
	const TITLE = 'title';
	const URL = 'url';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::CREATED_DATE)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::FEED_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_FEED))
			;
		// varchar(64)
		$validation
			->addField(self::GUID)
			->string()
			->setMaxLength(64)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(1) unsigned
		$validation
			->addField(self::IS_CLOSED)
			->bit()
			;
		// varchar(255)
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// varchar(255)
		$validation
			->addField(self::URL)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::CREATED_DATE]))
			$fields[self::CREATED_DATE] = time();
		
		$sql = "INSERT INTO feed_item () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$context = CerberusContexts::CONTEXT_FEED_ITEM;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_FEED_ITEM, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'feed_item', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.feed_item.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_FEED_ITEM, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('feed_item', $fields, $where);
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
		
		$change_fields = [];
		$custom_fields = [];

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_closed':
					$change_fields[DAO_FeedItem::IS_CLOSED] = !empty($v) ? 1 : 0;
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_FEED_ITEM, $ids);
		
		// Fields
		if(!empty($change_fields))
			DAO_FeedItem::update($ids, $change_fields, false);
	
		// Custom Fields
		if(!empty($custom_fields))
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_FEED_ITEM, $custom_fields, $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_FEED_ITEM, $do['behavior'], $ids);
		
		// Watchers
		if(isset($do['watchers']))
			C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_FEED_ITEM, $do['watchers'], $ids);
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_FEED_ITEM, $ids);
		
		$update->markCompleted();
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_FeedItem[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, feed_id, guid, title, url, created_date, is_closed ".
			"FROM feed_item ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FeedItem
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
	 * @return Model_FeedItem[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_FeedItem();
			$object->id = $row['id'];
			$object->feed_id = $row['feed_id'];
			$object->guid = $row['guid'];
			$object->title = $row['title'];
			$object->url = $row['url'];
			$object->created_date = $row['created_date'];
			$object->is_closed = $row['is_closed'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM feed_item WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_FEED_ITEM,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_FEED_ITEM,
					'context_table' => 'feed_item',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function random() {
		return self::_getRandom('feed_item');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_FeedItem::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_FeedItem', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"feed_item.id as %s, ".
			"feed_item.feed_id as %s, ".
			"feed_item.guid as %s, ".
			"feed_item.title as %s, ".
			"feed_item.url as %s, ".
			"feed_item.created_date as %s, ".
			"feed_item.is_closed as %s ",
				SearchFields_FeedItem::ID,
				SearchFields_FeedItem::FEED_ID,
				SearchFields_FeedItem::GUID,
				SearchFields_FeedItem::TITLE,
				SearchFields_FeedItem::URL,
				SearchFields_FeedItem::CREATED_DATE,
				SearchFields_FeedItem::IS_CLOSED
			);
			
		$join_sql = "FROM feed_item ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_FeedItem');
	
		return array(
			'primary_table' => 'feed_item',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
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
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_FeedItem::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(feed_item.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_FeedItem extends DevblocksSearchFields {
	const ID = 'fi_id';
	const FEED_ID = 'fi_feed_id';
	const GUID = 'fi_guid';
	const TITLE = 'fi_title';
	const URL = 'fi_url';
	const CREATED_DATE = 'fi_created_date';
	const IS_CLOSED = 'fi_is_closed';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Virtuals
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_FEED_SEARCH = '*_feed_search';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'feed_item.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_FEED_ITEM => new DevblocksSearchFieldContextKeys('feed_item.id', self::ID),
			CerberusContexts::CONTEXT_FEED => new DevblocksSearchFieldContextKeys('feed_item.feed_id', self::FEED_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_FEED_ITEM, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_FEED_ITEM, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_FEED_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_FEED, 'feed_item.feed_id');
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_FEED_ITEM)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_FEED_ITEM, self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
		
		return false;
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'closed':
				$key = 'isClosed';
				break;
				
			case 'feed':
				$key = 'feed.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_FeedItem::FEED_ID:
				$models = DAO_Feed::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0,$values))
					$label_map[0] = DevblocksPlatform::translate('common.none');
				return $label_map;
				break;
				
			case SearchFields_FeedItem::ID:
				$models = DAO_FeedItem::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				if(in_array(0,$values))
					$label_map[0] = DevblocksPlatform::translate('common.none');
				return $label_map;
				break;
				
			case SearchFields_FeedItem::IS_CLOSED:
				return parent::_getLabelsForKeyBooleanValues();
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
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
			self::ID => new DevblocksSearchField(self::ID, 'feed_item', 'id', $translate->_('common.id'), null, true),
			self::FEED_ID => new DevblocksSearchField(self::FEED_ID, 'feed_item', 'feed_id', $translate->_('dao.feed_item.feed_id'), null, true),
			self::GUID => new DevblocksSearchField(self::GUID, 'feed_item', 'guid', $translate->_('dao.feed_item.guid'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'feed_item', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::URL => new DevblocksSearchField(self::URL, 'feed_item', 'url', $translate->_('common.url'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'feed_item', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'feed_item', 'is_closed', $translate->_('dao.feed_item.is_closed'), Model_CustomField::TYPE_CHECKBOX, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_FEED_SEARCH => new DevblocksSearchField(self::VIRTUAL_FEED_SEARCH, '*', 'feed_search', null, null, false),
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

class Model_FeedItem {
	public $id;
	public $feed_id;
	public $guid;
	public $title;
	public $url;
	public $created_date;
	public $is_closed;
};

class View_FeedItem extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'feed_items';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Headlines');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_FeedItem::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_FeedItem::URL,
			SearchFields_FeedItem::FEED_ID,
			SearchFields_FeedItem::CREATED_DATE,
		);
		$this->addColumnsHidden(array(
			SearchFields_FeedItem::GUID,
			SearchFields_FeedItem::ID,
			SearchFields_FeedItem::FULLTEXT_COMMENT_CONTENT,
			SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK,
			SearchFields_FeedItem::VIRTUAL_FEED_SEARCH,
			SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET,
			SearchFields_FeedItem::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FeedItem::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_FeedItem');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_FeedItem', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_FeedItem', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Feed
				case SearchFields_FeedItem::FEED_ID:
					$pass = true;
					break;
					
				// Booleans
				case SearchFields_FeedItem::IS_CLOSED:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK:
				case SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET:
				case SearchFields_FeedItem::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_FEED_ITEM;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_FeedItem::FEED_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_FeedItem::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;

			case SearchFields_FeedItem::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_FeedItem::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FeedItem::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_FeedItem::FULLTEXT_COMMENT_CONTENT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_FeedItem::CREATED_DATE),
				),
			'feed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_FeedItem::VIRTUAL_FEED_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_FEED, 'q' => ''],
					]
				),
			'feed.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_FeedItem::FEED_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_FEED, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_FEED_ITEM],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_FeedItem::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_FEED_ITEM, 'q' => ''],
					]
				),
			'isClosed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_FeedItem::IS_CLOSED),
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FeedItem::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'url' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_FeedItem::URL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_FeedItem::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_FEED_ITEM, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_FEED, $fields, 'feed');
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
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
			case 'feed':
				$field_key = SearchFields_FeedItem::FEED_ID;
				$oper = null;
				$patterns = [];
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $patterns);
				
				$feeds = DAO_Feed::getWhere();
				$values = [];
				
				if(is_array($patterns))
				foreach($patterns as $pattern) {
					foreach($feeds as $feed_id => $feed) {
						if(false !== stripos($feed->name, $pattern))
							$values[$feed_id] = true;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
		
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_FeedItem::VIRTUAL_WATCHERS, $tokens);
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

		$tpl->assign('workers', DAO_Worker::getAll());
		
		// [TODO] Cache getAll()
		$feeds = DAO_Feed::getWhere();
		$tpl->assign('feeds', $feeds);
		
		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.feed_reader::feeds/item/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_FEED_SEARCH:
				echo sprintf("Feed matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
				
			case SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_FeedItem::IS_CLOSED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_FeedItem::FEED_ID:
				$label_map = SearchFields_FeedItem::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_FeedItem::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FeedItem::GUID:
			case SearchFields_FeedItem::TITLE:
			case SearchFields_FeedItem::URL:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_FeedItem::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_FeedItem::CREATED_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_FeedItem::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_FeedItem::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_FeedItem::FEED_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_FeedItem::FULLTEXT_COMMENT_CONTENT:
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
};

class Context_FeedItem extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_FEED_ITEM;
	
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_FeedItem::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=feed_item&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_FeedItem();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['feed_id'] = array(
			'label' => mb_ucfirst($translate->_('dao.feed_item.feed_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->feed_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_FEED,
			],
		);

		$properties['guid'] = array(
			'label' => mb_ucfirst($translate->_('dao.feed_item.guid')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->guid,
		);
		
		$properties['is_closed'] = array(
			'label' => mb_ucfirst($translate->_('dao.feed_item.is_closed')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_closed,
		);

		$properties['created_date'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_date,
		);
		
		$properties['url'] = array(
			'label' => mb_ucfirst($translate->_('common.url')),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->url,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$item = DAO_FeedItem::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($item->title);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $item->id,
			'name' => $item->title,
			'permalink' => $url,
			'updated' => $item->created_date,
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
			'feed__label',
			'created_at',
			'is_closed',
			'url',
		);
	}
	
	function getContext($item, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Feed:Item:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEED_ITEM);

		// Polymorph
		if(is_numeric($item)) {
			$item = DAO_FeedItem::get($item);
		} elseif($item instanceof Model_FeedItem) {
			// It's what we want already.
		} elseif(is_array($item)) {
			$item = Cerb_ORMHelper::recastArrayToModel($item, 'Model_FeedItem');
		} else {
			$item = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'created_at' => $prefix.$translate->_('common.created'),
			'guid' => $prefix.$translate->_('dao.feed_item.guid'),
			'is_closed' => $prefix.$translate->_('dao.feed_item.is_closed'),
			'title' => $prefix.$translate->_('common.title'),
			'url' => $prefix.$translate->_('common.url'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'created_at' => Model_CustomField::TYPE_DATE,
			'guid' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'url' => Model_CustomField::TYPE_URL,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_FEED_ITEM;
		$token_values['_types'] = $token_types;
		
		// Feed item token values
		if($item) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $item->title;
			$token_values['id'] = $item->id;
			$token_values['created_at'] = $item->created_date;
			$token_values['guid'] = $item->guid;
			$token_values['is_closed'] = $item->is_closed;
			$token_values['title'] = $item->title;
			$token_values['url'] = $item->url;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($item, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=feed_item&id=%d-%s",$item->id, DevblocksPlatform::strToPermalink($item->title)), true);
			
			// Feed
			@$feed_id = $item->feed_id;
			$token_values['feed_id'] = $feed_id;
		}
		
		// Feed
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_FEED, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'feed_',
			$prefix.'Feed:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_FeedItem::CREATED_DATE,
			'feed_id' => DAO_FeedItem::FEED_ID,
			'guid' => DAO_FeedItem::GUID,
			'id' => DAO_FeedItem::ID,
			'is_closed' => DAO_FeedItem::IS_CLOSED,
			'links' => '_links',
			'title' => DAO_FeedItem::TITLE,
			'url' => DAO_FeedItem::URL,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['feed_id']['notes'] = "The ID of the [feed](/docs/records/types/feed/) containing this item";
		$keys['guid']['notes'] = "The globally unique ID of this item in the feed";
		$keys['is_closed']['notes'] = "Is this item viewed/resolved?";
		$keys['title']['notes'] = "The title of this feed item";
		$keys['url']['notes'] = "The URL of this feed item";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_FEED_ITEM;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->addParams(array(
			SearchFields_FeedItem::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_FeedItem::IS_CLOSED,'=',0),
		), true);
		$view->addParamsDefault(array(
			SearchFields_FeedItem::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_FeedItem::IS_CLOSED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_FeedItem::CREATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		//$view->name = 'Calls';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_FeedItem::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_FEED_ITEM;
		$model = null;
		
		if(!empty($context_id)) {
			$model = DAO_FeedItem::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
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
			$tpl->display('devblocks:cerberusweb.feed_reader::feeds/item/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};