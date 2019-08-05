<?php
class ProfileWidget_Responsibilities extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.responsibilities';

	function __construct($manifest=null) {
		parent::__construct($manifest);
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
	
	function showResponsibilitiesPopupAction(Model_ProfileWidget $model) {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		
		switch($context) {
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
				
				$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/responsibilities/peek_by_group_editable.tpl');
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
					
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
	
	function saveResponsibilityJsonAction(Model_ProfileWidget $model) {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', '');
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'], 'integer', '');
		@$responsibility = DevblocksPlatform::importGPC($_REQUEST['responsibility'], 'integer', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
	
	function saveResponsibilitiesPopupAction(Model_ProfileWidget $model) {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();

		switch($context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(!$active_worker->isGroupManager($context_id))
					return;
				
				@$responsibilities = DevblocksPlatform::importGPC($_REQUEST['responsibilities'], 'array', []);
				
				if(false == ($group = DAO_Group::get($context_id)))
					return;
				
				$group->setResponsibilities($responsibilities);
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(!$active_worker->is_superuser)
					return;
				
				@$responsibilities = DevblocksPlatform::importGPC($_REQUEST['responsibilities'], 'array', []);
				
				if(false == ($worker = DAO_Worker::get($context_id)))
					return;
				
				$worker->setResponsibilities($responsibilities);
				break;
		}
	}
}
