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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class DAO_DevblocksTemplate extends DevblocksORMHelper {
	const CONTENT = 'content';
	const ID = 'id';
	const LAST_UPDATED = 'last_updated';
	const PATH = 'path';
	const PLUGIN_ID = 'plugin_id';
	const TAG = 'tag';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// mediumtext
		$validation
			->addField(self::CONTENT)
			->string()
			->setMaxLength(16777215)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::LAST_UPDATED)
			->timestamp()
			;
		// varchar(255)
		$validation
			->addField(self::PATH)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::PLUGIN_ID)
			->string()
			->setMaxLength(255)
			;
		// varchar(255)
		$validation
			->addField(self::TAG)
			->string()
			->setMaxLength(255)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO devblocks_template () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
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
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		//$custom_fields = [];
		$deleted = false;

		if(is_array($do))
		foreach(array_keys($do) as $k) {
			switch($k) {
				case 'deleted':
					$deleted = true;
					break;
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						//$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		if(!$deleted) {
			if (!empty($change_fields))
				DAO_DevblocksTemplate::update($ids, $change_fields);
		} else {
			DAO_DevblocksTemplate::delete($ids);
		}
			
		$update->markCompleted();
		return true;
	}
	
	/**
	 * @param string $where
	 * @return Model_DevblocksTemplate[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, plugin_id, path, tag, last_updated, content ".
			"FROM devblocks_template ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_DevblocksTemplate
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
	 * @param array $ids
	 * @return Model_DevblocksTemplate
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			return false;
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return array();
		
		$objects = self::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));
		
		return $objects;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_DevblocksTemplate[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		$tpl = DevblocksPlatform::services()->template();
		$tpl_sandbox = DevblocksPlatform::getTemplateSandboxService();
		
		if(empty($ids))
			return;
		
		// Load the template models before deleting them
		$templates = DAO_DevblocksTemplate::getIds($ids);

		// Delete from database
		$ids_list = implode(',', $ids);
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_template WHERE id IN (%s)", $ids_list));
		
		// Clear templates_c compile cache with the models
		foreach($templates as $template) { /* @var $template Model_DevblocksTemplate */
			$hash_key = sprintf("devblocks:%s:%s:%s", $template->plugin_id, $template->tag, $template->path);
			$tpl->clearCompiledTemplate($hash_key, APP_BUILD);
			$tpl_sandbox->clearCompiledTemplate($hash_key, null);
		}
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksTemplate::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DevblocksTemplate', $sortBy);
		
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
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DevblocksTemplate');
		
		$result = array(
			'primary_table' => 'devblocks_template',
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
			SearchFields_DevblocksTemplate::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	static function importXmlFile($filename, $tag) {
		$db = DevblocksPlatform::services()->database();
		$tpl = DevblocksPlatform::services()->template();
		$tpl_sandbox = DevblocksPlatform::services()->templateSandbox();

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
			
			$hash_key = sprintf("devblocks:%s:%s:%s", $plugin_id, $tag, $path);
			$tpl->clearCompiledTemplate($hash_key, APP_BUILD);
			$tpl_sandbox->clearCompiledTemplate($hash_key, null);
		}
		
		unset($xml);
	}
};

class Model_DevblocksTemplate {
	public $id;
	public $plugin_id;
	public $path;
	public $tag;
	public $last_updated;
	public $content;
	
	function getDefaultContent() {
		// Pull from filesystem for editing
		$content = '';
		if(null != ($plugin = DevblocksPlatform::getPlugin($this->plugin_id))) {
			$path = $plugin->getStoragePath() . '/templates/' . $this->path;
			if(file_exists($path)) {
				$content = file_get_contents($path);
			}
		}
		return $content;
	}
};

class SearchFields_DevblocksTemplate extends DevblocksSearchFields {
	const ID = 'd_id';
	const PLUGIN_ID = 'd_plugin_id';
	const PATH = 'd_path';
	const TAG = 'd_tag';
	const LAST_UPDATED = 'd_last_updated';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'devblocks_template.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('devblocks_template.id', self::ID),
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
			case SearchFields_DevblocksTemplate::ID:
				$models = DAO_DevblocksTemplate::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'devblocks_template', 'id', $translate->_('common.id'), null, true),
			self::PLUGIN_ID => new DevblocksSearchField(self::PLUGIN_ID, 'devblocks_template', 'plugin_id', $translate->_('Plugin'), null, true),
			self::PATH => new DevblocksSearchField(self::PATH, 'devblocks_template', 'path', $translate->_('path'), null, true),
			self::TAG => new DevblocksSearchField(self::TAG, 'devblocks_template', 'tag', $translate->_('tag'), null, true),
			self::LAST_UPDATED => new DevblocksSearchField(self::LAST_UPDATED, 'devblocks_template', 'last_updated', $translate->_('common.updated'), null, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

if(class_exists('C4_AbstractView')):
class View_DevblocksTemplate extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'templates';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Templates';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DevblocksTemplate::PATH;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_DevblocksTemplate::PLUGIN_ID,
//			SearchFields_DevblocksTemplate::TAG,
			SearchFields_DevblocksTemplate::LAST_UPDATED,
		);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_DevblocksTemplate::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_DevblocksTemplate');
		
		return $objects;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_DevblocksTemplate::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksTemplate::PATH, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_DevblocksTemplate::ID),
				),
			'path' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksTemplate::PATH, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'plugin' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksTemplate::PLUGIN_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
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
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_DevblocksTemplate::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			// String
			case SearchFields_DevblocksTemplate::PATH:
			case SearchFields_DevblocksTemplate::PLUGIN_ID:
			case SearchFields_DevblocksTemplate::TAG:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			// Date
			case SearchFields_DevblocksTemplate::LAST_UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			// Number
			case SearchFields_DevblocksTemplate::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
};
endif;