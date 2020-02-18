<?php
class DAO_ContextSavedSearch extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const QUERY = 'query';
	const TAG = 'tag';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'saved_searches_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			;
		$validation
			->addField(self::QUERY)
			->string()
			->setMaxLength(65535)
			->setRequired(true)
			;
		$validation
			->addField(self::TAG)
			->string()
			->setMaxLength(64)
			->setUnique(get_class())
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '-'))) {
					$error = "may only contain letters, numbers, and dashes";
					return false;
				}
					
				if(strlen($string) > 64) {
					$error = "must be shorter than 64 characters.";
					return false;
				}
				
				return true;
			})
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
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
		
		$sql = "INSERT INTO context_saved_search () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_SAVED_SEARCH;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_SAVED_SEARCH, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'context_saved_search', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_saved_search.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_SAVED_SEARCH, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_saved_search', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_SAVED_SEARCH;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$owner_context = $fields[self::OWNER_CONTEXT];
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID]);
		
		// Verify that the actor can use this new owner
		if($owner_context) {
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextSavedSearch[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, context, owner_context, owner_context_id, tag, query, updated_at ".
			"FROM context_saved_search ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * 
	 * @param string $tag
	 * @return Model_ContextSavedSearch|NULL
	 */
	static function getByTag($tag) {
		$results = self::getWhere(
			sprintf('%s=%s',
				self::TAG,
				Cerb_ORMHelper::qstr($tag)
			)
		);
		
		if(empty($results))
			return null;
		
		return array_shift($results);
	}
	
	static function getByContext($context=null) {
		// [TODO] Cache by context
		
		$searches = DAO_ContextSavedSearch::getAll();
		
		if($context) {
			$searches = array_filter($searches, function($search) use ($context) {
				if($search->context == $context)
					return true;
				
				return false;
			});
		}
		
		return $searches;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_ContextSavedSearch[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ContextSavedSearch::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ContextSavedSearch	 */
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
	 * 
	 * @param array $ids
	 * @return Model_ContextSavedSearch[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * 
	 * @param mixed $actor
	 * @param string $context
	 * @return Model_ContextSavedSearch
	 */
	static function getUsableByActor($actor, $context=null) {
		// [TODO] Cache?
		return self::getReadableByActor($actor, $context, true);
	}
	
	/**
	 * 
	 * @param mixed $actor
	 * @param string $context
	 * @param bool $ignore_admins
	 * @return Model_ContextSavedSearch
	 */
	static function getReadableByActor($actor, $context=null, $ignore_admins=false) {
		$searches = DAO_ContextSavedSearch::getByContext($context);
		$privs = Context_ContextSavedSearch::isReadableByActor($searches, $actor, $ignore_admins);
		return array_intersect_key($searches, array_flip(array_keys($privs, true)));
	}
	
	/**
	 * 
	 * @param mixed $actor
	 * @param string $context
	 * @param bool $ignore_admins
	 * @return Model_ContextSavedSearch
	 */
	static function getWriteableByActor($actor, $context=null, $ignore_admins=false) {
		$searches = DAO_ContextSavedSearch::getByContext($context);
		$privs = Context_ContextSavedSearch::isWriteableByActor($searches, $actor, $ignore_admins);
		return array_intersect_key($searches, array_flip(array_keys($privs, true)));
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContextSavedSearch[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextSavedSearch();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->tag = $row['tag'];
			$object->query = $row['query'];
			$object->context = $row['context'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->updated_at = intval($row['updated_at']);
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('context_saved_search');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_saved_search WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.context.saved.search',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
		$cache->removeByTags(['schema_mentions']);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextSavedSearch::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextSavedSearch', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_saved_search.id as %s, ".
			"context_saved_search.name as %s, ".
			"context_saved_search.tag as %s, ".
			"context_saved_search.query as %s, ".
			"context_saved_search.context as %s, ".
			"context_saved_search.owner_context as %s, ".
			"context_saved_search.owner_context_id as %s, ".
			"context_saved_search.updated_at as %s ",
				SearchFields_ContextSavedSearch::ID,
				SearchFields_ContextSavedSearch::NAME,
				SearchFields_ContextSavedSearch::TAG,
				SearchFields_ContextSavedSearch::QUERY,
				SearchFields_ContextSavedSearch::CONTEXT,
				SearchFields_ContextSavedSearch::OWNER_CONTEXT,
				SearchFields_ContextSavedSearch::OWNER_CONTEXT_ID,
				SearchFields_ContextSavedSearch::UPDATED_AT
			);
			
		$join_sql = "FROM context_saved_search ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextSavedSearch');
	
		return array(
			'primary_table' => 'context_saved_search',
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
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_ContextSavedSearch::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(context_saved_search.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContextSavedSearch extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const TAG = 'c_tag';
	const QUERY = 'c_query';
	const CONTEXT = 'c_context';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const UPDATED_AT = 'c_updated_at';
	
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_saved_search.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_SAVED_SEARCH => new DevblocksSearchFieldContextKeys('context_saved_search.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_SAVED_SEARCH)), self::getPrimaryKey());
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
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContextSavedSearch::CONTEXT:
				return parent::_getLabelsForKeyContextValues();
				break;
				
			case SearchFields_ContextSavedSearch::ID:
				$models = DAO_ContextSavedSearch::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'context_saved_search', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'context_saved_search', 'name', $translate->_('common.name'), null, true),
			self::TAG => new DevblocksSearchField(self::TAG, 'context_saved_search', 'tag', $translate->_('common.tag'), null, true),
			self::QUERY => new DevblocksSearchField(self::QUERY, 'context_saved_search', 'query', $translate->_('common.query'), null, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_saved_search', 'context', $translate->_('common.record.type'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'context_saved_search', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'context_saved_search', 'owner_context_id', null, null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'context_saved_search', 'updated_at', $translate->_('common.updated'), null, true),
			
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContextSavedSearch {
	public $id = 0;
	public $name = null;
	public $context = null;
	public $tag = null;
	public $query = null;
	public $owner_context = null;
	public $owner_context_id = 0;
	public $updated_at = 0;
	
	function getImageUrl() {
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->write(sprintf('c=avatars&type=saved_search&id=%d', $this->id)) . '?v=' . $this->updated_at;
	}
	
	public function getContextExtension($as_instance=true) {
		if($as_instance) {
			return Extension_DevblocksContext::get($this->context);
		} else {
			return Extension_DevblocksContext::get($this->context, false);
		}
	}
	
	public function getResults($limit=100) {
		$limit = DevblocksPlatform::intClamp($limit, 1, 1000);
		
		if(false == ($context_ext = $this->getContextExtension()))
			return false;
		
		if(false == ($view = $context_ext->getSearchView()))
			return false;
		
		$view->addParamsWithQuickSearch($this->query, true);
		$view->renderLimit = $limit;
		$view->renderPage = 0;
		$view->renderTotal = false;
		
		list($results,) = $view->getData();
		
		return $results;
	}
};

class View_ContextSavedSearch extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'context_saved_searches';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Saved Searches');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ContextSavedSearch::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContextSavedSearch::NAME,
			SearchFields_ContextSavedSearch::CONTEXT,
			SearchFields_ContextSavedSearch::QUERY,
			SearchFields_ContextSavedSearch::TAG,
			// [TODO] Virtual Owner
			SearchFields_ContextSavedSearch::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_ContextSavedSearch::OWNER_CONTEXT,
			SearchFields_ContextSavedSearch::OWNER_CONTEXT_ID,
			SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContextSavedSearch::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ContextSavedSearch');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ContextSavedSearch', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContextSavedSearch', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ContextSavedSearch::CONTEXT:
				case SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET:
					$pass = true;
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
		$context = 'cerberusweb.contexts.context.saved.search';

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ContextSavedSearch::CONTEXT:
				$contexts = DevblocksPlatform::objectsToArrays(Extension_DevblocksContext::getAll(false));
				$label_map = array_column($contexts, 'name', 'id');
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
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
		$search_fields = SearchFields_ContextSavedSearch::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'context' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::CONTEXT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_SAVED_SEARCH],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::ID),
					'examples' => [
						['type' => 'chooser', 'context' => 'cerberusweb.contexts.context.saved.search', 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'query' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::QUERY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'tag' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::TAG, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ContextSavedSearch::UPDATED_AT),
				),
		);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			default:
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

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.context.saved.search');
		$tpl->assign('custom_fields', $custom_fields);
		
		// Contexts
		$contexts = Extension_DevblocksContext::getAll(false, ['search']);
		$tpl->assign('contexts', $contexts);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/contexts/saved_search/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ContextSavedSearch::CONTEXT:
				$label_map = SearchFields_ContextSavedSearch::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContextSavedSearch::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextSavedSearch::CONTEXT:
			case SearchFields_ContextSavedSearch::NAME:
			case SearchFields_ContextSavedSearch::QUERY:
			case SearchFields_ContextSavedSearch::TAG:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContextSavedSearch::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContextSavedSearch::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ContextSavedSearch::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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

class Context_ContextSavedSearch extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.context.saved.search';
	
	static function isReadableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isReadableByDelegateOwner($actor, self::ID, $models, 'owner_', $ignore_admins);
	}
	
	static function isWriteableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, self::ID, $models, 'owner_', $ignore_admins);
	}

	function getRandom() {
		return DAO_ContextSavedSearch::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=saved_search&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ContextSavedSearch();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['owner'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.owner'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			]
		);
		
		$properties['tag'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.tag'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->tag,
		);
		
		
		$search_context_ext = $model->getContextExtension(false);
		
		$properties['context'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.record.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => @$search_context_ext->name ?: null,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['query'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.query'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->query,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$context_saved_search = DAO_ContextSavedSearch::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($context_saved_search->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $context_saved_search->id,
			'name' => $context_saved_search->name,
			'permalink' => $url,
			'updated' => $context_saved_search->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'context',
			'tag',
			'updated_at',
			'query',
		);
	}
	
	function getContext($context_saved_search, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Saved Search:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.context.saved.search');

		// Polymorph
		if(is_numeric($context_saved_search)) {
			$context_saved_search = DAO_ContextSavedSearch::get($context_saved_search);
		} elseif($context_saved_search instanceof Model_ContextSavedSearch) {
			// It's what we want already.
		} elseif(is_array($context_saved_search)) {
			$context_saved_search = Cerb_ORMHelper::recastArrayToModel($context_saved_search, 'Model_ContextSavedSearch');
		} else {
			$context_saved_search = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'context' => $prefix.$translate->_('common.type'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'query' => $prefix.$translate->_('common.query'),
			'tag' => $prefix.$translate->_('common.tag'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' => 'context_url',
			'query' => Model_CustomField::TYPE_SINGLE_LINE,
			'tag' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
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
		
		$token_values['_context'] = 'cerberusweb.contexts.context.saved.search';
		$token_values['_types'] = $token_types;
		
		if($context_saved_search) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $context_saved_search->name;
			$token_values['_image_url'] = $url_writer->writeNoProxy(sprintf('c=avatars&ctx=%s&id=%d', 'saved_search', $context_saved_search->id), true) . '?v=' . $context_saved_search->updated_at;
			$token_values['context'] = $context_saved_search->context;
			$token_values['id'] = $context_saved_search->id;
			$token_values['name'] = $context_saved_search->name;
			$token_values['owner__context'] = $context_saved_search->owner_context;
			$token_values['owner_id'] = $context_saved_search->owner_context_id;
			$token_values['query'] = $context_saved_search->query;
			$token_values['tag'] = $context_saved_search->tag;
			$token_values['updated_at'] = $context_saved_search->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($context_saved_search, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=saved_search&id=%d-%s",$context_saved_search->id, DevblocksPlatform::strToPermalink($context_saved_search->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'context' => DAO_ContextSavedSearch::CONTEXT,
			'id' => DAO_ContextSavedSearch::ID,
			'links' => '_links',
			'name' => DAO_ContextSavedSearch::NAME,
			'owner__context' => DAO_ContextSavedSearch::OWNER_CONTEXT,
			'owner_id' => DAO_ContextSavedSearch::OWNER_CONTEXT_ID,
			'query' => DAO_ContextSavedSearch::QUERY,
			'tag' => DAO_ContextSavedSearch::TAG,
			'updated_at' => DAO_ContextSavedSearch::UPDATED_AT,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['context']['notes'] = "The [record type](/docs/records/types/) of this search query; e.g. `ticket`";
		$keys['query']['notes'] = "The [search query](/docs/search/); e.g. `status:o`";
		$keys['tag']['notes'] = "A human-friendly nickname for this search (e.g. `open_tickets`)";
		
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
		
		$context = Context_ContextSavedSearch::ID;
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
		$view->name = 'Saved Search';
		/*
		$view->addParams(array(
			SearchFields_ContextSavedSearch::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ContextSavedSearch::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ContextSavedSearch::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Saved Search';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_SAVED_SEARCH;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_ContextSavedSearch::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_ContextSavedSearch::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree();
			$tpl->assign('owners_menu', $owners_menu);
			
			// Contexts
			$contexts = Extension_DevblocksContext::getAll(false, ['search']);
			$tpl->assign('contexts', $contexts);
			
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
			$tpl->display('devblocks:cerberusweb.core::internal/contexts/saved_search/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
