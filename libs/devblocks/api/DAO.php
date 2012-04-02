<?php
abstract class DevblocksORMHelper {
	static protected function _getWhereSQL($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		// Where
		$where_sql = !empty($where) ? sprintf("WHERE %s ", $where) : '';
		
		// Sorting
		if(is_array($sortBy)) {
			$sortPairs = array();
			foreach($sortBy as $k => $v) {
				$sortPairs[] = sprintf("%s %s",
					$v,
					(is_array($sortAsc) ? (@$sortAsc[$k] ? 'ASC' : 'DESC') : ($sortAsc ? 'ASC' : 'DESC')) 
				);
			}
			
			$sort_sql = 'ORDER BY '. implode(', ', $sortPairs) . ' ';
			
		} else {
			$sortAsc = ($sortAsc) ? 'ASC' : 'DESC';
			$sort_sql = !empty($sortBy) ? sprintf("ORDER BY %s %s ", $sortBy, $sortAsc) : '';
		}
		
		// Limit
		$limit_sql = !empty($limit) ? sprintf("LIMIT 0,%d ", $limit) : '';
		
		$return = array(
			$where_sql,
			$sort_sql,
			$limit_sql
		);
		
		return $return;
	}
	
	/**
	 * @param integer $id
	 * @param array $fields
	 */
	static protected function _update($ids=array(), $table, $fields, $idcol='id') {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($ids))
			return;
		
		foreach($fields as $k => $v) {
		    if(is_null($v))
		        $value = 'NULL';
		    else
		        $value = $db->qstr($v);
		    
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
			
		$sql = sprintf("UPDATE %s SET %s WHERE %s IN (%s)",
			$table,
			implode(', ', $sets),
			$idcol,
			implode(',', $ids)
		);
		$db->Execute($sql); 
	}
	
	static protected function _updateWhere($table, $fields, $where) {
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($where))
			return;
		
		foreach($fields as $k => $v) {
		    if(is_null($v))
		        $value = 'NULL';
		    else
		        $value = $db->qstr($v);
		    
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
			
		$sql = sprintf("UPDATE %s SET %s WHERE %s",
			$table,
			implode(', ', $sets),
			$where
		);
		$db->Execute($sql); 
	}
	
	static protected function _parseSearchParams($params,$columns=array(),$fields,$sortBy='') {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tables = array();
		$wheres = array();
		$selects = array();
		
		// Sort By
		if(!empty($sortBy) && isset($fields[$sortBy]))
			$tables[$fields[$sortBy]->db_table] = $fields[$sortBy]->db_table;
		
		// Columns
		if(is_array($columns))
		foreach($columns as $column) {
			$table_name = $fields[$column]->db_table;
			$tables[$fields[$column]->db_table] = $table_name;
			
			// Skip virtuals
			if('*' == $table_name)
				continue;
			
			$selects[] = sprintf("%s.%s AS %s",
				$fields[$column]->db_table,
				$fields[$column]->db_column,
				$column
			);
		}
		
		// Params
		if(is_array($params))
		foreach($params as $param) {
			// Skip virtuals
			if(!is_array($param) && !is_object($param))
				continue;
			
			if(!is_array($param) && '*_' == substr($param->field,0,2))
				continue;
			
			// Is this a criteria group (OR, AND)?
			if(is_array($param)) {
				$where = self::_parseNestedSearchParams($param, $tables, $fields);
				
			// Is this a single parameter?
			} elseif($param instanceOf DevblocksSearchCriteria) { /* @var $param DevblocksSearchCriteria */
				// [JAS]: Filter allowed columns (ignore invalid/deprecated)
				if(!isset($fields[$param->field]))
					continue;
				
				// [JAS]: Indexes for optimization
				$tables[$fields[$param->field]->db_table] = $fields[$param->field]->db_table;
				$where = $param->getWhereSQL($fields);
			}
			
			if(!empty($where)) $wheres[] = $where;
		}
		
		return array($tables, $wheres, $selects);
	}
	
