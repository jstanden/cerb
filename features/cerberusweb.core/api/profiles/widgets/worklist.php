<?php
class ProfileWidget_Worklist extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.worklist';
	
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		@$view_context = $model->extension_params['context'];
		@$query = $model->extension_params['query'];
		@$query_required = $model->extension_params['query_required'];
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Unique instance per widget/record combo
		$view_id = sprintf('profile_widget_%d_%d', $model->id, $context_id);
		
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
			$defaults->view_columns = $model->extension_params['columns'];
			$defaults->options['header_color'] = @$model->extension_params['header_color'] ?: '#626c70';
			$defaults->renderLimit = DevblocksPlatform::intClamp(@$model->extension_params['render_limit'], 1, 50);
			
			if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($defaults, false)))
				return;
		}
		
		$view->renderPage = 0;
		
		// [TODO] Adding params this way invalidates the view checksum
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if($query_required) {
			$query_required = $tpl_builder->build($query_required, $dict);
		}
		
		$view->addParamsRequiredWithQuickSearch($query_required);
		
		if($query) {
			$query = $tpl_builder->build($query, $dict);
		}
		
		$view->setParamsQuery($query);
		$view->addParamsWithQuickSearch($query);
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('context_mfts', $context_mfts);
		
		@$context = $model->extension_params['context'];
		@$columns = @$model->extension_params['columns'] ?: [];
		
		if($context)
			$columns = $this->_getContextColumns($context, $columns);
			
		$tpl->assign('columns', $columns);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/worklist/config.tpl');
	}
	
	function saveConfig(array $fields, $id=null, &$error=null) {
		if($id) {
			// Remove worker view models
			$view_id = sprintf('profile_widget_%d_', $id);
			DAO_WorkerViewModel::deleteByViewIdPrefix($view_id);
		}
		
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
}