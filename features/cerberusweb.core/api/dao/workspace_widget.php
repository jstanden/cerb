<?php
/************************************************************************
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

class DAO_WorkspaceWidget extends Cerb_ORMHelper {
	const CACHE_TTL = 'cache_ttl';
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const LABEL = 'label';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';
	const UPDATED_AT = 'updated_at';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// mediumint(8) unsigned
		$validation
			->addField(self::CACHE_TTL)
			->uint(3)
			;
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
			->addField(self::LABEL)
			->string()
			->setMaxLength(255)
			;
		// text
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// char(4)
		$validation
			->addField(self::POS)
			->string()
			->setMaxLength(4)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
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
		
		$sql = "INSERT INTO workspace_widget () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $option_bits = 0) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Make changes
			parent::_update($batch_ids, 'workspace_widget', $fields, 'id', $option_bits);
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_widget', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspaceWidget[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, extension_id, workspace_tab_id, label, updated_at, params_json, pos, cache_ttl ".
			"FROM workspace_widget ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkspaceWidget
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		// [TODO] Pull from cache
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getByTab($tab_id) {
		return self::getWhere(sprintf("%s = %d",
				self::WORKSPACE_TAB_ID,
				$tab_id
			),
			DAO_WorkspaceWidget::POS,
			true
		);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WorkspaceWidget[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspaceWidget();
			$object->id = intval($row['id']);
			$object->extension_id = $row['extension_id'];
			$object->workspace_tab_id = $row['workspace_tab_id'];
			$object->label = $row['label'];
			$object->updated_at = intval($row['updated_at']);
			$object->pos = $row['pos'];
			$object->cache_ttl = intval($row['cache_ttl']);
			
			if(false != ($params = @json_decode($row['params_json'], true)))
				$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('workspace_widget');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM workspace_widget WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
		$eventMgr = DevblocksPlatform::services()->event();
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
	
	static function deleteByTab($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM workspace_widget WHERE workspace_tab_id IN (%s)", $ids_list));
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspaceWidget::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_WorkspaceWidget', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_widget.id as %s, ".
			"workspace_widget.extension_id as %s, ".
			"workspace_widget.workspace_tab_id as %s, ".
			"workspace_widget.label as %s, ".
			"workspace_widget.updated_at as %s, ".
			"workspace_widget.params_json as %s, ".
			"workspace_widget.pos as %s, ".
			"workspace_widget.cache_ttl as %s ",
				SearchFields_WorkspaceWidget::ID,
				SearchFields_WorkspaceWidget::EXTENSION_ID,
				SearchFields_WorkspaceWidget::WORKSPACE_TAB_ID,
				SearchFields_WorkspaceWidget::LABEL,
				SearchFields_WorkspaceWidget::UPDATED_AT,
				SearchFields_WorkspaceWidget::PARAMS_JSON,
				SearchFields_WorkspaceWidget::POS,
				SearchFields_WorkspaceWidget::CACHE_TTL
			);
			
		$join_sql = "FROM workspace_widget ";
		
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WorkspaceWidget');
	
		return array(
			'primary_table' => 'workspace_widget',
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
			$object_id = intval($row[SearchFields_WorkspaceWidget::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(workspace_widget.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_WorkspaceWidget extends DevblocksSearchFields {
	const ID = 'w_id';
	const EXTENSION_ID = 'w_extension_id';
	const WORKSPACE_TAB_ID = 'w_workspace_tab_id';
	const LABEL = 'w_label';
	const UPDATED_AT = 'w_updated_at';
	const PARAMS_JSON = 'w_params_json';
	const POS = 'w_pos';
	const CACHE_TTL = 'w_cache_ttl';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'workspace_widget.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WORKSPACE_WIDGET => new DevblocksSearchFieldContextKeys('workspace_widget.id', self::ID),
			CerberusContexts::CONTEXT_WORKSPACE_TAB => new DevblocksSearchFieldContextKeys('workspace_widget.workspace_tab_id', self::WORKSPACE_TAB_ID),
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
			self::ID => new DevblocksSearchField(self::ID, 'workspace_widget', 'id', $translate->_('common.id'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'workspace_widget', 'extension_id', $translate->_('common.extension'), null, true),
			self::WORKSPACE_TAB_ID => new DevblocksSearchField(self::WORKSPACE_TAB_ID, 'workspace_widget', 'workspace_tab_id', null, null, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'workspace_widget', 'label', $translate->_('common.label'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'workspace_widget', 'updated_at', $translate->_('common.updated'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'workspace_widget', 'params_json', $translate->_('common.params'), null, false),
			self::POS => new DevblocksSearchField(self::POS, 'workspace_widget', 'pos', null, null, true),
			self::CACHE_TTL => new DevblocksSearchField(self::CACHE_TL, 'workspace_widget', 'cache_ttl', null, Model_CustomField::TYPE_NUMBER, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_WorkspaceWidget {
	public $id = 0;
	public $extension_id = '';
	public $workspace_tab_id = 0;
	public $label = '';
	public $updated_at = 0;
	public $pos = '0000';
	public $cache_ttl = 60;
	public $params = array();
	
	function getWorkspaceTab() {
		return DAO_WorkspaceTab::get($this->workspace_tab_id);
	}
	
	function getWorkspacePage() {
		if(false == ($tab = $this->getWorkspaceTab()))
			return;
		
		return $tab->getWorkspacePage();
	}
};

class Context_WorkspaceWidget extends Extension_DevblocksContext {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $models, 'tab_page_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $models, 'tab_page_owner_');
	}
	
	function getRandom() {
		return DAO_WorkspaceTab::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::services()->url();

		if(null == ($workspace_widget = DAO_WorkspaceWidget::get($context_id)))
			return array();
		
		/*
		$url = $url_writer->write(sprintf("c=pages&id=%d",
			$workspace_tab->workspace_page_id
		));
		*/
		
		return array(
			'id' => $workspace_widget->id,
			'name' => $workspace_widget->label,
			'permalink' => null,
			'updated' => $workspace_widget->updated_at,
		);
	}
	
	function getContext($widget, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Widget:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET);
		
		// Polymorph
		if(is_numeric($widget)) {
			$widget = DAO_WorkspaceWidget::get($widget);
		} elseif($widget instanceof Model_WorkspaceWidget) {
			// It's what we want already.
		} elseif(is_array($widget)) {
			$widget = Cerb_ORMHelper::recastArrayToModel($widget, 'Model_WorkspaceWidget');
		} else {
			$widget = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'label' => $prefix.$translate->_('common.label'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKSPACE_WIDGET;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $widget) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $widget->label;
			$token_values['id'] = $widget->id;
			$token_values['extension_id'] = $widget->extension_id;
			$token_values['label'] = $widget->label;
			
			$token_values['tab_id'] = $widget->workspace_tab_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($widget, $token_values);
		}
		
		// Tab
		$merge_token_labels = array();
		$merge_token_values = array();
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
			'cache_ttl' => DAO_WorkspaceWidget::CACHE_TTL,
			'id' => DAO_WorkspaceWidget::ID,
			'extension_id' => DAO_WorkspaceWidget::EXTENSION_ID,
			'label' => DAO_WorkspaceWidget::LABEL,
			'pos' => DAO_WorkspaceWidget::POS,
			'tab_id' => DAO_WorkspaceWidget::WORKSPACE_TAB_ID,
			'updated_at' => DAO_WorkspaceWidget::UPDATED_AT,
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
				
				$out_fields[DAO_WorkspaceWidget::PARAMS_JSON] = $json;
				break;
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_WIDGET;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, false);
		}
		
		switch($token) {
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
		$view->name = 'Widgets';
		$view->renderSortBy = SearchFields_WorkspaceWidget::ID;
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
		$view->name = 'Widgets';
		
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