	static private function _parseNestedSearchParams($param,&$tables,$fields) {
		$outer_wheres = array();
		$group_wheres = array();
		@$group_oper = strtoupper(array_shift($param));
		$where = '';
		
		switch($group_oper) {
			case DevblocksSearchCriteria::GROUP_OR:
			case DevblocksSearchCriteria::GROUP_AND:
				foreach($param as $p) { /* @var $$p DevblocksSearchCriteria */
					if(is_array($p)) {
						$outer_wheres[] = self::_parseNestedSearchParams($p, $tables, $fields);
						
					} else {
						// Skip virtuals
						if('*_' == substr($p->field,0,2))
							continue;
						
						// [JAS]: Filter allowed columns (ignore invalid/deprecated)
						if(!isset($fields[$p->field]))
							continue;
						
						// [JAS]: Indexes for optimization
						$tables[$fields[$p->field]->db_table] = $fields[$p->field]->db_table;
						$group_wheres[] = $p->getWhereSQL($fields);
						
						$where = sprintf("(%s)",
							implode(" $group_oper ", $group_wheres)
						);
					}
				}
				
				break;
		}
		
		if(!empty($outer_wheres)) {
			return sprintf("(%s)",
				implode(" $group_oper ", $outer_wheres)
			);
			
		} else {
			return $where;
			
		}
		
	}
};

class DAO_Platform {
    static function cleanupPluginTables() {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		/*
		 * Make sure this uses the DB directly and not the registry, since
		 * that automatically filters out bad rows and we'd never purge them.
		 */
	    $sql = sprintf("SELECT p.* ".
			"FROM %splugin p ".
			"ORDER BY p.enabled DESC, p.name ASC ",
			$prefix
		);
		$results = $db->GetArray($sql); 

		foreach($results as $row) {
		    $plugin = new DevblocksPluginManifest();
		    @$plugin->id = $row['id'];
		    @$plugin->dir = $row['dir'];
		    
			if(!file_exists(APP_PATH . '/' . $plugin->dir)) {
				$plugin->purge();
			}
		}
				
		DevblocksPlatform::clearCache();
    }
    
    static function maint() {
    	$db = DevblocksPlatform::getDatabaseService();
    	$logger = DevblocksPlatform::getConsoleLog();
    	
    	$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
    	
    	$sql = sprintf("DELETE %1\$sextension FROM %1\$sextension ".
    		"LEFT JOIN %1\$splugin ON (%1\$sextension.plugin_id=%1\$splugin.id) ".
    		"WHERE %1\$splugin.id IS NULL",
    		$prefix
    	);
    	$db->Execute($sql);
    	$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extensions.');
    	
    	$sql = sprintf("DELETE %1\$sproperty_store FROM %1\$sproperty_store ".
    		"LEFT JOIN %1\$sextension ON (%1\$sproperty_store.extension_id=%1\$sextension.id) ".
    		"LEFT JOIN %1\$splugin ON (%1\$sextension.plugin_id=%1\$splugin.id) ".
    		"WHERE %1\$sextension.id IS NULL",
    		$prefix
    	);
    	$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extension properties.');
    }
    
