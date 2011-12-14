<?php
class DAO_PluginLibrary extends C4_ORMHelper {
	const ID = 'id';
	const PLUGIN_ID = 'plugin_id';
	const NAME = 'name';
	const AUTHOR = 'author';
	const DESCRIPTION = 'description';
	const LINK = 'link';
	const LATEST_VERSION = 'latest_version';
	const ICON_URL = 'icon_url';
	const REQUIREMENTS_JSON = 'requirements_json';
	const UPDATED = 'updated';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO plugin_library () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'plugin_library', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('plugin_library', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_PluginLibrary[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, plugin_id, name, author, description, link, latest_version, icon_url, requirements_json, updated ".
			"FROM plugin_library ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_PluginLibrary	 */
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
	 * @return Model_PluginLibrary[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_PluginLibrary();
			$object->id = $row['id'];
			$object->plugin_id = $row['plugin_id'];
			$object->name = $row['name'];
			$object->author = $row['author'];
			$object->description = $row['description'];
			$object->link = $row['link'];
			$object->latest_version = $row['latest_version'];
			$object->icon_url = $row['icon_url'];
			$object->updated = $row['updated'];
			
			$object->requirements_json = $row['requirements_json'];
			if(!empty($object->requirements_json))
				@$object->requirements = json_decode($object->requirements_json, true);
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function flush() {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute("DELETE FROM plugin_library");
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM plugin_library WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => 'cerberusweb.contexts.',
                	'context_ids' => $ids
                )
            )
	    );
	    */
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_PluginLibrary::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"plugin_library.id as %s, ".
			"plugin_library.plugin_id as %s, ".
			"plugin_library.name as %s, ".
			"plugin_library.author as %s, ".
			"plugin_library.description as %s, ".
			"plugin_library.link as %s, ".
			"plugin_library.latest_version as %s, ".
			"plugin_library.icon_url as %s, ".
			"plugin_library.requirements_json as %s, ".
			"plugin_library.updated as %s ",
				SearchFields_PluginLibrary::ID,
				SearchFields_PluginLibrary::PLUGIN_ID,
				SearchFields_PluginLibrary::NAME,
				SearchFields_PluginLibrary::AUTHOR,
				SearchFields_PluginLibrary::DESCRIPTION,
				SearchFields_PluginLibrary::LINK,
				SearchFields_PluginLibrary::LATEST_VERSION,
				SearchFields_PluginLibrary::ICON_URL,
				SearchFields_PluginLibrary::REQUIREMENTS_JSON,
				SearchFields_PluginLibrary::UPDATED
			);
			
