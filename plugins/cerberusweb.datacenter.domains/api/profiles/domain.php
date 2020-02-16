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

class PageSection_ProfilesDomain extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		
		$context = CerberusContexts::CONTEXT_DOMAIN;
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // domain
		@$context_id = intval(array_shift($stack));
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($delete)) { // delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_DOMAIN)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Domain::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
				@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
				@$server_id = DevblocksPlatform::importGPC($_POST['server_id'],'integer',0);
				@$created = DevblocksPlatform::importGPC($_POST['created'],'string','');
				@$contact_address_ids = DevblocksPlatform::importGPC($_POST['contact_address_id'],'array',[]);
				
				if(false == (@$created = strtotime($created)))
					$created = time();
				
				$fields = array(
					DAO_Domain::NAME => $name,
					DAO_Domain::SERVER_ID => $server_id,
					DAO_Domain::CREATED => $created,
				);
				
				// Create/Update
				if(empty($id)) {
					if(!DAO_Domain::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Domain::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Domain::create($fields)))
						throw new Exception_DevblocksAjaxValidationError("There was an error creating the record.");
					
					DAO_Domain::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_DOMAIN, $id);
					}
					
				} else {
					if(!DAO_Domain::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Domain::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Domain::update($id, $fields);
					DAO_Domain::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Comments
				DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_DOMAIN, $id);
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_DOMAIN, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Address context links
				DAO_ContextLink::setContextOutboundLinks(CerberusContexts::CONTEXT_DOMAIN, $id, CerberusContexts::CONTEXT_ADDRESS, $contact_address_ids);
			
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => 'An error occurred.',
				));
				return;
			
		}
	}
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		// Servers
		if(DevblocksPlatform::isPluginEnabled('cerberusweb.datacenter.servers')) {
			$servers = DAO_Server::getAll();
			$tpl->assign('servers', $servers);
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_DOMAIN, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_DOMAIN)))
			return [];
		
		/* @var $context_ext IDevblocksContextBroadcast */
		
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		@$token_labels = $token_values['_labels'] ?: [];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.datacenter.domains::domain/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();
	
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		$status = DevblocksPlatform::importGPC($_POST['status'],'string','');
		$server_id = DevblocksPlatform::importGPC($_POST['server_id'],'string','');
		
		// Delete
		if(strlen($status) > 0) {
			switch($status) {
				case 'deleted':
					if($active_worker->hasPriv('contexts.cerberusweb.contexts.datacenter.domain.delete')) {
						$do['delete'] = true;
					}
					break;
			}
		}
		
		if(strlen($server_id)) {
			$do['server_id'] = intval($server_id);
		}
		
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.datacenter.domain.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'],'integer',0);
			@$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'],'integer',0);
			@$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'],'array',[]);
			@$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'],'integer',0);
			@$broadcast_status_id = DevblocksPlatform::importGPC($_POST['broadcast_status_id'],'integer',0);
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = [
					'to' => $broadcast_to,
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'status_id' => $broadcast_status_id,
					'group_id' => $broadcast_group_id,
					'bucket_id' => $broadcast_bucket_id,
					'worker_id' => $active_worker->id,
				];
			}
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
			@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Domain::ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
};