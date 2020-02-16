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

class PageSection_SetupMailOutgoing extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$settings = DevblocksPlatform::services()->pluginSettings();
		$response = DevblocksPlatform::getHttpResponse();
		
		$visit->set(ChConfigurationPage::ID, 'mail_outgoing');
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // mail_outgoing
		@$tab = array_shift($stack);
		$tpl->assign('tab', $tab);
		
		$templates = $settings->get('cerberusweb.core',CerberusSettings::MAIL_AUTOMATED_TEMPLATES, '', true);
		$tpl->assign('templates', $templates);
		
		$default_templates = json_decode(CerberusSettingsDefaults::MAIL_AUTOMATED_TEMPLATES, true);
		$tpl->assign('default_templates', $default_templates);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_outgoing/index.tpl');
	}
	
	function saveSettingsJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		$worker = CerberusApplication::getActiveWorker();
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$mail_default_from_id = DevblocksPlatform::importGPC($_POST['mail_default_from_id'],'integer',0);
			
			// [TODO] Validate the settings
			
			$settings = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::MAIL_DEFAULT_FROM_ID, $mail_default_from_id);
			
			echo json_encode([
				'status' => true,
				'message' => DevblocksPlatform::translate('success.saved_changes'),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage()
			]);
			return;
			
		}
	}
	
	function renderTabMailTransportsAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_MailTransport');
		$defaults->id = 'config_mail_transports';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.email_transports');
		$defaults->view_columns = array(
			SearchFields_MailTransport::NAME,
			SearchFields_MailTransport::EXTENSION_ID,
			SearchFields_MailTransport::CREATED_AT,
			SearchFields_MailTransport::UPDATED_AT,
		);
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function renderTabMailSenderAddressesAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Address');
		$defaults->id = 'config_sender_addresses';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.sender_addresses');
		$defaults->view_columns = array(
			SearchFields_Address::HOST,
			SearchFields_Address::UPDATED,
			SearchFields_Address::MAIL_TRANSPORT_ID,
		);
		$defaults->paramsRequired = [
			new DevblocksSearchCriteria(SearchFields_Address::MAIL_TRANSPORT_ID, DevblocksSearchCriteria::OPER_NEQ, 0)
		];
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function saveTemplatesJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$settings = DevblocksPlatform::services()->pluginSettings();
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$templates = DevblocksPlatform::importGPC($_POST['templates'],'array',[]);
			
			// [TODO] Validate templates
			
			$settings->set('cerberusweb.core',CerberusSettings::MAIL_AUTOMATED_TEMPLATES, $templates, true);
			
			echo json_encode([
				'status'=>true,
				'message' => DevblocksPlatform::translate('success.saved_changes'),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'status'=>false,
				'error'=>$e->getMessage()
			]);
			return;
		}
	}
	
	function renderTabMailQueueAction() {
		$tpl = DevblocksPlatform::services()->template();
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_MailQueue');
		$defaults->id = 'config_mail_queue';
		$defaults->name = 'Mail Queue';
		$defaults->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::UPDATED,
			SearchFields_MailQueue::WORKER_ID,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
		);
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->addColumnsHidden(array(
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::TICKET_ID,
			));
			$view->addParamsRequired(array(
				SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED,'=', 1)
			), true);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
}