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

class PageSection_InternalDashboards extends Extension_PageSection {
	function render() {}
	
	function getContextFieldsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */
			echo json_encode(false);
			return;
		}
		
		$results = [];
		$params_avail = $view->getParamsAvailable();
		
		$subtotals = [];
		
		if($view instanceof IAbstractView_Subtotals) /* @var $view IAbstractView_Subtotals */
			$subtotals = $view->getSubtotalFields();
		
		if(is_array($params_avail))
		foreach($params_avail as $param) { /* @var $param DevblocksSearchField */
			if(empty($param->db_label))
				continue;
		
			$results[] = array(
				'key' => $param->token,
				'label' => mb_convert_case($param->db_label, MB_CASE_LOWER),
				'type' => $param->type,
				'sortable' => $param->is_sortable,
				'subtotals' => array_key_exists($param->token, $subtotals),
			);
		}
		
		echo json_encode($results);
	}
	
	function getContextPlaceholdersJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		$labels = [];
		$values = [];
		
		CerberusContexts::getContext($context, null, $labels, $values, null, true);
		
		if(empty($labels)) {
			echo json_encode(false);
			return;
		}
		
		$types = @$values['_types'] ?: [];
		$results = array();
		
		foreach($labels as $k => $v) {
			$results[] = array(
				'key' => $k,
				'label' => $v,
				'type' => @$types[$k] ?: '',
			);
		}
		
		echo json_encode($results);
	}
	
	function setWidgetPositionsAction() {
		@$columns = DevblocksPlatform::importGPC($_REQUEST['column'], 'array', array());

		if(is_array($columns))
		foreach($columns as $idx => $widget_ids) {
			foreach(DevblocksPlatform::parseCsvString($widget_ids) as $n => $widget_id) {
				$pos = sprintf("%d%03d", $idx, $n);
				
				DAO_WorkspaceWidget::update($widget_id, array(
					DAO_WorkspaceWidget::POS => $pos,
				));
			}
			
			// [TODO] Kill cache on dashboard
		}
	}
	
	function handleWidgetActionAction() {
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'], 'string', '');
		@$widget_action = DevblocksPlatform::importGPC($_REQUEST['widget_action'], 'string', '');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		if(false == ($widget = DAO_WorkspaceWidget::get($widget_id)))
			return;
		
		if(!Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			return;
		
		if(false == ($widget_extension = $widget->getExtension()))
			return;
		
		if($widget_extension instanceof Extension_WorkspaceWidget && method_exists($widget_extension, $widget_action.'Action')) {
			call_user_func([$widget_extension, $widget_action.'Action']);
		}
	}
}

class WorkspaceTab_Dashboards extends Extension_WorkspaceTab {
	const ID = 'core.workspace.tab.dashboard';
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('tab', $tab);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$active_worker = CerberusApplication::getActiveWorker();
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		@$layout = $tab->params['layout'] ?: '';
		
		$zones = [
			'content' => [],
		];
		
		switch($layout) {
			case 'sidebar_left':
				$zones = [
					'sidebar' => [],
					'content' => [],
				];
				break;
				
			case 'sidebar_right':
				$zones = [
					'content' => [],
					'sidebar' => [],
				];
				break;
				
			case 'thirds':
				$zones = [
					'left' => [],
					'center' => [],
					'right' => [],
				];
				break;
		}

		// Sanitize zones
		foreach($widgets as $widget_id => $widget) {
			if(array_key_exists($widget->zone, $zones)) {
				$zones[$widget->zone][$widget_id] = $widget;
				continue;
			}
			
			// If the zone doesn't exist, drop the widget into the first zone
			$zones[key($zones)][$widget_id] = $widget;
		}
		
		$tpl->assign('layout', $layout);
		$tpl->assign('zones', $zones);
		$tpl->assign('model', $tab);
		
		// Prompted placeholders
		$prompts = $tab->getPlaceholderPrompts();
		$tpl->assign('prompts', $prompts);
		
		$tab_prefs = $tab->getDashboardPrefsAsWorker($active_worker);
		$tpl->assign('tab_prefs', $tab_prefs);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/tab.tpl');
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'uid' => 'workspace_tab_' . $tab->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_TAB,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
				'widgets' => array(),
			),
		);
		
		$widgets = DAO_WorkspaceWidget::getByTab($tab->id);
		
		foreach($widgets as $widget) {
			$widget_json = array(
				'uid' => 'workspace_widget_' . $widget->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
				'label' => $widget->label,
				'extension_id' => $widget->extension_id,
				'pos' => $widget->pos,
				'width_units' => $widget->width_units,
				'zone' => $widget->zone,
				'params' => $widget->params,
			);
			
			$json['tab']['widgets'][] = $widget_json;
		}
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab->id) || !is_array($json))
			return false;
		
		// Backwards compatibility
		if(isset($json['tab']))
			$json = $json['tab'];
		
		if(!isset($json['widgets']) || !is_array($json['widgets']))
			return false;
		
		foreach($json['widgets'] as $widget) {
			DAO_WorkspaceWidget::create([
				DAO_WorkspaceWidget::LABEL => $widget['label'],
				DAO_WorkspaceWidget::EXTENSION_ID => $widget['extension_id'],
				DAO_WorkspaceWidget::POS => $widget['pos'],
				DAO_WorkspaceWidget::PARAMS_JSON => json_encode($widget['params']),
				DAO_WorkspaceWidget::WORKSPACE_TAB_ID => $tab->id,
				DAO_WorkspaceWidget::WIDTH_UNITS => @$widget['width_units'] ?: 2,
				DAO_WorkspaceWidget::ZONE => @$widget['zone'] ?: '',
				DAO_WorkspaceWidget::UPDATED_AT => time(),
			]);
		}
		
		return true;
	}
}

