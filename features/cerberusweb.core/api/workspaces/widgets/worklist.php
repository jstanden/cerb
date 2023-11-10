<?php
class WorkspaceWidget_Worklist extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	const ID = 'core.workspace.widget.worklist';
	
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}

	function getView(Model_WorkspaceWidget $widget) {
		$view_context = $widget->params['context'] ?? null;
		$query = $widget->params['query'] ?? null;
		$query_required = $widget->params['query_required'] ?? null;
		
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
	
	function render(Model_WorkspaceWidget $widget) {
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
		
		$context = $widget->params['context'] ?? null;
		$columns = $widget->params['columns'] ?? [];
		
		if($context)
			$columns = $this->_getContextColumns($context, $columns);
			
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/worklist/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return match ($action) {
			'getContextColumnsJson' => $this->_widgetConfigAction_getContextColumnsJson($model),
			default => false,
		};
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', array());
		
		// Remove worker view models
		$view_id = sprintf('widget_%d_worklist', $widget->id);
		DAO_WorkerViewModel::deleteByViewId($view_id);
		
		// Save
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
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
	
	public function _widgetConfigAction_getContextColumnsJson(Model_WorkspaceWidget $widget) {
		$context = $widget->params['context'] ?: DevblocksPlatform::importGPC($_POST['context'] ?? '', 'string', '') ?: null;
		$columns = $widget->params['columns'] ?? [];
		
		if($context) {
			$columns = $this->_getContextColumns($context, $columns);
		} else {
			$columns = [];
		}
		
		header('Content-Type: application/json');
		echo json_encode($columns);
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