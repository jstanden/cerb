<?php
abstract class DevblocksORMHelper {
	const OPT_GET_NO_CACHE = 1;
	const OPT_GET_MASTER_ONLY = 2;
	
	const OPT_UPDATE_NO_FLUSH_CACHE = 1;
	const OPT_UPDATE_NO_EVENTS = 2;
	const OPT_UPDATE_NO_READ_AFTER_WRITE = 4;
	
	static protected function _buildSortClause($sortBy, $sortAsc, $fields, &$select_sql, $search_class=null) {
		$sort_sql = null;
		
		if(!is_array($sortBy))
			$sortBy = array($sortBy);
		
		if(!is_array($sortAsc))
			$sortAsc = array($sortAsc);
		
		// Append custom fields to the SELECT if (and only if) we're sorting on it
		
		foreach($sortBy as $sort_field) {
			if('cf_' != substr($sort_field,0,3))
				continue;
			
			if(false == ($field_id = intval(substr($sort_field, 3))))
				continue;
	
			if(false == ($field = DAO_CustomField::get($field_id)))
				continue;
			
			$cfield_key = null;
			
			if($search_class && class_exists($search_class))
				$cfield_key = $search_class::getCustomFieldContextWhereKey($field->context);
			
			if($cfield_key) {
				$select_sql .= sprintf(", (SELECT field_value FROM %s WHERE context=%s AND context_id=%s AND field_id=%d ORDER BY field_value%s) AS %s ",
					DAO_CustomFieldValue::getValueTableName($field->id),
					Cerb_ORMHelper::qstr($field->context),
					$cfield_key,
					$field_id,
					' LIMIT 1',
					$sort_field
				);
			}
		}
		
		if(is_array($sortBy) && is_array($sortAsc) && count($sortBy) == count($sortAsc)) {
			$sorts = array();
			
			foreach($sortBy as $idx => $field) {
				// We can't sort on virtual fields, the field must exist, and must be flagged sortable
				if('*'==substr($field,0,1) 
						|| !isset($fields[$field]) 
						|| !$fields[$field]->is_sortable) {
					continue;
				}
				
				@$asc = $sortAsc[$idx];
				
				$sorts[] = sprintf("%s %s",
					$field,
					($asc || is_null($asc)) ? "ASC" : "DESC"
				);
			}
			
			if(!empty($sorts))
				$sort_sql = sprintf('ORDER BY %s', implode(', ', $sorts));
			
		}
		
		return $sort_sql;
	}
	
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
	static protected function _insert($table, $fields, $idcol='id') {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($fields) || empty($fields))
			return;
		
		foreach($fields as $k => &$v) {
			if(is_null($v))
				$v = 'NULL';
			else
				$v = $db->qstr($v);
		}

		$sql = sprintf("INSERT INTO %s (%s) VALUES (%s)",
			$table,
			implode(', ', array_keys($fields)),
			implode(', ', array_values($fields))
		);
		$db->ExecuteMaster($sql);
		