class WorkspaceWidget_Gauge extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);
		
		if(!empty($data))
			$widget->params = $data;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($this->_loadData($widget))) {
			echo "This gauge doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/gauge.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/gauge/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		if(isset($params['threshold_values']))
		foreach($params['threshold_values'] as $idx => $val) {
			if(0 == strlen($val)) {
				unset($params['threshold_values'][$idx]);
				unset($params['threshold_labels'][$idx]);
				unset($params['threshold_colors'][$idx]);
				continue;
			}
			
			@$label = $params['threshold_labels'][$idx];
			
			if(empty($label))
				$params['threshold_labels'][$idx] = $val;
			
			@$color = strtoupper($params['threshold_colors'][$idx]);
			
			if(empty($color))
				$color = '#FFFFFF';
			
			$params['threshold_colors'][$idx] = $color;
		}
		
		$len = count($params['threshold_colors']);
		
		if($len) {
			if(0 == strcasecmp($params['threshold_colors'][0], '#FFFFFF')) {
				$params['threshold_colors'][0] = '#CF2C1D';
			}
			
			if(0 == strcasecmp($params['threshold_colors'][$len-1], '#FFFFFF')) {
				$params['threshold_colors'][$len-1] = '#66AD11';
			}
			
			$params['threshold_colors'] = DevblocksPlatform::colorLerpArray($params['threshold_colors']);
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);

		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$results = array(
			'Label' => $widget->label,
			'Value' => $widget->params['metric_value'],
			'Type' => $widget->params['metric_type'],
			'Prefix' => $widget->params['metric_prefix'],
			'Suffix' => $widget->params['metric_suffix'],
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'metric' => array(
					'value' => @$widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
				'thresholds' => array(),
			),
		);
		
		if(isset($widget->params['threshold_labels']) && is_array($widget->params['threshold_labels']))
		foreach(array_keys($widget->params['threshold_labels']) as $idx) {
			if(
				empty($widget->params['threshold_labels'][$idx])
				|| !isset($widget->params['threshold_values'][$idx])
			)
				continue;
		
			$results['widget']['thresholds'][] = array(
				'label' => $widget->params['threshold_labels'][$idx],
				'value' => $widget->params['threshold_values'][$idx],
				'color' => $widget->params['threshold_colors'][$idx],
			);
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_BehaviorTree extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.behavior.tree';
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$behavior_id_template = $widget->params['behavior'];
		
		$labels = $values = $merge_token_labels = $merge_token_values = [];
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'current_worker_',
			'Current Worker:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $widget, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'widget_',
			'Widget:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$behavior_id = $tpl_builder->build($behavior_id_template, $dict);
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		if(false == ($event = $behavior->getEvent()))
			return;
		
		if(false == ($behavior->getBot()))
			return;
		
		$tpl->assign('behavior', $behavior);
		$tpl->assign('event', $event->manifest);
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/behavior/tab.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/behavior_tree/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};

class WorkspaceWidget_RecordFields extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.record.fields';
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		@$target_context = $widget->params['context'];
		@$target_context_id = $widget->params['context_id'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Are we showing fields for a different record?
		
		$record_dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		if(!$target_context || is_null($target_context_id))
			return;
		
		$context = $target_context;
		$context_id = $tpl_builder->build($target_context_id, $record_dict);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(!method_exists($dao_class, 'get') || false == ($record = $dao_class::get($context_id))) {
			$tpl->assign('context_ext', $context_ext);
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/empty.tpl');
			return;
		}
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $record, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$tpl->assign('dict', $dict);
		
		if(!($context_ext instanceof IDevblocksContextProfile))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('widget', $widget);
		
		// Properties
		
		$properties_selected = @$widget->params['properties'] ?: [];
		
		foreach($properties_selected as &$v)
			$v = array_flip($v);
		
		$properties_available = $context_ext->profileGetFields($record);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $record->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties_available = array_merge($properties_available, $properties_cfields);
		
		$properties = [];
		
		// Only keep selected properties
		if(isset($properties_selected[0]))
			foreach(array_keys($properties_selected[0]) as $key)
				if(isset($properties_available[$key]))
					$properties[$key] = $properties_available[$key];
		
		// Empty fields
		
		$show_empty_fields = @$widget->params['options']['show_empty_properties'] ?: false;
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $record->id, $values, true);
		$properties_custom_fieldsets = array_intersect_key($properties_custom_fieldsets, $properties_selected);
		
		// Only keep selected properties
		foreach($properties_custom_fieldsets as $fieldset_id => &$fieldset_properties)
			$fieldset_properties['properties'] = array_intersect_key($fieldset_properties['properties'], @$properties_selected[$fieldset_id] ?: []);
		
		if(!$show_empty_fields) {
			$filter_empty_properties = function(&$properties) {
				foreach($properties as $k => $property) {
					if(!empty($property['value']))
						continue;
					
					switch($property['type']) {
						// Checkboxes can be empty
						case Model_CustomField::TYPE_CHECKBOX:
							continue 2;
							break;
							
						// Sliders can have empty values
						case 'slider':
							continue 2;
							break;
						
						case Model_CustomField::TYPE_LINK:
							// App-owned context links can be blank
							if(@$property['params']['context'] == CerberusContexts::CONTEXT_APPLICATION)
								continue 2;
							break;
					}
					
					unset($properties[$k]);
				}
			};
			
			$filter_empty_properties($properties);
			
			foreach($properties_custom_fieldsets as $fieldset_id => &$fieldset) {
				$filter_empty_properties($fieldset['properties']);
				
				if(empty($fieldset['properties']))
					unset($properties_custom_fieldsets[$fieldset_id]);
			}
		}
		
		$tpl->assign('properties', $properties);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		@$show_links = $widget->params['links']['show'];
		
		if($show_links) {
			$properties_links = [
				$context => [
					$record->id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$record->id,
							[]
						),
				],
			];
			$tpl->assign('properties_links', $properties_links);
		}
		
		// Card search buttons
		
		$search_buttons = $this->_getSearchButtons($widget, $record_dict);
		$tpl->assign('search_buttons', $search_buttons);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		@$context = $widget->params['context'];
		
		if($context) {
			$context_ext = Extension_DevblocksContext::get($context);
			$tpl->assign('context_ext', $context_ext);
			
			// =================================================================
			// Properties
			
			$properties = $context_ext->profileGetFields();
			
			$tpl->assign('custom_field_values', []);
			
			$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context);
			
			if(!empty($properties_cfields))
				$properties = array_merge($properties, $properties_cfields);
			
			// Sort properties by the configured order
			
			@$properties_enabled = array_flip($widget->params['properties'][0] ?: []);
			
			uksort($properties, function($a, $b) use ($properties_enabled, $properties) {
				$a_pos = array_key_exists($a, $properties_enabled) ? $properties_enabled[$a] : 1000;
				$b_pos = array_key_exists($b, $properties_enabled) ? $properties_enabled[$b] : 1000;
				
				if($a_pos == $b_pos)
					return $properties[$a]['label'] > $properties[$b]['label'] ? 1 : -1;
				
				return $a_pos < $b_pos ? -1 : 1;
			});
			
			$tpl->assign('properties', $properties);
			
			$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, null, [], true);
			$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
			
			// =================================================================
			// Search buttons
			
			$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
			$tpl->assign('search_contexts', $search_contexts);
			
			$search_buttons = $this->_getSearchButtons($widget, null);
			$tpl->assign('search_buttons', $search_buttons);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	private function _getSearchButtons(Model_WorkspaceWidget $model, DevblocksDictionaryDelegate $dict=null) {
		@$search = $model->params['search'] ?: [];
		
		$search_buttons = [];
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(empty($search))
			return [];
		
		if(is_array($search) && array_key_exists('context', $search))
		foreach(array_keys($search['context']) as $idx) {
			$query = $search['query'][$idx];
			
			if($dict) {
				$query = $tpl_builder->build($query, $dict);
			}
			
			$search_buttons[] = [
				'context' => $search['context'][$idx],
				'label_singular' => $search['label_singular'][$idx],
				'label_plural' => $search['label_plural'][$idx],
				'query' => $query,
			];
		}
		
		// If we have a dictionary, perform the actual counts
		if($dict) {
			$results = [];
			
			if(is_array($search_buttons))
			foreach($search_buttons as $search_button) {
				if(false == ($search_button_context = Extension_DevblocksContext::get($search_button['context'], true)))
					continue;
				
				if(false == ($view = $search_button_context->getTempView()))
					continue;
				
				$label_aliases = Extension_DevblocksContext::getAliasesForContext($search_button_context->manifest);
				$label_singular = @$search_button['label_singular'] ?: $label_aliases['singular'];
				$label_plural = @$search_button['label_plural'] ?: $label_aliases['plural'];
				
				$search_button_query = $tpl_builder->build($search_button['query'], $dict);
				$view->addParamsWithQuickSearch($search_button_query);
				
				$total = $view->getData()[1];
				
				$results[] = [
					'label' => ($total == 1 ? $label_singular : $label_plural),
					'context' => $search_button_context->id,
					'count' => $total,
					'query' => $search_button_query,
				];
			}
			
			return $results;
		}
		
		return $search_buttons;
	}
};

