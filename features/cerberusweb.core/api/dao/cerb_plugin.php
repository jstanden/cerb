<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/

class DAO_CerbPlugin extends Cerb_ORMHelper {
	const ID = 'id';
	const ENABLED = 'enabled';
	const NAME = 'name';
	const DESCRIPTION = 'description';
	const AUTHOR = 'author';
	const VERSION = 'version';
	const DIR = 'dir';
	const LINK = 'link';
	const MANIFEST_CACHE_JSON = 'manifest_cache_json';

// 	static function create($fields) {
// 		$db = DevblocksPlatform::getDatabaseService();
		
// 		$sql = "INSERT INTO cerb_plugin () VALUES ()";
// 		$db->Execute($sql);
// 		$id = $db->LastInsertId();
		
// 		self::update($id, $fields);
		
// 		return $id;
// 	}
	
// 	static function update($ids, $fields) {
// 		parent::_update($ids, 'cerb_plugin', $fields);
// 	}
	
// 	static function updateWhere($fields, $where) {
// 		parent::_updateWhere('cerb_plugin', $fields, $where);
// 	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CerbPlugin[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, enabled, name, description, author, version, dir, link, manifest_cache_json ".
			"FROM cerb_plugin ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CerbPlugin	 */
	static function get($id) {
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
		
		while($row = mysql_fetch_assoc($rs)) {
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
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
// 	static function delete($ids) {
// 		return true;
// 	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CerbPlugin::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);

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
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'cerb_plugin',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY cerb_plugin.id ' : '').
			$sort_sql;

		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = $row[SearchFields_CerbPlugin::ID];
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT cerb_plugin.id) " : "SELECT COUNT(cerb_plugin.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_CerbPlugin implements IDevblocksSearchFields {
	const ID = 'c_id';
	const ENABLED = 'c_enabled';
	const NAME = 'c_name';
	const DESCRIPTION = 'c_description';
	const AUTHOR = 'c_author';
	const VERSION = 'c_version';
	const DIR = 'c_dir';
	const LINK = 'c_link';
	const MANIFEST_CACHE_JSON = 'c_manifest_cache_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'cerb_plugin', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER),
			self::ENABLED => new DevblocksSearchField(self::ENABLED, 'cerb_plugin', 'enabled', $translate->_('common.enabled'), Model_CustomField::TYPE_CHECKBOX),
			self::NAME => new DevblocksSearchField(self::NAME, 'cerb_plugin', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'cerb_plugin', 'description', $translate->_('dao.cerb_plugin.description'), Model_CustomField::TYPE_MULTI_LINE),
			self::AUTHOR => new DevblocksSearchField(self::AUTHOR, 'cerb_plugin', 'author', $translate->_('dao.cerb_plugin.author'), Model_CustomField::TYPE_SINGLE_LINE),
			self::VERSION => new DevblocksSearchField(self::VERSION, 'cerb_plugin', 'version', $translate->_('dao.cerb_plugin.version'), null),
			self::DIR => new DevblocksSearchField(self::DIR, 'cerb_plugin', 'dir', null, null),
			self::LINK => new DevblocksSearchField(self::LINK, 'cerb_plugin', 'link', $translate->_('common.url'), Model_CustomField::TYPE_URL),
			self::MANIFEST_CACHE_JSON => new DevblocksSearchField(self::MANIFEST_CACHE_JSON, 'cerb_plugin', 'manifest_cache_json', null, null),
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

class View_CerbPlugin extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'cerb5_plugins';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;

		$this->name = $translate->_('Cerb6 Plugins');
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
		
		$this->addParamsHidden(array(
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
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CerbPlugin', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CerbPlugin::AUTHOR:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CerbPlugin', $column);
				break;
				
			case SearchFields_CerbPlugin::ENABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_CerbPlugin', $column);
				break;
		}
		
		return $counts;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/plugins/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CerbPlugin::ID:
			case SearchFields_CerbPlugin::NAME:
			case SearchFields_CerbPlugin::DESCRIPTION:
			case SearchFields_CerbPlugin::AUTHOR:
			case SearchFields_CerbPlugin::VERSION:
			case SearchFields_CerbPlugin::LINK:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_CerbPlugin::ENABLED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		$translate = DevblocksPlatform::getTranslationService();
		
		switch($field) {
			case SearchFields_CerbPlugin::ENABLED:
				$this->_renderCriteriaParamBoolean($param);
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
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
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
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_CerbPlugin::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_CerbPlugin::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CerbPlugin::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_CerbPlugin::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_CerbPlugin::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

