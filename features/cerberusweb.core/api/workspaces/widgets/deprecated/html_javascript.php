<?php
class WorkspaceWidget_CustomHTML extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	function render(Model_WorkspaceWidget $widget) {
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
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));

		// Clear caches
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(sprintf("widget%d_datasource", $widget->id));
		
		return true;
	}
	
	private function _getHtml($widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder()->newInstance('html');
		
		if(empty($active_worker) || !Context_WorkspaceWidget::isReadableByActor($widget, $active_worker))
			return;
		
		@$content = $widget->params['content'];
		
		$labels = $values = $worker_labels = $worker_values = [];
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $worker_labels, $worker_values, null, true, true);
		CerberusContexts::merge('current_worker_', null, $worker_labels, $worker_values, $labels, $values);
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		$html = $tpl_builder->build($content, $dict);
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
		return DevblocksPlatform::purifyHTML($html, false, true, [$filter]);
	}
};