class WorkspaceWidget_BotBehavior extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$behavior_id = $widget->params['behavior_id'];
		@$behavior_vars = DevblocksPlatform::importVar(@$widget->params['behavior_vars'], 'array', []);
		
		if(!$behavior_id 
			|| false == ($widget_behavior = DAO_TriggerEvent::get($behavior_id))
			|| $widget_behavior->event_point != Event_DashboardWidgetRender::ID
			) {
			echo "A bot behavior isn't configured.";
			return;
		}
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DashboardWidgetRender::ID,
			[
				'widget' => $widget,
				'_variables' => $behavior_vars,
				'actions' => &$actions,
			]
		);
		
		if(false == ($event = $widget_behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $widget_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		// Format behavior vars
		
		if(is_array($behavior_vars))
		foreach($behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!isset($widget_behavior->variables[$k]))
					continue;
				
				$value = $widget_behavior->formatVariable($widget_behavior->variables[$k], $v);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$widget_behavior->runDecisionTree($dict, false, $event);
		
		$value = null;
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'render_html':
					$html = @$action['html'];
					echo $html;
					break;
			}
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/bot/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};

class WorkspaceWidget_Calendar extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'], 'integer', null);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'], 'integer', null);
		
		@$calendar_id_template = $widget->params['calendar_id'];
		
		$labels = $values = $merge_token_labels = $merge_token_values = [];
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'current_worker_',
			'Current Worker:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $widget, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'widget_',
			'Widget:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$calendar_id = $tpl_builder->build($calendar_id_template, $dict);
		
		if(empty($calendar_id) || null == ($calendar = DAO_Calendar::get($calendar_id))) { /* @var Model_Calendar $calendar */
			echo "A calendar isn't linked to this widget. Configure it to select one.";
			return;
		}
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		// Occlusion
		
		$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
		$availability->occludeCalendarEvents($calendar_events);
		
		// Template scope
		
		$tpl->assign('widget', $widget);
		$tpl->assign('calendar', $calendar);
		$tpl->assign('calendar_events', $calendar_events);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/calendar/calendar.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Calendars
		
		$calendars = DAO_Calendar::getAll();
		$tpl->assign('calendars', $calendars);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/calendar/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	function showCalendarTabAction(Model_WorkspaceWidget $model) {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(null == ($calendar = DAO_Calendar::get($calendar_id))) /* @var Model_Calendar $calendar */
			return;
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		// Occlusion
		
		$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
		$availability->occludeCalendarEvents($calendar_events);
		
		// Template scope
		$tpl->assign('widget', $model);
		$tpl->assign('calendar', $calendar);
		$tpl->assign('calendar_events', $calendar_events);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/calendar/calendar.tpl');
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$calendar_id = $widget->params['calendar_id'];
		
		if(false == ($calendar = DAO_Calendar::get($calendar_id)))
			return false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar(null, null);
		
		$fp = fopen("php://temp", 'r+');
		
		$headings = array(
			'Date',
			'Label',
			'Start',
			'End',
			'Is Available',
			'Color',
			//Link',
		);
		
		fputcsv($fp, $headings);
		
		// [TODO] This needs to use the selected month/year from widget
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		foreach($calendar_events as $events) {
			foreach($events as $event) {
				fputcsv($fp, array(
					date('r', $event['ts']),
					$event['label'],
					$event['ts'],
					$event['ts_end'],
					$event['is_available'],
					$event['color'],
					//$event['link'], // [TODO] Translate ctx:// links
				));
			}
		}
		
		unset($calendar_events);
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$calendar_id = $widget->params['calendar_id'];
		
		if(false == ($calendar = DAO_Calendar::get($calendar_id)))
			return false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar(null, null);
		
		// [TODO] This needs to use the selected month/year from widget
		$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
		
		$json_events = array();
		
		// [TODO] This should export a fully formed calendar (headings, weeks, days)
		// [TODO] The widget export should give the date range used as well
		
		foreach($calendar_events as $events) {
			foreach($events as $event) {
				$json_events[] = array(
					'label' => $event['label'],
					'date' => date('r', $event['ts']),
					'ts' => $event['ts'],
					'ts_end' => $event['ts_end'],
					'is_available' => $event['is_available'],
					'color' => $event['color'],
					//'link' => $event['link'], // [TODO] Translate ctx:// links
				);
			}
		}
		
		unset($calendar_events);
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'calendar',
				'version' => 'Cerb ' . APP_VERSION,
				'events' => $json_events,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Clock extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		@$timezone = $widget->params['timezone'];
		
		if(empty($timezone)) {
			echo "This clock doesn't have a timezone. Configure it and set one.";
			return;
		}
		
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);

		$offset = $datetimezone->getOffset($datetime);
		$tpl->assign('offset', $offset);
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/clock.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Timezones
		
		$date = DevblocksPlatform::services()->date();
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/clock/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'Label' => $widget->label,
			'Timezone' => $widget->params['timezone'],
			'Timestamp' => $datetime->getTimestamp(),
			'Output' => $datetime->format('r'),
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$timezone = $widget->params['timezone'];
		$datetimezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $datetimezone);
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'time' => array(
					'timezone' => $widget->params['timezone'],
					'timestamp' => $datetime->getTimestamp(),
					'output' => $datetime->format('r'),
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Counter extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);
		
		if(!empty($data))
			$widget->params = $data;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($this->_loadData($widget))) {
			echo "This counter doesn't have a data source. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/counter.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/counter/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);

		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$results = array(
			'Label' => $widget->label,
			'Value' => $widget->params['metric_value'],
			'Type' => $widget->params['metric_type'],
			'Prefix' => $widget->params['metric_prefix'],
			'Suffix' => $widget->params['metric_suffix'],
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'metric' => array(
					'value' => $widget->params['metric_value'],
					'type' => $widget->params['metric_type'],
					'prefix' => $widget->params['metric_prefix'],
					'suffix' => $widget->params['metric_suffix'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Countdown extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		if(!isset($widget->params['target_timestamp'])) {
			echo "This countdown doesn't have a target date. Configure it and set one.";
			return;
		}
		
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/countdown.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/countdown/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		if(isset($params['target_timestamp'])) {
			@$timestamp = intval(strtotime($params['target_timestamp']));
			$params['target_timestamp'] = $timestamp;
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'Label' => $widget->label,
			'Timestamp' => $widget->params['target_timestamp'],
			'Output' => DevblocksPlatform::strSecsToString($diff, 2),
		);

		$fp = fopen("php://temp", 'r+');
		
		fputcsv($fp, array_keys($results));
		fputcsv($fp, array_values($results));
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$diff = max(0, intval($widget->params['target_timestamp']) - time());
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'countdown' => array(
					'output' => DevblocksPlatform::strSecsToString($diff, 2),
					'timestamp' => $widget->params['target_timestamp'],
				),
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartCategories extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$xaxis_format = DevblocksPlatform::importGPC($widget->params['xaxis_format'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'], 'string', '');
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		@$xaxis_key = $results['_']['format_params']['xaxis_key'] ?: '';
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
			'padding' => [
				'left' => 150,
			],
			'data' => [
				'x' => $xaxis_key,
				'columns' => $results['data'],
				'type' => 'bar',
				'colors' => [
					'hits' => '#1f77b4'
				]
			],
			'axis' => [
				'rotated' => true,
				'x' => [
					'type' => 'category',
					'tick' => [
						'format' => null,
						'multiline' => true,
						'multilineMax' => 2,
						'width' => 150,
					]
				],
				'y' => [
					'tick' => [
						'rotate' => -90,
						'format' => null
					]
				]
			],
			'legend' => [
				'show' => true,
			]
		];
		
		if(@$results['_']['stacked']) {
			$config_json['data']['type']  = 'bar';
			$groups = array_column($results['data'], 0);
			array_shift($groups);
			$config_json['data']['groups'] = [array_values($groups)];
			$config_json['legend']['show'] = true;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
			
		} else {
			$config_json['data']['type']  = 'bar';
			$config_json['legend']['show'] = false;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
		}
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/categories/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/categories/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
		// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($data['data'] as $d) {
			fputcsv($fp, $d);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'chart_pie',
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartPie extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false == ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$chart_as = DevblocksPlatform::importGPC($widget->params['chart_as'], 'string', null);
		@$options = DevblocksPlatform::importGPC($widget->params['options'], 'array', []);
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
			'data' => [
				'columns' => $results['data'],
				'type' => $chart_as == 'pie' ? 'pie' : 'donut'
			],
			'donut' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'pie' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'tooltip' => [
				'format' => [
					'value' => null,
				],
			],
			'legend' => [
				'show' => true,
			]
		];
		
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/pie/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/pie/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, [
			'Label',
			'Value',
		]);
		
		foreach($data['data'] as $d) {
			fputcsv($fp, $d);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartScatterplot extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		@$xaxis_format = DevblocksPlatform::importGPC($widget->params['xaxis_format'], 'string', '');
		@$xaxis_label = DevblocksPlatform::importGPC($widget->params['xaxis_label'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'], 'string', '');
		@$yaxis_label = DevblocksPlatform::importGPC($widget->params['yaxis_label'], 'string', '');
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
			'data' => [
				'xs' => [],
				'columns' => $results['data'],
				'type' => 'scatter',
			],
			'axis' => [
				'x' => [
					'tick' => [
						'format' => null,
						'fit' => false,
						'rotate' => -90,
					]
				],
				'y' => [
					'tick' => [
						'fit' => false,
						'format' => null,
					]
				]
			],
		];
		
		foreach($results['data'] as $result) {
			if(@DevblocksPlatform::strEndsWith($result[0], '_x'))
				$config_json['data']['xs'][mb_substr($result[0],0,-2)] = $result[0];
		}
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/scatterplot/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/scatterplot/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
		// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, [
			'Label',
			'X',
			'Y',
		]);
		
		foreach($data['data'] as $idx => $result) {
			$label = array_shift($result);
			$data['data'][$label] = $result;
			unset($data['data'][$idx]);
		}
		
		$points = [];
		
		foreach($data['data'] as $key => $result) {
			if(DevblocksPlatform::strEndsWith($key, '_x')) {
				$new_key = mb_substr($key,0,-2);
				
				foreach($result as $idx => $x) {
					$points[] = [
						$new_key,
						$x,
						$data['data'][$new_key][$idx]
					];
				}
			}
		}
		
		foreach($points as $label => $d) {
			fputcsv($fp, $d);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => 'chart_pie',
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartTable extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker= CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		if(0 != strcasecmp('table', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'table' format.");
			return;
		}
		
		$tpl->assign('widget', $widget);
		$tpl->assign('results', $results);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/table/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/table/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, array_column($data['data']['columns'], 'label'));
		
		// Data
		foreach($data['data']['rows'] as $r) {
			$row = [];
			
			// [TODO] Format using data types, and include a raw column
			foreach(array_keys($data['data']['columns']) as $c_key) {
				$row[] = $r[$c_key];
			}
			
			fputcsv($fp, $row);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartTimeSeries extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$chart_as = DevblocksPlatform::importGPC($widget->params['chart_as'], 'string', 'line');
		@$options = DevblocksPlatform::importGPC($widget->params['options'], 'array', []);
		@$xaxis_label = DevblocksPlatform::importGPC($widget->params['xaxis_label'], 'string', '');
		@$yaxis_label = DevblocksPlatform::importGPC($widget->params['yaxis_label'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'], 'string', '');
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$error = null;
		
		if(false === ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(!$results) {
			echo "(no data)";
			return;
		}
		
		if(0 != strcasecmp('timeseries', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'timeseries' format.");
			return;
		}
		
		// Error
		$xaxis_key = @$results['_']['format_params']['xaxis_key'];
		$xaxis_format = @$results['_']['format_params']['xaxis_format'];
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
			'data' => [
				'x' => 'ts',
				'xFormat' => '%Y-%m-%d',
				'json' => $results['data'],
				'type' => 'line'
			],
			'axis' => [
				'x' => [
					'type' => 'timeseries',
					'tick' => [
						'rotate' => -90,
						'fit' => true,
					]
				],
				'y' => [
					'tick' => [
						'fit' => true,
					]
				]
			],
			'subchart' => [
				'show' => false,
				'size' => [
					'height' => 50,
				]
			],
			'legend' => [
				'show' => true,
			],
			'point' => [
				'show' => true,
			]
		];
		
		$config_json['data']['xFormat']  = $xaxis_format;
		
		if($xaxis_format)
			$config_json['axis']['x']['tick']['format']  = $xaxis_format;
		
		$config_json['subchart']['show']  = @$options['subchart'] ? true : false;
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		$config_json['point']['show']  = @$options['show_points'] ? true : false;
		
		switch($chart_as) {
			case 'line':
				$config_json['data']['type']  = 'line';
				break;
				
			case 'spline':
				$config_json['data']['type']  = 'spline';
				break;
				
			case 'area':
				$config_json['data']['type']  = 'area-step';
				$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				break;
				
			case 'bar':
				$config_json['data']['type'] = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				break;
				
			case 'bar_stacked':
				$config_json['data']['type']  = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				break;
		}
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
			
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/timeseries/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/timeseries/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	// Prompted placeholders
	
	function getPlaceholderPrompts(Model_WorkspaceWidget $widget) {
		$json = DevblocksPlatform::importVar($widget->params['data_query_inputs'], 'string', '');
		
		if(!$json)
			return [];
		
		if(false == ($prompts = json_decode($json, true)))
			return [];
		
		return $prompts;
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, [
			'Date',
			'Label',
			'Value',
		]);
		
		if(!isset($data['data']))
			return;
		
		if(!isset($data['data']['ts']))
			return;
		
		$x_dates = $data['data']['ts'];
		unset($data['data']['ts']);
		
		foreach($x_dates as $x_idx => $x_date) {
			foreach($data['data'] as $series_label => $series_data) {
				$row = [
					$x_date,
					$series_label,
					$series_data[$x_idx],
				];
				fputcsv($fp, $row);
			}
		}
		
		rewind($fp);
		
		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_ChartLegacy extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$series = $widget->params['series'];

		if(empty($series)) {
			return false;
		}
		
		$xaxis_keys = [];
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			@$datasource_extid = $series_params['datasource'];

			if(empty($datasource_extid)) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid))) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			$params_prefix = sprintf("[series][%d]", $series_idx);

			$data = $datasource_ext->getData($widget, $series_params, $params_prefix);
			
			if(!empty($data)) {
				$widget->params['series'][$series_idx] = $data;
				
				$xaxis_keys = array_merge(
					$xaxis_keys,
					array_column($data['data'], 'x_label', 'x')
				);
				
			} else {
				unset($widget->params['series'][$series_idx]);
			}
		}
		
		// Normalize the series x-axes
		
		if('bar' == $widget->params['chart_type']) {
			ksort($xaxis_keys);
			
			foreach($widget->params['series'] as $series_idx => &$series_params) {
				$data = $series_params['data'];
				$xaxis_diff = array_diff_key($xaxis_keys, $data);
				
				if($xaxis_diff) {
					foreach($xaxis_diff as $x => $x_label) {
						$data[$x] = [
							'x' => $x,
							'y' => 0,
							'x_label' => $x_label,
							'y_label' => DevblocksPlatform::formatNumberAs(0, $series_params['yaxis_format']),
						];
					}
					
					ksort($data);
				}
				
				$series_params['data'] = array_values($data);
			}
			
			$widget->params['xaxis_keys'] = $xaxis_keys;
			
		} else {
			foreach($widget->params['series'] as $series_idx => &$series_params) {
				$series_params['data'] = array_values($series_params['data']);
			}
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($this->_loadData($widget))) {
			echo "This chart doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		// Calculate subtotals
		
		$chart_type = DevblocksPlatform::importVar(@$widget->params['chart_type'], 'string', '');
		$chart_display = DevblocksPlatform::importVar(@$widget->params['chart_display'], 'string', '');
		$series_subtotals = DevblocksPlatform::importVar(@$widget->params['chart_subtotal_series'], 'array', []);
		
		if(in_array($chart_display,['','table']) && $series_subtotals) {
			$subtotals = array_fill_keys($series_subtotals, []);
			
			foreach($widget->params['series'] as $series_idx => &$series) {
				$data = array_column($series['data'], 'y');
				$sum = array_sum($data);
				$yaxis_format = $series['yaxis_format'];
				
				if($data) {
					if(array_key_exists('sum', $subtotals)) {
						$subtotals['sum'][$series_idx] = [
							'value' => $sum,
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('mean', $subtotals)) {
						$subtotals['mean'][$series_idx] = [
							'value' => $sum/count($data),
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('min', $subtotals)) {
						$subtotals['min'][$series_idx] = [
							'value' => min($data),
							'format' => $yaxis_format,
						];
					}
					
					if(array_key_exists('max', $subtotals)) {
						$subtotals['max'][$series_idx] = [
							'value' => max($data),
							'format' => $yaxis_format,
						];
					}
				}
			}
			
			$widget->params['subtotals'] = $subtotals;
		}
		
		$row_subtotals = DevblocksPlatform::importVar(@$widget->params['chart_subtotal_row'], 'array', []);
		
		// If this is a bar chart with more than one series
		if($chart_type == 'bar' && $row_subtotals && count($widget->params['series']) > 1) {
			$yaxis_formats = array_count_values(array_column($widget->params['series'], 'yaxis_format'));
			
			// If all of the series have a consistent format
			if(1 == count($yaxis_formats)) {
				$yaxis_format = key($yaxis_formats);
				$x_subtotals = array_fill_keys($row_subtotals, []);
				$values = [];
				
				foreach($widget->params['series'] as $series_idx => &$series) {
					foreach($series['data'] as $data) {
						$values[$data['x']][] = $data['y'];
					}
				}
				
				foreach($values as $x => $data) {
					if(array_key_exists('sum', $x_subtotals)) {
						$x_subtotals['sum'][$x] = [
							'value' => array_sum($data),
						];
					}
					
					if(array_key_exists('mean', $x_subtotals)) {
						$x_subtotals['mean'][$x] = [
							'value' => array_sum($data) / count($data),
						];
					}
					
					if(array_key_exists('min', $x_subtotals)) {
						$x_subtotals['min'][$x] = [
							'value' => min($data),
						];
					}
					
					if(array_key_exists('max', $x_subtotals)) {
						$x_subtotals['max'][$x] = [
							'value' => max($data),
						];
					}
				}
				
				$widget->params['x_subtotals'] = [
					'format' => $yaxis_format,
					'data' => $x_subtotals,
				];
			}
		}
		
		$tpl->assign('widget', $widget);
		
		switch($widget->params['chart_type']) {
			case 'bar':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/bar_chart_legacy.tpl');
				break;
				
			default:
			case 'line':
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/line_chart_legacy.tpl');
				break;
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Datasource Extensions
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		foreach($params['series'] as $idx => $series) {
			// [TODO] The extension should be able to filter the properties here (on all widgets)
			// [TODO] $datasource = $series['datasource'];
			
			// Convert the serialized model to proper JSON before saving
		
			if(isset($series['worklist_model_json'])) {
				$worklist_model = json_decode($series['worklist_model_json'], true);
				unset($series['worklist_model_json']);
				
				if(empty($worklist_model) && isset($series['context'])) {
					if(false != ($context_ext = Extension_DevblocksContext::get($series['context']))) {
						if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
							$worklist_model['context'] = $context_ext->id;
						}
					}
				}
				
				$series['worklist_model'] = $worklist_model;
				$params['series'][$idx] = $series;
			}
			
			if(isset($series['line_color'])) {
				if(false != ($rgb = $this->_hex2RGB($series['line_color']))) {
					$params['series'][$idx]['fill_color'] = sprintf("rgba(%d,%d,%d,0.15)", $rgb['r'], $rgb['g'], $rgb['b']);
				}
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
	}
	
	// Source: http://www.php.net/manual/en/function.hexdec.php#99478
	private function _hex2RGB($hex_color) {
		$hex_color = preg_replace("/[^0-9A-Fa-f]/", '', $hex_color); // Gets a proper hex string
		$rgb = array();
		
		// If a proper hex code, convert using bitwise operation. No overhead... faster
		if (strlen($hex_color) == 6) {
			$color_value = hexdec($hex_color);
			$rgb['r'] = 0xFF & ($color_value >> 0x10);
			$rgb['g'] = 0xFF & ($color_value >> 0x8);
			$rgb['b'] = 0xFF & $color_value;
			
		// If shorthand notation, need some string manipulations
		} elseif (strlen($hex_color) == 3) {
			$rgb['r'] = hexdec(str_repeat(substr($hex_color, 0, 1), 2));
			$rgb['g'] = hexdec(str_repeat(substr($hex_color, 1, 1), 2));
			$rgb['b'] = hexdec(str_repeat(substr($hex_color, 2, 1), 2));
			
		} else {
			return false;
		}
		
		return $rgb;
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array();
		
		$results[] = array(
			'Series #',
			'Series Label',
			'Data X Label',
			'Data X Value',
			'Data Y Label',
			'Data Y Value',
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			if(is_array($data))
			foreach($data as $v) {
				$row = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);

				$results[] = $row;
			}
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'series' => [],
			),
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			$results['widget']['series'][$series_idx] = array(
				'id' => $series_idx,
				'label' => $series_params['label'],
				'data' => array(),
			);
			
			if(is_array($data))
			foreach($data as $v) {
				$row = array(
					'x' => $v['x'],
					'x_label' => $v['x_label'],
					'y' => $v['y'],
					'y_label' => $v['y_label'],
				);
				
				$results['widget']['series'][$series_idx]['data'][] = $row;
			}
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Subtotals extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$view_id = sprintf("widget%d_worklist", $widget->id);

		if(null == ($view = self::getViewFromParams($widget, $widget->params, $view_id)))
			return;
		
		if(!($view instanceof IAbstractView_Subtotals))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$fields = $view->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);

		if(empty($view->renderSubtotals) || !isset($fields[$view->renderSubtotals])) {
			echo "You need to enable subtotals on the worklist in this widget's configuration.";
			return;
		}
		
		$counts = $view->getSubtotalCounts($view->renderSubtotals);
		
		if(!$counts) {
			echo sprintf('(%s)', 
				DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translate('common.data.no'))
			);
			return;
		}

		if(null != (@$limit_to = $widget->params['limit_to'])) {
			$counts = array_slice($counts, 0, $limit_to, true);
		}
		
		switch(@$widget->params['style']) {
			case 'pie':
				$data = [];
				
				foreach($counts as $d) {
					$data[] = [$d['label'], intval($d['hits'])];
				}
				
				$tpl->assign('data_json', json_encode($data));
				$tpl->assign('widget', $widget);
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/pie_chart.tpl');
				break;
				
			default:
			case 'list':
				$tpl->assign('subtotal_counts', $counts);
				$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/subtotals/subtotals.tpl');
				break;
		}
		
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		
		if(null == ($view = self::getViewFromParams($widget, $widget->params, $view_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Contexts
		
		$context_mfts = Extension_DevblocksContext::getAll(false, 'workspace');
		$tpl->assign('context_mfts', $context_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/subtotals/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		// Convert the serialized model to proper JSON before saving
		
		if(isset($params['worklist_model_json'])) {
			$worklist_model = json_decode($params['worklist_model_json'], true);
			unset($params['worklist_model_json']);
			
			if(empty($worklist_model) && isset($params['context'])) {
				if(false != ($context_ext = Extension_DevblocksContext::get($params['context']))) {
					if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
						$worklist_model['context'] = $context_ext->id;
					}
				}
			}
			
			$params['worklist_model'] = $worklist_model;
		}
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
		
		// Save the widget
		
		DAO_WorkspaceWidget::update($widget->id, [
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		]);
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == $this->_exportDataLoad($widget)) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataLoad(Model_WorkspaceWidget &$widget) {
		$view_id = sprintf("widget%d_worklist", $widget->id);
		
		if(null == ($view = self::getViewFromParams($widget, $widget->params, $view_id)))
			return false;

		if(!($view instanceof IAbstractView_Subtotals))
			return false;
		
		$fields = $view->getSubtotalFields();
		
		if(empty($view->renderSubtotals) || !isset($fields[$view->renderSubtotals])) {
			return false;
		}
		
		$counts = $view->getSubtotalCounts($view->renderSubtotals);

		if(null != (@$limit_to = $widget->params['limit_to'])) {
			$counts = array_slice($counts, 0, $limit_to, true);
		}
		
		DevblocksPlatform::sortObjects($counts, '[hits]', false);
		
		$widget->params['counts'] = $counts;
		return true;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		@$counts = $widget->params['counts'];
		
		if(!is_array($counts))
			return false;
		
		$results = array();
		
		$results[] = array(
			'Label',
			'Count',
		);
		
		foreach($counts as $count) {
			$results[] = array(
				$count['label'],
				$count['hits'],
			);
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		@$counts = $widget->params['counts'];
		
		if(!is_array($counts))
			return false;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'counts' => array(),
			),
		);

		foreach($counts as $count) {
			$results['widget']['counts'][] = array(
				'label' => $count['label'],
				'count' => $count['hits'],
			);
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Worklist extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	const ID = 'core.workspace.widget.worklist';
	
	function getView(Model_WorkspaceWidget $widget) {
		@$view_context = $widget->params['context'];
		@$query = $widget->params['query'];
		@$query_required = $widget->params['query_required'];
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Unique instance per widget/record combo
		$view_id = sprintf('widget_%d_worklist', $widget->id);
		
		if(false == $view_context || false == ($view_context_ext = Extension_DevblocksContext::get($view_context)))
			return;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$defaults = C4_AbstractViewModel::loadFromClass($view_context_ext->getViewClass());
			$defaults->id = $view_id;
			$defaults->is_ephemeral = true;
			$defaults->options = [];
			$defaults->name = ' ';
			$defaults->paramsEditable = [];
			$defaults->paramsDefault = [];
			$defaults->view_columns = $widget->params['columns'];
			$defaults->options['header_color'] = @$widget->params['header_color'] ?: '#626c70';
			$defaults->renderLimit = DevblocksPlatform::intClamp(@$widget->params['render_limit'], 1, 50);
			
			if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($defaults, false)))
				return;
		}
		
		$view->renderPage = 0;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WORKLIST,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		if($query_required) {
			$query_required = $tpl_builder->build($query_required, $dict);
		}
		
		$view->addParamsRequiredWithQuickSearch($query_required);
		
		if($query) {
			$query = $tpl_builder->build($query, $dict);
		}
		
		$view->setParamsQuery($query);
		$view->addParamsWithQuickSearch($query);
		
		return $view;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		$view = $this->getView($widget);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('context_mfts', $context_mfts);
		
		@$context = $widget->params['context'];
		@$columns = @$widget->params['columns'] ?: [];
		
		if($context)
			$columns = $this->_getContextColumns($context, $columns);
			
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/worklist/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// Remove worker view models
		$view_id = sprintf('widget_%d_worklist', $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
		
		// Save
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
	private function _getContextColumns($context, $columns_selected=[]) {
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			return json_encode(false);
		}
		
		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) /* @var $view C4_AbstractView */
			return json_encode(false);
		
		$view->setAutoPersist(false);
		
		$results = [];
		
		$columns_avail = $view->getColumnsAvailable();
		
		if(empty($columns_selected))
			$columns_selected = $view->view_columns;
		
		if(is_array($columns_avail))
		foreach($columns_avail as $column) {
			if(empty($column->db_label))
				continue;
			
			$results[] = array(
				'key' => $column->token,
				'label' => mb_convert_case($column->db_label, MB_CASE_TITLE),
				'type' => $column->type,
				'is_selected' => in_array($column->token, $columns_selected),
			);
		}
		
		usort($results, function($a, $b) use ($columns_selected) {
			if($a['is_selected'] == $b['is_selected']) {
				if($a['is_selected']) {
					$a_idx = array_search($a['key'], $columns_selected);
					$b_idx = array_search($b['key'], $columns_selected);
					return $a_idx < $b_idx ? -1 : 1;
					
				} else {
					return $a['label'] < $b['label'] ? -1 : 1;
				}
				
			} else {
				return $a['is_selected'] ? -1 : 1;
			}
		});
		
		return $results;
	}
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($view = $this->getView($widget)))
			return false;
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCSV($widget, $view);
				break;
				
			case 'json':
			default:
				return $this->_exportDataAsJson($widget, $view);
				break;
		}
	}
	
	private function _exportDataLoadAsContexts(Model_WorkspaceWidget $widget, $view) {
		$results = [];
		
		@$context_ext = Extension_DevblocksContext::getByViewClass(get_class($view));

		if(empty($context_ext))
			return [];
		
		$models = $view->getDataAsObjects();
		
		/*
		 * [TODO] This should be able to reuse lazy loads (e.g. every calendar_event may share
		 * a calendar_event.calendar_id link to the same record
		 *
		 */
		
		foreach($models as $model) {
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context_ext->id, $model, $labels, $values, null, true);
			
			unset($values['_loaded']);
			
			$dict = new DevblocksDictionaryDelegate($values);
			
			if(isset($context_ext->params['context_expand_export'])) {
				@$context_expand = DevblocksPlatform::parseCsvString($context_ext->params['context_expand_export']);
				
				foreach($context_expand as $expand)
					$dict->$expand;
			}
			
			$values = $dict->getDictionary();
			
			foreach($values as $k => $v) {
				// Hide complex values
				if(!is_string($v) && !is_numeric($v)) {
					unset($values[$k]);
					continue;
				}
				
				// Hide any failed key lookups
				if(substr($k,0,1) == '_' && is_null($v)) {
					unset($values[$k]);
					continue;
				}
				
				// Hide meta data
				if(preg_match('/__context$/', $k)) {
					unset($values[$k]);
					continue;
				}
			}
			
			$results[] = $values;
		}
		
		return $results;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget, $view) {
		$export_data = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'page' => $view->renderPage,
				'count' => 0,
				'results' => array(),
			),
		);
		
		$results = $this->_exportDataLoadAsContexts($widget, $view);
		
		$export_data['widget']['count'] = count($results);
		$export_data['widget']['results'] = $results;
			
		return DevblocksPlatform::strFormatJson($export_data);
	}
	
	private function _exportDataAsCSV(Model_WorkspaceWidget $widget, $view) {
		$results = $this->_exportDataLoadAsContexts($widget, $view);
		
		$fp = fopen("php://temp", 'r+');

		if(!empty($results)) {
			$first_result = current($results);
			$headings = array();
			
			foreach(array_keys($first_result) as $k)
				$headings[] = $k;
			
			fputcsv($fp, $headings);
		}
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
};

