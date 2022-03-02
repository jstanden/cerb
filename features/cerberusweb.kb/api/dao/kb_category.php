<?php
class DAO_KbCategory extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const PARENT_ID = 'parent_id';
	const UPDATED_AT = 'updated_at';
	
	const CACHE_ALL = 'ch_cache_kbcategories_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(64)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(64)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::PARENT_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_KB_CATEGORY, true))
			;
		// int(10) unsigned
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
		
		$sql = sprintf("INSERT INTO kb_category () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_KB_CATEGORY, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_KB_CATEGORY, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'kb_category', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.kb_category.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_KB_CATEGORY, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('kb_category', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	static function getTreeMap($prune_empty=false) {
		$db = DevblocksPlatform::services()->database();
		
		$categories = self::getAll();
		$tree = [];

		// Fake recursion
		foreach($categories as $cat_id => $cat) {
			$pid = $cat->parent_id;
			if(!isset($tree[$pid])) {
				$tree[$pid] = [];
			}
				
			$tree[$pid][$cat_id] = 0;
		}
		
		// Add counts (and bubble up)
		$sql = "SELECT count(*) AS hits, kb_category_id FROM kb_article_to_category GROUP BY kb_category_id";
		$rs = $db->QueryReader($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$count_cat = intval($row['kb_category_id']);
			$count_hits = intval($row['hits']);
			
			if(!isset($categories[$count_cat]))
				continue;
			
			$visited = [];
			$pid = $count_cat;
			while($pid) {
				@$parent_id = $categories[$pid]->parent_id;
				
				// Break infinite loops
				if(array_key_exists($parent_id, $visited))
					break;
				
				$tree[$parent_id][$pid] += $count_hits;
				$pid = $parent_id;
				
				$visited[$pid] = true;
			}
		}
		
		mysqli_free_result($rs);
		
		// Filter out empty categories on public
		if($prune_empty) {
			foreach($tree as $parent_id => $nodes) {
				$tree[$parent_id] = array_filter($nodes, function($count) {
					return !empty($count);
				});
			}
		}
		
		return $tree;
	}

	/**
	 *
	 * @param bool $nocache
	 * @return Model_KbCategory[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($categories = $cache->load(self::CACHE_ALL))) {
			$categories = self::getWhere(
				null,
				DAO_KbCategory::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($categories))
				return false;
			
			$cache->save($categories, self::CACHE_ALL);
		}
		
		return $categories;
	}
	
	static function getTopics() {
		$categories = self::getAll();
		
		if(is_array($categories))
		foreach($categories as $key => $category) { /* @var $category Model_KbCategory */
			if(0 != $category->parent_id)
				unset($categories[$key]);
		}
		
		return $categories;
	}
	
	static function getTree($root=0) {
		$levels = [];
		$map = self::getTreeMap();
		
		self::_recurseTree($levels,$map,$root);
		
		return $levels;
	}
	
	// [TODO] Move to Model_KbCategoryTree?
	static private function _recurseTree(&$levels,$map,$node=0,$level=-1) {
		if(!isset($map[$node]) || empty($map[$node]))
			return;

		$level++; // we're dropping down a node

		// recurse through children
		foreach(array_keys($map[$node]) as $pid) {
			$levels[$pid] = $level;
			self::_recurseTree($levels,$map,$pid,$level);
		}
	}
	
	static public function getAncestors($root_id, $categories=null) {
		if(empty($categories))
			$categories = DAO_KbCategory::getAll();
		
		if(!isset($categories[$root_id]))
			return false;
			
		$breadcrumb = [];
		
		$pid = $root_id;
		
		while($pid) {
			// Kill infinite loops from poorly constructed trees
			if(isset($breadcrumb[$pid]))
				break;
			
			$breadcrumb[$pid] = $categories[$pid];
			
			if(isset($categories[$pid])) {
				$pid = $categories[$pid]->parent_id;
			} else {
				$pid = 0;
			}
		}
		
		return array_reverse($breadcrumb, true);
	}
	
	static public function getDescendents($root_id) {
		$tree = self::getTree($root_id);
		@$ids = array_merge(array($root_id),array_keys($tree));
		return $ids;
	}
	
	/**
	 * @param string $where
	 * @return Model_KbCategory[]
	 */
	static function getWhere($where=null, $sortBy=DAO_KbCategory::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, parent_id, name, updated_at ".
			"FROM kb_category ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;

		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_KbCategory
	 */
	static function get($id) {
		$objects = self::getAll();
		
		if(empty($id))
			return null;
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_KbCategory[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_KbCategory[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_KbCategory();
			$object->id = intval($row['id']);
			$object->parent_id = intval($row['parent_id']);
			$object->name = $row['name'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		foreach($ids as $id) {
			$descendents = DAO_KbCategory::getDescendents($id);
			$ids_list = implode(',', DevblocksPlatform::sanitizeArray($descendents, 'int'));
			
			$db->ExecuteMaster(sprintf("DELETE FROM kb_category WHERE id IN (%s)", $ids_list));
			$db->ExecuteMaster(sprintf("DELETE FROM kb_article_to_category WHERE kb_category_id IN (%s)", $ids_list));
		}
		
		self::clearCache();
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('kb_category');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_KbCategory::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_KbCategory', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"kbc.id as %s, ".
			"kbc.name as %s, ".
			"kbc.updated_at as %s, ".
			"kbc.parent_id as %s ",
				SearchFields_KbCategory::ID,
				SearchFields_KbCategory::NAME,
				SearchFields_KbCategory::UPDATED_AT,
				SearchFields_KbCategory::PARENT_ID
			);
			
		$join_sql = "FROM kb_category kbc ";
		
		// [JAS]: Dynamic table joins
		if(isset($tables['katc'])) {
			$select_sql .= sprintf(", katc.kb_article_id AS %s ",
				SearchFields_KbArticle::TOP_CATEGORY_ID
			);
			$join_sql .= "LEFT JOIN kb_article_to_category katc ON (kbc.id=katc.kb_category_id) ";
		}

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_KbCategory');
		
		$result = array(
			'primary_table' => 'kbc',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];
		
		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM kb_category ".
			"WHERE name LIKE %s ".
			"LIMIT 25",
			$db->qstr($term.'%')
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$ids[] = $row['id'];
		}
		
		switch($as) {
			case 'ids':
				return $ids;
				break;
				
			default:
				return DAO_KbCategory::getIds($ids);
				break;
		}
	}
	
	static function countByArticleId($article_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(kb_category_id) FROM kb_article_to_category WHERE kb_article_id = %d",
			$article_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function countByParentId($parent_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(id) FROM kb_category WHERE parent_id = %d",
			$parent_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	/**
	 * @param string[] $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param int $limit
	 * @param int $page
	 * @param null $sortBy
	 * @param null $sortAsc
	 * @param bool $withCounts
	 * @return array|bool
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_KbCategory::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_KbCategory extends DevblocksSearchFields {
	// Table
	const ID = 'kbc_id';
	const PARENT_ID = 'kbc_parent_id';
	const NAME = 'kbc_name';
	const UPDATED_AT = 'kbc_updated_at';
	
	const ARTICLE_ID = 'katc_article_id';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'kbc.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_KB_CATEGORY => new DevblocksSearchFieldContextKeys('kbc.id', self::ID),
			CerberusContexts::CONTEXT_KB_ARTICLE => new DevblocksSearchFieldContextKeys('katc.kb_article_id', self::ARTICLE_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_KB_CATEGORY), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_KB_CATEGORY, self::getPrimaryKey());
				
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
			case 'parent':
				$key = 'parent.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_KbCategory::ARTICLE_ID:
				$models = DAO_KbArticle::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				break;
				
			case SearchFields_KbCategory::ID:
			case SearchFields_KbCategory::PARENT_ID:
				$models = DAO_KbCategory::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
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
			self::ID => new DevblocksSearchField(self::ID, 'kbc', 'id', $translate->_('common.id'), null, true),
			self::PARENT_ID => new DevblocksSearchField(self::PARENT_ID, 'kbc', 'parent_id', $translate->_('common.parent'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'kbc', 'name', $translate->_('common.name'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'kbc', 'updated_at', $translate->_('common.updated'), null, true),
			
			self::ARTICLE_ID => new DevblocksSearchField(self::ARTICLE_ID, 'katc', 'kb_article_id', DevblocksPlatform::translateCapitalized('kb.common.knowledgebase_article'), Model_CustomField::TYPE_NUMBER, true),
			
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Context_KbCategory extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.kb_category';
	const URI = 'kb_category';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_KbCategory::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=kb_category&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$properties = [];
		
		if(is_null($model))
			$model = new Model_KbCategory();
		
		$properties['name'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.name'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['parent_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.parent'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->parent_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_KB_CATEGORY,
			]
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(null == ($category = DAO_KbCategory::get($context_id)))
			return [];
		
		return array(
			'id' => $category->id,
			'name' => $category->name,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&type=kb_category&id=%d-%s", $category->id, DevblocksPlatform::strToPermalink($category->name), true)),
			'updated' => 0, // [TODO]
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		$results = DAO_KbCategory::autocomplete($term);

		if(is_array($results))
		foreach($results as $id => $record) {
			$entry = new stdClass();
			$entry->label = sprintf("%s", $record->name);
			$entry->value = sprintf("%d", $id);
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($category, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Category:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_CATEGORY);
		
		// Polymorph
		if(is_numeric($category)) {
			$category = DAO_KbCategory::get($category);
		} elseif($category instanceof Model_KbCategory) {
			// It's what we want already.
		} elseif(is_array($category)) {
			$category = Cerb_ORMHelper::recastArrayToModel($category, 'Model_KbCategory');
		} else {
			$category = null;
		}
		/* @var $category Model_KbCategory */
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'parent_id' => $prefix.$translate->_('common.parent'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'parent_id' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_KbCategory::ID;
		$token_values['_type'] = Context_KbCategory::URI;
		
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $category) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $category->name;
			$token_values['id'] = $category->id;
			$token_values['name'] = $category->name;
			$token_values['parent__context'] = CerberusContexts::CONTEXT_KB_CATEGORY;
			$token_values['parent_id'] = $category->parent_id;
			$token_values['updated_at'] = $category->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($category, $token_values);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_KbCategory::ID,
			'links' => '_links',
			'name' => DAO_KbCategory::NAME,
			'parent_id' => DAO_KbCategory::PARENT_ID,
			'updated_at' => DAO_KbCategory::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['parent_id']['notes'] = "The ID of the parent [category](/docs/records/types/kb_category/); if `0` this is a top-level topic";
		
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
		
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
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
		$view->addParams([], true);
		$view->renderSortBy = SearchFields_KbCategory::NAME;
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
				//new DevblocksSearchCriteria(SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_KbCategory::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$model = new Model_KbCategory();
		}
		
		if(!$context_id || $edit) {
			if($model && $model->id) {
				if(!Context_KbCategory::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					list($k,$v) = array_pad(explode(':', $token, 2), 2, null);
					
					if($v)
					switch($k) {
						case 'parent.id':
							$model->parent_id = $v;
							break;
					}
				}
			}
			
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
			$tpl->display('devblocks:cerberusweb.kb::kb/category/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};

class Model_KbCategory {
	public $id = 0;
	public $parent_id = 0;
	public $name;
	public $updated_at = 0;
};

class View_KbCategory extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'kb_categories';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('kb.common.knowledgebase_categories');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_KbCategory::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_KbCategory::NAME,
			SearchFields_KbCategory::PARENT_ID,
			SearchFields_KbCategory::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK,
			SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET,
			SearchFields_KbCategory::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_KbCategory::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_KbCategory');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_KbCategory', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_KbCategory', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_KbCategory::PARENT_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK:
				case SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET:
				case SearchFields_KbCategory::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_KB_CATEGORY;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_KbCategory::PARENT_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_KbCategory::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_KbCategory::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_KbCategory::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'article.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbCategory::ARTICLE_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_KB_ARTICLE, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_KB_CATEGORY],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbCategory::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_KB_CATEGORY, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_KbCategory::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'parent.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_KbCategory::PARENT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_KB_CATEGORY, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_KbCategory::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_KbCategory::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_KB_CATEGORY, $fields, null);
		
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
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_KbCategory::VIRTUAL_WATCHERS, $tokens);
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_KB_CATEGORY);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.kb::kb/category/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_KbCategory::ARTICLE_ID:
			case SearchFields_KbCategory::PARENT_ID:
				$label_map = SearchFields_KbCategory::getLabelsForKeyValues($field, $values);
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
			case SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_KbCategory::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_KbCategory::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_KbCategory::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_KbCategory::ID:
			case SearchFields_KbCategory::PARENT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_KbCategory::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_KbCategory::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
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