		$join_sql = "FROM plugin_library ";
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'plugin_library',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
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
			($has_multiple_values ? 'GROUP BY plugin_library.id ' : '').
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
			$object_id = intval($row[SearchFields_PluginLibrary::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT plugin_library.id) " : "SELECT COUNT(plugin_library.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_PluginLibrary implements IDevblocksSearchFields {
	const ID = 'p_id';
	const PLUGIN_ID = 'p_plugin_id';
	const NAME = 'p_name';
	const AUTHOR = 'p_author';
	const DESCRIPTION = 'p_description';
	const LINK = 'p_link';
	const LATEST_VERSION = 'p_latest_version';
	const ICON_URL = 'p_icon_url';
	const REQUIREMENTS_JSON = 'p_requirements_json';
	const UPDATED = 'p_updated';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'plugin_library', 'id', $translate->_('common.id')),
			self::PLUGIN_ID => new DevblocksSearchField(self::PLUGIN_ID, 'plugin_library', 'plugin_id', $translate->_('dao.plugin_library.plugin_id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'plugin_library', 'name', $translate->_('common.name')),
			self::AUTHOR => new DevblocksSearchField(self::AUTHOR, 'plugin_library', 'author', $translate->_('dao.cerb_plugin.author')),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'plugin_library', 'description', $translate->_('dao.cerb_plugin.description')),
			self::LINK => new DevblocksSearchField(self::LINK, 'plugin_library', 'link', $translate->_('common.url')),
			self::LATEST_VERSION => new DevblocksSearchField(self::LATEST_VERSION, 'plugin_library', 'latest_version', $translate->_('dao.cerb_plugin.version')),
			self::ICON_URL => new DevblocksSearchField(self::ICON_URL, 'plugin_library', 'icon_url', $translate->_('dao.plugin_library.icon_url')),
			self::REQUIREMENTS_JSON => new DevblocksSearchField(self::REQUIREMENTS_JSON, 'plugin_library', 'requirements_json', $translate->_('dao.plugin_library.requirements_json')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'plugin_library', 'updated', $translate->_('common.updated')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_PluginLibrary {
	public $id;
	public $plugin_id;
	public $name;
	public $author;
	public $description;
	public $link;
	public $latest_version;
	public $icon_url;
	public $updated;
	public $requirements_json;
	public $requirements;
	
	// [TODO] Move this somewhere reusable
	static function testRequirements($requirements) {
		$requirements_errors = array();
		
		// Check version information
		if(
			null != (@$plugin_app_version = $requirements['app_version'])
			&& isset($plugin_app_version['min'])
			&& isset($plugin_app_version['max'])
		) {
			$app_version = DevblocksPlatform::strVersionToInt(APP_VERSION);
			
			// If APP_VERSION is below the min or above the max
			if($plugin_app_version['min'] > $app_version)
				$requirements_errors[] = 'This plugin requires a Cerb5 version of at least ' . DevblocksPlatform::intVersionToStr($plugin_app_version['min']) . ' and you are using ' . APP_VERSION;
			
			if($plugin_app_version['max'] < $app_version)
				$requirements_errors[] = 'This plugin was tested through Cerb5 version ' . DevblocksPlatform::intVersionToStr($plugin_app_version['max']) . ' and you are using ' . APP_VERSION;
			
		// If no version information is available, fail.
		} else {
			$requirements_errors[] = 'This plugin is missing requirements information in its manifest';
		}
		
		// Check PHP extensions
		if(isset($requirements['php_extensions'])) 
		foreach($requirements['php_extensions'] as $php_extension) {
			if(!extension_loaded($php_extension))
				$requirements_errors[] = sprintf("The '%s' PHP extension is required", $php_extension);
		}
		
		// Check dependencies
		if(isset($requirements['dependencies'])) {
			$plugins = DevblocksPlatform::getPluginRegistry();
			foreach($requirements['dependencies'] as $dependency) {
				if(!isset($plugins[$dependency])) {
					$requirements_errors[] = sprintf("The '%s' plugin is required", $dependency);
				} else if(!$plugins[$dependency]->enabled) {
					$dependency_name = isset($plugins[$dependency]) ? $plugins[$dependency]->name : $dependency; 
					$requirements_errors[] = sprintf("The '%s' (%s) plugin must be enabled first", $dependency_name, $dependency);
				}
			}
		}
		
		// Status
		
		return $requirements_errors;
	}	
};

class View_PluginLibrary extends C4_AbstractView {
	const DEFAULT_ID = 'plugin_library';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Plugin Library');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_PluginLibrary::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_PluginLibrary::AUTHOR,
			SearchFields_PluginLibrary::LATEST_VERSION,
			SearchFields_PluginLibrary::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_PluginLibrary::ICON_URL,
			SearchFields_PluginLibrary::ID,
			SearchFields_PluginLibrary::REQUIREMENTS_JSON,
		));
		
		$this->addParamsHidden(array(
			SearchFields_PluginLibrary::ICON_URL,
			SearchFields_PluginLibrary::ID,
			SearchFields_PluginLibrary::REQUIREMENTS_JSON,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_PluginLibrary::search(
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
		return $this->_doGetDataSample('DAO_PluginLibrary', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);		
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugin_library/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_PluginLibrary::PLUGIN_ID:
			case SearchFields_PluginLibrary::NAME:
			case SearchFields_PluginLibrary::AUTHOR:
			case SearchFields_PluginLibrary::DESCRIPTION:
			case SearchFields_PluginLibrary::LINK:
			case SearchFields_PluginLibrary::LATEST_VERSION:
			case SearchFields_PluginLibrary::ICON_URL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_PluginLibrary::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_PluginLibrary::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_PluginLibrary::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_PluginLibrary::PLUGIN_ID:
			case SearchFields_PluginLibrary::NAME:
			case SearchFields_PluginLibrary::AUTHOR:
			case SearchFields_PluginLibrary::DESCRIPTION:
			case SearchFields_PluginLibrary::LINK:
			case SearchFields_PluginLibrary::LATEST_VERSION:
			case SearchFields_PluginLibrary::ICON_URL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_PluginLibrary::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_PluginLibrary::UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
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
					//$change_fields[DAO_PluginLibrary::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_PluginLibrary::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_PluginLibrary::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_PluginLibrary::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_PluginLibrary::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