		return $db->LastInsertId();
	}
	
	/**
	 * @param integer $id
	 * @param array $fields
	 */
	static protected function _update($ids=array(), $table, $fields, $idcol='id', $option_bits = 0) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
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
			
		$db_option_bits = ($option_bits & DevblocksORMHelper::OPT_UPDATE_NO_READ_AFTER_WRITE) ? _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE : 0;
		
		$sql = sprintf("UPDATE %s SET %s WHERE %s IN (%s)",
			$table,
			implode(', ', $sets),
			$idcol,
			implode(',', $ids)
		);
		$db->ExecuteMaster($sql, $db_option_bits);
	}
	
	static protected function _updateWhere($table, $fields, $where) {
		$db = DevblocksPlatform::services()->database();
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
		$db->ExecuteMaster($sql);
	}
	
	static protected function _parseSearchParams($params, $columns=array(), $search_class, $sortBy='') {
		$db = DevblocksPlatform::services()->database();
		
		if(!class_exists($search_class) || !class_implements($search_class, 'DevblocksSearchFields'))
			return false;
		
		$pkey = $search_class::getPrimaryKey();
		$fields = $search_class::getFields();
		
		$tables = array();
		$selects = array();
		$wheres = array();
		
		// Sort By
		if(!empty($sortBy)) {
			if(is_string($sortBy) && isset($fields[$sortBy])) {
				$tables[$fields[$sortBy]->db_table] = $fields[$sortBy]->db_table;
				
			} elseif(is_array($sortBy)) {
				foreach($sortBy as $sort_field) {
					if(isset($fields[$sort_field]))
						$tables[$fields[$sort_field]->db_table] = $fields[$sort_field]->db_table;
				}
			}
		}
		
		// Columns
		if(is_array($columns))
		foreach($columns as $column) {
			if(!isset($fields[$column]))
				continue;
			
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
		foreach($params as $param_key => $param) {
			if(!is_array($param) && !is_object($param)) {
				$where = "-1";
				
			// Is this a criteria group (OR, AND)?
			} elseif(is_array($param)) {
				$where = self::_parseNestedSearchParams($param, $tables, $search_class, $pkey);
				
			// Is this a single parameter?
			} elseif($param instanceOf DevblocksSearchCriteria) { /* @var $param DevblocksSearchCriteria */
				if(isset($fields[$param->field]))
					$tables[$fields[$param->field]->db_table] = $fields[$param->field]->db_table;
				
				$where = $search_class::getWhereSQL($param);
			}
			
			if(0 != strlen($where)) {
				$wheres[$param_key] = $where;
			}
		}
		
		return array($tables, $wheres, $selects);
	}
	
	static private function _parseNestedSearchParams($param, &$tables, $search_class, $pkey=null) {
		$pkey = $search_class::getPrimaryKey();
		$fields = $search_class::getFields();
		
		$outer_wheres = array();
		$group_wheres = array();
		@$group_oper = DevblocksPlatform::strUpper(array_shift($param));
		$sql = '';
		$where = '';
		
		if(empty($param))
			return null;
		
		switch($group_oper) {
			case DevblocksSearchCriteria::GROUP_OR:
			case DevblocksSearchCriteria::GROUP_AND:
				foreach($param as $p) { /* @var $p DevblocksSearchCriteria */
					if(is_array($p)) {
						$outer_wheres[] = self::_parseNestedSearchParams($p, $tables, $search_class, $pkey);
						
					} else {
						if(!isset($fields[$p->field])) {
							$group_wheres[] = '0';
							
						} else {
							// [JAS]: Indexes for optimization
							if(isset($fields[$p->field]))
								$tables[$fields[$p->field]->db_table] = $fields[$p->field]->db_table;
							
							$group_wheres[] = $search_class::getWhereSQL($p);
						}
						
						$where = sprintf("%s",
							implode(" $group_oper ", $group_wheres)
						);
					}
				}
				break;
		}
		
		if(0 != strlen($where))
			$outer_wheres[] = $where;
		
		if($group_oper && $outer_wheres) {
			$sql = sprintf("(%s)",
				implode(" $group_oper ", $outer_wheres)
			);
		}
		
		return $sql;
	}
};

class DAO_Platform extends DevblocksORMHelper {
	static function cleanupPluginTables() {
		$db = DevblocksPlatform::services()->database();
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
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin = new DevblocksPluginManifest();
			@$plugin->id = $row['id'];
			@$plugin->dir = $row['dir'];
			
			if(!file_exists($plugin->getStoragePath())) {
				$plugin->purge();
			}
		}
				
		DevblocksPlatform::clearCache();
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		$db->ExecuteMaster(sprintf("DELETE FROM %1\$sextension WHERE plugin_id NOT IN (SELECT id FROM %1\$splugin)", $prefix));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extensions.');
		
