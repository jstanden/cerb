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

class DAO_WorkspacePage extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	
	const _CACHE_ALL = 'ch_workspace_pages';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			;
		// int(10) unsigned
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();

		$sql = "INSERT INTO workspace_page () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_page', $fields);
		self::clearCache();
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_page', $fields, $where);
		self::clearCache();
	}

	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($pages = $cache->load(self::_CACHE_ALL))) {
			$pages = self::getWhere(
				null,
				DAO_WorkspacePage::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($pages))
				return false;
			
			$cache->save($pages, self::_CACHE_ALL);
		}
		
		return $pages;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspacePage[]
	 */
	static function getWhere($where=null, $sortBy=DAO_WorkspacePage::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, extension_id ".
			"FROM workspace_page ".
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

	static function getByOwner($context, $context_id, $sortBy=null, $sortAsc=true, $limit=null) {
		$pages = array();
		
		$all_pages = self::getAll();
		foreach($all_pages as $page_id => $page) { /* @var $page Model_WorkspacePage */
			if($page->owner_context == $context
				&& $page->owner_context_id == $context_id) {
				
				$pages[$page_id] = $page;
			}
		}

		return $pages;
	}

	static function getByWorker($worker) {
		if(is_a($worker,'Model_Worker')) {
			// This is what we want
		} elseif(is_numeric($worker)) {
			$worker = DAO_Worker::get($worker);
		} else {
			return array();
		}

		$memberships = $worker->getMemberships();
		$roles = $worker->getRoles();
		
		$pages = array();
		$all_pages = self::getAll();
		
		foreach($all_pages as $page_id => $page) { /* @var $page Model_WorkspacePage */
			switch($page->owner_context) {
				case CerberusContexts::CONTEXT_ROLE:
					if(isset($roles[$page->owner_context_id]))
						$pages[$page_id] = $page;
					break;
					
				case CerberusContexts::CONTEXT_GROUP:
					if(isset($memberships[$page->owner_context_id]))
						$pages[$page_id] = $page;
					break;
					
				case CerberusContexts::CONTEXT_WORKER:
					if($worker->id == $page->owner_context_id)
						$pages[$page_id] = $page;
					break;
			}
		}

		return $pages;
	}
	
	static function getUsers($page_id) {
		$results = array();
		
		if(false == ($instances = DAO_WorkerPref::getByKey('menu_json')) || !is_array($instances) || empty($instances))
			return array();
		
		foreach($instances as $worker_id => $instance) {
			if(false == ($menu = json_decode($instance)))
				continue;
			
			if(in_array($page_id, $menu))
				$results[] = $worker_id;
		}
		
		return $results;
	}

	/**
	 * @param integer $id
	 * @return Model_WorkspacePage
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	/**
	 * @param resource $rs
	 * @return Model_WorkspacePage[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspacePage();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->extension_id = $row['extension_id'];
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function deleteByOwner($owner_context, $owner_context_ids) {
		if(!is_array($owner_context_ids))
			$owner_context_ids = array($owner_context_ids);

		foreach($owner_context_ids as $owner_context_id) {
			$pages = DAO_WorkspacePage::getByOwner($owner_context, $owner_context_id);
			DAO_WorkspacePage::delete(array_keys($pages));
		}
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();

		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		// Cascade delete tabs and lists
		DAO_WorkspaceTab::deleteByPage($ids);
		
		// Delete pages
		$db->ExecuteMaster(sprintf("DELETE FROM workspace_page WHERE id IN (%s)", $ids_list));

		self::clearCache();
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspacePage::getFields();

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_WorkspacePage', $sortBy);

		$select_sql = sprintf("SELECT ".
			"workspace_page.id as %s, ".
			"workspace_page.name as %s, ".
			"workspace_page.owner_context as %s, ".
			"workspace_page.owner_context_id as %s, ".
			"workspace_page.extension_id as %s ",
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::NAME,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID,
			SearchFields_WorkspacePage::EXTENSION_ID
		);
			
		$join_sql = "FROM workspace_page ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WorkspacePage');

		return array(
			'primary_table' => 'workspace_page',
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

		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
		
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
			$object_id = intval($row[SearchFields_WorkspacePage::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(workspace_page.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}

		mysqli_free_result($rs);

		return array($results,$total);
	}

	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();

		$db->ExecuteMaster("DELETE FROM workspace_tab WHERE workspace_page_id NOT IN (SELECT id FROM workspace_page)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' workspace_tab records.');
	}

	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
};

class DAO_WorkspaceTab extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';
	const WORKSPACE_PAGE_ID = 'workspace_page_id';
	
	const _CACHE_ALL = 'ch_workspace_tabs';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(128)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			;
		// text
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::POS)
			->uint(1)
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKSPACE_PAGE_ID)
			->id()
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO workspace_tab () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_tab', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_tab', $fields, $where);
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($tabs = $cache->load(self::_CACHE_ALL))) {
			$tabs = self::getWhere(
				null,
				DAO_WorkspaceTab::POS,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($tabs))
				return false;
			
			$cache->save($tabs, self::_CACHE_ALL);
		}
		
		return $tabs;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspaceTab[]
	 */
	static function getWhere($where=null, $sortBy=DAO_WorkspaceTab::POS, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, workspace_page_id, pos, extension_id, params_json ".
			"FROM workspace_tab ".
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
	 * @param integer $id
	 * @return Model_WorkspaceTab
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByPage($page_id) {
		$all_tabs = self::getAll();
		$tabs = array();
		
		foreach($all_tabs as $tab_id => $tab) { /* @var $tab Model_WorkspaceTab */
			if($tab->workspace_page_id == $page_id)
				$tabs[$tab_id] = $tab;
		}

		return $tabs;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WorkspaceTab[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspaceTab();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->workspace_page_id = $row['workspace_page_id'];
			$object->pos = $row['pos'];
			$object->extension_id = $row['extension_id'];
			
			if(!empty($row['params_json']) && false !== ($params = json_decode($row['params_json'], true)))
				@$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('workspace_tab');
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		DAO_WorkspaceWidget::deleteByTab($ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM workspace_list WHERE workspace_tab_id IN (%s)", $ids_list));
		
		$db->ExecuteMaster(sprintf("DELETE FROM workspace_tab WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	static function deleteByPage($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Find tab IDs by given page IDs
		$rows = $db->GetArrayMaster(sprintf("SELECT id FROM workspace_tab WHERE workspace_page_id IN (%s)", $ids_list));

		// Loop tab IDs and delete
		if(is_array($rows))
		foreach($rows as $row)
			self::delete($row['id']);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspaceTab::getFields();
		
		list($tables, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Workspace', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_tab.id as %s, ".
			"workspace_tab.name as %s, ".
			"workspace_tab.workspace_page_id as %s, ".
			"workspace_tab.pos as %s, ".
			"workspace_tab.extension_id as %s ",
				SearchFields_WorkspaceTab::ID,
				SearchFields_WorkspaceTab::NAME,
				SearchFields_WorkspaceTab::WORKSPACE_PAGE_ID,
				SearchFields_WorkspaceTab::POS,
				SearchFields_WorkspaceTab::EXTENSION_ID
			);
			
		$join_sql = "FROM workspace_tab ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WorkspaceTab');
	
		return array(
			'primary_table' => 'workspace_tab',
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
			$object_id = intval($row[SearchFields_WorkspaceTab::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(workspace_tab.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$db->ExecuteMaster("DELETE FROM workspace_list WHERE workspace_tab_id NOT IN (SELECT id FROM workspace_tab)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' workspace_list records.');
	}

	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
};

class SearchFields_WorkspacePage extends DevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const OWNER_CONTEXT = 'w_owner_context';
	const OWNER_CONTEXT_ID = 'w_owner_context_id';
	const EXTENSION_ID = 'w_extension_id';
	
	const VIRTUAL_OWNER = '*_owner';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'workspace_page.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WORKSPACE_PAGE => new DevblocksSearchFieldContextKeys('workspace_page.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'workspace_page.owner_context', 'workspace_page.owner_context_id');
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
			self::ID => new DevblocksSearchField(self::ID, 'workspace_page', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'workspace_page', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'workspace_page', 'owner_context', null, null, false),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'workspace_page', 'owner_context_id', null, null, false),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'workspace_page', 'extension_id', null, null, true),
				
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class SearchFields_WorkspaceTab extends DevblocksSearchFields {
	const ID = 'w_id';
	const NAME = 'w_name';
	const WORKSPACE_PAGE_ID = 'w_workspace_page_id';
	const POS = 'w_pos';
	const EXTENSION_ID = 'w_extension_id';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'workspace_tab.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WORKSPACE_TAB => new DevblocksSearchFieldContextKeys('workspace_tab.id', self::ID),
			CerberusContexts::CONTEXT_WORKSPACE_PAGE => new DevblocksSearchFieldContextKeys('workspace_tab.workspace_page_id', self::WORKSPACE_PAGE_ID),
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
			self::ID => new DevblocksSearchField(self::ID, 'workspace_tab', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'workspace_tab', 'name', $translate->_('common.name'), null, true),
			self::WORKSPACE_PAGE_ID => new DevblocksSearchField(self::WORKSPACE_PAGE_ID, 'workspace_tab', 'workspace_page_id', null, null, true),
			self::POS => new DevblocksSearchField(self::POS, 'workspace_tab', 'pos', null, null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'workspace_tab', 'extension_id', null, null, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_WorkspacePage {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $extension_id;
	
	function getExtension() {
		$extension = Extension_WorkspacePage::get($this->extension_id);
		return $extension;
	}
	
	/**
	 *
	 * @param Model_Worker $as_worker
	 * @return Model_WorkspaceTab[]
	 */
	function getTabs(Model_Worker $as_worker=null) {
		$tabs = DAO_WorkspaceTab::getByPage($this->id);
		
		// Order by given worker prefs
		if(!empty($as_worker)) {
			$available_tabs = $tabs;
			$tabs = array();
			
			// Do we have prefs?
			@$json = DAO_WorkerPref::get($as_worker->id, 'page_tabs_' . $this->id . '_json', null);
			$tab_ids = json_decode($json);
			
			if(!is_array($tab_ids) || empty($json))
				return $available_tabs;
			
			// Sort tabs by the worker's preferences
			foreach($tab_ids as $tab_id) {
				if(isset($available_tabs[$tab_id])) {
					$tabs[$tab_id] = $available_tabs[$tab_id];
					unset($available_tabs[$tab_id]);
				}
			}

			// Add anything left to the end that the worker didn't explicitly sort
			if(!empty($available_tabs))
				$tabs += $available_tabs;
		}
		
		return $tabs;
	}
	
	function getUsers() {
		return DAO_WorkspacePage::getUsers($this->id);
	}
};

class Model_WorkspaceTab {
	public $id;
	public $name;
	public $workspace_page_id;
	public $pos;
	public $extension_id;
	public $params=array();
	
	/**
	 * @return Model_WorkspacePage
	 */
	function getWorkspacePage() {
		return DAO_WorkspacePage::get($this->workspace_page_id);
	}
	
	/**
	 * @return Extension_WorkspaceTab
	 */
	function getExtension() {
		$extension_id = $this->extension_id;
		
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true)))
			return $extension;
		
		return null;
	}
	
	/**
	 * @return Model_WorkspaceList[]
	 */
	function getWorklists() {
		return DAO_WorkspaceList::getByTab($this->id);
	}
};

class DAO_WorkspaceList extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const ID = 'id';
	const LIST_POS = 'list_pos';
	const LIST_VIEW = 'list_view';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::CONTEXT)
			->context()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// smallint(5) unsigned
		$validation
			->addField(self::LIST_POS)
			->uint(2)
			;
		// mediumtext
		$validation
			->addField(self::LIST_VIEW)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKSPACE_TAB_ID)
			->id()
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO workspace_list () ".
			"VALUES ()"
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_WorkspaceList
	 */
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
	 * @param string $where
	 * @return Model_WorkspaceList[]
	 */
	static function getWhere($where) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, workspace_tab_id, context, list_view, list_pos ".
			"FROM workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ").
			"ORDER BY list_pos ASC";
		
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;

		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspaceList();
			$object->id = intval($row['id']);
			$object->workspace_tab_id = intval($row['workspace_tab_id']);
			$object->context = $row['context'];
			$object->list_pos = intval($row['list_pos']);
			
			$list_view = $row['list_view'];
			if(!empty($list_view)) {
				@$object->list_view = unserialize($list_view);
			}
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function getByTab($tab_id) {
		return DAO_WorkspaceList::getWhere(sprintf("%s = %d",
			DAO_WorkspaceList::WORKSPACE_TAB_ID,
			$tab_id,
			DAO_WorkspaceList::LIST_POS
		));
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_list', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_list', $fields, $where);
	}
	
	static function random() {
		return self::_getRandom('workspace_list');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::services()->database();
		$ids_list = implode(',', $ids);
		
		if(false == ($db->ExecuteMaster(sprintf("DELETE FROM workspace_list WHERE id IN (%s)", $ids_list))))
			return false;
		
		// Delete worker view prefs
		foreach($ids as $id) {
			$db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE view_id = 'cust_%d'", $id));
		}
	}
};

class Model_WorkspaceList {
	public $id = 0;
	public $workspace_tab_id = 0;
	public $context = '';
	public $list_view = '';
	public $list_pos = 0;
};

class Model_WorkspaceListView {
	public $title = 'New List';
	public $options = array();
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
	public $params_required = array();
	public $sort_by = null;
	public $sort_asc = 1;
	public $subtotals = '';
};

class View_WorkspacePage extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'workspace_page';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Pages');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WorkspacePage::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WorkspacePage::NAME,
			SearchFields_WorkspacePage::VIRTUAL_OWNER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID,
		));

		$this->addParamsHidden(array(
			SearchFields_WorkspacePage::ID,
			SearchFields_WorkspacePage::OWNER_CONTEXT,
			SearchFields_WorkspacePage::OWNER_CONTEXT_ID,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WorkspacePage::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_WorkspacePage');
		
		return $objects;
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WorkspacePage', $size);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_WorkspacePage::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WorkspacePage::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WorkspacePage::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WorkspacePage::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add 'owner.*'
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
				if($field == 'owner' || DevblocksPlatform::strStartsWith($field, 'owner.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_WorkspacePage::VIRTUAL_OWNER);
				
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

		$tpl->display('devblocks:cerberusweb.core::pages/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WorkspacePage::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;

			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner matches');
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
		return SearchFields_WorkspacePage::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkspacePage::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_WorkspacePage::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_WorkspacePage extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_PAGE, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_PAGE, $models);
	}
	
	function getRandom() {
		return DAO_WorkspacePage::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($workspace_page = DAO_WorkspacePage::get($context_id)))
			return [];
		
		$url = $url_writer->write(sprintf("c=pages&id=%d",
			$workspace_page->id
		));
		
		//$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($workspace_page->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $workspace_page->id,
			'name' => $workspace_page->name,
			'permalink' => $url,
			'updated' => 0, // [TODO]
		];
	}
	
	function getContext($page, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Page:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE);
		
		// Polymorph
		if(is_numeric($page)) {
			$page = DAO_WorkspacePage::get($page);
		} elseif($page instanceof Model_WorkspacePage) {
			// It's what we want already.
		} elseif(is_array($page)) {
			$page = Cerb_ORMHelper::recastArrayToModel($page, 'Model_WorkspacePage');
		} else {
			$page = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'name' => $prefix.$translate->_('common.name'),
			'owner_context' => $prefix.$translate->_('common.context'),
			'owner_context_id' => $prefix.$translate->_('common.context_id'),
			'extension_id' => $prefix.$translate->_('common.extension'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner_context' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner_context_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKSPACE_PAGE;
		$token_values['_types'] = $token_types;

		// Token values
		if(null != $page) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $page->name;
			$token_values['id'] = $page->id;
			$token_values['name'] = $page->name;
			$token_values['extension_id'] = $page->extension_id;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($page, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=pages&id=%d-%s",$page->id, DevblocksPlatform::strToPermalink($page->name)), true);
			
			$token_values['owner__context'] = $page->owner_context;
			$token_values['owner_id'] = $page->owner_context_id;
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'extension_id' => DAO_WorkspacePage::EXTENSION_ID,
			'id' => DAO_WorkspacePage::ID,
			'name' => DAO_WorkspacePage::NAME,
			'owner__context' => DAO_WorkspacePage::OWNER_CONTEXT,
			'owner_id' => DAO_WorkspacePage::OWNER_CONTEXT_ID,
		];
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_PAGE;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'tabs':
				$tabs = DAO_WorkspaceTab::getByPage($context_id);
				$values['tabs'] = array();
				
				foreach(array_keys($tabs) as $tab_id) {
					$tab_labels = array();
					$tab_values = array();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, $tab_id, $tab_labels, $tab_values, null, true);
					$values['tabs'][] = $tab_values;
				}
				break;
				
			case 'widgets':
				$values = $dictionary;
				
				if(!isset($values['tabs']))
					$values = self::lazyLoadContextValues('tabs', $values);
				
				if(!is_array($values['tabs']))
					break;
				
				$context_tab = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKSPACE_TAB); /* @var $context_widget Context_WorkspaceTab */
				
				// Send the lazy load request to the tab itself
				foreach($values['tabs'] as $idx => $tab) {
					$values['tabs'][$idx] = $context_tab->lazyLoadContextValues('widgets', $values['tabs'][$idx]);
				}
				break;
				
			case 'worklists':
				$values = $dictionary;

				if(!isset($values['tabs']))
					$values = self::lazyLoadContextValues('tabs', $values);
				
				if(!is_array($values['tabs']))
					break;
				
				$context_tab = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKSPACE_TAB); /* @var $context_widget Context_WorkspaceTab */
				
				// Send the lazy load request to the tab itself
				foreach($values['tabs'] as $idx => $tab) {
					$values['tabs'][$idx] = $context_tab->lazyLoadContextValues('worklists', $values['tabs'][$idx]);
				}
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
		
		$active_worker = CerberusApplication::getActiveWorker();
			
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Pages';
		
		$params_req = array();
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getMemberships());
			$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$params = $view->getParamsFromQuickSearch(sprintf('(owner.app:cerb OR owner.worker:(id:[%d]) OR owner.group:(id:[%s]) OR owner.role:(id:[%s])',
				$active_worker->id,
				implode(',', $worker_group_ids),
				implode(',', $worker_role_ids)
			));
			
			$params_req['_ownership'] = $params[0];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderSortBy = SearchFields_WorkspacePage::ID;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Pages';
		
		$params_req = array();
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getMemberships());
			$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$params = $view->getParamsFromQuickSearch(sprintf('(owner.app:cerb OR owner.worker:(id:[%d]) OR owner.group:(id:[%s]) OR owner.role:(id:[%s])',
				$active_worker->id,
				implode(',', $worker_group_ids),
				implode(',', $worker_role_ids)
			));
			
			$params_req['_ownership'] = $params[0];
		}
		
		$view->renderTemplate = 'context';
		return $view;
	}
};

