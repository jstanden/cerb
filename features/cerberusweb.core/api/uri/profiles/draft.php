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

class PageSection_ProfilesDraft extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // draft
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_DRAFT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_DRAFT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_MailQueue::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
				$error = null;
				
				// Load the existing model so we can detect changes
				if (!$id || false == ($draft = DAO_MailQueue::get($id)))
					throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
				
				$fields = [];
				
				// Fields
				@$is_queued = DevblocksPlatform::importGPC($_REQUEST['is_queued'], 'bit', 0);
				@$send_at = DevblocksPlatform::importGPC($_REQUEST['send_at'], 'string', '');
				
				$fields[DAO_MailQueue::IS_QUEUED] = $is_queued;
				$fields[DAO_MailQueue::QUEUE_FAILS] = 0;
				
				if($is_queued) {
					if(!$send_at)
						$send_at = 'now';
					
					$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = strtotime($send_at);
					$draft->params['send_at'] = $send_at;
					
				} else {
					$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = 0;
					$draft->params['send_at'] = $send_at;
				}
				
				$fields[DAO_MailQueue::PARAMS_JSON] = json_encode($draft->params);
				$fields[DAO_MailQueue::UPDATED] = time();
				
				// Save
				if (!empty($id)) {
					if (!DAO_MailQueue::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (!DAO_MailQueue::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailQueue::update($id, $fields);
					DAO_MailQueue::onUpdateByActor($active_worker, $fields, $id);
					
				} else {
					if (!DAO_MailQueue::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (!DAO_MailQueue::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (false == ($id = DAO_MailQueue::create($fields)))
						return false;
					
					DAO_MailQueue::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if (!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_DRAFT, $id);
					}
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if (!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_DRAFT, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => '', // [TODO]
					'view_id' => $view_id,
				));
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
		}
	}
	
	function saveDraft() {
		$active_worker = CerberusApplication::getActiveWorker();
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);

		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');

		$params = [];
		
		$hint_to = null;
		$type = null;
		
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','compose');

		switch($type) {
			case 'compose':
				foreach($_POST as $k => $v) {
					if(substr($k,0,6) == 'field_')
						continue;
					
					$params[$k] = $v;
				}

				// We don't need these fields
				unset($params['c']);
				unset($params['a']);
				unset($params['view_id']);
				unset($params['draft_id']);
				unset($params['group_or_bucket_id']);
				
				// Custom fields
				
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'],'array',array());
				$field_ids = DevblocksPlatform::sanitizeArray($field_ids, 'integer', array('nonzero','unique'));

				if(!empty($field_ids)) {
					$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
					
					if(!empty($field_values)) {
						$params['custom_fields'] = DAO_CustomFieldValue::formatFieldValues($field_values);
					}
				}
				
				$type = 'mail.compose';
				$hint_to = $to;
				break;
				
			default:
				return false;
				break;
		}
		
		$fields = array(
			DAO_MailQueue::TYPE => $type,
			DAO_MailQueue::TICKET_ID => 0,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::NAME => $subject,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		// If there are attachments, link them to this draft record
		if(isset($params['file_ids']) && is_array($params['file_ids']))
			DAO_Attachment::setLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $params['file_ids']);
		
		return $draft_id;
	}
	
	function saveDraftAction() {
		if(false == ($draft_id = $this->saveDraft())) {
			echo json_encode([]);
			return;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('timestamp', time());
		$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
		
		echo json_encode(['draft_id'=>$draft_id, 'html'=>$html]);
	}
	
	function deleteDraftAction() {
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($draft_id)
			&& null != ($draft = DAO_MailQueue::get($draft_id))
			&&
				(
					$active_worker->id == $draft->worker_id
					|| $active_worker->hasPriv('contexts.cerberusweb.contexts.draft.delete')
				)
			) {
			DAO_MailQueue::delete($draft_id);
		}
	}
	
	function showDraftsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Draft fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string'));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_MailQueue::ID, 'in', $ids)
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