		$db->ExecuteMaster(sprintf("DELETE FROM %1\$sproperty_store WHERE extension_id NOT IN (SELECT id FROM %1\$sextension)", $prefix));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' orphaned extension properties.');
	}
	
	static function updatePlugin($id, $fields) {
		$db = DevblocksPlatform::services()->database();
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
		$db->ExecuteMaster($sql);
	}
	
	static function deleteExtension($extension_id) {
		$db = DevblocksPlatform::services()->database();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		// Nuke cached extension manifest
		$sql = sprintf("DELETE FROM %sextension WHERE id = %s",
			$prefix,
			$db->qstr($extension_id)
		);
		$db->ExecuteMaster($sql);
		
		// Nuke cached extension properties
		$sql = sprintf("DELETE FROM %sproperty_store WHERE extension_id = %s",
			$prefix,
			$db->qstr($extension_id)
		);
		$db->ExecuteMaster($sql);
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
		
		$db = DevblocksPlatform::services()->database();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		// [JAS]: [TODO] Does the GTE below do what we need with the primary key mucking up redundant patches?
		$sql = sprintf("SELECT run_date FROM %spatch_history WHERE plugin_id = %s AND revision >= %d",
			$prefix,
			$db->qstr($plugin_id),
			$revision
		);
		
		if($db->GetOneMaster($sql))
			 return true;
			 
		return false;
	}
	
	/**
	 * @param string $plugin_id
	 * @param integer $revision
	 */
	static function setPatchRan($plugin_id,$revision) {
		$db = DevblocksPlatform::services()->database();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
		$sql = sprintf("REPLACE INTO ${prefix}patch_history (plugin_id, revision, run_date) ".
			"VALUES (%s, %d, %d)",
			$db->qstr($plugin_id),
			$revision,
			time()
		);
		$db->ExecuteMaster($sql);
	}
	
	static function getClassLoaderMap() {
		if(null == ($db = DevblocksPlatform::services()->database()))
			return array();

		if(DevblocksPlatform::isDatabaseEmpty())
			return array();
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		$class_loader_map = array();
		
		$sql = sprintf("SELECT class, plugin_id, rel_path FROM %sclass_loader ORDER BY plugin_id", $prefix);
		$results = $db->GetArrayMaster($sql);
		
		foreach($results as $row) {
			@$class = $row['class'];
			@$plugin_id = $row['plugin_id'];
			@$rel_path = $row['rel_path'];
			
			// Make sure the plugin is valid
			if(isset($plugins[$plugin_id])) {
				// Build an absolute path
				$path = $plugins[$plugin_id]->getStoragePath() . '/' . $rel_path;
				
				// Init the array
				if(!isset($class_loader_map[$path]))
					$class_loader_map[$path] = array();
				
				$class_loader_map[$path][] = $class;
			}
		}
		
		return $class_loader_map;
	}
	
};

class DAO_DevblocksSetting extends DevblocksORMHelper {
	static function set($plugin_id, $key, $value) {
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		$db->ExecuteMaster(sprintf(
			"REPLACE INTO devblocks_setting (plugin_id, setting, value) ".
			"VALUES (%s,%s,%s) ",
				$db->qstr($plugin_id),
				$db->qstr($key),
				$db->qstr($value)
		));
	}
	
	// This doesn't need to cache because it's handled by the platform
	static function getSettings($plugin_id) {
		$tables = DevblocksPlatform::getDatabaseTables();
		
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		$settings = array();
		
		if(!isset($tables['devblocks_setting']))
			return $settings;
		
		$results = $db->GetArrayMaster(sprintf("SELECT setting, value FROM devblocks_setting WHERE plugin_id = %s",
			$db->qstr($plugin_id)
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$settings[$row['setting']] = $row['value'];
		}
		
		return $settings;
	}
	
	static function delete($plugin_id, array $keys=[]) {
		if(false == ($db = DevblocksPlatform::services()->database()))
			return;
		
		return $db->ExecuteMaster(sprintf("DELETE FROM devblocks_setting WHERE plugin_id = %s AND setting IN (%s)",
			$db->qstr($plugin_id),
			implode(',', $db->qstrArray($keys))
		));
	}
};

class DAO_DevblocksExtensionPropertyStore extends DevblocksORMHelper {
	const EXTENSION_ID = 'extension_id';
	const PROPERTY = 'property';
	const VALUE = 'value';
	
	static private function _getCacheKey($extension_id) {
		return sprintf("devblocks:ext:%s:params",
			DevblocksPlatform::strAlphaNum($extension_id, '_.')
		);
	}
	
