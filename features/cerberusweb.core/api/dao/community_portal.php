<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class DAO_CommunityTool extends Cerb_ORMHelper {
	const CODE = 'code';
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
	const _PARAMS_JSON = '_params_json';
	
	const _CACHE_ALL = 'dao_communitytool_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CODE)
			->string()
			->setMaxLength(8)
			;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(128)
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
			->setMaxLength(128)
			->setRequired(true)
			;
		$validation
			->addField(self::_PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->setMaxLength(32)
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
					
				if(strlen($string) > 32) {
					$error = "must be shorter than 32 characters.";
					return false;
				}
				
				return true;
			})
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
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	public static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::CODE]))
			$fields[self::CODE] = self::generateUniqueCode();
		
		if(!isset($fields[self::URI]))
			$fields[self::URI] = $fields[self::CODE];
		
		$sql = sprintf("INSERT INTO community_tool () ".
			"VALUES ()"
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}

	// [TODO] APIize?
	public static function generateUniqueCode($length=8) {
		$db = DevblocksPlatform::services()->database();
		
		// [JAS]: [TODO] Inf loop check
		do {
			$code = substr(md5(mt_rand(0,1000) . microtime()),0,$length);
			$exists = $db->GetOneMaster(sprintf("SELECT id FROM community_tool WHERE code = %s",$db->qstr($code)));
			
		} while(!empty($exists));
		
		return $code;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		@$params_json = $fields[self::_PARAMS_JSON];
		unset($fields[self::_PARAMS_JSON]);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_PORTAL;
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
			parent::_update($batch_ids, 'community_tool', $fields);
			
			if(false !== (@$params = json_decode($params_json, true))) {
				$portals = DAO_CommunityTool::getIds($ids);
				
				if(is_array($portals) && is_array($params))
				foreach($portals as $portal) {
					foreach($params as $k => $v) {
						DAO_CommunityToolProperty::set($portal->code, $k, $v);
					}
				}
			}
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.community_tool.update',
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
		parent::_updateWhere('community_tool', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_PORTAL;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, name, code, extension_id, updated_at, uri ".
			"FROM community_tool ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_CommunityTool::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_CommunityTool
	 */
	public static function get($id) {
		if(empty($id))
			return null;
		
		$portals = self::getAll();
		
		if(isset($portals[$id]))
			return $portals[$id];
		
		return NULL;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_CommunityTool[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 *
	 * @param string $code
	 * @return Model_CommunityTool
	 */
	public static function getByCode($code, $check_aliases=true) {
		if(empty($code))
			return NULL;
		
		$portals = DAO_CommunityTool::getAll();
		
		$codes = array_column(DevblocksPlatform::objectsToArrays($portals), 'code', 'id');
		
		if(false !== ($portal_id = array_search($code, $codes)))
			return $portals[$portal_id];
		
		if($check_aliases) {
			if(false != ($portal = DAO_CommunityTool::getByPath($code)))
				return $portal;
		}
		
		return NULL;
	}
	
	/**
	 *
	 * @param string $path
	 * @return Model_CommunityTool
	 */
	public static function getByPath($path) {
		if(empty($path))
			return NULL;
		
		$portals = DAO_CommunityTool::getAll();
		
		$paths = array_column(DevblocksPlatform::objectsToArrays($portals), 'uri', 'id');
		
		if(false !== ($portal_id = array_search($path, $paths)))
			return $portals[$portal_id];
		
		return NULL;
	}
	
	static private function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CommunityTool();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->code = $row['code'];
			$object->extension_id = $row['extension_id'];
			$object->updated_at = intval($row['updated_at']);
			$object->uri = $row['uri'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('community_tool');
	}
	
	public static function count() {
		$portals = self::getAll();
		return count($portals);
	}
	
	public static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return false;
		
		$ids_string = implode(',', $ids);
		
		// Nuke portals
		$sql = sprintf("DELETE FROM community_tool WHERE id IN (%s)", $ids_string);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Nuke portal config
		$sql = "DELETE FROM community_tool_property WHERE tool_code NOT IN (SELECT code FROM community_tool)";
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_PORTAL,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CommunityTool::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CommunityTool', $sortBy);
					
		$select_sql = sprintf("SELECT ".
			"ct.id as %s, ".
			"ct.name as %s, ".
			"ct.code as %s, ".
			"ct.extension_id as %s, ".
			"ct.updated_at as %s, ".
			"ct.uri as %s ",
				SearchFields_CommunityTool::ID,
				SearchFields_CommunityTool::NAME,
				SearchFields_CommunityTool::CODE,
				SearchFields_CommunityTool::EXTENSION_ID,
				SearchFields_CommunityTool::UPDATED_AT,
				SearchFields_CommunityTool::URI
			);
		
		$join_sql = "FROM community_tool ct ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CommunityTool');
		
		$result = array(
			'primary_table' => 'ct',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 *
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
			SearchFields_CommunityTool::ID,
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

class SearchFields_CommunityTool extends DevblocksSearchFields {
	// Table
	const ID = 'ct_id';
	const NAME = 'ct_name';
	const CODE = 'ct_code';
	const EXTENSION_ID = 'ct_extension_id';
	const UPDATED_AT = 'ct_updated_at';
	const URI = 'ct_uri';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'ct.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_PORTAL => new DevblocksSearchFieldContextKeys('ct.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_PORTAL, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_PORTAL)), self::getPrimaryKey());
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
			case 'extension':
				$key = 'type';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_CommunityTool::EXTENSION_ID:
				return parent::_getLabelsForKeyExtensionValues(Extension_CommunityPortal::ID);
				break;
				
			case SearchFields_CommunityTool::ID:
				$models = DAO_CommunityTool::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'ct', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'ct', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CODE => new DevblocksSearchField(self::CODE, 'ct', 'code', $translate->_('community_portal.code'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'ct', 'extension_id', $translate->_('common.extension'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'ct', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'ct', 'uri', $translate->_('common.path'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
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

class DAO_CommunityToolProperty extends Cerb_ORMHelper {
	const PROPERTY_KEY = 'property_key';
	const PROPERTY_VALUE = 'property_value';
	const TOOL_CODE = 'tool_code';
	
	const _CACHE_PREFIX = 'um_comtoolprops_';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::PROPERTY_KEY)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::PROPERTY_VALUE)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::TOOL_CODE)
			->string()
			->setMaxLength(8)
			->setRequired(true)
			;
		
		return $validation->getFields();
	}
	
	static function getAllByTool($tool_code) {
		$cache = DevblocksPlatform::services()->cache();

		if(null == ($props = $cache->load(self::_CACHE_PREFIX.$tool_code))) {
			$props = array();
			
			$db = DevblocksPlatform::services()->database();
			
			$sql = sprintf("SELECT property_key, property_value ".
				"FROM community_tool_property ".
				"WHERE tool_code = %s ",
				$db->qstr($tool_code)
			);
			
			if(false === ($rs = $db->QueryReader($sql)))
				return false;
			
			$props = array();
			
			if(!($rs instanceof mysqli_result))
				return false;
			
			while($row = mysqli_fetch_assoc($rs)) {
				$k = $row['property_key'];
				$v = $row['property_value'];
				$props[$k] = $v;
			}
			
			mysqli_free_result($rs);
			
			$cache->save($props, self::_CACHE_PREFIX.$tool_code);
		}
		
		return $props;
	}
	
	static function getJson($tool_code, $key, $default=null) {
		return self::get($tool_code, $key, $default, true);
	}
	
	static function get($tool_code, $key, $default=null, $json_decode=false) {
		$props = self::getAllByTool($tool_code);
		@$val = $props[$key];
		
		$val = (is_null($val) || (!is_numeric($val) && empty($val))) ? $default : $val;
		
		if($json_decode)
			$val = @json_decode($val, true);
		
		if(false === $val)
			$val = $default;
		
		return $val;
	}
	
	static function setJson($tool_code, $key, $value) {
		self::set($tool_code, $key, $value, true);
	}
	
	static function set($tool_code, $key, $value, $json_encode=false) {
		$db = DevblocksPlatform::services()->database();
		
		if($json_encode)
			$value = json_encode($value);
		
		$db->ExecuteMaster(sprintf("REPLACE INTO community_tool_property (tool_code, property_key, property_value) ".
			"VALUES (%s, %s, %s)",
			$db->qstr($tool_code),
			$db->qstr($key),
			$db->qstr($value)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_PREFIX.$tool_code);
	}
};

class Model_CommunityTool {
	public $id = 0;
	public $name = '';
	public $code = '';
	public $extension_id = '';
	public $updated_at = 0;
	public $uri = '';
	
	/**
	 * @return DevblocksExtensionManifest|null
	 */
	function getExtension() {
		return Extension_CommunityPortal::get($this->extension_id);
	}
	
	function getParam($param_key, $default=null, $json_decode=false) {
		return DAO_CommunityToolProperty::get($this->code, $param_key, $default, $json_decode);
	}
};

class View_CommunityPortal extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'community_portals';
	const DEFAULT_TITLE = 'Community Portals';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CommunityTool::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CommunityTool::NAME,
			SearchFields_CommunityTool::EXTENSION_ID,
			SearchFields_CommunityTool::URI,
			SearchFields_CommunityTool::CODE,
			SearchFields_CommunityTool::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_CommunityTool::VIRTUAL_CONTEXT_LINK,
			SearchFields_CommunityTool::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->addParamsDefault(array(
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_CommunityTool::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CommunityTool');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CommunityTool', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CommunityTool', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_CommunityTool::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CommunityTool::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_PORTAL;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
//			case SearchFields_CommunityTool::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_CommunityTool::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_CommunityTool::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_CommunityTool::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_CommunityTool::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'code' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::CODE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CommunityTool::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_PORTAL],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CommunityTool::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_PORTAL, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'path' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::EXTENSION_ID),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CommunityTool::UPDATED_AT),
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

		// Tool Manifests
		$tools = DevblocksPlatform::getExtensions('cerb.portal', false);
		$tpl->assign('tool_extensions', $tools);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PORTAL);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/community_portal/view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CommunityTool::EXTENSION_ID:
				$label_map = SearchFields_CommunityTool::getLabelsForKeyValues($field, $values);
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
			case SearchFields_CommunityTool::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CommunityTool::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CommunityTool::CODE:
			case SearchFields_CommunityTool::NAME:
			case SearchFields_CommunityTool::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CommunityTool::EXTENSION_ID:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_CommunityTool::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
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

class Context_CommunityTool extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.portal';
	
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
	
	function getViewClass() {
		return 'View_CommunityPortal';
	}
	
	function getRandom() {
		return DAO_CommunityTool::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=community_portal&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_CommunityTool */
		
		if(is_null($model))
			$model = new Model_CommunityTool();
		
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
		
		$properties['extension'] = array(
			'label' => mb_ucfirst($translate->_('common.extension')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => @$model->getExtension()->manifest->name,
		);
		
		$properties['path'] = array(
			'label' => mb_ucfirst($translate->_('common.path')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		);
		
		$properties['code'] = array(
			'label' => mb_ucfirst($translate->_('community_portal.code')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->code,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$community_tool = DAO_CommunityTool::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($community_tool->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $community_tool->id,
			'name' => $community_tool->name,
			'permalink' => $url,
			'updated' => $community_tool->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'extension_id',
			'code',
			'uri',
			'updated_at',
		);
	}
	
	function getContext($community_tool, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Community Portal:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.portal');

		// Polymorph
		if(is_numeric($community_tool)) {
			$community_tool = DAO_CommunityTool::get($community_tool);
		} elseif($community_tool instanceof Model_CommunityTool) {
			// It's what we want already.
		} elseif(is_array($community_tool)) {
			$community_tool = Cerb_ORMHelper::recastArrayToModel($community_tool, 'Model_CommunityTool');
		} else {
			$community_tool = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'code' => $prefix.$translate->_('community_portal.code'),
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.path'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'code' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_PORTAL;
		$token_values['_types'] = $token_types;
		
		if($community_tool) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $community_tool->name;
			$token_values['code'] = $community_tool->code;
			$token_values['extension_id'] = $community_tool->extension_id;
			$token_values['id'] = $community_tool->id;
			$token_values['name'] = $community_tool->name;
			$token_values['uri'] = $community_tool->uri;
			$token_values['updated_at'] = $community_tool->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($community_tool, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=community_portal&id=%d-%s",$community_tool->id, DevblocksPlatform::strToPermalink($community_tool->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'code' => DAO_CommunityTool::CODE,
			'extension_id' => DAO_CommunityTool::EXTENSION_ID,
			'id' => DAO_CommunityTool::ID,
			'links' => '_links',
			'name' => DAO_CommunityTool::NAME,
			'params' => DAO_CommunityTool::_PARAMS_JSON,
			'uri' => DAO_CommunityTool::URI,
			'updated_at' => DAO_CommunityTool::UPDATED_AT,
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
		
		$keys['code']['notes'] = 'Randomized internal ID for the portal';
		$keys['extension_id']['notes'] = "[Community Portal Type](/docs/plugins/extensions/points/cerb.portal/)";
		$keys['uri']['notes'] = 'Human-friendly nickname for the portal. Must be unique.';
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_CommunityTool::_PARAMS_JSON] = $json;
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
		
		$context = CerberusContexts::CONTEXT_PORTAL;
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
		$view->name = 'Community Portal';
		/*
		$view->addParams(array(
			SearchFields_CommunityTool::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_CommunityTool::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_CommunityTool::UPDATED_AT;
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
		$view->name = 'Community Portal';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CommunityTool::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_PORTAL;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_CommunityTool::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_CommunityTool::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$tool_manifests = DevblocksPlatform::getExtensions('cerb.portal', false);
			$tpl->assign('tool_manifests', $tool_manifests);

			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/community_portal/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