	static function updatePlugin($id, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE %splugin SET %s WHERE id = %s",
			$prefix,
			implode(', ', $sets),
			$db->qstr($id)
		);
		$db->Execute($sql); 
	}
	
	static function deleteExtension($extension_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		// Nuke cached extension manifest
		$sql = sprintf("DELETE FROM %sextension WHERE id = %s",
			$prefix,
			$db->qstr($extension_id)
		);
		$db->Execute($sql);
		
		// Nuke cached extension properties
		$sql = sprintf("DELETE FROM %sproperty_store WHERE extension_id = %s",
			$prefix,
			$db->qstr($extension_id)
		);
		$db->Execute($sql);
	}

	/**
	 * @param string $plugin_id
	 * @param integer $revision
	 * @return boolean
	 */
	static function hasPatchRun($plugin_id,$revision) {
		$tables = DevblocksPlatform::getDatabaseTables();
		if(empty($tables))
			return false;
		
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		// [JAS]: [TODO] Does the GTE below do what we need with the primary key mucking up redundant patches?
		$sql = sprintf("SELECT run_date FROM %spatch_history WHERE plugin_id = %s AND revision >= %d",
			$prefix,
			$db->qstr($plugin_id),
			$revision
		);
		
		if($db->GetOne($sql))
			 return true;
			 
		return false;
	}
	
	/**
	 * @param string $plugin_id
	 * @param integer $revision
	 */
	static function setPatchRan($plugin_id,$revision) {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		$sql = sprintf("REPLACE INTO ${prefix}patch_history (plugin_id, revision, run_date) ".
			"VALUES (%s, %d, %d)",
			$db->qstr($plugin_id),
			$revision,
			time()
		);
		$db->Execute($sql);
	}
	
	static function getClassLoaderMap() {
		if(null == ($db = DevblocksPlatform::getDatabaseService()) || !$db->isConnected())
			return array();
			
		$tables = DevblocksPlatform::getDatabaseTables();
		if(empty($tables))
			return array();

		$plugins = DevblocksPlatform::getPluginRegistry();
			
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup		
		$class_loader_map = array();
		
		$sql = sprintf("SELECT class, plugin_id, rel_path FROM %sclass_loader ORDER BY plugin_id", $prefix);
		$results = $db->GetArray($sql);

		foreach($results as $row) {
			@$class = $row['class'];
			@$plugin_id = $row['plugin_id'];
			@$rel_path = $row['rel_path'];
			
			// Make sure the plugin is valid
			if(isset($plugins[$plugin_id])) {
				// Build an absolute path
				$path = APP_PATH . DIRECTORY_SEPARATOR . $plugins[$plugin_id]->dir . DIRECTORY_SEPARATOR . $rel_path;
				
				// Init the array
				if(!isset($class_loader_map[$path]))
					$class_loader_map[$path] = array();
				
				$class_loader_map[$path][] = $class;
			}
		}
		
		return $class_loader_map;
	}
	
	static function getUriRoutingMap() {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup		
		
		$uri_routing_map = array();
	
		$sql = sprintf("SELECT uri, plugin_id, controller_id FROM %suri_routing ORDER BY plugin_id", $prefix);
		$results = $db->GetArray($sql);

		foreach($results as $row) {
			@$uri = $row['uri'];
			@$plugin_id = $row['plugin_id'];
			@$controller_id = $row['controller_id'];
			
			$uri_routing_map[$uri] = $controller_id;
		}
	
		return $uri_routing_map;
	}
};

class DAO_DevblocksSetting extends DevblocksORMHelper {
	static function set($plugin_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf(
			"REPLACE INTO devblocks_setting (plugin_id, setting, value) ".
			"VALUES (%s,%s,%s) ",
				$db->qstr($plugin_id),
				$db->qstr($key),
				$db->qstr($value)
		));
		
//		$cache = DevblocksPlatform::getCacheService();
//		$cache->remove(DevblocksPlatform::CACHE_SETTINGS);
	}
	
	static function get($plugin_id, $key) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s AND setting = %s",
			$db->qstr($plugin_id),
			$db->qstr($key)
		);
		$value = $db->GetOne($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); 
		
		return $value;
	}
	
	static function getSettings($plugin_id=null) {
	    $cache = DevblocksPlatform::getCacheService();
	    if(null === ($plugin_settings = $cache->load(DevblocksPlatform::CACHE_SETTINGS))) {
			$db = DevblocksPlatform::getDatabaseService();
			$plugin_settings = array();
			
			$sql = sprintf("SELECT plugin_id,setting,value FROM devblocks_setting");
			$results = $db->GetArray($sql); 
			
			foreach($results as $row) {
				$plugin_id = $row['plugin_id'];
				$k = $row['setting'];
				$v = $row['value'];
				
				if(!isset($plugin_settings[$plugin_id]))
					$plugin_settings[$plugin_id] = array();
				
				$plugin_settings[$plugin_id][$k] = $v;
			}
			
			if(!empty($plugin_settings))
				$cache->save($plugin_settings, DevblocksPlatform::CACHE_SETTINGS);
	    }
	    
		return $plugin_settings;
	}
};