class Context_WorkspaceTab extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_TAB, $models, 'page_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_TAB, $models, 'page_owner_');
	}
	
	function getRandom() {
		return DAO_WorkspaceTab::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($workspace_tab = DAO_WorkspaceTab::get($context_id)))
			return array();
		
		$url = $url_writer(sprintf("c=pages&id=%d",
			$workspace_tab->workspace_page_id
		));
		
		return array(
			'id' => $workspace_tab->id,
			'name' => $workspace_tab->name,
			'permalink' => $url,
			'updated' => 0, // [TODO]
		);
	}
	
	function getContext($tab, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Tab:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_TAB);
		
		// Polymorph
		if(is_numeric($tab)) {
			$tab = DAO_WorkspaceTab::get($tab);
		} elseif($tab instanceof Model_WorkspaceTab) {
			// It's what we want already.
		} elseif(is_array($tab)) {
			$tab = Cerb_ORMHelper::recastArrayToModel($tab, 'Model_WorkspaceTab');
		} else {
			$tab = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKSPACE_TAB;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $tab) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $tab->name;
			$token_values['id'] = $tab->id;
			$token_values['name'] = $tab->name;
			$token_values['extension_id'] = $tab->extension_id;
			$token_values['page_id'] = $tab->workspace_page_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($tab, $token_values);
		}
		
		// Page
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE, null, $merge_token_labels, $merge_token_values, '', true);
		
		CerberusContexts::merge(
			'page_',
			$prefix.'Page:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'extension_id' => DAO_WorkspaceTab::EXTENSION_ID,
			'id' => DAO_WorkspaceTab::ID,
			'name' => DAO_WorkspaceTab::NAME,
			'page_id' => DAO_WorkspaceTab::WORKSPACE_PAGE_ID,
			'pos' => DAO_WorkspaceTab::POS,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_WorkspaceTab::PARAMS_JSON] = $json;
				break;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_TAB;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			case 'widgets':
				$values = $dictionary;

				if(!isset($values['widgets']))
					$values['widgets'] = array();
				
				$widgets = DAO_WorkspaceWidget::getByTab($context_id);

				if(is_array($widgets))
				foreach($widgets as $widget) { /* @var $widget Model_WorkspaceWidget */
					$widget_labels = array();
					$widget_values = array();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $widget, $widget_labels, $widget_values, null, true);
					$values['widgets'][] = $widget_values;
				}
				break;
			
			case 'widgets_data':
				$values = $dictionary;
				
				if(!isset($values['widgets']))
					$values = self::lazyLoadContextValues('widgets', $values);
				
				if(!isset($values['widgets']))
					break;
				
				$widgets = DAO_WorkspaceWidget::getByTab($context_id);
				
				if(is_array($values['widgets']))
				foreach($values['widgets'] as $k => $widget) {
					if(!isset($widgets[$widget['id']]))
						continue;
				
					$widget_ext = Extension_WorkspaceWidget::get($widget['extension_id']);
					
					$values['widgets'][$k]['data'] = false;
					
					if(!($widget_ext instanceof ICerbWorkspaceWidget_ExportData))
						continue;
					
					@$json = json_decode($widget_ext->exportData($widgets[$widget['id']], 'json'), true);

					if(!is_array($json))
						continue;
					
					// Remove redundant data
					if(isset($json['widget'])) {
						unset($json['widget']['label']);
						unset($json['widget']['version']);
					}
					
					$values['widgets'][$k]['data'] = isset($json['widget']) ? $json['widget'] : $json;
				}
				break;
				
			case 'worklists':
				$values = $dictionary;

				if(!isset($values['worklists']))
					$values['worklists'] = array();
				
				$worklists = DAO_WorkspaceList::getByTab($context_id);

				if(is_array($worklists))
				foreach($worklists as $worklist) { /* @var $worklist Model_WorkspaceList */
					if(empty($worklist->list_view))
						continue;
					
					$values['worklists'][] = array(
						'id' => $worklist->id,
						'title' => $worklist->list_view->title,
						'context' => $worklist->context,
					);
				}
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
		$view->name = 'Tabs';
		
		$view->renderSortBy = SearchFields_WorkspaceTab::ID;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tabs';
		
		$params_req = array();
		
		/*
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WorkspacePage::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_WorkspacePage::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		*/
		
		$view->renderTemplate = 'context';
		return $view;
	}
};

