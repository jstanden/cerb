<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_SetupCards extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'cards');
		
		$context_manifests = Extension_DevblocksContext::getAll(false, array('cards'));
		$tpl->assign('context_manifests', $context_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cards/index.tpl');
	}
	
	private function _getRecordType($ext_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('ext_id', $ext_id);

		//  Make sure the extension exists before continuing
		if(false == ($context_ext = Extension_DevblocksContext::get($ext_id)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		
		CerberusContexts::getContext($context_ext->id, null, $labels, $null, '', true, false);
		$tpl->assign('labels', $labels);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$properties = DevblocksPlatform::getPluginSetting('cerberusweb.core', 'card:' . $context_ext->id, array(), true);
		
		if(empty($properties))
			$properties = $context_ext->getDefaultProperties();
		
		$tpl->assign('tokens', $properties);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cards/edit_record.tpl');
	}
	
	// Ajax
	function getRecordTypeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id']);
		$this->_getRecordType($ext_id);
	}
	
	function saveRecordTypeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		// Type of custom fields
		@$ext_id = DevblocksPlatform::importGPC($_POST['ext_id'],'string','');
		@$tokens = DevblocksPlatform::importGPC($_POST['tokens'],'array',array());
		
		header('Content-Type: application/json');
		
		DevblocksPlatform::setPluginSetting('cerberusweb.core', 'card:' . $ext_id, $tokens, true);
		echo json_encode(true);
		return;
	}
};