class WorkspaceWidget_CustomHTML extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		if(false == ($widget->getWorkspacePage()))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		
		$html = $this->_getHtml($widget);
		$tpl->assign('html', $html);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/custom_html/render.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		
		// Placeholders
		
		$labels = [];
		$values = [];
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			$tpl->assign('labels', $labels);
		}
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/_legacy/custom_html/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	private function _getHtml($widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(empty($active_worker) || !Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			return;
		
		@$content = $widget->params['content'];
		
		$labels = $values = $worker_labels = $worker_values = [];
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $worker_labels, $worker_values, null, true, true);
		CerberusContexts::merge('current_worker_', null, $worker_labels, $worker_values, $labels, $values);
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		$html = $tpl_builder->build($content, $dict);
		return DevblocksPlatform::purifyHTML($html, false, false);
	}
};

class WorkspaceWidget_MapGeoPoints extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$projection = DevblocksPlatform::importGPC($widget->params['projection'], 'string', 'world');
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			return;
		}
		
		if(0 != strcasecmp('geojson', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'geojson' format.");
			return;
		}
		
		$points = $results['data'];
		
		$tpl->assign('points', $points);
		$tpl->assign('widget', $widget);
		
		switch($projection) {
			case 'usa':
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_usa.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::internal/widgets/map/geopoints/render_world.tpl');
				break;
		}
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/map/geopoints/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};