	static function getAll() {
		$extensions = DevblocksPlatform::getExtensionRegistry();
		$cache = DevblocksPlatform::getCacheService();
		
		if(null == ($params = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::services()->database();
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
			
			if(false == ($results = $db->GetArrayMaster($sql)))
				return false;
			
			foreach($results as $row) {
				$params[$row['extension_id']][$row['property']] = $row['value'];
			}
			
			$cache->save($params, self::_CACHE_ALL);
		}
		
		return $params;
	}
	
	static function getByExtension($extension_id) {
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = self::_getCacheKey($extension_id);
		
		if(null === ($params = $cache->load($cache_key))) {
			$db = DevblocksPlatform::services()->database();
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
			$params = array();
			
			if(false != ($extension = DevblocksPlatform::getExtension($extension_id, false, true))) {
				$params = $extension->params;
			}
			
			$sql = sprintf("SELECT property, value ".
				"FROM %sproperty_store ".
				"WHERE extension_id = %s",
				$prefix,
				$db->qstr($extension_id)
			);
			
			if(false == ($results = $db->GetArrayMaster($sql)))
				return false;
			
			if(is_array($results))
			foreach($results as $row)
				$params[$row['property']] = $row['value'];
			
			$cache->save($params, $cache_key);
		}
		
		return $params;
	}

	static function get($extension_id, $key, $default=null) {
		$params = self::getByExtension($extension_id);
		return isset($params[$key]) ? $params[$key] : $default;
	}
	
	static function put($extension_id, $key, $value) {
		$db = DevblocksPlatform::services()->database();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		$cache_key = self::_getCacheKey($extension_id);

		$db->ExecuteMaster(sprintf(
			"REPLACE INTO ${prefix}property_store (extension_id, property, value) ".
			"VALUES (%s,%s,%s)",
			$db->qstr($extension_id),
			$db->qstr($key),
			$db->qstr($value)
		));

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove($cache_key);
		return true;
	}
};

class DAO_Translation extends DevblocksORMHelper {
	const ID = 'id';
	const STRING_ID = 'string_id';
	const LANG_CODE = 'lang_code';
	const STRING_DEFAULT = 'string_default';
	const STRING_OVERRIDE = 'string_override';

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO translation () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
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
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, string_id, lang_code, string_default, string_override ".
			"FROM translation ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY string_id ASC, lang_code ASC";
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TranslationDefault	 */
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
	
	static function importTmxFile($filename) {
		$db = DevblocksPlatform::services()->database();
		
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
			$msgid = DevblocksPlatform::strLower((string) $tu['tuid']);
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
				$strings_xml = $plugin->getStoragePath() . '/strings.xml';
				if(file_exists($strings_xml)) {
					self::importTmxFile($strings_xml);
				}
			}
		}
	}
	
	static function getDefinedLangCodes() {
		$db = DevblocksPlatform::services()->database();
		$translate = DevblocksPlatform::getTranslationService();
		
		$lang_codes = array();
		
		// Look up distinct land codes from existing translations
		$sql = sprintf("SELECT DISTINCT lang_code FROM translation ORDER BY lang_code ASC");
		$results = $db->GetArraySlave($sql);
		
		// Languages
		$langs = $translate->getLanguageCodes();

		// Countries
		$countries = $translate->getCountryCodes();
		
		foreach($results as $row) {
			$code = $row['lang_code'];
			$data = explode('_', $code);
			@$lang = $langs[DevblocksPlatform::strLower($data[0])];
			@$terr = $countries[DevblocksPlatform::strUpper($data[1])];

			$lang_codes[$code] = (!empty($lang) && !empty($terr))
				? ($lang . ' (' . $terr . ')')
				: $code;
		}
		
		return $lang_codes;
	}
	
