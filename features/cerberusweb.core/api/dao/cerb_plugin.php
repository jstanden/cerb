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

class DAO_CerbPlugin extends Cerb_ORMHelper {
	const AUTHOR = 'author';
	const DESCRIPTION = 'description';
	const DIR = 'dir';
	const ENABLED = 'enabled';
	const ID = 'id';
	const LINK = 'link';
	const MANIFEST_CACHE_JSON = 'manifest_cache_json';
	const NAME = 'name';
	const VERSION = 'version';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::AUTHOR)
			->string()
			->setMaxLength(64)
			;
		$validation
			->addField(self::DESCRIPTION)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::DIR)
			->string()
			;
		$validation
			->addField(self::ENABLED)
			->bit()
			;
		$validation
			->addField(self::ID)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::LINK)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::MANIFEST_CACHE_JSON)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::NAME)
			->string()
			;
		$validation
			->addField(self::VERSION)
			->uint()
			;
		
		return $validation->getFields();
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CerbPlugin[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, enabled, name, description, author, version, dir, link, manifest_cache_json ".
			"FROM cerb_plugin ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CerbPlugin	 */
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
	 * @return Model_CerbPlugin[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CerbPlugin();
			$object->id = $row['id'];
			$object->enabled = $row['enabled'];
			$object->name = $row['name'];
			$object->description = $row['description'];
			$object->author = $row['author'];
			$object->version = $row['version'];
			$object->dir = $row['dir'];
			$object->link = $row['link'];
			$object->manifest_cache_json = $row['manifest_cache_json'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
// 	static function delete($ids) {
// 		return true;
// 	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CerbPlugin::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CerbPlugin', $sortBy);

		$select_sql = sprintf("SELECT ".
			"cerb_plugin.id as %s, ".
			"cerb_plugin.enabled as %s, ".
			"cerb_plugin.name as %s, ".
			"cerb_plugin.description as %s, ".
			"cerb_plugin.author as %s, ".
			"cerb_plugin.version as %s, ".
			"cerb_plugin.dir as %s, ".
			"cerb_plugin.link as %s, ".
			"cerb_plugin.manifest_cache_json as %s ",
				SearchFields_CerbPlugin::ID,
				SearchFields_CerbPlugin::ENABLED,
				SearchFields_CerbPlugin::NAME,
				SearchFields_CerbPlugin::DESCRIPTION,
				SearchFields_CerbPlugin::AUTHOR,
				SearchFields_CerbPlugin::VERSION,
				SearchFields_CerbPlugin::DIR,
				SearchFields_CerbPlugin::LINK,
				SearchFields_CerbPlugin::MANIFEST_CACHE_JSON
			);
			
		$join_sql = "FROM cerb_plugin ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CerbPlugin');
	
		return array(
			'primary_table' => 'cerb_plugin',
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
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = $row[SearchFields_CerbPlugin::ID];
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(cerb_plugin.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_CerbPlugin extends DevblocksSearchFields {
	const ID = 'c_id';
	const ENABLED = 'c_enabled';
	const NAME = 'c_name';
	const DESCRIPTION = 'c_description';
	const AUTHOR = 'c_author';
	const VERSION = 'c_version';
	const DIR = 'c_dir';
	const LINK = 'c_link';
	const MANIFEST_CACHE_JSON = 'c_manifest_cache_json';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'cerb_plugin.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('cerb_plugin.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_CerbPlugin::ID:
				$models = DAO_CerbPlugin::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'cerb_plugin', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::ENABLED => new DevblocksSearchField(self::ENABLED, 'cerb_plugin', 'enabled', $translate->_('common.enabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'cerb_plugin', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'cerb_plugin', 'description', $translate->_('dao.cerb_plugin.description'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::AUTHOR => new DevblocksSearchField(self::AUTHOR, 'cerb_plugin', 'author', $translate->_('common.author'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::VERSION => new DevblocksSearchField(self::VERSION, 'cerb_plugin', 'version', $translate->_('dao.cerb_plugin.version'), null, true),
			self::DIR => new DevblocksSearchField(self::DIR, 'cerb_plugin', 'dir', null, null, true),
			self::LINK => new DevblocksSearchField(self::LINK, 'cerb_plugin', 'link', $translate->_('common.url'), Model_CustomField::TYPE_URL, true),
			self::MANIFEST_CACHE_JSON => new DevblocksSearchField(self::MANIFEST_CACHE_JSON, 'cerb_plugin', 'manifest_cache_json', null, null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_CerbPlugin {
	public $id;
	public $enabled;
	public $name;
	public $description;
	public $author;
	public $version;
	public $dir;
	public $link;
	public $manifest_cache_json;
};

class View_CerbPlugin extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'cerb_plugins';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;

		$this->name = $translate->_('Cerb Plugins');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_CerbPlugin::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CerbPlugin::AUTHOR,
			SearchFields_CerbPlugin::VERSION,
		);

		$this->addColumnsHidden(array(
			SearchFields_CerbPlugin::DIR,
			SearchFields_CerbPlugin::MANIFEST_CACHE_JSON,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CerbPlugin::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CerbPlugin');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CerbPlugin', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_CerbPlugin::AUTHOR:
				case SearchFields_CerbPlugin::ENABLED:
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
		$context = null;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CerbPlugin::AUTHOR:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_CerbPlugin::ENABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_CerbPlugin::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'author' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::AUTHOR, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'desc' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'enabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_CerbPlugin::ENABLED),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::ID),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'url' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CerbPlugin::LINK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'version' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CerbPlugin::VERSION),
					'examples' => array(
						'<=1.0',
						'2.0',
					),
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
			
			case 'version':
				foreach($tokens as &$token) {
					switch($token->type) {
						case 'T_QUOTED_TEXT':
						case 'T_TEXT':
							$v = $token->value;
							
							if(preg_match('#^([\!\=\>\<]+)(.*)#', $v, $matches)) {
								$oper_hint = trim($matches[1]);
								$v = trim($matches[2]);
								$v = $oper_hint . DevblocksPlatform::strVersionToInt($v, 3);
								
							} else if(preg_match('#^(.*)?\.\.\.(.*)#', $v, $matches)) {
								$from = DevblocksPlatform::strVersionToInt(trim($matches[1]), 3);
								$to = DevblocksPlatform::strVersionToInt(trim($matches[2]), 3);
								$v = sprintf("%d...%d", $from, $to);
							} else {
								$v = DevblocksPlatform::strVersionToInt($v, 3);
							}
							
							$token->value = $v;
							break;
					}
				}
				
				$param = DevblocksSearchCriteria::getNumberParamFromTokens('version', $tokens);
				$param->field = SearchFields_CerbPlugin::VERSION;
				return $param;
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

		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/plugins/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		$translate = DevblocksPlatform::getTranslationService();
		
		switch($field) {
			case SearchFields_CerbPlugin::ENABLED:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_CerbPlugin::VERSION:
				if(is_array($param->value)) {
					$sep = ' or ';
					$strings = array();
					
					if($param->operator == DevblocksSearchCriteria::OPER_BETWEEN)
						$sep = ' and ';
					
					foreach($param->value as $value)
						$strings[] = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::intVersionToStr($value));
					
					echo implode($sep, $strings);
					
				} else {
					echo DevblocksPlatform::strEscapeHtml(DevblocksPlatform::intVersionToStr($param->value));
				}
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CerbPlugin::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CerbPlugin::ID:
			case SearchFields_CerbPlugin::NAME:
			case SearchFields_CerbPlugin::DESCRIPTION:
			case SearchFields_CerbPlugin::AUTHOR:
			case SearchFields_CerbPlugin::VERSION:
			case SearchFields_CerbPlugin::LINK:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CerbPlugin::ENABLED:
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};