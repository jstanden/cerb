<?php
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
			->setRequired(true)
			->addValidator(function($value, &$error=null) {
				if(false == Extension_WorkspaceTab::get($value)) {
					$error = sprintf("is not a valid workspace tab extension (%s).", $value);
					return false;
				}
				
				return true;
			})
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
			->setRequired(true)
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
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKSPACE_PAGE))
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
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
		$context = CerberusContexts::CONTEXT_WORKSPACE_TAB;
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'workspace_tab', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_tab', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, $fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_WORKSPACE_TAB;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::WORKSPACE_PAGE_ID])) {
			$error = "A 'page_id' is required.";
			return false;
		}
		
		if(isset($fields[self::WORKSPACE_PAGE_ID])) {
			@$page_id = $fields[self::WORKSPACE_PAGE_ID];
			
			if(!$page_id) {
				$error = "Invalid 'page_id' value.";
				return false;
			}
			
			if(!Context_WorkspacePage::isWriteableByActor($page_id, $actor)) {
				$error = "You do not have permission to create tabs on this workspace page.";
				return false;
			}
		}
		
		return true;
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
			'links' => '_links',
			'name' => DAO_WorkspaceTab::NAME,
			'page_id' => DAO_WorkspaceTab::WORKSPACE_PAGE_ID,
			'pos' => DAO_WorkspaceTab::POS,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'links':
				$this->_getDaoFieldsLinks($value, $out_fields, $error);
				break;
			
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
		if(false != ($defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass()))) {
			$defaults->id = $view_id;
			$defaults->is_ephemeral = true;
		}
		
		if(false == ($view = C4_AbstractViewLoader::getView($view_id, $defaults ?: null)))
			return false;
		
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