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

class PageSection_SetupMailSmtp extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_smtp');
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_MailTransport');
		$defaults->id = 'setup_mail_transports';
		
		if(false != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		} 
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_smtp/index.tpl');
	}
	
	function getTransportParamsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string',null);
		
		if(false == ($mail_transport_ext = Extension_MailTransport::get($extension_id)))
			return;
		
		if(empty($id) || false == ($model = DAO_MailTransport::get($id))) {
			$model = new Model_MailTransport();
			$model->extension_id = $mail_transport_ext->id;
		}
		
		$mail_transport_ext->renderConfig($model);
		
		exit;
	}
	
	function testTransportParamsAction() {
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string',null);
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string',null);
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'][$extension_id],'array',array());
		
		$worker = CerberusApplication::getActiveWorker();
		
		try {
			if(!($worker instanceof Model_Worker) || !$worker->is_superuser)
				throw new Exception_DevblocksAjaxError("You are not an administrator.");
			
			if(empty($name))
				throw new Exception_DevblocksAjaxError('The "name" field is required.');
			
			if(empty($extension_id) || false == ($mail_transport_ext = Extension_MailTransport::get($extension_id)))
				throw new Exception_DevblocksAjaxError('The "transport" field is required.');
			
			// Test the transport specfic parameters
			$error = null;
			if(false == $mail_transport_ext->testConfig($params, $error))
				throw new Exception_DevblocksAjaxError($error);
			
		} catch(Exception_DevblocksAjaxError $e) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('status'=>false, 'error'=>$e->getMessage()));
			return;
			
		} catch(Exception $e) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('status'=>false, 'error'=>'A problem occurred. Please check your settings and try again.'));
			return;
		}
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('status'=>true));
	}
	
	function saveTransportPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_MailTransport::delete($id);
			
		} else {
			@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string',null);
			@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string',null);
			@$is_default = DevblocksPlatform::importGPC($_REQUEST['is_default'],'integer',0);
			@$params = DevblocksPlatform::importGPC($_REQUEST['params'][$extension_id],'array',array());
			@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',null);
			
			if(!is_array($params))
				$params = array();
			
			$fields = array(
				DAO_MailTransport::NAME => $name,
				DAO_MailTransport::EXTENSION_ID => $extension_id,
				DAO_MailTransport::PARAMS_JSON => json_encode($params),
			);
			
			if(empty($id)) { // New
				$id = DAO_MailTransport::create($fields);
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, 'cerberusweb.contexts.mail.transport', $id);
				
			} else { // Edit
				DAO_MailTransport::update($id, $fields);
				
			}
			
			if($is_default) {
				DAO_MailTransport::setDefault($id);
			}

			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost('cerberusweb.contexts.mail.transport', $id, $field_ids);
		}		
	}
}