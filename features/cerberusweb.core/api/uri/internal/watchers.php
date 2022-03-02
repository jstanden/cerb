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

class PageSection_InternalWatchers extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'showContextWatchersPopup':
					return $this->_internalAction_showContextWatchersPopup();
				case 'saveContextWatchersPopupJson':
					return $this->_internalAction_saveContextWatchersPopupJson();
				case 'toggleCurrentWorkerAsWatcher':
					return $this->_internalAction_toggleCurrentWorkerAsWatcher();
			}
		}
		return false;
	}
	
	private function _internalAction_showContextWatchersPopup() {
		$tpl = DevblocksPlatform::services()->template();
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'] ?? null, 'integer', 0);
		$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'] ?? null, 'integer', 0);
		$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'] ?? null, 'integer', 0);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('group_id', $group_id);
		$tpl->assign('bucket_id', $bucket_id);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('context_ext', $context_ext);
		
		if(false == ($dao_class = $context_ext->getDaoClass()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($model = call_user_func([$dao_class, 'get'], $context_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$context_labels = $context_values = [];
		CerberusContexts::getContext($context, $model, $context_labels, $context_values);
		$tpl->assign('context_values', $context_values);
		
		// Workers
		
		$sample = CerberusContexts::getWatchers($context, $context_id);
		$population = DAO_Worker::getAllActive();
		
		$worker_picker_data = CerberusApplication::getWorkerPickerData($population, $sample, $group_id, $bucket_id);
		$tpl->assign('worker_picker_data', $worker_picker_data);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_peek.tpl');
	}
	
	private function _internalAction_saveContextWatchersPopupJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer', 0);
		$initial_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['initial_sample'] ?? null, 'array', []), 'int');
		$current_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['current_sample'] ?? null, 'array', []), 'int');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!CerberusContexts::isWriteableByActor($context, $context_id, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Added
		$additions = array_diff($current_sample, $initial_sample);
		CerberusContexts::addWatchers($context, $context_id, $additions);
		
		// Removed
		$removals = array_diff($initial_sample, $current_sample);
		CerberusContexts::removeWatchers($context, $context_id, $removals);
		
		// Return JSON data
		header("Content-Type: application/json; charset=". LANG_CHARSET_CODE);
		
		echo json_encode(array(
			'context' => $context,
			'context_id' => $context_id,
			'count' => count($current_sample),
			'has_active_worker' => in_array($active_worker->id, $current_sample),
		));
	}
	
	private function _internalAction_toggleCurrentWorkerAsWatcher() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer', 0);
		
		if(!$context || !$context_id || !$active_worker)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$worker_id = $active_worker->id;
		
		$watchers = CerberusContexts::getWatchers($context, $context_id);
		
		if(!isset($watchers[$worker_id])) {
			CerberusContexts::addWatchers($context, $context_id, array($worker_id));
			$watchers[$worker_id] = $active_worker;
		
		} else {
			CerberusContexts::removeWatchers($context, $context_id, array($worker_id));
			unset($watchers[$worker_id]);
		}
		
		// Return JSON data
		header("Content-Type: application/json; charset=". LANG_CHARSET_CODE);
		
		echo json_encode(array(
			'context' => $context,
			'context_id' => $context_id,
			'count' => count($watchers),
			'has_active_worker' => isset($watchers[$worker_id]),
		));
	}
}