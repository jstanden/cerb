<?php
class DAO_Skill extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const NAME = 'name';
	const SKILLSET_ID = 'skillset_id';
	const UPDATED_AT = 'updated_at';
	
	const CACHE_ALL = 'dao_skills_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::SKILLSET_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_SKILLSET))
			->setRequired(true)
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
		
		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = true;
		
		$sql = "INSERT INTO skill () VALUES ()";
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
		
		$context = CerberusContexts::CONTEXT_SKILL;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_SKILL, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'skill', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.skill.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_SKILL, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('skill', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_SKILL;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Skill[]
	 */
	static function getWhere($where=null, $sortBy=DAO_Skill::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, skillset_id, created_at, updated_at ".
			"FROM skill ".
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
	 * @param bool $nocache
	 * @return Model_Skill[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_Skill::getWhere(
				null,
				DAO_Skill::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_Skill
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
	
	static function getBySkillset($skillset_id) {
		$skills = self::getAll();
		
		$skills = array_filter($skills, function($skill) use ($skillset_id) {
			if($skill->skillset_id == $skillset_id)
				return true;
			
			return false;
		});
		
		return $skills;
	}
	
	static function getContextSkillLevels($context, $context_id) {
		$db = DevblocksPlatform::services()->database();
		
		// [TODO] Do we cache this for workers?
		// [TODO] Or do we pull worker skill levels separately?
		
		$results = $db->GetArraySlave(sprintf("SELECT skill_id, skill_level FROM context_to_skill WHERE context = %s AND context_id = %d",
			$db->qstr($context),
			$context_id
		));
		
		$skill_levels = [];
		
		foreach($results as $result) {
			$skill_levels[intval($result['skill_id'])] = intval($result['skill_level']);
		}
		
		return $skill_levels;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Skill[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Skill();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->skillset_id = $row['skillset_id'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function count() {
		if(false == ($skills = DAO_Skill::getAll()) || !is_array($skills))
			return 0;
		
		return count($skills);
	}
	
	static function random() {
		return self::_getRandom('skill');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM skill WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_SKILL,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Skill::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Skill', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"skill.id as %s, ".
			"skill.name as %s, ".
			"skill.skillset_id as %s, ".
			"skill.created_at as %s, ".
			"skill.updated_at as %s ",
				SearchFields_Skill::ID,
				SearchFields_Skill::NAME,
				SearchFields_Skill::SKILLSET_ID,
				SearchFields_Skill::CREATED_AT,
				SearchFields_Skill::UPDATED_AT
			);
			
		$join_sql = "FROM skill ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Skill');
	
		return array(
			'primary_table' => 'skill',
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
		
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Skill::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(skill.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}

};

class SearchFields_Skill extends DevblocksSearchFields {
	const ID = 's_id';
	const NAME = 's_name';
	const SKILLSET_ID = 's_skillset_id';
	const CREATED_AT = 's_created_at';
	const UPDATED_AT = 's_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_SKILLSET_SEARCH = '*_skillset_search';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'skill.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_SKILL => new DevblocksSearchFieldContextKeys('skill.id', self::ID),
			CerberusContexts::CONTEXT_SKILLSET => new DevblocksSearchFieldContextKeys('skill.skillset_id', self::SKILLSET_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_SKILL, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_SKILL)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_SKILLSET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_SKILLSET, 'skill.skillset_id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_SKILL, self::getPrimaryKey());
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
			case 'skillset':
				$key = 'skillset.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Skill::ID:
				$models = DAO_Skill::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_Skill::SKILLSET_ID:
				$models = DAO_Skillset::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'skill', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'skill', 'name', $translate->_('common.name'), null, true),
			self::SKILLSET_ID => new DevblocksSearchField(self::SKILLSET_ID, 'skill', 'skillset_id', $translate->_('common.skillset'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'skill', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'skill', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_SKILLSET_SEARCH => new DevblocksSearchField(self::VIRTUAL_SKILLSET_SEARCH, '*', 'skillset_search', null, null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
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

class Model_Skill {
	public $id;
	public $name;
	public $skillset_id;
	public $created_at;
	public $updated_at;
};

class View_Skill extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'skill';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.skills');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Skill::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Skill::NAME,
			SearchFields_Skill::SKILLSET_ID,
			SearchFields_Skill::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_Skill::VIRTUAL_CONTEXT_LINK,
			SearchFields_Skill::VIRTUAL_HAS_FIELDSET,
			SearchFields_Skill::VIRTUAL_SKILLSET_SEARCH,
			SearchFields_Skill::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Skill::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Skill');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Skill', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Skill', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Skill::SKILLSET_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Skill::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Skill::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Skill::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_SKILL;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Skill::SKILLSET_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Skill::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_Skill::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Skill::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Skill::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Skill::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Skill::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Skill::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Skill::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_SKILL],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Skill::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_SKILL, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Skill::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'skillset' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Skill::VIRTUAL_SKILLSET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_SKILLSET, 'q' => ''],
					]
				),
			'skillset.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Skill::SKILLSET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_SKILLSET, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Skill::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Skill::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_SKILL, $fields, null);
		
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
			
			case 'skillset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Skill::VIRTUAL_SKILLSET_SEARCH);
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

		// Skillsets
		$skillsets = DAO_Skillset::getAll();
		$tpl->assign('skillsets', $skillsets);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SKILL);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/skills/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Skill::SKILLSET_ID:
				$label_map = SearchFields_Skill::getLabelsForKeyValues($field, $values);
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
			case SearchFields_Skill::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Skill::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Skill::VIRTUAL_SKILLSET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.skillset')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_Skill::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Skill::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Skill::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Skill::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Skill::CREATED_AT:
			case SearchFields_Skill::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Skill::SKILLSET_ID:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field, $oper, $options);
				break;
				
			case SearchFields_Skill::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Skill::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Skill::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
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

class Context_Skill extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = 'cerberusweb.contexts.skill';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admin workers can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	function getRandom() {
		return DAO_Skill::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=skill&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Skill();
		
		/*
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		*/
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->name,
		);
			
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$skill = DAO_Skill::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($skill->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $skill->id,
			'name' => $skill->name,
			'permalink' => $url,
			'updated' => $skill->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'skillset__label',
			'updated_at',
		);
	}
	
	function getContext($skill, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Skill:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SKILL);

		// Polymorph
		if(is_numeric($skill)) {
			$skill = DAO_Skill::get($skill);
		} elseif($skill instanceof Model_Skill) {
			// It's what we want already.
		} elseif(is_array($skill)) {
			$skill = Cerb_ORMHelper::recastArrayToModel($skill, 'Model_Skill');
		} else {
			$skill = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_SKILL;
		$token_values['_types'] = $token_types;
		
		if($skill) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $skill->name;
			$token_values['id'] = $skill->id;
			$token_values['name'] = $skill->name;
			$token_values['skillset_id'] = $skill->skillset_id;
			$token_values['updated_at'] = $skill->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($skill, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=skill&id=%d-%s",$skill->id, DevblocksPlatform::strToPermalink($skill->name)), true);
		}
		
		// Skillset
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_SKILLSET, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'skillset_',
			$prefix.'Skillset:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_Skill::ID,
			'links' => '_links',
			'name' => DAO_Skill::NAME,
			'skillset_id' => DAO_Skill::SKILLSET_ID,
			'updated_at' => DAO_Skill::UPDATED_AT,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['skillset_id']['notes'] = "The ID of the [skillset](/docs/records/types/skillset/) containing this skill";
		
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
		
		$context = CerberusContexts::CONTEXT_SKILL;
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
		$view->name = 'Skill';
		/*
		$view->addParams(array(
			SearchFields_Skill::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Skill::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Skill::UPDATED_AT;
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
		$view->name = 'Skills';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Skill::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($skill = DAO_Skill::get($context_id))) {
			$tpl->assign('model', $skill);
		}
		
		$skillsets = DAO_Skillset::getAll();
		$tpl->assign('skillsets', $skillsets);
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SKILL, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_SKILL, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}

		// Comments
		
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_SKILL, $context_id);
		$comments = array_reverse($comments, true);
		$tpl->assign('comments', $comments);
		
		// Template
		
		if(empty($skillsets)) {
			$tpl->assign('error_message', "You can't add new skills until you add at least one skillset first.");
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return;
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/skills/peek.tpl');
	}
};
