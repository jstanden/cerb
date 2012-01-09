<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupMailFrom extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$visit->set(ChConfigurationPage::ID, 'mail_from');

		$addresses = DAO_AddressOutgoing::getAll();
		$tpl->assign('addresses', $addresses);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_from/index.tpl');
	}

	function peekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($address = DAO_AddressOutgoing::get($id)))
			$tpl->assign('address', $address);
			
		// Signature
		$worker_token_labels = array();
		$worker_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
		$tpl->assign('worker_token_labels', $worker_token_labels);
			
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_from/peek.tpl');
	}
	
	function savePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$form_action = DevblocksPlatform::importGPC($_REQUEST['form_action'], 'string', '');
		@$is_default = DevblocksPlatform::importGPC($_REQUEST['is_default'], 'integer', 0);
		@$reply_from = DevblocksPlatform::importGPC($_REQUEST['reply_from'], 'string', '');
		@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'], 'string', '');
		@$reply_signature = DevblocksPlatform::importGPC($_REQUEST['reply_signature'], 'string', '');

		$worker = CerberusApplication::getActiveWorker();
	
		if(!$worker || !$worker->is_superuser)
			throw new Exception("You are not an administrator.");
		
		switch($form_action) {
			case 'delete':
				DAO_AddressOutgoing::delete($id);
				break;
				
			default:
				if(empty($id)) { // create
					if(false === ($address = DAO_Address::lookupAddress($reply_from, true)))
						throw new Exception();
						
					$id = $address->id;
					
					$fields = array(
						DAO_AddressOutgoing::ADDRESS_ID => $id,
						DAO_AddressOutgoing::REPLY_PERSONAL => $reply_personal,
						DAO_AddressOutgoing::REPLY_SIGNATURE => $reply_signature,
					);
					DAO_AddressOutgoing::create($fields);
					
				} else { // update
					$fields = array(
						DAO_AddressOutgoing::REPLY_PERSONAL => $reply_personal,
						DAO_AddressOutgoing::REPLY_SIGNATURE => $reply_signature,
					);
					DAO_AddressOutgoing::update($id, $fields);
					
				}
				
				if(!empty($is_default))
					DAO_AddressOutgoing::setDefault($id);
				
				break;
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','mail_from')));
		exit;
	}
	
}