<?php
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
	
	function showContextWatchersPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'], 'integer', 0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('group_id', $group_id);
		$tpl->assign('bucket_id', $bucket_id);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		$context_labels = array();
		$context_values = array();
		CerberusContexts::getContext($context, $context_id, $context_labels, $context_values);
		$tpl->assign('context_values', $context_values);
		
		// Workers
		
		$sample = CerberusContexts::getWatchers($context, $context_id);
		$population = DAO_Worker::getAllActive();
		
		$worker_picker_data = CerberusApplication::getWorkerPickerData($population, $sample, $group_id, $bucket_id);
		$tpl->assign('worker_picker_data', $worker_picker_data);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_peek.tpl');
	}
	
	function saveContextWatchersPopupJsonAction() {
		@$context = DevblocksPlatform::importGPC($_POST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'], 'integer', 0);
		@$initial_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['initial_sample'], 'array', array()), 'int');
		@$current_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['current_sample'], 'array', array()), 'int');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
	
	function toggleCurrentWorkerAsWatcherAction() {
		@$context = DevblocksPlatform::importGPC($_POST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($context) || empty($context_id) || empty($active_worker))
			return;
		
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