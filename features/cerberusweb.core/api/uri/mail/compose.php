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

class PageSection_MailCompose extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = $visit->getWorker();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker->hasPriv('core.mail.send'))
			break;
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		// Workers
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Groups+Buckets
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		// SendMailToolbarItem Extensions
		$sendMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.send.toolbaritem', true);
		if(!empty($sendMailToolbarItems))
			$tpl->assign('sendmail_toolbaritems', $sendMailToolbarItems);

		// Attachments				
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));

		// Preferences
		$defaults = array(
			'group_id' => DAO_WorkerPref::get($active_worker->id,'compose.group_id',0),
			'bucket_id' => DAO_WorkerPref::get($active_worker->id,'compose.bucket_id',0),
			'status' => DAO_WorkerPref::get($active_worker->id,'compose.status','waiting'),
		);
		
		// Continue a draft?
		if(null != ($draft_id = @$response->path[2])) {
			$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND %s = %s",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id,
				DAO_MailQueue::TYPE,
				C4_ORMHelper::qstr(Model_MailQueue::TYPE_COMPOSE)
			));
			
			@$draft = $drafts[$draft_id];
			
			if(!empty($drafts)) {
				$tpl->assign('draft', $draft);
				
				// Overload the defaults of the form
				if(isset($draft->params['group_id']))
					$defaults['group_id'] = $draft->params['group_id']; 
				if(isset($draft->params['bucket_id']))
					$defaults['bucket_id'] = $draft->params['bucket_id']; 
			}
		}
		
		$tpl->assign('defaults', $defaults);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, 0);
		$tpl->assign('custom_fields', $custom_fields);

		$default_group_id = isset($defaults['group_id']) ? $defaults['group_id'] : key($groups);
		$group_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, $default_group_id);
		$tpl->assign('group_fields', $group_fields);
		
		// Link to last created ticket
		if($visit->exists('compose.last_ticket')) {
			$ticket_mask = $visit->get('compose.last_ticket');
			$tpl->assign('last_ticket_mask', $ticket_mask);
			$visit->set('compose.last_ticket',null); // clear
		}
		
		$tpl->display('devblocks:cerberusweb.core::mail/section/compose.tpl');		
	}
	
	function sendAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.send'))
			return;
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');

		@$group_or_bucket_id = DevblocksPlatform::importGPC($_POST['group_or_bucket_id'],'string', '');
		@list($group_id, $bucket_id) = explode('_', $group_or_bucket_id, 2);
		$group_id = intval($group_id);
		$bucket_id = intval($bucket_id);
		
		@$org_name = DevblocksPlatform::importGPC($_POST['org_name'],'string');
		@$to = rtrim(DevblocksPlatform::importGPC($_POST['to'],'string'),' ,');
		@$cc = rtrim(DevblocksPlatform::importGPC($_POST['cc'],'string',''),' ,;');
		@$bcc = rtrim(DevblocksPlatform::importGPC($_POST['bcc'],'string',''),' ,;');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$add_me_as_watcher = DevblocksPlatform::importGPC($_POST['add_me_as_watcher'],'integer',0);
		@$options_dont_send = DevblocksPlatform::importGPC($_POST['options_dont_send'],'integer',0);
		
		// No destination?
		if(empty($to)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('mail','compose')));
			return;
		}
		
		// Org
		$org_id = 0;
		if(!empty($org_name)) {
			$org_id = DAO_ContactOrg::lookup($org_name, true);
		} else {
			// If we weren't given an organization, use the first recipient
			$to_addys = CerberusMail::parseRfcAddresses($to);
			if(is_array($to_addys) && !empty($to_addys)) {
				if(null != ($to_addy = DAO_Address::lookupAddress(key($to_addys), true))) {
					if(!empty($to_addy->contact_org_id))
						$org_id = $to_addy->contact_org_id;
				}
			}
		}

		$properties = array(
			'draft_id' => $draft_id,
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'org_id' => $org_id,
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'ticket_reopen' => $ticket_reopen,
		);
		
		// Custom fields
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
		if(!empty($field_values)) {
			$properties['custom_fields'] = $field_values;
		}
		
		// Options
		if(!empty($options_dont_send))
			$properties['dont_send'] = 1;
		
		$ticket_id = CerberusMail::compose($properties);
		
		if(!empty($ticket_id)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
				
			if($add_me_as_watcher)
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $active_worker->id);
				
			// Preferences
			
			DAO_WorkerPref::set($active_worker->id, 'compose.group_id', $group_id);
			DAO_WorkerPref::set($active_worker->id, 'compose.bucket_id', $bucket_id);
		
			// Redirect 
			
			$ticket = DAO_Ticket::get($ticket_id);
			
			$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
			$visit->set('compose.last_ticket', $ticket->mask);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('mail','compose')));
		exit;
	}
	
	function showComposePeekAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	    
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('to', $to);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Groups+Buckets
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Load Defaults
		$subject = $visit->get('compose.defaults.subject', '');
		$tpl->assign('default_subject', $subject);
		
		// Preferences
		$defaults = array(
			'group_id' => DAO_WorkerPref::get($active_worker->id,'compose.group_id',0),
			'bucket_id' => DAO_WorkerPref::get($active_worker->id,'compose.bucket_id',0),
			'status' => DAO_WorkerPref::get($active_worker->id,'compose.status','waiting'),
		);
		$tpl->assign('defaults', $defaults);
		
		$tpl->display('devblocks:cerberusweb.core::mail/section/compose/peek.tpl');
	}
	
	function saveComposePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0); 
		@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer',0); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$cc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_POST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$add_me_as_watcher = DevblocksPlatform::importGPC($_POST['add_me_as_watcher'],'integer',0);

		$visit = CerberusApplication::getVisit();

		// Save Defaults
		$visit->set('compose.defaults.from', $group_id);
		$visit->set('compose.defaults.subject', $subject);
		
		// Send
		$properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'to' => $to,
//			'cc' => $cc,
//			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'ticket_reopen' => $ticket_reopen,
		);
		
		$ticket_id = CerberusMail::compose($properties);

		if($add_me_as_watcher) {
			$active_worker = CerberusApplication::getActiveWorker();
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $active_worker->id);
		}
		
		if(!empty($view_id)) {
			$defaults = new C4_AbstractViewModel();
			$defaults->class_name = 'View_Ticket';
			$defaults->id = $view_id;
			
			$view = C4_AbstractViewLoader::getView($view_id, $defaults);
			$view->render();
		}
		exit;
	}
	
	function getComposeSignatureAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$raw = DevblocksPlatform::importGPC($_REQUEST['raw'],'integer',0);
		
		// Parsed or raw?
		$active_worker = !empty($raw) ? null : CerberusApplication::getActiveWorker();
		
		if(empty($group_id) || null == ($group = DAO_Group::get($group_id))) {
			$replyto_default = DAO_AddressOutgoing::getDefault();
			echo $replyto_default->getReplySignature($active_worker);
			
		} else {
			echo $group->getReplySignature($bucket_id, $active_worker);
		}
	}	
};