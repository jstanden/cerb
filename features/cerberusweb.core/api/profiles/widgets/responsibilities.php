<?php
class ProfileWidget_Responsibilities extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.responsibilities';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}

	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'renderPopup':
				return $this->_profileWidgetAction_renderPopup($model);
			case 'savePopupJson':
				return $this->_profileWidgetAction_savePopupJson($model);
		}
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		switch($context) {
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
					
				$tpl->assign('worker', $worker);
				
				$responsibilities = $worker->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$tpl->assign('widget', $model);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/tab_by_worker_readonly.tpl');
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				if(false == ($group = DAO_Group::get($context_id)))
					return;
					
				$tpl->assign('group', $group);
				
				$buckets = $group->getBuckets();
				$tpl->assign('buckets', $buckets);
				
				$members = $group->getMembers();
				$tpl->assign('members', $members);
				
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$responsibilities = $group->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->assign('widget', $model);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/tab_by_group_readonly.tpl');
				break;
		}
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		//$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
	
	private function _profileWidgetAction_renderPopup(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'] ?? null, 'string', '');
		
		$tpl->assign('widget', $model);
		
		switch($context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(false == ($group = DAO_Group::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Group::isWriteableByActor($group, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('group', $group);
				
				$buckets = $group->getBuckets();
				$tpl->assign('buckets', $buckets);
				
				$members = $group->getMembers();
				$tpl->assign('members', $members);
				
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$responsibilities = $group->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/peek_by_group_editable.tpl');
				break;
			
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Worker::isWriteableByActor($worker, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
					
				$tpl->assign('worker', $worker);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$memberships = $worker->getMemberships();
				$tpl->assign('memberships', $memberships);
				
				$responsibilities = $worker->getResponsibilities();
				$tpl->assign('responsibilities', $responsibilities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/peek_by_worker_editable.tpl');
				break;
		}
	}
	
	private function _profileWidgetAction_savePopupJson(Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'integer', '');
		$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'] ?? null, 'integer', '');
		$responsibility = DevblocksPlatform::importGPC($_POST['responsibility'] ?? null, 'integer', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(false == ($bucket = DAO_Bucket::get($bucket_id)))
				throw new Exception_DevblocksAjaxValidationError("Invalid bucket.");
			
			if(!$active_worker->isGroupManager($bucket->group_id))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			
			DAO_Worker::setResponsibility($worker_id, $bucket_id, $responsibility);
			
			echo json_encode([ 'status' => true ]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([ 'status' => false, 'error' => $e->getMessage() ]);
		}
	}
}
