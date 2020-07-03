<?php
class WorkspaceWidget_Subtotals extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	function render(Model_WorkspaceWidget $widget) {
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
		
		if(null == (self::getViewFromParams($widget, $widget->params, $view_id)))
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
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
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