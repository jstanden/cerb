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
	
	function addRecommendationAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		
		DAO_ContextRecommendation::add($context, $context_id, $worker_id);
	}
	
	function removeRecommendationAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		
		DAO_ContextRecommendation::remove($context, $context_id, $worker_id);
	}
	
	function renderPickerAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$recommendations_expanded = DevblocksPlatform::importGPC($_REQUEST['recommendations_expanded'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$recommendations = array();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$tpl->assign('recommendations_expanded', $recommendations_expanded ? true : false);
		
		if(false == ($ticket = DAO_Ticket::get($context_id)))
			return;
		
		// Owner
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'], 'integer', 0);
		$ticket->owner_id = $owner_id;
		$tpl->assign('owner_id', $ticket->owner_id);
		
		// Importance
		@$importance = DevblocksPlatform::importGPC($_REQUEST['importance'], 'integer', 0);
		$ticket->importance = $importance;
		
		// Group+Bucket
		$group_id = $_REQUEST['group_id'];
		$bucket_id = $_REQUEST['bucket_id'];
		$ticket->group_id = $group_id;
		$ticket->bucket_id = $bucket_id;
		
		$recommendations = DAO_ContextRecommendation::get($context, $context_id);
		
		if($ticket->owner_id && !in_array($ticket->owner_id, $recommendations))
			$recommendations[] = $ticket->owner_id;
		
		$tpl->assign('recommended_workers', $recommendations);
		
		$recommendation_scores = DAO_ContextRecommendation::nominate($ticket);
		$tpl->assign('recommendation_scores', $recommendation_scores);
		
		// Workloads
		
		$workloads = DAO_Worker::getWorkloads();
		$tpl->assign('workloads', $workloads);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/recommendations/_worker_recommendation_picker.tpl');
	}
}
endif;