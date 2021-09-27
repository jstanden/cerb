<?php
class DAO_CardWidget extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const NAME = 'name';
	const POS = 'pos';
	const RECORD_TYPE = 'record_type';
	const UPDATED_AT = 'updated_at';
	const WIDTH_UNITS = 'width_units';
	const ZONE = 'zone';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setRequired(true)
		;
		$validation
			->addField(self::EXTENSION_PARAMS_JSON)
			->string()
			->setMaxLength(16777216)
		;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
		;
		$validation
			->addField(self::POS)
			->number()
			->setMin(0)
			->setMax(255)
		;
		$validation
			->addField(self::RECORD_TYPE)
			->context()
			->setRequired(true)
		;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::WIDTH_UNITS)
			->number()
			->setMin(1)
			->setMax(255)
		;
		$validation
			->addField(self::ZONE)
			->string()
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
		
		if(!array_key_exists(DAO_CardWidget::CREATED_AT, $fields))
			$fields[DAO_CardWidget::CREATED_AT] = time();
		
		$sql = "INSERT INTO card_widget () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CARD_WIDGET, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'card_widget', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.card_widget.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('card_widget', $fields, $where);
	}
	
	static function reorder(array $zones=[]) {
		if(empty($zones))
			return;
		
		$db = DevblocksPlatform::services()->database();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$values = [];
		
		foreach($zones as $zone => $ids)
			foreach($ids as $pos => $id)
				$values[] = sprintf("(%d,%s,%d)", $id, $db->qstr($zone), $pos+1);
		
		if(empty($values))
			return;
		
		$sql = sprintf("INSERT INTO card_widget (id, zone, pos) VALUES %s ON DUPLICATE KEY UPDATE zone=VALUES(zone), pos=VALUES(pos)",
			implode(',', $values)
		);
		
		$db->ExecuteMaster($sql);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CardWidget[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone ".
			"FROM card_widget ".
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
	 * @param $context
	 * @return Model_CardWidget[]
	 */
	static function getByContext($context) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf('card_widgets:' . $context);
		
		if(false == ($widgets = $cache->load($cache_key))) {
			$widgets = self::getWhere(
				sprintf("record_type = %s",
					Cerb_ORMHelper::qstr($context)
				),
				'pos',
				true
			);
			
			$cache->save($widgets, $cache_key, ['schema_card_widgets'], 86400);
		}
		
		return $widgets;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_CardWidget[]
	 */
	/*
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
		$objects = self::getWhere(null, DAO_CardWidget::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
		
		//if(!is_array($objects))
		//	return false;
		
		//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}
	*/
	
	/**
	 * @param integer $id
	 * @return Model_CardWidget
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
	 *
	 * @param array $ids
	 * @return Model_CardWidget[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_CardWidget[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CardWidget();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->record_type = $row['record_type'];
			$object->extension_id = $row['extension_id'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$object->pos = $row['pos'];
			$object->width_units = $row['width_units'];
			$object->zone = $row['zone'];
			
			$object->extension_params = @json_decode($row['extension_params_json'], true) ?: [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('card_widget');
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->removeByTags(['schema_card_widgets']);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM card_widget WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CARD_WIDGET,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CardWidget::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CardWidget', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"card_widget.id as %s, ".
			"card_widget.name as %s, ".
			"card_widget.record_type as %s, ".
			"card_widget.extension_id as %s, ".
			"card_widget.created_at as %s, ".
			"card_widget.updated_at as %s, ".
			"card_widget.pos as %s, ".
			"card_widget.width_units as %s, ".
			"card_widget.zone as %s ",
			SearchFields_CardWidget::ID,
			SearchFields_CardWidget::NAME,
			SearchFields_CardWidget::RECORD_TYPE,
			SearchFields_CardWidget::EXTENSION_ID,
			SearchFields_CardWidget::CREATED_AT,
			SearchFields_CardWidget::UPDATED_AT,
			SearchFields_CardWidget::POS,
			SearchFields_CardWidget::WIDTH_UNITS,
			SearchFields_CardWidget::ZONE
		);
		
		$join_sql = "FROM card_widget ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CardWidget');
		
		return array(
			'primary_table' => 'card_widget',
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
			SearchFields_CardWidget::ID,
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

class SearchFields_CardWidget extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const RECORD_TYPE = 'c_record_type';
	const EXTENSION_ID = 'c_extension_id';
	const EXTENSION_PARAMS_JSON = 'c_extension_params_json';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';
	const POS = 'c_pos';
	const WIDTH_UNITS = 'c_width_units';
	const ZONE = 'c_zone';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'card_widget.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CARD_WIDGET => new DevblocksSearchFieldContextKeys('card_widget.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CARD_WIDGET, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CARD_WIDGET), '%s'), self::getPrimaryKey());
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
			case SearchFields_CardWidget::ID:
				$models = DAO_CardWidget::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'card_widget', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'card_widget', 'name', $translate->_('common.name'), null, true),
			self::RECORD_TYPE => new DevblocksSearchField(self::RECORD_TYPE, 'card_widget', 'record_type', $translate->_('common.record.type'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'card_widget', 'extension_id', $translate->_('common.type'), null, true),
			self::EXTENSION_PARAMS_JSON => new DevblocksSearchField(self::EXTENSION_PARAMS_JSON, 'card_widget', 'extension_params_json', null, null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'card_widget', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'card_widget', 'updated_at', $translate->_('common.updated'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'card_widget', 'pos', $translate->_('common.order'), null, true),
			self::WIDTH_UNITS => new DevblocksSearchField(self::WIDTH_UNITS, 'card_widget', 'width_units', $translate->_('common.width'), null, true),
			self::ZONE => new DevblocksSearchField(self::ZONE, 'card_widget', 'zone', $translate->_('common.zone'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
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

class Model_CardWidget {
	public $id;
	public $name;
	public $record_type;
	public $extension_id;
	public $extension_params = [];
	public $created_at;
	public $updated_at;
	public $pos;
	public $width_units;
	public $zone;
	
	/**
	 * @return Extension_CardWidget|null
	 */
	function getExtension() {
		return Extension_CardWidget::get($this->extension_id);
	}
	
	/**
	 * @return Extension_DevblocksContext|DevblocksExtensionManifest|null
	 */
	function getRecordExtension($as_instance=true) {
		return Extension_DevblocksContext::get($this->record_type, $as_instance);
	}
	
	function getUniqueId($context_id) {
		return sprintf("%d_%d", $this->id, $context_id);
	}
};

class View_CardWidget extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'card_widgets';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.card.widgets');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CardWidget::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = array(
			SearchFields_CardWidget::NAME,
			SearchFields_CardWidget::RECORD_TYPE,
			SearchFields_CardWidget::EXTENSION_ID,
			SearchFields_CardWidget::POS,
			SearchFields_CardWidget::WIDTH_UNITS,
			SearchFields_CardWidget::ZONE,
			SearchFields_CardWidget::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_CardWidget::EXTENSION_PARAMS_JSON,
			SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK,
			SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_CardWidget::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CardWidget');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CardWidget', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CardWidget', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
					case SearchFields_CardWidget::EXTENSION_ID:
					case SearchFields_CardWidget::RECORD_TYPE:
					case SearchFields_CardWidget::WIDTH_UNITS:
					case SearchFields_CardWidget::ZONE:
						$pass = true;
						break;
					
					// Virtuals
					case SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK:
					case SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_CardWidget::EXTENSION_ID:
			case SearchFields_CardWidget::RECORD_TYPE:
			case SearchFields_CardWidget::WIDTH_UNITS:
			case SearchFields_CardWidget::ZONE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
			
			case SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_CardWidget::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CardWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CardWidget::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CARD_WIDGET],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CardWidget::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CARD_WIDGET, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CardWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'pos' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CardWidget::POS),
				),
			'type' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CardWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CardWidget::UPDATED_AT),
				),
			'width' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CardWidget::WIDTH_UNITS),
				),
			'zone' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CardWidget::ZONE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CARD_WIDGET, $fields, null);
		
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CARD_WIDGET);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/card_widget/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		
		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_CardWidget::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_CardWidget::NAME:
			case SearchFields_CardWidget::EXTENSION_ID:
			case SearchFields_CardWidget::RECORD_TYPE:
			case SearchFields_CardWidget::ZONE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_CardWidget::ID:
			case SearchFields_CardWidget::POS:
			case SearchFields_CardWidget::WIDTH_UNITS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_CardWidget::CREATED_AT:
			case SearchFields_CardWidget::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_CardWidget::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
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

