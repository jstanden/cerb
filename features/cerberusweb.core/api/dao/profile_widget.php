<?php
class DAO_ProfileWidget extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const ID = 'id';
	const NAME = 'name';
	const OPTIONS_KATA = 'options_kata';
	const POS = 'pos';
	const PROFILE_TAB_ID = 'profile_tab_id';
	const UPDATED_AT = 'updated_at';
	const WIDTH_UNITS = 'width_units';
	const ZONE = 'zone';
	
	const _CACHE_ALL = 'profile_widgets_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
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
			->addField(self::OPTIONS_KATA)
			->string()
			->setMaxLength(65535)
		;
		$validation
			->addField(self::POS)
			->number()
			->setMin(0)
			->setMax(255)
			;
		$validation
			->addField(self::PROFILE_TAB_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_PROFILE_TAB, true))
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
		
		$sql = "INSERT INTO profile_widget () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_PROFILE_WIDGET, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
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
			parent::_update($batch_ids, 'profile_widget', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.profile_widget.update',
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
		parent::_updateWhere('profile_widget', $fields, $where);
	}
	
	static function reorder(array $zones=[], $profile_tab_id=null) {
		if(empty($zones))
			return;
		
		$db = DevblocksPlatform::services()->database();
		$active_worker = CerberusApplication::getActiveWorker();
		$cache = DevblocksPlatform::services()->cache();
		
		if(!$active_worker->is_superuser)
			return;
		
		$values = [];
		
		foreach($zones as $zone => $ids)
			foreach($ids as $pos => $id)
				$values[] = sprintf("(%d,%s,%d)", $id, $db->qstr($zone), $pos+1);
		
		if(empty($values))
			return;
		
		$sql = sprintf("INSERT INTO profile_widget (id, zone, pos) VALUES %s ON DUPLICATE KEY UPDATE zone=VALUES(zone), pos=VALUES(pos)",
			implode(',', $values)
		);
		
		$db->ExecuteMaster($sql);
		
		if($profile_tab_id) {
			$cache_key = sprintf("profile_tab_%d", $profile_tab_id);
			$cache->remove($cache_key);
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ProfileWidget[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, profile_tab_id, extension_id, extension_params_json, options_kata, pos, width_units, updated_at, zone ".
			"FROM profile_widget ".
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
	 * @return Model_ProfileWidget[]
	 */
	static function getByContext($context) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf('profile_widgets:%s', $context);
		
		if(false == ($widgets = $cache->load($cache_key))) {
			$widgets = self::getWhere(
				sprintf("profile_tab_id IN (SELECT id FROM profile_tab WHERE context = %s)",
					Cerb_ORMHelper::qstr($context)
				),
				'pos',
				true
			);
			
			$cache->save($widgets, $cache_key, ['schema_profile_widgets'], 86400);
		}
		
		return $widgets;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_ProfileWidget[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ProfileWidget::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return [];
				
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	static function getByTab($profile_tab_id) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("profile_tab_%d", $profile_tab_id);
		
		if(null === ($objects = $cache->load($cache_key))) {
			$objects = self::getWhere(
				sprintf("%s = %d",
					Cerb_ORMHelper::escape(self::PROFILE_TAB_ID),
					$profile_tab_id
				),
				DAO_ProfileWidget::POS,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($objects))
				return false;
				
			$cache->save($objects, $cache_key, ['schema_profile_widgets'], 86400);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @param string $context_hint
	 * @return Model_ProfileWidget
	 */
	static function get($id, $context_hint=null) {
		if(!$id)
			return null;
		
		if($context_hint) {
			$widgets = DAO_ProfileWidget::getByContext($context_hint);
			
			if(array_key_exists($id, $widgets))
				return $widgets[$id];
			
			return null;
		}
		
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
	 * @return Model_ProfileWidget[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ProfileWidget[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ProfileWidget();
			$object->extension_id = $row['extension_id'];
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->options_kata = $row['options_kata'];
			$object->pos = DevblocksPlatform::intClamp($row['width_units'], 0, 255);
			$object->profile_tab_id = $row['profile_tab_id'];
			$object->updated_at = $row['updated_at'];
			$object->width_units = DevblocksPlatform::intClamp($row['width_units'], 1, 255);
			$object->zone = $row['zone'];
			
			if(false != ($params = json_decode($row['extension_params_json'] ?? '', true)))
				$object->extension_params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('profile_widget');
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
		$cache->removeByTags(['schema_profile_widgets']);
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM profile_widget WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ProfileWidget::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ProfileWidget', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"profile_widget.id as %s, ".
			"profile_widget.name as %s, ".
			"profile_widget.profile_tab_id as %s, ".
			"profile_widget.extension_id as %s, ".
			"profile_widget.extension_params_json as %s, ".
			"profile_widget.zone as %s, ".
			"profile_widget.pos as %s, ".
			"profile_widget.width_units as %s, ".
			"profile_widget.updated_at as %s ",
				SearchFields_ProfileWidget::ID,
				SearchFields_ProfileWidget::NAME,
				SearchFields_ProfileWidget::PROFILE_TAB_ID,
				SearchFields_ProfileWidget::EXTENSION_ID,
				SearchFields_ProfileWidget::EXTENSION_PARAMS_JSON,
				SearchFields_ProfileWidget::ZONE,
				SearchFields_ProfileWidget::POS,
				SearchFields_ProfileWidget::WIDTH_UNITS,
				SearchFields_ProfileWidget::UPDATED_AT
			);
			
		$join_sql = "FROM profile_widget ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ProfileWidget');
	
		return array(
			'primary_table' => 'profile_widget',
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
			SearchFields_ProfileWidget::ID,
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

class SearchFields_ProfileWidget extends DevblocksSearchFields {
	const EXTENSION_ID = 'p_extension_id';
	const EXTENSION_PARAMS_JSON = 'p_extension_params_json';
	const ID = 'p_id';
	const NAME = 'p_name';
	const POS = 'p_pos';
	const PROFILE_TAB_ID = 'p_profile_tab_id';
	const UPDATED_AT = 'p_updated_at';
	const WIDTH_UNITS = 'p_width_units';
	const ZONE = 'p_zone';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_TAB_SEARCH = '*_tab_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'profile_widget.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_PROFILE_WIDGET => new DevblocksSearchFieldContextKeys('profile_widget.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_PROFILE_WIDGET, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_PROFILE_WIDGET), '%s'), self::getPrimaryKey());
				
			case self::VIRTUAL_TAB_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_PROFILE_TAB, 'profile_widget.profile_tab_id');
				
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'tab':
				$key = 'tab.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ProfileWidget::ID:
				$models = DAO_ProfileWidget::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_ProfileWidget::PROFILE_TAB_ID:
				$models = DAO_ProfileTab::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_ProfileWidget::EXTENSION_ID:
				$extension = Extension_ProfileWidget::getAll(false);
				return array_column(DevblocksPlatform::objectsToArrays($extension), 'name', 'id');
				
			case SearchFields_ProfileWidget::WIDTH_UNITS:
				return [
					1 => '25%',
					2 => '50%',
					3 => '75%',
					4 => '100%',
				];
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
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'profile_widget', 'extension_id', $translate->_('common.type'), null, true),
			self::EXTENSION_PARAMS_JSON => new DevblocksSearchField(self::EXTENSION_PARAMS_JSON, 'profile_widget', 'extension_params_json', null, null, true),
			self::ID => new DevblocksSearchField(self::ID, 'profile_widget', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'profile_widget', 'name', $translate->_('common.name'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'profile_widget', 'pos', $translate->_('common.order'), null, true),
			self::PROFILE_TAB_ID => new DevblocksSearchField(self::PROFILE_TAB_ID, 'profile_widget', 'profile_tab_id', $translate->_('common.tab'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'profile_widget', 'updated_at', $translate->_('common.updated'), null, true),
			self::WIDTH_UNITS => new DevblocksSearchField(self::WIDTH_UNITS, 'profile_widget', 'width_units', $translate->_('Width Units'), null, true),
			self::ZONE => new DevblocksSearchField(self::ZONE, 'profile_widget', 'zone', $translate->_('common.zone'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_TAB_SEARCH => new DevblocksSearchField(self::VIRTUAL_TAB_SEARCH, '*', 'tab_search', null, null, false),
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

class Model_ProfileWidget extends DevblocksRecordModel {
	public $extension_id = 0;
	public $extension_params = [];
	public $id = 0;
	public $name = null;
	public $options_kata = '';
	public $pos = 0;
	public $profile_tab_id = 0;
	public $updated_at = 0;
	public $width_units = 4;
	public $zone = null;
	
	function isHidden(?DevblocksDictionaryDelegate $dict=null) : bool {
		if(!$dict || !$this->options_kata)
			return false;
		
		$dict->scrubKeys('widget_');
		$dict->mergeKeys('widget_', DevblocksDictionaryDelegate::getDictionaryFromModel($this, Context_ProfileWidget::ID));
		
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		if(!($options = $kata->parse($this->options_kata, $error)))
			return false;
		
		if(!($options = $kata->formatTree($options, $dict, $error)))
			return false;
		
		if(array_key_exists('hidden', $options) && $options['hidden'])
			return true;
		
		return false;
	}
	
	/**
	 * @return Model_ProfileTab
	 */
	function getProfileTab() {
		return DAO_ProfileTab::get($this->profile_tab_id);
	}
	
	/**
	 * @return DevblocksExtensionManifest|null
	 */
	function getExtension() {
		return Extension_ProfileWidget::get($this->extension_id);
	}
};

class View_ProfileWidget extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'profile_widgets';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Profile Widgets');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ProfileWidget::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ProfileWidget::NAME,
			SearchFields_ProfileWidget::PROFILE_TAB_ID,
			SearchFields_ProfileWidget::EXTENSION_ID,
			SearchFields_ProfileWidget::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_ProfileWidget::EXTENSION_PARAMS_JSON,
			SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK,
			SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET,
			SearchFields_ProfileWidget::VIRTUAL_TAB_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ProfileWidget::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ProfileWidget');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_ProfileWidget', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ProfileWidget', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ProfileWidget::EXTENSION_ID:
				case SearchFields_ProfileWidget::PROFILE_TAB_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_ProfileWidget::EXTENSION_ID:
			case SearchFields_ProfileWidget::PROFILE_TAB_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_ProfileWidget::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_ProfileWidget::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProfileWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_PROFILE_WIDGET],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProfileWidget::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PROFILE_WIDGET, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProfileWidget::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'pos' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProfileWidget::POS),
				),
			'tab.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProfileWidget::PROFILE_TAB_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PROFILE_TAB, 'q' => ''],
					]
				),
			'tab' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ProfileWidget::VIRTUAL_TAB_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_PROFILE_TAB, 'q' => ''],
					]
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProfileWidget::EXTENSION_ID),
					// [TODO] Examples
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ProfileWidget::UPDATED_AT),
				),
			'width' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ProfileWidget::WIDTH_UNITS),
				),
			'zone' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ProfileWidget::ZONE),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $fields, null);
		
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
				
			case 'tab':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ProfileWidget::VIRTUAL_TAB_SEARCH);
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PROFILE_WIDGET);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Tabs
		$profile_tabs = DAO_ProfileTab::getAll();
		$tpl->assign('profile_tabs', $profile_tabs);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/profiles/widgets/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ProfileWidget::EXTENSION_ID:
			case SearchFields_ProfileWidget::PROFILE_TAB_ID:
			case SearchFields_ProfileWidget::WIDTH_UNITS:
				$label_map = SearchFields_ProfileWidget::getLabelsForKeyValues($field, $values);
				$this->_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_ProfileWidget::VIRTUAL_TAB_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.profile.tab')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_ProfileWidget::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ProfileWidget::NAME:
			case SearchFields_ProfileWidget::EXTENSION_ID:
			case SearchFields_ProfileWidget::EXTENSION_PARAMS_JSON:
			case SearchFields_ProfileWidget::ZONE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ProfileWidget::ID:
			case SearchFields_ProfileWidget::POS:
			case SearchFields_ProfileWidget::PROFILE_TAB_ID:
			case SearchFields_ProfileWidget::WIDTH_UNITS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ProfileWidget::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null,'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ProfileWidget::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null,'array',[]);
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

