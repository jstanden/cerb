<?php
/************************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
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

class DAO_WorkspaceWidget extends Cerb_ORMHelper {
	const ID = 'id';
	const EXTENSION_ID = 'extension_id';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	const LABEL = 'label';
	const UPDATED_AT = 'updated_at';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO workspace_widget () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, 'workspace_widget', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.workspace_widget.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
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
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, extension_id, workspace_tab_id, label, updated_at, params_json, pos ".
			"FROM workspace_widget ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkspaceWidget
	 */
	static function get($id) {
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
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkspaceWidget();
			$object->id = intval($row['id']);
			$object->extension_id = $row['extension_id'];
			$object->workspace_tab_id = $row['workspace_tab_id'];
			$object->label = $row['label'];
			$object->updated_at = $row['updated_at'];
			$object->pos = $row['pos'];
			
			if(false != ($params = @json_decode($row['params_json'], true)))
				$object->params = $params;
			
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
		
		$db->Execute(sprintf("DELETE FROM workspace_widget WHERE id IN (%s)", $ids_list));
		
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
	
	static function deleteByTab($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM workspace_widget WHERE workspace_tab_id IN (%s)", $ids_list));
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspaceWidget::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_widget.id as %s, ".
			"workspace_widget.extension_id as %s, ".
			"workspace_widget.workspace_tab_id as %s, ".
			"workspace_widget.label as %s, ".
			"workspace_widget.updated_at as %s, ".
			"workspace_widget.params_json as %s, ".
			"workspace_widget.pos as %s ",
				SearchFields_WorkspaceWidget::ID,
				SearchFields_WorkspaceWidget::EXTENSION_ID,
				SearchFields_WorkspaceWidget::WORKSPACE_TAB_ID,
				SearchFields_WorkspaceWidget::LABEL,
				SearchFields_WorkspaceWidget::UPDATED_AT,
				SearchFields_WorkspaceWidget::PARAMS_JSON,
				SearchFields_WorkspaceWidget::POS
			);
			
		$join_sql = "FROM workspace_widget ";
		
		$has_multiple_values = false;
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		array_walk_recursive(
			$params,
			array('DAO_WorkspaceWidget', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'tables' => &$tables,
				'has_multiple_values' => &$has_multiple_values
			)
		);
	
		return array(
			'primary_table' => 'workspace_widget',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
			*/
		}
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
			($has_multiple_values ? 'GROUP BY workspace_widget.id ' : '').
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
			$object_id = intval($row[SearchFields_WorkspaceWidget::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT workspace_widget.id) " : "SELECT COUNT(workspace_widget.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_WorkspaceWidget implements IDevblocksSearchFields {
	const ID = 'w_id';
	const EXTENSION_ID = 'w_extension_id';
	const WORKSPACE_TAB_ID = 'w_workspace_tab_id';
	const LABEL = 'w_label';
	const UPDATED_AT = 'w_updated_at';
	const PARAMS_JSON = 'w_params_json';
	const POS = 'w_pos';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'workspace_widget', 'id', $translate->_('common.id')),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'workspace_widget', 'extension_id', $translate->_('common.extension')),
			self::WORKSPACE_TAB_ID => new DevblocksSearchField(self::WORKSPACE_TAB_ID, 'workspace_widget', 'workspace_tab_id', null),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'workspace_widget', 'label', $translate->_('common.label')),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'workspace_widget', 'updated_at', $translate->_('common.updated')),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'workspace_widget', 'params_json', $translate->_('common.params')),
			self::POS => new DevblocksSearchField(self::POS, 'workspace_widget', 'pos', null),
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
	public $params = array();
};

class Context_WorkspaceWidget extends Extension_DevblocksContext {
	function getRandom() {
		//return DAO_WorkspaceTab::random();
	}
	
	function getMeta($context_id) {
		$url_writer = DevblocksPlatform::getUrlService();

		if(null == ($workspace_widget = DAO_WorkspaceWidget::get($context_id)))
			return array();
		
		/*
		$url = $url_writer(sprintf("c=pages&id=%d",
			$workspace_tab->workspace_page_id
		));
		*/
		
		return array(
			'id' => $workspace_widget->id,
			'name' => $workspace_widget->label,
			'permalink' => null,
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
		} else {
			$widget = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.extension'),
			'label' => $prefix.$translate->_('common.label'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, false);
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
			
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
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
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Widgets';
		$view->renderSortBy = SearchFields_WorkspaceWidget::ID;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
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
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};