class Context_CardWidget extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_CARD_WIDGET;
	const URI = 'card_widget';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_CardWidget::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=card_widget&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_CardWidget();
		
		$properties['created_at'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['extension_id'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => @$model->getExtension()->manifest->name ?? $model->extension_id,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['order'] = array(
			'label' => mb_ucfirst($translate->_('common.order')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->pos,
		);
		
		$properties['record_type'] = array(
			'label' => mb_ucfirst($translate->_('common.record.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => @$model->getRecordExtension()->manifest->name ?? $model->record_type,
		);
		
		$properties['updated_at'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['width_units'] = array(
			'label' => mb_ucfirst($translate->_('common.width')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->width_units,
		);
		
		$properties['zone'] = array(
			'label' => mb_ucfirst($translate->_('common.zone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->zone,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($card_widget = DAO_CardWidget::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($card_widget->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $card_widget->id,
			'name' => $card_widget->name,
			'permalink' => $url,
			'updated' => $card_widget->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($card_widget, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Card Widget:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CARD_WIDGET);
		
		// Polymorph
		if(is_numeric($card_widget)) {
			$card_widget = DAO_CardWidget::get($card_widget);
		} elseif($card_widget instanceof Model_CardWidget) {
			// It's what we want already.
		} elseif(is_array($card_widget)) {
			$card_widget = Cerb_ORMHelper::recastArrayToModel($card_widget, 'Model_CardWidget');
		} else {
			$card_widget = null;
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
		
		$token_values['_context'] = Context_CardWidget::ID;
		$token_values['_type'] = Context_CardWidget::URI;
		
		$token_values['_types'] = $token_types;
		
		if($card_widget) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $card_widget->name;
			$token_values['id'] = $card_widget->id;
			$token_values['name'] = $card_widget->name;
			$token_values['updated_at'] = $card_widget->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($card_widget, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=card_widget&id=%d-%s",$card_widget->id, DevblocksPlatform::strToPermalink($card_widget->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'extension_id' => DAO_CardWidget::EXTENSION_ID,
			'id' => DAO_CardWidget::ID,
			'links' => '_links',
			'name' => DAO_CardWidget::NAME,
			'pos' => DAO_CardWidget::POS,
			'record_type' => DAO_CardWidget::RECORD_TYPE,
			'updated_at' => DAO_CardWidget::UPDATED_AT,
			'width_units' => DAO_CardWidget::WIDTH_UNITS,
			'zone' => DAO_CardWidget::ZONE,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['extension_params'] = [
			'key' => 'extension_params',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		$keys['extension_id']['notes'] = "[Card Widget Type](/docs/plugins/extensions/points/cerb.card.widget/)";
		$keys['pos']['notes'] = "The order of the widget on the card; `0` is first (top-left) proceeding in rows then columns";
		$keys['record_type']['notes'] = "The record type of the card containing this widget";
		$keys['width_units']['notes'] = "`1` (25%), `2` (50%), `3` (75%), `4` (100%)";
		$keys['zone']['notes'] = "The name of the dashboard zone containing the widget; this varies by layout; generally `sidebar` and `content`";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'extension_params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_CardWidget::EXTENSION_PARAMS_JSON] = $json;
				break;
			
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
		
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
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
		$view->name = 'Card Widget';
		$view->renderSortBy = SearchFields_CardWidget::UPDATED_AT;
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
		$view->name = 'Card Widget';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CardWidget::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CARD_WIDGET;
		
		$model = null;
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($model = DAO_CardWidget::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_CardWidget::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$model = new Model_CardWidget();
			}
			
			if($edit) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token);
					
					if($v)
						switch($k) {
							case 'context':
								$model->record_type = $v;
								break;
						}
				}
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$context_mfts = Extension_DevblocksContext::getAll(false);
			$tpl->assign('context_mfts', $context_mfts);
			
			// Widget extensions
			
			$widget_extensions = Extension_CardWidget::getByContext($model->record_type, false);
			$tpl->assign('widget_extensions', $widget_extensions);
			
			// Placeholder menu
			
			if($model) {
				$labels = $values = [];
				
				// Record dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext($model->record_type, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('record_', 'Record ', $merge_labels, $merge_values, $labels, $values);
				
				// Widget dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_CARD_WIDGET, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('widget_', 'Widget ', $merge_labels, $merge_values, $labels, $values);
				
				$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
				$tpl->assign('placeholders', $placeholders);
			}
			
			// Library
			
			if(empty($context_id) && false != ($record_context_mft = $model->getRecordExtension(false))) {
				$context_aliases = Extension_DevblocksContext::getAliasesForContext($record_context_mft);
				$packages = DAO_PackageLibrary::getByPoint([
					'card_widget:' . $context_aliases['uri'],
					'card_widget',
				
				]);
				$tpl->assign('packages', $packages);
			}
			
			// View
			$tpl->assign('model', $model);
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/card_widget/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
