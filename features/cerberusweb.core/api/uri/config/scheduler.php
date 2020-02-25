<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupScheduler extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'scheduler');
		
		$jobs = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
		$tpl->assign('jobs', $jobs);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/scheduler/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'getJob':
					return $this->_configAction_getJob();
				case 'saveJobJson':
					return $this->_configAction_saveJobJson();
			}
		}
		return false;
	}
	
	private function _configAction_getJob() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');

		if(null == ($job = DevblocksPlatform::getExtension($id, true)))
			return;
			
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('job', $job);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/scheduler/job.tpl');
	}
	
	private function _configAction_saveJobJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		try {
			// Save the job changes
			@$id = DevblocksPlatform::importGPC($_POST['id'],'string','');
			@$enabled = DevblocksPlatform::importGPC($_POST['enabled'],'integer',0);
			@$locked = DevblocksPlatform::importGPC($_POST['locked'],'integer',0);
			@$duration = DevblocksPlatform::importGPC($_POST['duration'],'integer',5);
			@$term = DevblocksPlatform::importGPC($_POST['term'],'string','m');
			@$starting = DevblocksPlatform::importGPC($_POST['starting'],'string','');
					
			$manifest = DevblocksPlatform::getExtension($id);
			$job = $manifest->createInstance(); /* @var $job CerberusCronPageExtension */
	
			if(!empty($starting)) {
				$starting_time = strtotime($starting);
				if(false === $starting_time)
					$starting_time = time();
					
				$starting_time -= CerberusCronPageExtension::getIntervalAsSeconds($duration, $term);
				$job->setParam(CerberusCronPageExtension::PARAM_LASTRUN, $starting_time);
			}
			
			if(!$job instanceof CerberusCronPageExtension)
				throw new Exception("Can't load scheduler job.");

			$job->setParam(CerberusCronPageExtension::PARAM_ENABLED, $enabled);
			$job->setParam(CerberusCronPageExtension::PARAM_LOCKED, $locked);
			$job->setParam(CerberusCronPageExtension::PARAM_DURATION, $duration);
			$job->setParam(CerberusCronPageExtension::PARAM_TERM, $term);
			
			$job->saveConfiguration();
				
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
}