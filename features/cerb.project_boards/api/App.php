<?php
class ProfileWidget_ProjectBoard extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.project_board';

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
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['context_id'];
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($board = DAO_ProjectBoard::get($context_id))) {
				$tpl->display('devblocks:cerb.project_boards::boards/board/empty_tip.tpl');
				return;
			}
		}
		
		$tpl->assign('widget', $model);
		$tpl->assign('board', $board);
		
		$contexts = Extension_DevblocksContext::getAll(false, 'links');
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/board.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerb.project_boards::widgets/project_board/config.tpl');
	}
}

class WorkspaceWidget_ProjectBoard extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.project_board';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$project_board_template = $widget->params['project_board_id'];
		
		$record_dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$board_id = $tpl_builder->build($project_board_template, $record_dict);
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		$tpl->assign('board', $board);
		$tpl->assign('widget', $widget);
		
		$contexts = Extension_DevblocksContext::getAll(false, 'links');
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/board.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		
		$tpl->display('devblocks:cerb.project_boards::workspaces/widgets/project_board/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
}