class WorkspaceWidget_PieChart extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		// Per series datasources
		@$datasource_extid = $widget->params['datasource'];

		if(empty($datasource_extid)) {
			return false;
		}
		
		if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid)))
			return false;
		
		$data = $datasource_ext->getData($widget, $widget->params);

		// Convert raw data
		if(isset($data['data'])) {
			foreach($data['data'] as $wedge) {
				$label = @$wedge['metric_label'] ?: '';
				$value = @$wedge['metric_value'] ?: 0;
				
				if(empty($value))
					continue;
				
				$data['wedge_labels'][] = $label;
				$data['wedge_values'][] = $value;
			}
			
			unset($data['data']);
		}
		
		if(!empty($data))
			$widget->params = $data;
		
		$wedge_colors = array(
			'#57970A',
			'#007CBD',
			'#7047BA',
			'#8B0F98',
			'#CF2C1D',
			'#E97514',
			'#FFA100',
			'#3E6D07',
			'#345C05',
			'#005988',
			'#004B73',
			'#503386',
			'#442B71',
			'#640A6D',
			'#55085C',
			'#951F14',
			'#7E1A11',
			'#A8540E',
			'#8E470B',
			'#B87400',
			'#9C6200',
			'#CCCCCC',
		);
		$widget->params['wedge_colors'] = $wedge_colors;
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == $this->_loadData($widget)) {
			echo "This pie chart doesn't have a data source. Configure it and select one.";
			return;
		}

		$tpl->assign('widget', $widget);
		
		// [TODO] Test arbitrary pie charts
		
		//$data = [];
		//foreach($counts as $d) {
		//	$data[] = [$d['label'], intval($d['hits'])];
		//}
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/pie_chart_legacy.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/pie_chart/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);

		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == $this->_loadData($widget)) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		$results = array();
		
		$results[] = array(
			'Label',
			'Count',
		);
		
		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];

			$results[] = array(
				$wedge_label,
				$wedge_value,
			);
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		if(!isset($widget->params['wedge_labels']))
			return false;
		
		if(!is_array($widget->params['wedge_labels']))
			return false;
		
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'counts' => array(),
			),
		);

		foreach(array_keys($widget->params['wedge_labels']) as $idx) {
			@$wedge_label = $widget->params['wedge_labels'][$idx];
			@$wedge_value = $widget->params['wedge_values'][$idx];
			@$wedge_color = $widget->params['wedge_colors'][$idx];

			// Reuse the last color
			if(empty($wedge_color))
				$wedge_color = end($widget->params['wedge_colors']);
			
			$results['widget']['counts'][] = array(
				'label' => $wedge_label,
				'count' => $wedge_value,
				'color' => $wedge_color,
			);
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};