class DAO_DevblocksExtensionPropertyStore extends DevblocksORMHelper {
	const EXTENSION_ID = 'extension_id';
	const PROPERTY = 'property';
	const VALUE = 'value';
	
	const _CACHE_ALL = 'devblocks_property_store';
	
	static function getAll() {
		$extensions = DevblocksPlatform::getExtensionRegistry(true);
		$cache = DevblocksPlatform::getCacheService();
		
		if(null == ($params = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
			$params = array();
			
			// Add manifest params as our initial params

			foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
				$params[$extension->id] = $extension->params;
			}
			
			// Now load the DB params on top of them
			
			$sql = sprintf("SELECT extension_id, property, value ".
				"FROM %sproperty_store ",
				$prefix
			);
			$results = $db->GetArray($sql);
			
			foreach($results as $row) {
				$params[$row['extension_id']][$row['property']] = $row['value'];
			}
			
			$cache->save($params, self::_CACHE_ALL);
		}
		
		return $params;
	}
	
	static function getByExtension($extension_id) {
		$params = self::getAll();
		
		if(isset($params[$extension_id]))
			return $params[$extension_id];
			
		return array();
	}

	static function get($extension_id, $key, $default=null) {
	    $params = self::getByExtension($extension_id);
	    return isset($params[$key]) ? $params[$key] : $default;
	}
	
	static function put($extension_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		$db->Execute(sprintf(
			"REPLACE INTO ${prefix}property_store (extension_id, property, value) ".
			"VALUES (%s,%s,%s)",
			$db->qstr($extension_id),
			$db->qstr($key),
			$db->qstr($value)	
		));

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
		return true;
	}
};