class Context_ProfileWidget extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_PROFILE_WIDGET;
	const URI = 'profile_widget';
	
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
		return DAO_ProfileWidget::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=profile_widget&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ProfileWidget();
		
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
		
		$properties['profile_tab_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.tab'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->profile_tab_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_PROFILE_TAB,
			],
		);
		
		$properties['type'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getExtension()->manifest->name ?? '',
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($profile_widget = DAO_ProfileWidget::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($profile_widget->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $profile_widget->id,
			'name' => $profile_widget->name,
			'permalink' => $url,
			'updated' => $profile_widget->updated_at,
		];
	}
	
	function getDefaultProperties() {
		return array(
			'extension_id',
			'profile_tab__label',
			'updated_at',
		);
	}
	
	function getContext($profile_widget, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Profile Widget:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PROFILE_WIDGET);

		// Polymorph
		if(is_numeric($profile_widget)) {
			$profile_widget = DAO_ProfileWidget::get($profile_widget);
		} elseif($profile_widget instanceof Model_ProfileWidget) {
			// It's what we want already.
		} elseif(is_array($profile_widget)) {
			$profile_widget = Cerb_ORMHelper::recastArrayToModel($profile_widget, 'Model_ProfileWidget');
		} else {
			$profile_widget = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'pos' => $prefix.$translate->_('common.order'),
			'width_units' => $prefix.$translate->_('common.width'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'zone' => $prefix.$translate->_('common.zone'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'extension_id' => 'extension',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'pos' => Model_CustomField::TYPE_NUMBER,
			'record_url' => Model_CustomField::TYPE_URL,
			'width_units' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'zone' => Model_CustomField::TYPE_SINGLE_LINE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_ProfileWidget::ID;
		$token_values['_type'] = Context_ProfileWidget::URI;
		
		$token_values['_types'] = $token_types;
		
		if($profile_widget) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $profile_widget->name;
			$token_values['extension_id'] = $profile_widget->extension_id;
			$token_values['id'] = $profile_widget->id;
			$token_values['name'] = $profile_widget->name;
			$token_values['pos'] = $profile_widget->pos;
			$token_values['profile_tab_id'] = $profile_widget->profile_tab_id;
			$token_values['updated_at'] = $profile_widget->updated_at;
			$token_values['width_units'] = $profile_widget->width_units;
			$token_values['zone'] = $profile_widget->zone;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($profile_widget, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=profile_widget&id=%d-%s",$profile_widget->id, DevblocksPlatform::strToPermalink($profile_widget->name)), true);
		}
		
		// Tab
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_TAB, null, $merge_token_labels, $merge_token_values, '', true);
		
			CerberusContexts::merge(
				'profile_tab_',
				$prefix.'Tab:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_ProfileWidget::ID,
			'extension_id' => DAO_ProfileWidget::EXTENSION_ID,
			'links' => '_links',
			'name' => DAO_ProfileWidget::NAME,
			'pos' => DAO_ProfileWidget::POS,
			'profile_tab_id' => DAO_ProfileWidget::PROFILE_TAB_ID,
			'updated_at' => DAO_ProfileWidget::UPDATED_AT,
			'width_units' => DAO_ProfileWidget::WIDTH_UNITS,
			'zone' => DAO_ProfileWidget::ZONE,
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
		
		$keys['extension_id']['notes'] = "[Profile Widget Type](/docs/plugins/extensions/points/cerb.profile.tab.widget/)";
		$keys['pos']['notes'] = "The order of the widget on the profile; `0` is first (top-left) proceeding in rows then columns";
		$keys['profile_tab_id']['notes'] = "The ID of the [profile tab](/docs/records/types/profile_tab/) dashboard containing this widget";
		$keys['width_units']['notes'] = "`1` (25%), `2` (50%), `3` (75%), `4` (100%)";
		$keys['zone']['notes'] = "The name of the dashboard zone containing the widget; this varies by layout; generally `sidebar` and `content`";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
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
				
				$out_fields[DAO_ProfileWidget::EXTENSION_PARAMS_JSON] = $json;
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
		
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
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
		$view->name = 'Profile Widget';
		/*
		$view->addParams(array(
			SearchFields_ProfileWidget::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ProfileWidget::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ProfileWidget::UPDATED_AT;
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
		$view->name = 'Profile Widget';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ProfileWidget::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_PROFILE_WIDGET;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!($model = DAO_ProfileWidget::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_ProfileWidget::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$model = new Model_ProfileWidget();
			}
			
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					list($k,$v) = array_pad(explode(':', $token, 2), 2, null);
					
					if($v)
					switch($k) {
						case 'tab':
							$model->profile_tab_id = intval($v);
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
			
			// Widget extensions
			
			if(($profile_tab = $model->getProfileTab())) {
				$tpl->assign('profile_tab', $profile_tab);
				
				$widget_extensions = Extension_ProfileWidget::getByContext($profile_tab->context, false);
				$tpl->assign('widget_extensions', $widget_extensions);
				
				// Library
				
				if(empty($context_id)) {
					$profile_context_mft = $profile_tab->getContextExtension(false);
					$context_aliases = Extension_DevblocksContext::getAliasesForContext($profile_context_mft);
					$packages = DAO_PackageLibrary::getByPoint([
						'profile_widget:' . $context_aliases['uri'],
						'profile_widget',
						
					]);
					$tpl->assign('packages', $packages);
				}
			}
			
			// Placeholder menu
			
			if(isset($model) && $profile_tab) {
				$labels = $values = [];
				
				// Record dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext($profile_tab->context, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('record_', 'Record ', $merge_labels, $merge_values, $labels, $values);
				
				// Merge in the widget dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('widget_', 'Widget ', $merge_labels, $merge_values, $labels, $values);
				
				// Merge in the current worker dictionary
				$merge_labels = $merge_values = [];
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_labels, $merge_values, '', true);
				CerberusContexts::merge('current_worker_', 'Current worker ', $merge_labels, $merge_values, $labels, $values);
				
				$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
				$tpl->assign('placeholders', $placeholders);
			}
			
			// Template
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->assign('model', $model);
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
