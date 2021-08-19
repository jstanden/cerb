<?php
class WorkspaceWidget_RecordFields extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.record.fields';
	
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	function render(Model_WorkspaceWidget $widget) {
		$target_context = $widget->params['context'] ?? '';
		$target_context_id = $widget->params['context_id'] ?? 0;
		
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
		
		// Toolbar
		
		if(($toolbar_kata = $widget->params['toolbar_kata'] ?? false)) {
			$toolbar_fields = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $record_dict);
			$tpl->assign('toolbar_fields', $toolbar_fields);
		}
		
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
			
			if($context_ext instanceof IDevblocksContextProfile) {
				$properties = $context_ext->profileGetFields();
				
				$tpl->assign('custom_field_values', []);
				
				$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context);
				
				if (!empty($properties_cfields))
					$properties = array_merge($properties, $properties_cfields);
				
				// Sort properties by the configured order
				
				@$properties_enabled = array_flip($widget->params['properties'][0] ?: []);
				
				uksort($properties, function ($a, $b) use ($properties_enabled, $properties) {
					$a_pos = array_key_exists($a, $properties_enabled) ? $properties_enabled[$a] : 1000;
					$b_pos = array_key_exists($b, $properties_enabled) ? $properties_enabled[$b] : 1000;
					
					if ($a_pos == $b_pos)
						return $properties[$a]['label'] > $properties[$b]['label'] ? 1 : -1;
					
					return $a_pos < $b_pos ? -1 : 1;
				});
				
				$tpl->assign('properties', $properties);
				
				$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, null, [], true);
				$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
			}
			
			// =================================================================
			// Search buttons
			
			$search_contexts = Extension_DevblocksContext::getAll(false, ['workspace']);
			$tpl->assign('search_contexts', $search_contexts);
			
			$search_buttons = $this->_getSearchButtons($widget, null);
			$tpl->assign('search_buttons', $search_buttons);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
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