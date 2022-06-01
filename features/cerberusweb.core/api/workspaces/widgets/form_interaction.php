<?php
/**
 * Class WorkspaceWidget_FormInteraction
 */
class WorkspaceWidget_FormInteraction extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		if (!array_key_exists('interactions_kata', $widget->params)) {
			$widget->params['interactions_kata'] = "";
		}
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/form_interaction/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!Context_WorkspaceWidget::isWriteableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch ($action) {
			case 'previewInteractions':
				return $this->_workspaceWidgetConfig_previewInteractions($model);
		}
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
	}
	
	private function _workspaceWidgetConfig_previewInteractions(Model_WorkspaceWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$interactions_kata = DevblocksPlatform::importGPC($_POST['interactions_kata'] ?? null, 'string', '');
		
		$model->params['interactions_kata'] = $interactions_kata;
		
		$values = [
			'caller_name' => 'cerb.toolbar.workspaceWidget.interactions',
			
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $model->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$model->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$toolbar = $this->getInteractions($model, $dict);
		
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/preview.tpl');
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.workspaceWidget.interactions',
			
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$this->renderInteractionChooser($widget, $dict);
	}
	
	function getInteractions(Model_WorkspaceWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$interactions_kata = $widget->params['interactions_kata'];
		
		$results = [];
		
		if (DevblocksPlatform::strStartsWith($interactions_kata, '---')) {
			// Render errors
			if (false == ($interactions_yaml = $tpl_builder->build($interactions_kata, $dict)))
				return false;
			
			if (false == ($interactions = DevblocksPlatform::services()->string()->yamlParse($interactions_yaml, 0)))
				return false;
			
			if (!array_key_exists('behaviors', $interactions))
				return [];
			
			// Transpile YAML->KATA
			if(is_array($interactions) && array_key_exists('behaviors', $interactions))
			foreach($interactions['behaviors'] as $interaction) {
				if(!is_array($interaction))
					continue;
				
				if(!array_key_exists('id', $interaction) || !$interaction['id'])
					continue;
				
				$results[] = [
					'key' => $interaction['id'],
					'type' => 'interaction',
					'label' => $interaction['label'] ?? '',
					'icon' => $interaction['icon'] ?? '',
					'uri' => 'cerb:behavior:' . $interaction['id'],
					'inputs' => $interaction['inputs'] ?? [],
				];
			}
			
		} else {
			$results = DevblocksPlatform::services()->ui()->toolbar()->parse($interactions_kata, $dict);
		}
		
		return $results;
	}
	
	function renderInteractionChooser(Model_WorkspaceWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('dict', $dict);
		
		$interactions = $this->getInteractions($widget, $dict);
		$tpl->assign('interactions', $interactions);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/form_interaction/interaction_chooser.tpl');
	}
}