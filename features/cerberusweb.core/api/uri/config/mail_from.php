<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class PageSection_SetupMailFrom extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$settings = DevblocksPlatform::services()->pluginSettings();
		
		$visit->set(ChConfigurationPage::ID, 'mail_from');

		$addresses = DAO_AddressOutgoing::getAll();
		$tpl->assign('addresses', $addresses);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_from/index.tpl');
	}

	function peekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(!empty($id) && null != ($address = DAO_AddressOutgoing::get($id)))
			$tpl->assign('address', $address);
			
		// Mail transports
		$mail_transports = DAO_MailTransport::getAll();
		$tpl->assign('mail_transports', $mail_transports);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Signature
		$worker_token_labels = array();
		$worker_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($worker_token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_from/peek.tpl');
	}
	
	function savePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$form_action = DevblocksPlatform::importGPC($_REQUEST['form_action'], 'string', '');
		@$is_default = DevblocksPlatform::importGPC($_REQUEST['is_default'], 'integer', 0);
		@$reply_from = DevblocksPlatform::importGPC($_REQUEST['reply_from'], 'string', '');
		@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'], 'string', '');
		@$reply_signature = DevblocksPlatform::importGPC($_REQUEST['reply_signature'], 'string', '');
		@$reply_html_template_id = DevblocksPlatform::importGPC($_REQUEST['reply_html_template_id'], 'integer', 0);
		@$reply_mail_transport_id = DevblocksPlatform::importGPC($_REQUEST['reply_mail_transport_id'], 'integer', 0);

		$worker = CerberusApplication::getActiveWorker();
	
		// [TODO] Throw exceptions and switch this to a proper ajax editor popup
		if(!$worker || !$worker->is_superuser)
			return;
		
		switch($form_action) {
			case 'delete':
				DAO_AddressOutgoing::delete($id);
				break;
				
			default:
				if(empty($id)) { // create
					if(false === ($address = DAO_Address::lookupAddress($reply_from, true)))
						return;
						
					$id = $address->id;
					
					$fields = array(
						DAO_AddressOutgoing::ADDRESS_ID => $id,
						DAO_AddressOutgoing::REPLY_PERSONAL => $reply_personal,
						DAO_AddressOutgoing::REPLY_SIGNATURE => $reply_signature,
						DAO_AddressOutgoing::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
						DAO_AddressOutgoing::REPLY_MAIL_TRANSPORT_ID => $reply_mail_transport_id,
					);
					DAO_AddressOutgoing::create($fields);
					
				} else { // update
					$fields = array(
						DAO_AddressOutgoing::REPLY_PERSONAL => $reply_personal,
						DAO_AddressOutgoing::REPLY_SIGNATURE => $reply_signature,
						DAO_AddressOutgoing::REPLY_HTML_TEMPLATE_ID => $reply_html_template_id,
						DAO_AddressOutgoing::REPLY_MAIL_TRANSPORT_ID => $reply_mail_transport_id,
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