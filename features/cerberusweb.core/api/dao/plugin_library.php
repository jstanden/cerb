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

class DAO_PluginLibrary extends Cerb_ORMHelper {
	const AUTHOR = 'author';
	const DESCRIPTION = 'description';
	const ICON_URL = 'icon_url';
	const ID = 'id';
	const LATEST_VERSION = 'latest_version';
	const LINK = 'link';
	const NAME = 'name';
	const PLUGIN_ID = 'plugin_id';
	const REQUIREMENTS_JSON = 'requirements_json';
	const UPDATED = 'updated';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::AUTHOR)
			->string()
			->setMaxLength(255)
			;
		// text
		$validation
			->addField(self::DESCRIPTION)
			->string()
			->setMaxLength(65535)
			;
		// varchar(255)
		$validation
			->addField(self::ICON_URL)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::LATEST_VERSION)
			->uint(4)
			;
		// varchar(255)
		$validation
			->addField(self::LINK)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::PLUGIN_ID)
			->string()
			->setMaxLength(255)
			;
		// text
		$validation
			->addField(self::REQUIREMENTS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		@$id = $fields[self::ID];
		
		if(empty($id))
			return false;
		
		$sql = sprintf("INSERT INTO plugin_library (id) VALUES (%d)", $id);
		$db->ExecuteMaster($sql);
		
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
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, plugin_id, name, author, description, link, latest_version, icon_url, requirements_json, updated ".
			"FROM plugin_library ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_PluginLibrary	 */
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
	 * @return Model_PluginLibrary[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return array();
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function flush() {
		$db = DevblocksPlatform::services()->database();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		$db->ExecuteMaster("DELETE FROM plugin_library");
		
		if(isset($tables['fulltext_plugin_library']))
			$db->ExecuteMaster("DELETE FROM fulltext_plugin_library");
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM plugin_library WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_PluginLibrary::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_PluginLibrary', $sortBy);
		
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
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_PluginLibrary');
	
		$result = array(
			'primary_table' => 'plugin_library',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result; 
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
			$object_id = intval($row[SearchFields_PluginLibrary::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(plugin_library.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static function syncManifestsWithRepository() {
		$url = 'http://plugins.cerbweb.com/plugins/list?version=' . DevblocksPlatform::strVersionToInt(APP_VERSION);
		
		$tables = DevblocksPlatform::getDatabaseTables(true);
		
		if(!isset($tables['plugin_library']))
			return false;
		
		try {
			if(!extension_loaded("curl"))
				throw new Exception("The cURL PHP extension is not installed");
			
			$ch = DevblocksPlatform::curlInit($url);
			curl_setopt_array($ch, array(
				CURLOPT_TIMEOUT => 10,
			));
			$json_data = DevblocksPlatform::curlExec($ch, true);
			
		} catch(Exception $e) {
			return false;
		}
		
		if(false === ($plugins = json_decode($json_data, true)))
			return false;

		unset($json_data);
		
		// Clear local cache
		DAO_PluginLibrary::flush();
		
		// Import plugins to plugin_library
		if(is_array($plugins))
		foreach($plugins as $plugin) {
			$fields = array(
				DAO_PluginLibrary::ID => $plugin['seq'],
				DAO_PluginLibrary::PLUGIN_ID => $plugin['plugin_id'],
				DAO_PluginLibrary::NAME => $plugin['name'],
				DAO_PluginLibrary::AUTHOR => $plugin['author'],
				DAO_PluginLibrary::DESCRIPTION => $plugin['description'],
				DAO_PluginLibrary::LINK => $plugin['link'],
				DAO_PluginLibrary::ICON_URL => $plugin['icon_url'],
				DAO_PluginLibrary::UPDATED => $plugin['updated'],
				DAO_PluginLibrary::LATEST_VERSION => $plugin['latest_version'],
				DAO_PluginLibrary::REQUIREMENTS_JSON => $plugin['requirements_json'],
			);
			DAO_PluginLibrary::create($fields);
		}
		
		return count($plugins);
	}
	
	static function downloadUpdatedPluginsFromRepository() {
		if(!extension_loaded("curl") || false === ($count = DAO_PluginLibrary::syncManifestsWithRepository()))
			return false;
		
		$tables = DevblocksPlatform::getDatabaseTables(true);
		
		if(!isset($tables['plugin_library']))
			return false;
		
		if(false === ($plugin_library = DAO_PluginLibrary::getWhere()))
			return false;
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		$updated = 0;
		
		$plugin_library_keys = array_map(function($e) {
				return $e->plugin_id;
			},
			$plugin_library
		);
		
		asort($plugin_library_keys);
		
		$plugin_library_keys = array_flip($plugin_library_keys);
		
		// Find the library plugins we have installed that need updates
		
		if(is_array($plugin_library_keys))
		foreach($plugin_library_keys as $plugin_library_key => $plugin_library_id) {
			@$local_plugin = $plugins[$plugin_library_key]; /* @var $local_plugin DevblocksPluginManifest */
			@$remote_plugin = $plugin_library[$plugin_library_id]; /* @var $remote_plugin Model_PluginLibrary */
			
			// If not installed locally, skip it.
			
			if(empty($local_plugin)) {
				unset($plugin_library_keys[$plugin_library_key]);
				continue;
			}
			
			// If we're already on the latest version, skip it.
			
			if(intval($local_plugin->version) >= $remote_plugin->latest_version) {
				unset($plugin_library_keys[$plugin_library_key]);
				continue;
			}
			
			// If we can't meet the remote plugin's new requirements, skip it.
			
			$failed_requirements = Model_PluginLibrary::testRequirements($remote_plugin->requirements);
			if(!empty($failed_requirements)) {
				unset($plugin_library_keys[$plugin_library_key]);
				continue;
			}
		}
		
		// Auto install updated plugins
		if(is_array($plugin_library_keys))
		foreach($plugin_library_keys as $plugin_library_key => $plugin_library_id) {
			@$local_plugin = $plugins[$plugin_library_key]; /* @var $local_plugin DevblocksPluginManifest */
			@$remote_plugin = $plugin_library[$plugin_library_id]; /* @var $remote_plugin Model_PluginLibrary */

			// Don't auto-update any development plugin
			
			$plugin_path = $local_plugin->getStoragePath();
			if(file_exists($plugin_path . '/.git')) {
				continue;
			}
			
			$url = sprintf("http://plugins.cerbweb.com/plugins/download?plugin=%s&version=%d",
				urlencode($remote_plugin->plugin_id),
				$remote_plugin->latest_version
			);
			
			// Connect to portal for download URL
			$ch = DevblocksPlatform::curlInit($url);
			curl_setopt_array($ch, array(
				CURLOPT_SSL_VERIFYPEER => false,
			));
			$json_data = DevblocksPlatform::curlExec($ch, true);
			
			if(false === ($response = json_decode($json_data, true)))
				continue;
			
			@$package_url = $response['package_url'];
			
			if(empty($package_url))
				continue;
			
			$success = DevblocksPlatform::installPluginZipFromUrl($package_url);
				
			if($success) {
				$updated++;
				
				// Reload plugin translations
				$strings_xml = $local_plugin->getStoragePath() . '/strings.xml';
				if(file_exists($strings_xml)) {
					DAO_Translation::importTmxFile($strings_xml);
				}
			}
		}
		
		if($updated) {
			DevblocksPlatform::readPlugins(false);
			DevblocksPlatform::clearCache();
		}

		// Update the full-text index every time we sync
		$schema = Extension_DevblocksSearchSchema::get(Search_PluginLibrary::ID);
		$schema->reindex();
		$schema->index(time() + 30);
		
		return array(
			'count' => $count,
			'updated' => $updated,
		);
	}
	
};

class SearchFields_PluginLibrary extends DevblocksSearchFields {
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
	
	// Fulltexts
	const FULLTEXT_PLUGIN_LIBRARY = 'ft_plugin_library';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'plugin_library.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('plugin_library.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_PLUGIN_LIBRARY:
				return self::_getWhereSQLFromFulltextField($param, Search_PluginLibrary::ID, self::getPrimaryKey());
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
			case SearchFields_PluginLibrary::ID:
				$models = DAO_PluginLibrary::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'plugin_library', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::PLUGIN_ID => new DevblocksSearchField(self::PLUGIN_ID, 'plugin_library', 'plugin_id', $translate->_('dao.plugin_library.plugin_id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'plugin_library', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::AUTHOR => new DevblocksSearchField(self::AUTHOR, 'plugin_library', 'author', $translate->_('common.author'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'plugin_library', 'description', $translate->_('dao.cerb_plugin.description'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::LINK => new DevblocksSearchField(self::LINK, 'plugin_library', 'link', $translate->_('common.url'), Model_CustomField::TYPE_URL, true),
			self::LATEST_VERSION => new DevblocksSearchField(self::LATEST_VERSION, 'plugin_library', 'latest_version', $translate->_('dao.cerb_plugin.version'), null, true),
			self::ICON_URL => new DevblocksSearchField(self::ICON_URL, 'plugin_library', 'icon_url', $translate->_('dao.plugin_library.icon_url'), null, true),
			self::REQUIREMENTS_JSON => new DevblocksSearchField(self::REQUIREMENTS_JSON, 'plugin_library', 'requirements_json', $translate->_('dao.plugin_library.requirements_json'), null, false),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'plugin_library', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
				
			self::FULLTEXT_PLUGIN_LIBRARY => new DevblocksSearchField(self::FULLTEXT_PLUGIN_LIBRARY, 'ft', 'plugin_library', $translate->_('common.search.fulltext'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_PLUGIN_LIBRARY]->ft_schema = Search_PluginLibrary::ID;
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_PluginLibrary extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.plugin_library';
	
	public function getNamespace() {
		return 'plugin_library';
	}
	
	public function getAttributes() {
		return array();
	}
	
	public function getFields() {
		return array(
			'content',
		);
	}
	
	public function query($query, $attributes=array(), $limit=null) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		
		return $ids;
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the index has a delta, start from the current record
		if($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
		// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}
	
	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
				
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::services()->log();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%1$s = %2$d AND %3$s > %4$d) OR (%1$s > %2$d)',
				DAO_PluginLibrary::UPDATED,
				$ptr_time,
				DAO_PluginLibrary::ID,
				$id
			);
			$plugins = DAO_PluginLibrary::getWhere($where, array(DAO_PluginLibrary::UPDATED, DAO_PluginLibrary::ID), array(true, true), 100);

			if(empty($plugins)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			foreach($plugins as $plugin) { /* @var $plugin Model_PluginLibrary */
				$id = $plugin->id;
				$ptr_time = $plugin->updated;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array(
					'content' => implode("\n", array(
						$plugin->plugin_id,
						$plugin->name,
						$plugin->author,
						$plugin->description,
						$plugin->link,
					))
				);
				
				if(false === ($engine->index($this, $id, $doc)))
					return false;
			}
		}
		
		// If we ran out of records, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		$this->setParam('last_indexed_id', $ptr_id);
		$this->setParam('last_indexed_time', $ptr_time);
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
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
				$requirements_errors[] = 'This plugin requires a Cerb version of at least ' . DevblocksPlatform::intVersionToStr($plugin_app_version['min']) . ' and you are using ' . APP_VERSION;
			
			if($plugin_app_version['max'] < $app_version)
				$requirements_errors[] = 'This plugin was tested through Cerb version ' . DevblocksPlatform::intVersionToStr($plugin_app_version['max']) . ' and you are using ' . APP_VERSION;
			
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

class View_PluginLibrary extends C4_AbstractView implements IAbstractView_QuickSearch {
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
			SearchFields_PluginLibrary::FULLTEXT_PLUGIN_LIBRARY,
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_PluginLibrary');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_PluginLibrary', $size);
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_PluginLibrary::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::FULLTEXT_PLUGIN_LIBRARY),
				),
			'author' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::AUTHOR, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'desc' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'plugin.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::PLUGIN_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_PluginLibrary::UPDATED),
				),
			'url' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_PluginLibrary::LINK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'version' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_PluginLibrary::LATEST_VERSION),
					'examples' => array(
						'<=1.0',
						'2.0',
					),
				),
		);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_PluginLibrary::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['text']['examples'] = $ft_examples;
		
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
				$param->field = SearchFields_PluginLibrary::LATEST_VERSION;
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
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/plugin_library/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_PluginLibrary::LATEST_VERSION:
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
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_PluginLibrary::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_PluginLibrary::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_PluginLibrary::FULLTEXT_PLUGIN_LIBRARY:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};
