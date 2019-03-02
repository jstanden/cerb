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

class PageSection_SetupRecords extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		$visit->set(ChConfigurationPage::ID, 'records');
		
		$context_manifests = Extension_DevblocksContext::getAll(false, array('cards'));
		$tpl->assign('context_manifests', $context_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/records/index.tpl');
	}
	
	function showRecordPopupAction() {
		@$context_ext_id = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(!$context_ext_id || false == ($context_ext = Extension_DevblocksContext::get($context_ext_id, true)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context_ext->id, null, $labels, $values, '', true, false);
		$tpl->assign('labels', $labels);
		
		// =================================================================
		// Displayed fields
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tokens = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'card:' . $context_ext->id, [], true);
		
		if(empty($tokens))
			$tokens = $context_ext->getDefaultProperties();
		
		$tpl->assign('tokens', $tokens);
		
		// =================================================================
		// Search buttons
		
		$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
		$tpl->assign('search_contexts', $search_contexts);
		
		$search_buttons = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'card:search:' . $context_ext->id, [], true);
		$tpl->assign('search_buttons', $search_buttons);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::configuration/section/records/edit_record_popup.tpl');
	}
	
	function saveRecordPopupAction() {
		@$context_mft_id = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$tokens = DevblocksPlatform::importGPC($_REQUEST['tokens'],'array',[]);
		@$search = DevblocksPlatform::importGPC($_REQUEST['search'],'array',[]);
		@$profile_tabs = DevblocksPlatform::importGPC($_REQUEST['profile_tabs'],'array',[]);
		
		// Permissions
		if(false == ($active_worker = CerberusApplication::getActiveWorker())
			|| !$active_worker->is_superuser)
				return;
		
		header('Content-Type: application/json');
		
		if(!$context_mft_id || false == ($context_mft = Extension_DevblocksContext::get($context_mft_id, false)))
			return;
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'card:' . $context_mft->id, $tokens, true);
		
		$search_buttons = [];
		if(is_array($search) && array_key_exists('context', $search))
		foreach(array_keys($search['context']) as $idx) {
			$search_buttons[] = [
				'context' => $search['context'][$idx],
				'label_singular' => $search['label_singular'][$idx],
				'label_plural' => $search['label_plural'][$idx],
				'query' => $search['query'][$idx],
			];
		}
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'card:search:' . $context_mft->id, $search_buttons, true);
		
		echo json_encode(true);
	}
};