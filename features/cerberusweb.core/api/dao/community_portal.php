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

class DAO_CommunityTool extends Cerb_ORMHelper {
	const _CACHE_ALL = 'dao_communitytool_all';
	
	const ID = 'id';
	const NAME = 'name';
	const CODE = 'code';
	const EXTENSION_ID = 'extension_id';
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!isset($fields[self::CODE]))
			$fields[self::CODE] = self::generateUniqueCode();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
		// [JAS]: [TODO] Inf loop check
		do {
			$code = substr(md5(mt_rand(0,1000) * microtime()),0,$length);
			$exists = $db->GetOneMaster(sprintf("SELECT id FROM community_tool WHERE code = %s",$db->qstr($code)));
			
		} while(!empty($exists));
		
		return $code;
	}
	
	public static function update($id, $fields) {
		self::_update($id, 'community_tool', $fields);
		self::clearCache();
	}
	
	/**
	 * Enter description here...
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
	
	public static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_CommunityTool::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $code
	 * @return Model_CommunityTool
	 */
	public static function getByCode($code) {
		if(empty($code))
			return NULL;
		
		$portals = DAO_CommunityTool::getAll();
		
		foreach($portals as $portal) {
			if($portal->code == $code)
				return $portal;
		}
		
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_CommunityTool[]
	 */
	public static function getIds($ids=array()) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$portals = self::getAll();
		$ids = array_flip($ids);
		
		$portals = array_filter($portals, function($portal) use ($ids) {
			if(isset($ids[$portal->id]))
				return true;
			
			return false;
		});
		
		return $portals;
	}
	
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, name, code, extension_id ".
			"FROM community_tool ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_createObjectsFromResultSet($rs);
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
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	public static function count() {
		$portals = self::getAll();
		return count($portals);
	}
	
	public static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
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
		
		self::clearCache();
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CommunityTool::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CommunityTool', $sortBy);
					
		$select_sql = sprintf("SELECT ".
			"ct.id as %s, ".
			"ct.name as %s, ".
			"ct.code as %s, ".
			"ct.extension_id as %s ",
				SearchFields_CommunityTool::ID,
				SearchFields_CommunityTool::NAME,
				SearchFields_CommunityTool::CODE,
				SearchFields_CommunityTool::EXTENSION_ID
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
		$db = DevblocksPlatform::getDatabaseService();
	
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
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();

		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_CommunityTool::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(ct.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_CommunityTool extends DevblocksSearchFields {
	// Table
	const ID = 'ct_id';
	const NAME = 'ct_name';
	const CODE = 'ct_code';
	const EXTENSION_ID = 'ct_extension_id';
	
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
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
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
			SearchFields_CommunityTool::ID => new DevblocksSearchField(SearchFields_CommunityTool::ID, 'ct', 'id', $translate->_('common.id'), null, true),
			SearchFields_CommunityTool::NAME => new DevblocksSearchField(SearchFields_CommunityTool::NAME, 'ct', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_CommunityTool::CODE => new DevblocksSearchField(SearchFields_CommunityTool::CODE, 'ct', 'code', $translate->_('community_portal.code'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_CommunityTool::EXTENSION_ID => new DevblocksSearchField(SearchFields_CommunityTool::EXTENSION_ID, 'ct', 'extension_id', $translate->_('common.extension'), null, true),
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
	const TOOL_CODE = 'tool_code';
	const PROPERTY_KEY = 'property_key';
	const PROPERTY_VALUE = 'property_value';
	
	const _CACHE_PREFIX = 'um_comtoolprops_';
	
	static function getAllByTool($tool_code) {
		$cache = DevblocksPlatform::getCacheService();

		if(null == ($props = $cache->load(self::_CACHE_PREFIX.$tool_code))) {
			$props = array();
			
			$db = DevblocksPlatform::getDatabaseService();
			
			$sql = sprintf("SELECT property_key, property_value ".
				"FROM community_tool_property ".
				"WHERE tool_code = %s ",
				$db->qstr($tool_code)
			);
			
			if(false === ($rs = $db->ExecuteSlave($sql)))
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
	
	static function set($tool_code, $key, $value, $json_encode=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if($json_encode)
			$value = json_encode($value);
		
		$db->ExecuteMaster(sprintf("REPLACE INTO community_tool_property (tool_code, property_key, property_value) ".
			"VALUES (%s, %s, %s)",
			$db->qstr($tool_code),
			$db->qstr($key),
			$db->qstr($value)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_PREFIX.$tool_code);
	}
};

class DAO_CommunitySession extends Cerb_ORMHelper {
	const SESSION_ID = 'session_id';
	const CREATED = 'created';
	const UPDATED = 'updated';
	const CSRF_TOKEN = 'csrf_token';
	const PROPERTIES = 'properties';
	
	static public function save(Model_CommunitySession $session) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("UPDATE community_session SET updated = %d, properties = %s WHERE session_id = %s",
			time(),
			$db->qstr(serialize($session->getProperties())),
			$db->qstr($session->session_id)
		);
		$db->ExecuteMaster($sql);
	}
	
	/**
	 * @param string $session_id
	 * @return Model_CommunitySession
	 */
	static public function get($session_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT session_id, created, updated, csrf_token, properties ".
			"FROM community_session ".
			"WHERE session_id = %s",
			$db->qstr($session_id)
		);
		$row = $db->GetRowSlave($sql);
		
		if(empty($row)) {
			$session = self::create($session_id);
		} else {
			$session = new Model_CommunitySession();
			$session->session_id = $row['session_id'];
			$session->created = $row['created'];
			$session->updated = $row['updated'];
			$session->csrf_token = $row['csrf_token'];
			
			if(!empty($row['properties']))
				@$session->setProperties(unserialize($row['properties']));
		}
		
		return $session;
	}
	
	static public function delete($session_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM community_session WHERE session_id = %s",
			$db->qstr($session_id)
		);
		$db->ExecuteMaster($sql);
		
		return TRUE;
	}
	
	/**
	 * @param string $session_id
	 * @return Model_CommunitySession
	 */
	static private function create($session_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$session = new Model_CommunitySession();
		$session->session_id = $session_id;
		$session->created = time();
		$session->updated = time();
		$session->csrf_token = CerberusApplication::generatePassword(128);
		
		$sql = sprintf("INSERT INTO community_session (session_id, created, updated, csrf_token, properties) ".
			"VALUES (%s, %d, %d, %s, '')",
			$db->qstr($session->session_id),
			$session->created,
			$session->updated,
			$db->qstr($session->csrf_token)
		);
		$db->ExecuteMaster($sql);
		
		self::gc(); // garbage collection
		
		return $session;
	}
	
	static private function gc() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE FROM community_session WHERE updated < %d",
			(time()-(60*60)) // 1 hr
		);
		$db->ExecuteMaster($sql);
	}
};

class Model_CommunityTool {
	public $id = 0;
	public $name = '';
	public $code = '';
	public $extension_id = '';
};

class Model_CommunitySession {
	public $session_id = '';
	public $created = 0;
	public $updated = 0;
	public $csrf_token = '';
	private $_properties = array();

	function login(Model_Contact $contact) {
		if(empty($contact) || empty($contact->id)) {
			$this->logout();
			return;
		}
		
		$this->setProperty('sc_login', $contact);
		
		DAO_Contact::update($contact->id, array(
			DAO_Contact::LAST_LOGIN_AT => time(),
		));
	}
	
	function logout() {
		$this->setProperty('sc_login', null);
	}
	
	function setProperties($properties) {
		$this->_properties = $properties;
	}
	
	function getProperties() {
		return $this->_properties;
	}
	
	function setProperty($key, $value) {
		if(null==$value) {
			unset($this->_properties[$key]);
		} else {
			$this->_properties[$key] = $value;
		}
		DAO_CommunitySession::save($this);
	}
	
	function getProperty($key, $default = null) {
		return isset($this->_properties[$key]) ? $this->_properties[$key] : $default;
	}
	
	function destroy() {
		$this->_properties = array();
		DAO_CommunitySession::delete($this->session_id);
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
			SearchFields_CommunityTool::CODE,
			SearchFields_CommunityTool::EXTENSION_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_CommunityTool::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CommunityTool::ID,
		));
		$this->addParamsDefault(array(
			//SearchFields_CommunityTool::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_CommunityTool::IS_DISABLED,'=',0),
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CommunityTool::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CommunityTool');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CommunityTool', $size);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_CommunityTool::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'code' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CommunityTool::CODE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
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
			default:
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Tool Manifests
		$tools = DevblocksPlatform::getExtensions('usermeet.tool', false);
		$tpl->assign('tool_extensions', $tools);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_PORTAL);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portals/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_CommunityTool::NAME:
			case SearchFields_CommunityTool::CODE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CommunityTool::EXTENSION_ID:
				$options = array();
				$portals = DevblocksPlatform::getExtensions('usermeet.tool', false);

				foreach($portals as $ext_id => $ext) {
					$options[$ext_id] = $ext->name;
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
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
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CommunityTool::EXTENSION_ID:
				$portals = DevblocksPlatform::getExtensions('usermeet.tool', false);
				$strings = array();
				
				foreach($values as $val) {
					if(!isset($portals[$val]))
						continue;
					else
						$strings[] = DevblocksPlatform::strEscapeHtml($portals[$val]->name);
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CommunityTool::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CommunityTool::NAME:
			case SearchFields_CommunityTool::CODE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CommunityTool::EXTENSION_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
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