	static function getByLang($lang='en_US') {
		$db = DevblocksPlatform::services()->database();
		
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
		$db = DevblocksPlatform::services()->database();
		
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
		
		if(!($rs instanceof mysqli_result))
			return $objects;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM translation WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByLangCodes($codes) {
		if(!is_array($codes)) $codes = array($codes);
		$db = DevblocksPlatform::services()->database();
		
		$codes_list = implode("','", $codes);
		
		$db->ExecuteMaster(sprintf("DELETE FROM translation WHERE lang_code IN ('%s') AND lang_code != 'en_US'", $codes_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Translation::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, array(), 'SearchFields_Translation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"tl.id as %s, ".
			"tl.string_id as %s, ".
			"tl.lang_code as %s, ".
			"tl.string_default as %s, ".
			"tl.string_override as %s ",
				SearchFields_Translation::ID,
				SearchFields_Translation::STRING_ID,
				SearchFields_Translation::LANG_CODE,
				SearchFields_Translation::STRING_DEFAULT,
				SearchFields_Translation::STRING_OVERRIDE
			 );
		
		$join_sql =
			"FROM translation tl ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Translation');
		
		$result = array(
			'primary_table' => 'translation',
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
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_Translation::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		return array($results,$total);
	}

};

class SearchFields_Translation extends DevblocksSearchFields {
	// Translate
	const ID = 'tl_id';
	const STRING_ID = 'tl_string_id';
	const LANG_CODE = 'tl_lang_code';
	const STRING_DEFAULT = 'tl_string_default';
	const STRING_OVERRIDE = 'tl_string_override';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'tl.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('tl.id', self::ID),
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
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'tl', 'id', $translate->_('translate.id'), null, true),
			self::STRING_ID => new DevblocksSearchField(self::STRING_ID, 'tl', 'string_id', $translate->_('translate.string_id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LANG_CODE => new DevblocksSearchField(self::LANG_CODE, 'tl', 'lang_code', $translate->_('translate.lang_code'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STRING_DEFAULT => new DevblocksSearchField(self::STRING_DEFAULT, 'tl', 'string_default', $translate->_('translate.string_default'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STRING_OVERRIDE => new DevblocksSearchField(self::STRING_OVERRIDE, 'tl', 'string_override', $translate->_('translate.string_override'), Model_CustomField::TYPE_SINGLE_LINE, true),
		);
	}
};

class DAO_DevblocksStorageQueue extends DevblocksORMHelper {
	static function getPendingProfiles() {
		$db = DevblocksPlatform::services()->database();
		
		return $db->GetArrayMaster("SELECT DISTINCT storage_extension, storage_profile_id, storage_namespace FROM devblocks_storage_queue_delete");
	}
	
	static function getKeys($storage_namespace, $storage_extension, $storage_profile_id=0, $limit=500) {
		$db = DevblocksPlatform::services()->database();
		
		$keys = $db->GetArrayMaster(sprintf("SELECT storage_key FROM devblocks_storage_queue_delete WHERE storage_namespace = %s AND storage_extension = %s AND storage_profile_id = %d LIMIT %d",
			$db->qstr($storage_namespace),
			$db->qstr($storage_extension),
			$storage_profile_id,
			$limit
		));
		
		return array_map(function($e) {
			return $e['storage_key'];
		}, $keys);
	}
	
	static function enqueueDelete($storage_namespace, $storage_key, $storage_extension, $storage_profile_id=0) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_storage_queue_delete (storage_namespace, storage_key, storage_extension, storage_profile_id) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($storage_namespace),
			$db->qstr($storage_key),
			$db->qstr($storage_extension),
			$storage_profile_id
		));
		
		return TRUE;
	}
	
	static function purgeKeys($keys, $storage_namespace, $storage_extension, $storage_profile_id=0) {
		$db = DevblocksPlatform::services()->database();
		
		$escaped_keys = array_map(function($e) use ($db) {
			return $db->qstr($e);
		}, $keys);
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_storage_queue_delete WHERE storage_namespace = %s AND storage_extension = %s AND storage_profile_id = %d AND storage_key IN (%s)",
			$db->qstr($storage_namespace),
			$db->qstr($storage_extension),
			$storage_profile_id,
			implode(',', $escaped_keys)
		));
		
		return TRUE;
	}
};

class DAO_DevblocksStorageProfile extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const EXTENSION_ID = 'extension_id';
	const PARAMS_JSON = 'params_json';

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO devblocks_storage_profile () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
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
			
			if(!is_array($profiles))
				return false;
			
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
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, name, extension_id, params_json ".
			"FROM devblocks_storage_profile ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->ExecuteSlave($sql);
		
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
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_storage_profile WHERE id IN (%s)", $ids_list));
		
		self::_clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksStorageProfile::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DevblocksStorageProfile', $sortBy);
		
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
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DevblocksStorageProfile');
		
		$result = array(
			'primary_table' => 'devblocks_storage_profile',
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
			$object_id = intval($row[SearchFields_DevblocksStorageProfile::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(devblocks_storage_profile.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_DevblocksStorageProfile extends DevblocksSearchFields {
	const ID = 'd_id';
	const NAME = 'd_name';
	const EXTENSION_ID = 'd_extension_id';
	const PARAMS_JSON = 'd_params_json';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'devblocks_storage_profile.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('devblocks_storage_profile.id', self::ID),
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
			self::ID => new DevblocksSearchField(self::ID, 'devblocks_storage_profile', 'id', $translate->_('id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'devblocks_storage_profile', 'name', $translate->_('name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'devblocks_storage_profile', 'extension_id', $translate->_('extension_id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'devblocks_storage_profile', 'params_json', $translate->_('params_json'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};
