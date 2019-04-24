<?php
class WorkspaceWidget_CustomHTML extends Extension_WorkspaceWidget {
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