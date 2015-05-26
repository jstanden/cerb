<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerbweb.com	    http://www.webgroupmedia.com/
 ***********************************************************************/

if(class_exists('Extension_PageSection')):
class PageSection_InternalRecommendations extends Extension_PageSection {
	function render() {}
	
	function showContextRecommendationsPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'], 'integer', 0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'], 'integer', 0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		
		$sample = DAO_ContextRecommendation::get($context, $context_id);
		$population = DAO_Worker::getAllActive();
		
		$worker_picker_data = CerberusApplication::getWorkerPickerData($population, $sample, $group_id, $bucket_id);
		$tpl->assign('worker_picker_data', $worker_picker_data);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/recommendations/context_recommend_peek.tpl');
	}
	
	function saveContextRecommendationsPopupJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$initial_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['initial_sample'], 'array', array()), 'int');
		@$current_sample = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['current_sample'], 'array', array()), 'int');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Added
		$additions = array_diff($current_sample, $initial_sample);
		
		foreach($additions as $worker_id)
			DAO_ContextRecommendation::add($context, $context_id, $worker_id);
		
		// Removed
		$removals = array_diff($initial_sample, $current_sample);
		
		foreach($removals as $worker_id)
			DAO_ContextRecommendation::remove($context, $context_id, $worker_id);
		
		// Return JSON data
		header("Content-Type: application/json; charset=". LANG_CHARSET_CODE);
		
		echo json_encode(array(
			'context' => $context,
			'context_id' => $context_id,
			'count' => count($current_sample),
			'has_active_worker' => in_array($active_worker->id, $current_sample),
		));
	}

}
endif;