<?php
class DAO_ClassifierExample extends Cerb_ORMHelper {
	const CLASS_ID = 'class_id';
	const CLASSIFIER_ID = 'classifier_id';
	const EXPRESSION = 'expression';
	const ID = 'id';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CLASS_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CLASSIFIER_CLASS, true))
			;
		$validation
			->addField(self::CLASSIFIER_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CLASSIFIER))
			;
		$validation
			->addField(self::EXPRESSION)
			->string()
			->setMaxLength(16777215)
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
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
		
		$sql = "INSERT INTO classifier_example () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'classifier_example', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.classifier_example.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('classifier_example', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::CLASSIFIER_ID])) {
			$error = "A 'classifier_id' is required.";
			return false;
		}
		
		if(isset($fields[self::CLASSIFIER_ID])) {
			@$classifier_id = $fields[self::CLASSIFIER_ID];
			
			if(!$classifier_id) {
				$error = "Invalid 'classifier_id' value.";
				return false;
			}
			
			if(!Context_Classifier::isWriteableByActor($classifier_id, $actor)) {
				$error = "You do not have permission to create training data on this classifier.";
				return false;
			}
		}
		
		return true;
	}
	
	static function onUpdateByActor($actor, $fields, $id) {
		@$classifier_id = $fields[self::CLASSIFIER_ID];
		@$class_id = $fields[self::CLASS_ID];
		@$expression = $fields[self::EXPRESSION];
		
		if($classifier_id && $class_id && $expression) {
			$bayes = DevblocksPlatform::services()->bayesClassifier();
			$bayes::train($expression, $classifier_id, $class_id);
		}
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ClassifierExample[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, classifier_id, class_id, expression, updated_at ".
			"FROM classifier_example ".
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
	
	static function getByClassifier($classifier_id) {
		return self::getWhere(sprintf("%s = %d",
			self::escape(DAO_ClassifierExample::CLASSIFIER_ID),
			$classifier_id
		));
	}
	
	static function getByClass($class_id) {
		return self::getWhere(sprintf("%s = %d",
			self::escape(DAO_ClassifierExample::CLASS_ID),
			$class_id
		));
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_ClassifierExample[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ClassifierExample::EXPRESSION, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ClassifierExample	 */
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
	 * @return Model_ClassifierExample[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ClassifierExample[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ClassifierExample();
			$object->id = $row['id'];
			$object->classifier_id = $row['classifier_id'];
			$object->class_id = $row['class_id'];
			$object->expression = $row['expression'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('classifier_example');
	}
	
	static public function countByClassifier($classifier_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(id) FROM classifier_example ".
			"WHERE classifier_id = %d",
			$classifier_id
		));
	}
	
	static public function countByClass($class_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(id) FROM classifier_example ".
			"WHERE class_id = %d",
			$class_id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM classifier_example WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ClassifierExample::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ClassifierExample', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"classifier_example.id as %s, ".
			"classifier_example.classifier_id as %s, ".
			"classifier_example.class_id as %s, ".
			"classifier_example.expression as %s, ".
			"classifier_example.updated_at as %s ",
				SearchFields_ClassifierExample::ID,
				SearchFields_ClassifierExample::CLASSIFIER_ID,
				SearchFields_ClassifierExample::CLASS_ID,
				SearchFields_ClassifierExample::EXPRESSION,
				SearchFields_ClassifierExample::UPDATED_AT
			);
			
		$join_sql = "FROM classifier_example ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ClassifierExample');
	
		return array(
			'primary_table' => 'classifier_example',
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
			SearchFields_ClassifierExample::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

};

class SearchFields_ClassifierExample extends DevblocksSearchFields {
	const ID = 'c_id';
	const CLASSIFIER_ID = 'c_classifier_id';
	const CLASS_ID = 'c_class_id';
	const EXPRESSION = 'c_expression';
	const UPDATED_AT = 'c_updated_at';
	
	const VIRTUAL_CLASSIFIER_SEARCH = '*_classifier_search';
	const VIRTUAL_CLASSIFIER_CLASS_SEARCH = '*_classifier_class_search';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'classifier_example.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE => new DevblocksSearchFieldContextKeys('classifier_example.id', self::ID),
			CerberusContexts::CONTEXT_CLASSIFIER => new DevblocksSearchFieldContextKeys('classifier_example.classifier_id', self::CLASSIFIER_ID),
			CerberusContexts::CONTEXT_CLASSIFIER_CLASS => new DevblocksSearchFieldContextKeys('classifier_example.class_id', self::CLASS_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CLASSIFIER_CLASS_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CLASSIFIER_CLASS, 'classifier_example.class_id');
				break;
				
			case self::VIRTUAL_CLASSIFIER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CLASSIFIER, 'classifier_example.classifier_id');
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE), '%s'), self::getPrimaryKey());
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
			case 'classifier':
				$key = 'classifier.id';
				break;
				
			case 'class':
				$key = 'class.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ClassifierExample::CLASSIFIER_ID:
				$models = DAO_Classifier::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				break;
				
			case SearchFields_ClassifierExample::CLASS_ID:
				$models = DAO_ClassifierClass::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				break;
			
			case SearchFields_ClassifierExample::ID:
				$models = DAO_ClassifierExample::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'expression', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'classifier_example', 'id', $translate->_('common.id'), null, true),
			self::CLASSIFIER_ID => new DevblocksSearchField(self::CLASSIFIER_ID, 'classifier_example', 'classifier_id', $translate->_('common.classifier'), null, true),
			self::CLASS_ID => new DevblocksSearchField(self::CLASS_ID, 'classifier_example', 'class_id', $translate->_('common.classifier.classification'), null, true),
			self::EXPRESSION => new DevblocksSearchField(self::EXPRESSION, 'classifier_example', 'expression', $translate->_('dao.classifier_example.expression'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'classifier_example', 'updated_at', $translate->_('common.updated'), null, true),
				
			self::VIRTUAL_CLASSIFIER_SEARCH => new DevblocksSearchField(self::VIRTUAL_CLASSIFIER_SEARCH, '*', 'classifier_search', null, null, false),
			self::VIRTUAL_CLASSIFIER_CLASS_SEARCH => new DevblocksSearchField(self::VIRTUAL_CLASSIFIER_CLASS_SEARCH, '*', 'classifier_class_search', null, null, false),
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

class Model_ClassifierExample {
	public $id = 0;
	public $classifier_id = 0;
	public $class_id = 0;
	public $expression = null;
	public $updated_at = 0;
	
	function getClassifier() {
		return DAO_Classifier::get($this->classifier_id);
	}
	
	function getClass() {
		return DAO_ClassifierClass::get($this->class_id);
	}
};

class View_ClassifierExample extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'classifier_examples';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_ucfirst($translate->_('common.examples'));
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ClassifierExample::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ClassifierExample::CLASS_ID,
			SearchFields_ClassifierExample::CLASSIFIER_ID,
			SearchFields_ClassifierExample::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_CLASS_SEARCH,
			SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_SEARCH,
			SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ClassifierExample::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ClassifierExample');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ClassifierExample', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ClassifierExample', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ClassifierExample::CLASS_ID:
				case SearchFields_ClassifierExample::CLASSIFIER_ID:
				case SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ClassifierExample::CLASS_ID:
				$label_map = function($ids) {
					$classes = DAO_ClassifierClass::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($classes), 'name', 'id');
				};
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ClassifierExample::CLASSIFIER_ID:
				$classifiers = DAO_Classifier::getAll();
				$label_map = array_column(DevblocksPlatform::objectsToArrays($classifiers), 'name', 'id');
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_ClassifierExample::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ClassifierExample::EXPRESSION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'class.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ClassifierExample::CLASS_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CLASSIFIER_CLASS, 'q' => ''],
					]
				),
			'class' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_CLASS_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CLASSIFIER_CLASS, 'q' => ''],
					]
				),
			'classifier.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ClassifierExample::CLASSIFIER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CLASSIFIER, 'q' => ''],
					]
				),
			'classifier' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CLASSIFIER, 'q' => ''],
					]
				),
			'expression' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ClassifierExample::EXPRESSION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ClassifierExample::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ClassifierExample::UPDATED_AT),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'class':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_CLASS_SEARCH);
				break;
				
			case 'classifier':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_SEARCH);
				break;
				
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

		// Classifiers
		$classifiers = DAO_Classifier::getAll();
		$tpl->assign('classifiers', $classifiers);
		
		// Classes
		$classes = DAO_ClassifierClass::getAll();
		$tpl->assign('classes', $classes);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/classifier/example/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ClassifierExample::CLASS_ID:
				$label_map = SearchFields_ClassifierExample::getLabelsForKeyValues($field, $values);
				self::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_ClassifierExample::CLASSIFIER_ID:
				$label_map = SearchFields_ClassifierExample::getLabelsForKeyValues($field, $values);
				self::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_CLASS_SEARCH:
				echo sprintf("Classification matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
				
			case SearchFields_ClassifierExample::VIRTUAL_CLASSIFIER_SEARCH:
				echo sprintf("Classifier matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;
				
			case SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ClassifierExample::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ClassifierExample::EXPRESSION:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ClassifierExample::ID:
			case SearchFields_ClassifierExample::CLASSIFIER_ID:
			case SearchFields_ClassifierExample::CLASS_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ClassifierExample::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ClassifierExample::VIRTUAL_HAS_FIELDSET:
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

class Context_ClassifierExample extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
	const URI = 'classifier_example';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $models, 'classifier_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE, $models, 'classifier_owner_');
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_ClassifierExample::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=classifier_example&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ClassifierExample();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('dao.classifier_example.expression')),
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
		
		$properties['classifier_id'] = array(
			'label' => mb_ucfirst($translate->_('common.classifier')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->classifier_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CLASSIFIER,
			],
		);
		
		$properties['class_id'] = array(
			'label' => mb_ucfirst($translate->_('common.classifier.classification')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->class_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CLASSIFIER_CLASS,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($classifier_example = DAO_ClassifierExample::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($classifier_example->expression);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $classifier_example->id,
			'name' => $classifier_example->expression,
			'permalink' => $url,
			'updated' => $classifier_example->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'classifier__label',
			'class__label',
			'updated_at',
		);
	}
	
	function getContext($classifier_example, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Example:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE);

		// Polymorph
		if(is_numeric($classifier_example)) {
			$classifier_example = DAO_ClassifierExample::get($classifier_example);
		} elseif($classifier_example instanceof Model_ClassifierExample) {
			// It's what we want already.
		} elseif(is_array($classifier_example)) {
			$classifier_example = Cerb_ORMHelper::recastArrayToModel($classifier_example, 'Model_ClassifierExample');
		} else {
			$classifier_example = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'expression' => $prefix.$translate->_('dao.classifier_example.expression'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
				
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'expression' => Model_CustomField::TYPE_SINGLE_LINE,
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
		$token_values = array();
		
		$token_values['_context'] = Context_ClassifierExample::ID;
		$token_values['_type'] = Context_ClassifierExample::URI;
		
		$token_values['_types'] = $token_types;
		
		if($classifier_example) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $classifier_example->expression;
			$token_values['id'] = $classifier_example->id;
			$token_values['expression'] = $classifier_example->expression;
			$token_values['updated_at'] = $classifier_example->updated_at;
			
			// Classifier
			$token_values['classifier_id'] = $classifier_example->classifier_id;
			
			// Classification
			$token_values['class_id'] = $classifier_example->class_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($classifier_example, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=classifier_example&id=%d-%s",$classifier_example->id, DevblocksPlatform::strToPermalink($classifier_example->expression)), true);
		}
		
		// Classifier
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CLASSIFIER, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'classifier_',
			$prefix.'Classifier:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Classification
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CLASSIFIER_CLASS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'class_',
			$prefix.'Classification:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'class_id' => DAO_ClassifierExample::CLASS_ID,
			'classifier_id' => DAO_ClassifierExample::CLASSIFIER_ID,
			'expression' => DAO_ClassifierExample::EXPRESSION,
			'id' => DAO_ClassifierExample::ID,
			'links' => '_links',
			'updated_at' => DAO_ClassifierExample::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['class_id']['notes'] = "The ID of the [classification](/docs/records/types/classifier_class/) this example trains";
		$keys['classifier_id']['notes'] = "The ID of the [classifier](/docs/records/types/classifier/) this example belongs to";
		$keys['expression']['notes'] = "The expression used for training the classifier";
		
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
		
		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
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
		$view->name = 'Examples';
		/*
		$view->addParams(array(
			SearchFields_ClassifierExample::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ClassifierExample::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ClassifierExample::UPDATED_AT;
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
		$view->name = 'Examples';
		
		$params_req = array();
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_ClassifierExample::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_ClassifierExample::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$model = new Model_ClassifierExample();
				
				if(false != ($view = C4_AbstractViewLoader::getView($view_id))) {
					switch(get_class($view)) {
						case 'View_ClassifierExample':
							$filters = $view->findParam(SearchFields_ClassifierExample::CLASSIFIER_ID, $view->getParams());
							
							if(!empty($filters)) {
								$filter = array_shift($filters);
								if(is_numeric($filter->value))
									$model->classifier_id = $filter->value;
							}
							
							$filters = $view->findParam(SearchFields_ClassifierExample::CLASS_ID, $view->getParams());
							
							if(!empty($filters)) {
								$filter = array_shift($filters);
								if(is_numeric($filter->value))
									$model->class_id = $filter->value;
							}
							break;
					}
				}
				
				if(!empty($edit)) {
					$tokens = explode(' ', trim($edit));
					
					foreach($tokens as $token) {
						@list($k,$v) = explode(':', $token);
						
						if(empty($k) || empty($v))
							continue;
						
						switch($k) {
							case 'classifier.id':
								$model->classifier_id = intval($v);
								break;
								
							case 'class.id':
								$model->class_id = intval($v);
								break;
								
							case 'text':
								$model->expression = urldecode($v);
								break;
						}
					}
				}
			}
			$tpl->assign('model', $model);
			
			// Classifiers
			$classifiers = DAO_Classifier::getAll();
			$tpl->assign('classifiers', $classifiers);
			
			// Classes
			$classes = DAO_ClassifierClass::getAll();
			$tpl->assign('classes', $classes);
			
			// Entities
			$bayes = DevblocksPlatform::services()->bayesClassifier();
			$entities = $bayes::getEntities();
			$tpl->assign('entities', $entities);
			
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
			$tpl->display('devblocks:cerberusweb.core::internal/classifier/example/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}	
	
};