class Context_WorkspaceWorklist extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	function getRandom() {
		return DAO_WorkspaceList::random();
	}
	
	function getDaoClass() {
		return 'DAO_WorkspaceList';
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($workspace_list = DAO_WorkspaceList::get($context_id)))
			return array();
		
		return array(
			'id' => $workspace_list->id,
			'name' => 'Worklist', //$workspace_list->label, // [TODO]
			'permalink' => null,
			'updated' => 0, //$workspace_list->updated, // [TODO]
		);
	}
	
	function getContext($worklist, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Worklist:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST);
		
		// Polymorph
		if(is_numeric($worklist)) {
			$worklist = DAO_WorkspaceList::get($worklist);
		} elseif($worklist instanceof Model_WorkspaceList) {
			// It's what we want already.
		} elseif(is_array($worklist)) {
			$worklist = Cerb_ORMHelper::recastArrayToModel($worklist, 'Model_WorkspaceWorklist');
		} else {
			$worklist = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'context' => $prefix.$translate->_('common.context'),
			'id' => $prefix.$translate->_('common.id'),
			'pos' => $prefix.$translate->_('common.pos'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'pos' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $worklist) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = 'Worklist'; //$worklist->name;
			$token_values['context'] = $worklist->context;
			$token_values['id'] = $worklist->id;
			$token_values['pos'] = $worklist->list_pos;
			$token_values['tab_id'] = $worklist->workspace_tab_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($worklist, $token_values);
		}
		
		// Tab
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'tab_',
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
			'context' => DAO_WorkspaceList::CONTEXT,
			'id' => DAO_WorkspaceList::ID,
			'pos' => DAO_WorkspaceList::LIST_POS,
			'tab_id' => DAO_WorkspaceList::WORKSPACE_TAB_ID,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'view':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(!isset($value['title'])) {
					$error = "is missing the 'title' key.";
					return false;
				}
				
				if(!isset($value['model'])) {
					$error = "is missing the 'model' key.";
					return false;
				}
				
				if(false == (@$view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($value['model'], ''))) {
					$error = 'is not a valid worklist.';
					return false;
				}
				
				// Build the list model
				$list = new Model_WorkspaceListView();
				$list->title = $value['title'];
				$list->options = $view->options;
				$list->columns = $view->view_columns;
				$list->params = $view->getEditableParams();
				$list->params_required = $view->getParamsRequired();
				$list->num_rows = $view->renderLimit;
				$list->sort_by = $view->renderSortBy;
				$list->sort_asc = $view->renderSortAsc;
				$list->subtotals = $view->renderSubtotals;

				// [TODO] Nasty serialization
				$out_fields[DAO_WorkspaceList::LIST_VIEW] = serialize($list);
				break;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, false);
		}
		
		switch($token) {
			/*
			case 'data':
				$values = $dictionary;
				
				if(null == ($widget = DAO_WorkspaceWidget::get($context_id)))
					break;
				
				$widget_ext = Extension_WorkspaceWidget::get($dictionary['extension_id']);

				$values['data'] = false;
				
				if(!($widget_ext instanceof ICerbWorkspaceWidget_ExportData))
					break;

				$json = json_decode($widget_ext->exportData($widget, 'json'), true);

				if(!is_array($json))
					break;
				
				// Remove redundant data
				if(isset($json['widget'])) {
					unset($json['widget']['label']);
					unset($json['widget']['version']);
				}
				
				$values['data'] = isset($json['widget']) ? $json['widget'] : $json;
				break;
			*/
				
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
		$view->name = 'Worklists';
		//$view->renderSortBy = SearchFields_WorkspaceList::ID; // [TODO]
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Worklists';
		
		$params_req = array();
		
		/*
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WorkspaceList::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_WorkspaceList::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		*/
		
		$view->renderTemplate = 'context';
		return $view;
	}
};
