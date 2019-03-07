<?php
class DAO_PackageLibrary extends Cerb_ORMHelper {
	const DESCRIPTION = 'description';
	const ID = 'id';
	const NAME = 'name';
	const INSTRUCTIONS = 'instructions';
	const PACKAGE_JSON = 'package_json';
	const POINT = 'point';
	const URI = 'uri';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::DESCRIPTION)
			->string()
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
			->addField(self::INSTRUCTIONS)
			->string()
			->setMaxLength('16 bits')
			;
		
		$validation
			->addField(self::PACKAGE_JSON)
			->string()
			->setMaxLength('24 bits')
			->setRequired(true)
			;
		
		$validation
			->addField(self::POINT)
			->string()
			->setRequired(true)
			;
		
		$validation
			->addField(self::URI)
			->string()
			->setRequired(true)
			->setUnique(get_class())
			->addFormatter(function(&$value, &$error=null) {
				$value = DevblocksPlatform::strLower($value);
				return true;
			})
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '_'))) {
					$error = "may only contain lowercase letters, numbers, and underscores";
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
		
		$sql = "INSERT INTO package_library () VALUES ()";
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
			
		$context = CerberusContexts::CONTEXT_PACKAGE;
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
			parent::_update($batch_ids, 'package_library', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.package_library.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('package_library', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_PACKAGE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_PackageLibrary[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, uri, name, description, point, updated_at ".
			"FROM package_library ".
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
	 * @return Model_PackageLibrary[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_PackageLibrary::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_PackageLibrary	 */
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
	 * @param string $point
	 * @return Model_PackageLibrary[]
	 */
	static function getByPoint($points) {
		if(empty($points))
			return [];
		
		// [TODO] Use cache
		
		if(!is_array($points))
			$points = [$points];
		
		$results = array_fill_keys($points, []);
		
		$packages = self::getWhere(
			sprintf("%s IN (%s)",
				Cerb_ORMHelper::escape(self::POINT),
				implode(',', Cerb_ORMHelper::qstrArray($points))
			),
			DAO_PackageLibrary::NAME,
			true,
			0
		);
		
		if(is_array($packages))
		foreach($packages as $package) {
			$results[$package->point][] = $package;
		}
		
		unset($packages);
		
		// Remove points with no packages
		$results = array_filter($results, function($packages) {
			if(!empty($packages))
				return true;
			return false;
		});
		
		return $results;
	}
	
	/**
	 * 
	 * @param string $uri
	 * @return Model_PackageLibrary|NULL
	 */
	static function getByUri($uri) {
		if(empty($uri))
			return null;
		
		// [TODO] Use cache
		
		$results = self::getWhere(
			sprintf("%s = %s",
				Cerb_ORMHelper::escape(self::URI),
				Cerb_ORMHelper::qstr($uri)
			),
			null,
			true,
			1
		);
		
		if(empty($results))
			return null;
		
		return array_shift($results);
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_PackageLibrary[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_PackageLibrary[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_PackageLibrary();
			$object->id = $row['id'];
			$object->uri = $row['uri'];
			$object->name = $row['name'];
			$object->description = $row['description'];
			$object->point = $row['point'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('package_library');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM package_library WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_PACKAGE,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_PackageLibrary::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_PackageLibrary', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"package_library.id as %s, ".
			"package_library.uri as %s, ".
			"package_library.name as %s, ".
			"package_library.description as %s, ".
			"package_library.point as %s, ".
			"package_library.updated_at as %s ",
				SearchFields_PackageLibrary::ID,
				SearchFields_PackageLibrary::URI,
				SearchFields_PackageLibrary::NAME,
				SearchFields_PackageLibrary::DESCRIPTION,
				SearchFields_PackageLibrary::POINT,
				SearchFields_PackageLibrary::UPDATED_AT
			);
			
		$join_sql = "FROM package_library ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_PackageLibrary');
	
		return array(
			'primary_table' => 'package_library',
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
			$object_id = intval($row[SearchFields_PackageLibrary::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(package_library.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_PackageLibrary extends DevblocksSearchFields {
	const ID = 'p_id';
	const URI = 'p_uri';
	const NAME = 'p_name';
	const DESCRIPTION = 'p_description';
	const POINT = 'p_point';
	const UPDATED_AT = 'p_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'package_library.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_PACKAGE => new DevblocksSearchFieldContextKeys('package_library.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_PACKAGE, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_PACKAGE)), self::getPrimaryKey());
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
			case SearchFields_PackageLibrary::ID:
				$models = DAO_PackageLibrary::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'package_library', 'id', $translate->_('common.id'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'package_library', 'uri', $translate->_('common.uri'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'package_library', 'name', $translate->_('common.name'), null, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'package_library', 'description', $translate->_('common.description'), null, true),
			self::POINT => new DevblocksSearchField(self::POINT, 'package_library', 'point', $translate->_('common.extension.point'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'package_library', 'updated_at', $translate->_('common.updated'), null, true),

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

class Model_PackageLibrary {
	public $id;
	public $uri;
	public $name;
	public $description;
	public $point;
	public $updated_at;
	
	function getInstructions() {
		$db = DevblocksPlatform::services()->database();
		
		return $db->GetOneSlave(sprintf("SELECT instructions FROM package_library WHERE id = %d",
			$this->id
		));
	}
	
	function getInstructionsAsHtml() {
		$html = DevblocksPlatform::parseMarkdown($this->getInstructions());
		return DevblocksPlatform::purifyHTML($html, true, true);
	}
	
	function getPackageJson() {
		$db = DevblocksPlatform::services()->database();
		
		return $db->GetOneSlave(sprintf("SELECT package_json FROM package_library WHERE id = %d",
			$this->id
		));
	}
	
	function getPrompts() {
		return CerberusApplication::packages()->prompts($this->getPackageJson());
	}
};

class View_PackageLibrary extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'package_library';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.packages');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_PackageLibrary::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_PackageLibrary::NAME,
			SearchFields_PackageLibrary::DESCRIPTION,
			SearchFields_PackageLibrary::POINT,
			SearchFields_PackageLibrary::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK,
			SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_PackageLibrary::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_PackageLibrary');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_PackageLibrary', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_PackageLibrary', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_PackageLibrary::POINT:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK:
				case SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_PACKAGE;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_PackageLibrary::POINT:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_PackageLibrary::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PackageLibrary::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'description' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PackageLibrary::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_PACKAGE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_PackageLibrary::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PACKAGE, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PackageLibrary::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'point' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PackageLibrary::POINT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_PackageLibrary::UPDATED_AT),
				),
			'uri' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PackageLibrary::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_PACKAGE, $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PACKAGE);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/package_library/view.tpl');
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
			case SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_PackageLibrary::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_PackageLibrary::URI:
			case SearchFields_PackageLibrary::NAME:
			case SearchFields_PackageLibrary::POINT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_PackageLibrary::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_PackageLibrary::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_PackageLibrary::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
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

class Context_PackageLibrary extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_PACKAGE;
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can modify
		return CerberusContexts::isActorAnAdmin($actor);
	}

	function getRandom() {
		return DAO_PackageLibrary::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=package&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_PackageLibrary();
		
		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['description'] = [
			'label' => mb_ucfirst($translate->_('common.description')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->description,
		];
		
		$properties['point'] = [
			'label' => mb_ucfirst($translate->_('common.extension.point')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->point,
		];
		
		$properties['uri'] = [
			'label' => $translate->_('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$package_library = DAO_PackageLibrary::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($package_library->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $package_library->id,
			'name' => $package_library->name,
			'permalink' => $url,
			'updated' => $package_library->updated_at,
		];
	}
	
	function getDefaultProperties() {
		return [
			'description',
			'uri',
			'point',
			'updated_at',
		];
	}
	
	function getContext($package_library, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Package Library:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PACKAGE);

		// Polymorph
		if(is_numeric($package_library)) {
			$package_library = DAO_PackageLibrary::get($package_library);
		} elseif($package_library instanceof Model_PackageLibrary) {
			// It's what we want already.
		} elseif(is_array($package_library)) {
			$package_library = Cerb_ORMHelper::recastArrayToModel($package_library, 'Model_PackageLibrary');
		} else {
			$package_library = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'description' => $prefix.$translate->_('common.description'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'point' => $prefix.$translate->_('common.extension.point'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'point' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_PACKAGE;
		$token_values['_types'] = $token_types;
		
		if($package_library) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $package_library->name;
			$token_values['description'] = $package_library->description;
			$token_values['id'] = $package_library->id;
			$token_values['name'] = $package_library->name;
			$token_values['point'] = $package_library->point;
			$token_values['updated_at'] = $package_library->updated_at;
			$token_values['uri'] = $package_library->uri;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($package_library, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=package&id=%d-%s",$package_library->id, DevblocksPlatform::strToPermalink($package_library->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_PackageLibrary::ID,
			'description' => DAO_PackageLibrary::DESCRIPTION,
			'links' => '_links',
			'name' => DAO_PackageLibrary::NAME,
			'point' => DAO_PackageLibrary::POINT,
			'updated_at' => DAO_PackageLibrary::UPDATED_AT,
			'uri' => DAO_PackageLibrary::URI,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['params'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		$keys['description']['notes'] = "A description of this library package's contents";
		$keys['point']['notes'] = "The library section containing this package";
		$keys['uri']['notes'] = "The unique identifier of this package";
		
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
		
		$context = CerberusContexts::CONTEXT_PACKAGE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
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
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Package Library';
		/*
		$view->addParams(array(
			SearchFields_PackageLibrary::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_PackageLibrary::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_PackageLibrary::UPDATED_AT;
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
		$view->name = 'Package Library';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_PackageLibrary::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_PACKAGE;
		
		if(!empty($context_id)) {
			$model = DAO_PackageLibrary::get($context_id);
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
			$tpl->display('devblocks:cerberusweb.core::internal/package_library/peek_edit.tpl');
			
		} else {
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							[]
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
			
			// Dictionary
			$labels = [];
			$values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Card search buttons
			$search_buttons = $context_ext->getCardSearchButtons($dict, []);
			$tpl->assign('search_buttons', $search_buttons);
			
			$tpl->display('devblocks:cerberusweb.core::internal/package_library/peek.tpl');
		}
	}
};