class WorkspaceWidget_Scatterplot extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	private function _loadData(Model_WorkspaceWidget &$widget) {
		@$series = $widget->params['series'];
		
		if(empty($series)) {
			return false;
		}
		
		// Multiple datasources
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			@$datasource_extid = $series_params['datasource'];

			if(empty($datasource_extid)) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			if(null == ($datasource_ext = Extension_WorkspaceWidgetDatasource::get($datasource_extid))) {
				unset($widget->params['series'][$series_idx]);
				continue;
			}
			
			$params_prefix = sprintf("[series][%d]", $series_idx);
			
			$data = $datasource_ext->getData($widget, $series_params, $params_prefix);

			if(!empty($data)) {
				$widget->params['series'][$series_idx] = $data;
			} else {
				unset($widget->params['series'][$series_idx]);
			}
		}
		
		return true;
	}
	
	function render(Model_WorkspaceWidget $widget, $refresh_options=[]) {
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($this->_loadData($widget))) {
			echo "This scatterplot doesn't have any data sources. Configure it and select one.";
			return;
		}
		
		$tpl->assign('widget', $widget);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/scatterplot.tpl');
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Limit to widget
		
		$datasource_mfts = Extension_WorkspaceWidgetDatasource::getAll(false, $this->manifest->id);
		$tpl->assign('datasource_mfts', $datasource_mfts);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/scatterplot/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		
		// [TODO] The extension should be able to filter the properties here
		
		foreach($params['series'] as $idx => $series) {
			// Convert the serialized model to proper JSON before saving
		
			if(isset($series['worklist_model_json'])) {
				$worklist_model = json_decode($series['worklist_model_json'], true);
				unset($series['worklist_model_json']);
				
				if(empty($worklist_model) && isset($series['context'])) {
					if(false != ($context_ext = Extension_DevblocksContext::get($series['context']))) {
						if(false != (@$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($context_ext->getChooserView(), $context_ext->id), true))) {
							$worklist_model['context'] = $context_ext->id;
						}
					}
				}
				
				$series['worklist_model'] = $worklist_model;
				$params['series'][$idx] = $series;
			}
		}
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear worker models
		
		$view_id = sprintf("widget%d_worklist", $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
		
		// Clear caches
		
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		if(false == ($this->_loadData($widget))) {
			return;
		}
		
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array();
		
		$results[] = array(
			'Series #',
			'Series Label',
			'Data X Label',
			'Data X Value',
			'Data Y Label',
			'Data Y Value',
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			if(is_array($data))
			foreach($data as $v) {
				$results[] = array(
					'series' => $series_idx,
					'series_label' => $series_params['label'],
					'x_label' => $v['x_label'],
					'x' => $v['x'],
					'y_label' => $v['y_label'],
					'y' => $v['y'],
				);
			}
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($results as $result) {
			fputcsv($fp, $result);
		}
		
		rewind($fp);

		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$series = $widget->params['series'];
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'series' => array(),
			),
		);
		
		if(is_array($series))
		foreach($series as $series_idx => $series_params) {
			if(!isset($series_params['data']) || empty($series_params['data']))
				continue;
		
			$data = $series_params['data'];
			
			$results['widget']['series'][$series_idx] = array(
				'id' => $series_idx,
				'label' => $series_params['label'],
				'data' => array(),
			);
			
			if(is_array($data))
			foreach($data as $v) {
				$row = array(
					'x' => $v['x'],
					'x_label' => $v['x_label'],
					'y' => $v['y'],
					'y_label' => $v['y_label'],
				);
				
				$results['widget']['series'][$series_idx]['data'][] = $row;
			}
		}
		
		return DevblocksPlatform::strFormatJson($results);
	}
};