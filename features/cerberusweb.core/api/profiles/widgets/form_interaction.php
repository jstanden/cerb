<?php
/**
 * Class ProfileWidget_FormInteraction
 */
class ProfileWidget_FormInteraction extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.form_interaction';
	
	function __construct($manifest = null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function renderConfig(Model_ProfileWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!array_key_exists('interactions_kata', $widget->extension_params)) {
			$widget->extension_params['interactions_kata'] = "";
		}
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/form_interaction/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isWriteableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'previewInteractions':
				return $this->_profileWidgetConfig_previewInteractions($model);
		}
		return false;
	}
	
	private function _profileWidgetConfig_previewInteractions(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$interactions_kata = DevblocksPlatform::importGPC($_POST['interactions_kata'] ?? null, 'string', '');
		
		$model->extension_params['interactions_kata'] = $interactions_kata;
		
		if(false == ($profile_tab = $model->getProfileTab()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($record_context_ext = Extension_DevblocksContext::getByAlias($profile_tab->context, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$dao_class = $record_context_ext->getDaoClass();
		
		if($dao_class && method_exists($dao_class, 'random')) {
			$values = [
				'caller_name' => 'cerb.toolbar.profileWidget.interactions',
				
				'record__context' => $record_context_ext->id,
				'record_id' => $dao_class::random(),
				
				'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
				'widget_id' => $model->id,
				
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id,
				
			];
			
		} else {
			$values = [];
		}
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$toolbar = $this->getInteractions($model, $dict);
		
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/preview.tpl');
	}
	
	function render(Model_ProfileWidget $widget, $context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();

		$dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.profileWidget.interactions',
			
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $widget->id,
			
			'record__context' => $context,
			'record_id' => $context_id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);

		$this->renderInteractionChooser($widget, $dict);
	}
	
	function getInteractions(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$interactions_kata = $widget->extension_params['interactions_kata'];
		
		$results = [];
		
		if(DevblocksPlatform::strStartsWith($interactions_kata, '---')) {
			// Render errors
			if(false == ($interactions_yaml = $tpl_builder->build($interactions_kata, $dict)))
				return false;
			
			if(false == ($interactions = DevblocksPlatform::services()->string()->yamlParse($interactions_yaml, 0)))
				return false;
			
			if(!array_key_exists('behaviors', $interactions))
				return [];
			
			// Transpile YAML->KATA
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
	
	function renderInteractionChooser(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('dict', $dict);
		
		$interactions = $this->getInteractions($widget, $dict);
		$tpl->assign('interactions', $interactions);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/form_interaction/interaction_chooser.tpl');
	}
}