class DAO_DevblocksTemplate extends DevblocksORMHelper {
	const ID = 'id';
	const PLUGIN_ID = 'plugin_id';
	const PATH = 'path';
	const TAG = 'tag';
	const LAST_UPDATED = 'last_updated';
	const CONTENT = 'content';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO devblocks_template () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'devblocks_template', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('devblocks_template', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_DevblocksTemplate[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, plugin_id, path, tag, last_updated, content ".
			"FROM devblocks_template ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_DevblocksTemplate	 */
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
	 * @return Model_DevblocksTemplate[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_DevblocksTemplate();
			$object->id = $row['id'];
			$object->plugin_id = $row['plugin_id'];
			$object->path = $row['path'];
			$object->tag = $row['tag'];
			$object->last_updated = $row['last_updated'];
			$object->content = $row['content'];
			$objects[$object->id] = $object;
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		$db->Execute(sprintf("DELETE FROM devblocks_template WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksTemplate::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"devblocks_template.id as %s, ".
			"devblocks_template.plugin_id as %s, ".
			"devblocks_template.path as %s, ".
			"devblocks_template.tag as %s, ".
			"devblocks_template.last_updated as %s ",
//			"devblocks_template.content as %s ",
				SearchFields_DevblocksTemplate::ID,
				SearchFields_DevblocksTemplate::PLUGIN_ID,
				SearchFields_DevblocksTemplate::PATH,
				SearchFields_DevblocksTemplate::TAG,
				SearchFields_DevblocksTemplate::LAST_UPDATED
//				SearchFields_DevblocksTemplate::CONTENT
			);
			
		$join_sql = "FROM devblocks_template ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'devblocks_template.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		$result = array(
			'primary_table' => 'devblocks_template',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			($has_multiple_values ? 'GROUP BY devblocks_template.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_DevblocksTemplate::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT devblocks_template.id) " : "SELECT COUNT(devblocks_template.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
	}
	
	static function importXmlFile($filename, $tag) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!file_exists($filename) && empty($tag))
			return;
		
		if(false == (@$xml = simplexml_load_file($filename))) /* @var $xml SimpleXMLElement */
			return;

		// Loop through all the template elements and insert/update for this tag
		foreach($xml->templates->template as $eTemplate) { /* @var $eTemplate SimpleXMLElement */
			$plugin_id = (string) $eTemplate['plugin_id'];
			$path = (string) $eTemplate['path'];
			$content = (string) $eTemplate[0];

			// Pull the template if it exists already
			@$template = array_shift(self::getWhere(sprintf("%s = %s AND %s = %s AND %s = %s",
				self::PLUGIN_ID,
				$db->qstr($plugin_id),
				self::PATH,
				$db->qstr($path),
				self::TAG,
				$db->qstr($tag)
			)));

			// Common fields
			$fields = array(
				self::CONTENT => $content,
				self::LAST_UPDATED => time(),
			);
			
			// Create or update
			if(empty($template)) { // new
				$fields[self::PLUGIN_ID] = $plugin_id;
				$fields[self::PATH] = $path;
				$fields[self::TAG] = $tag;
				self::create($fields);
				
			} else { // update
				self::update($template->id, $fields);
				
			}
		}
			
		unset($xml);
	}	

};

class SearchFields_DevblocksTemplate implements IDevblocksSearchFields {
	const ID = 'd_id';
	const PLUGIN_ID = 'd_plugin_id';
	const PATH = 'd_path';
	const TAG = 'd_tag';
	const LAST_UPDATED = 'd_last_updated';
//	const CONTENT = 'd_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'devblocks_template', 'id', $translate->_('common.id')),
			self::PLUGIN_ID => new DevblocksSearchField(self::PLUGIN_ID, 'devblocks_template', 'plugin_id', $translate->_('plugin_id')),
			self::PATH => new DevblocksSearchField(self::PATH, 'devblocks_template', 'path', $translate->_('path')),
			self::TAG => new DevblocksSearchField(self::TAG, 'devblocks_template', 'tag', $translate->_('tag')),
			self::LAST_UPDATED => new DevblocksSearchField(self::LAST_UPDATED, 'devblocks_template', 'last_updated', $translate->_('last_updated')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class DAO_Translation extends DevblocksORMHelper {
	const ID = 'id';
	const STRING_ID = 'string_id';
	const LANG_CODE = 'lang_code';
	const STRING_DEFAULT = 'string_default';
	const STRING_OVERRIDE = 'string_override';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO translation () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'translation', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_TranslationDefault[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, string_id, lang_code, string_default, string_override ".
			"FROM translation ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY string_id ASC, lang_code ASC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TranslationDefault	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function importTmxFile($filename) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!file_exists($filename))
			return;
		
		/*
		 * [JAS] [TODO] This could be inefficient when reading a lot 
		 * of TMX sources, but it could also be inefficient always
		 * keeping it in memory after using it once.  I'm going to err
		 * on the side of a little extra DB work for the few times it's 
		 * called.
		 */
		
		$hash = array();
		foreach(DAO_Translation::getWhere() as $s) { /* @var $s Model_TranslationDefault */
			$hash[$s->lang_code.'_'.$s->string_id] = $s;
		}
		
		if(false == (@$xml = simplexml_load_file($filename))) /* @var $xml SimpleXMLElement */
			return;
			
		$namespaces = $xml->getNamespaces(true);
		
		foreach($xml->body->tu as $tu) { /* @var $tu SimpleXMLElement */
			$msgid = strtolower((string) $tu['tuid']);
			foreach($tu->tuv as $tuv) { /* @var $tuv SimpleXMLElement */
				$attribs = $tuv->attributes($namespaces['xml']); 
				$lang = (string) $attribs['lang'];
				$string = (string) $tuv->seg[0]; // [TODO] Handle multiple segs?
				
				@$hash_obj = $hash[$lang.'_'.$msgid]; /* @var $hash_obj Model_Translation */
				
				// If not found in the DB
				if(empty($hash_obj)) {
					$fields = array(
						DAO_Translation::STRING_ID => $msgid,
						DAO_Translation::LANG_CODE => $lang,
						DAO_Translation::STRING_DEFAULT => $string,
					);
					$id = DAO_Translation::create($fields);

					// Add to our hash to prevent dupes
					$new = new Model_Translation();
						$new->id = $id;
						$new->string_id = $msgid;
						$new->lang_code = $lang;
						$new->string_default = $string;
						$new->string_override = '';
					$hash[$lang.'_'.$msgid] = $new;
					
				// If exists in DB and the string has changed
				} elseif (!empty($hash_obj) && 0 != strcasecmp($string, $hash_obj->string_default)) {
					$fields = array(
						DAO_Translation::STRING_DEFAULT => $string,
					);
					DAO_Translation::update($hash_obj->id, $fields);
				}
			}
		}
	
		unset($xml);
	}
	
	static function reloadPluginStrings() {
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(is_array($plugins))
		foreach($plugins as $plugin_id => $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled) {
				$strings_xml = APP_PATH . '/' . $plugin->dir . '/strings.xml';
				if(file_exists($strings_xml)) {
					self::importTmxFile($strings_xml);
				}
			}
		}
	}
	
	static function getDefinedLangCodes() {
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$lang_codes = array();
		
		// Look up distinct land codes from existing translations
		$sql = sprintf("SELECT DISTINCT lang_code FROM translation ORDER BY lang_code ASC");
		$results = $db->GetArray($sql); 
		
		// Languages
		$langs = $translate->getLanguageCodes();

		// Countries
		$countries = $translate->getCountryCodes();
		
		foreach($results as $row) {
			$code = $row['lang_code'];
			$data = explode('_', $code);
			@$lang = $langs[strtolower($data[0])];
			@$terr = $countries[strtoupper($data[1])];

			$lang_codes[$code] = (!empty($lang) && !empty($terr))
				? ($lang . ' (' . $terr . ')')
				: $code;
		}
		
		return $lang_codes;
	}
	
	static function getByLang($lang='en_US') {
		$db = DevblocksPlatform::getDatabaseService();
		
		return self::getWhere(sprintf("%s = %s",
			self::LANG_CODE,
			$db->qstr($lang)
		));
	}
	
	static function getMapByLang($lang='en_US') {
		$strings = self::getByLang($lang);
		$map = array();
		
		if(is_array($strings))
		foreach($strings as $string) { /* @var $string Model_Translation */
			if($string instanceof Model_Translation)
				$map[$string->string_id] = $string;
		}
		
		return $map;
	}
	
	// [TODO] Allow null 2nd arg for all instances of a given string?
	static function getString($string_id, $lang='en_US') {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = self::getWhere(sprintf("%s = %s AND %s = %s",
			self::STRING_ID,
			$db->qstr($string_id),
			self::LANG_CODE,
			$db->qstr($lang)
		));

		if(!empty($objects) && is_array($objects))
			return array_shift($objects);
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TranslationDefault[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!is_resource($rs))
			return $objects;
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Translation();
			$object->id = $row['id'];
			$object->string_id = $row['string_id'];
			$object->lang_code = $row['lang_code'];
			$object->string_default = $row['string_default'];
			$object->string_override = $row['string_override'];
			$objects[$object->id] = $object;
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM translation WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByLangCodes($codes) {
		if(!is_array($codes)) $codes = array($codes);
		$db = DevblocksPlatform::getDatabaseService();
		
		$codes_list = implode("','", $codes);
		
		$db->Execute(sprintf("DELETE FROM translation WHERE lang_code IN ('%s') AND lang_code != 'en_US'", $codes_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Translation::getFields(); 
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"tl.id as %s, ".
			"tl.string_id as %s, ".
			"tl.lang_code as %s, ".
			"tl.string_default as %s, ".
			"tl.string_override as %s ",
//			"o.name as %s ".
			    SearchFields_Translation::ID,
			    SearchFields_Translation::STRING_ID,
			    SearchFields_Translation::LANG_CODE,
			    SearchFields_Translation::STRING_DEFAULT,
			    SearchFields_Translation::STRING_OVERRIDE
			 );
		
		$join_sql = 
			"FROM translation tl ";
//			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "

			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");

		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$result = array(
			'primary_table' => 'translation',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents(array(),$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY a.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Translation::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }	

};

class SearchFields_Translation implements IDevblocksSearchFields {
	// Translate
	const ID = 'tl_id';
	const STRING_ID = 'tl_string_id';
	const LANG_CODE = 'tl_lang_code';
	const STRING_DEFAULT = 'tl_string_default';
	const STRING_OVERRIDE = 'tl_string_override';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'tl', 'id', $translate->_('translate.id')),
			self::STRING_ID => new DevblocksSearchField(self::STRING_ID, 'tl', 'string_id', $translate->_('translate.string_id')),
			self::LANG_CODE => new DevblocksSearchField(self::LANG_CODE, 'tl', 'lang_code', $translate->_('translate.lang_code')),
			self::STRING_DEFAULT => new DevblocksSearchField(self::STRING_DEFAULT, 'tl', 'string_default', $translate->_('translate.string_default')),
			self::STRING_OVERRIDE => new DevblocksSearchField(self::STRING_OVERRIDE, 'tl', 'string_override', $translate->_('translate.string_override')),
		);
	}
};

class DAO_DevblocksStorageProfile extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const EXTENSION_ID = 'extension_id';
	const PARAMS_JSON = 'params_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO devblocks_storage_profile () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'devblocks_storage_profile', $fields);
		self::_clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('devblocks_storage_profile', $fields, $where);
		self::_clearCache();
	}
	
	static function getAll() {
	    $cache = DevblocksPlatform::getCacheService();
	    
	    if(null === ($profiles = $cache->load(DevblocksPlatform::CACHE_STORAGE_PROFILES))) {
	    	$profiles = self::getWhere();
	    	$cache->save($profiles, DevblocksPlatform::CACHE_STORAGE_PROFILES);
	    }
	    
	    return $profiles;
	}
	
	static private function _clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DevblocksPlatform::CACHE_STORAGE_PROFILES);
	}
	
	/**
	 * @param string $where
	 * @return Model_DevblocksStorageProfile[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, extension_id, params_json ".
			"FROM devblocks_storage_profile ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * Accepts either an integer id or a storage extension (profile_id=0)
	 * 
	 * @param mixed $id
	 * @return Model_DevblocksStorageProfile
	 **/
	static function get($id) {
		
		if(is_numeric($id)) {
			$profiles = self::getAll();
			if(isset($profiles[$id]))
				return $profiles[$id];
				
		} else {
			// [TODO] Validate extension id
			$profile = new Model_DevblocksStorageProfile();
			$profile->id = 0;
			$profile->extension_id = $id;
			return $profile;
		}
			
		return NULL;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_DevblocksStorageProfile[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_DevblocksStorageProfile();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->extension_id = $row['extension_id'];
			$object->params_json = $row['params_json'];
			
			if(false !== ($params = json_decode($object->params_json, true))) {
				$object->params = $params;
			} else {
				$object->params = array();
			}
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM devblocks_storage_profile WHERE id IN (%s)", $ids_list));
		
		self::_clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksStorageProfile::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"devblocks_storage_profile.id as %s, ".
			"devblocks_storage_profile.name as %s, ".
			"devblocks_storage_profile.extension_id as %s, ".
			"devblocks_storage_profile.params_json as %s ",
				SearchFields_DevblocksStorageProfile::ID,
				SearchFields_DevblocksStorageProfile::NAME,
				SearchFields_DevblocksStorageProfile::EXTENSION_ID,
				SearchFields_DevblocksStorageProfile::PARAMS_JSON
			);
			
		$join_sql = "FROM devblocks_storage_profile ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'devblocks_storage_profile.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		$result = array(
			'primary_table' => 'devblocks_storage_profile',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			($has_multiple_values ? 'GROUP BY devblocks_storage_profile.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
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
			$object_id = intval($row[SearchFields_DevblocksStorageProfile::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT devblocks_storage_profile.id) " : "SELECT COUNT(devblocks_storage_profile.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_DevblocksStorageProfile implements IDevblocksSearchFields {
	const ID = 'd_id';
	const NAME = 'd_name';
	const EXTENSION_ID = 'd_extension_id';
	const PARAMS_JSON = 'd_params_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'devblocks_storage_profile', 'id', $translate->_('id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'devblocks_storage_profile', 'name', $translate->_('name')),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'devblocks_storage_profile', 'extension_id', $translate->_('extension_id')),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'devblocks_storage_profile', 'params_json', $translate->_('params_json')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};
