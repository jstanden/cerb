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

class PageSection_ProfilesOpportunity extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		
		$context = CerberusContexts::CONTEXT_OPPORTUNITY;
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // opportunity
		@$context_id = intval(array_shift($stack));
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string','');
		
		$id = DevblocksPlatform::importGPC($_POST['opp_id'] ?? null, 'integer',0);
		$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string','');
		$status_id = DevblocksPlatform::importGPC($_POST['status_id'] ?? null, 'integer',0);
		$currency_amount = DevblocksPlatform::importGPC($_POST['currency_amount'] ?? null, 'string','0.00');
		$currency_id = DevblocksPlatform::importGPC($_POST['currency_id'] ?? null, 'integer',0);
		$closed_date_str = DevblocksPlatform::importGPC($_POST['closed_date'] ?? null, 'string','');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer',0);
		
		if(false != ($currency = DAO_Currency::get($currency_id))) {
			$currency_amount = DevblocksPlatform::strParseDecimal($currency_amount, $currency->decimal_at);
		} else {
			$currency_amount = '0';
		}
		
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = (0 != $status_id) ? time() : 0;

		if(!$status_id)
			$closed_date = 0;
			
		// Worker
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');

		try {
			if(!empty($id) && !empty($do_delete)) { // delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_OPPORTUNITY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_CrmOpportunity::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Opportunity::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_OPPORTUNITY, $model->id, $model->name);
				
				DAO_CrmOpportunity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			// Create/Edit
			} else {
				// Load the existing model
				if($id && false == ($opp = DAO_CrmOpportunity::get($id)))
					throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
				
				$fields = array(
					DAO_CrmOpportunity::NAME => $name,
					DAO_CrmOpportunity::CURRENCY_AMOUNT => $currency_amount,
					DAO_CrmOpportunity::CURRENCY_ID => $currency_id,
					DAO_CrmOpportunity::UPDATED_DATE => time(),
					DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
					DAO_CrmOpportunity::STATUS_ID => $status_id,
				);
				
				// Create
				if(empty($id)) {
					$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
					
					if(!DAO_CrmOpportunity::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CrmOpportunity::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CrmOpportunity::create($fields);
					DAO_CrmOpportunity::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_OPPORTUNITY, $id);
					}
					
				// Update
				} else {
					if(!DAO_CrmOpportunity::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CrmOpportunity::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CrmOpportunity::update($id, $fields);
					DAO_CrmOpportunity::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Comments
					DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $id);
				}
			
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _profileAction_showBulkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Permissions
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Opportunity::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null);

		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('opp_ids', implode(',', $id_list));
		}
		
		// Workers
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_OPPORTUNITY)))
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
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/bulk.tpl');
		return true;
	}
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Permissions
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Opportunity::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Filter: whole list or check
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');
		$ids = array();
		
		// View
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		$view = C4_AbstractViewLoader::getView($view_id); /* @var $view View_CrmOpportunity */
		$view->setAutoPersist(false);
		
		// Opp fields
		$status = trim(DevblocksPlatform::importGPC($_POST['status'] ?? null,'string',''));
		$closed_date = trim(DevblocksPlatform::importGPC($_POST['closed_date'] ?? null,'string',''));
		$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'] ?? null,'string',''));

		// Scheduled behavior
		$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'string','');
		$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'] ?? null, 'string','');
		$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array', []);
		
		$do = [];
		
		// Do: Status
		if(0 != strlen($status)) {
			if('deleted' == $status && !($active_worker && $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete'))) {
				// Do nothing if we don't have delete permission
			} else {
				$do['status'] = $status;
			}
		}
		
		// Do: Closed Date
		if(0 != strlen($closed_date))
			@$do['closed_date'] = intval(strtotime($closed_date));
		
		// Do: Worker
		if(0 != strlen($worker_id))
			$do['worker_id'] = $worker_id;

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Watchers
		$watcher_params = [];
		
		$watcher_add_ids = DevblocksPlatform::importGPC($_POST['do_watcher_add_ids'] ?? null, 'array', []);
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		$watcher_remove_ids = DevblocksPlatform::importGPC($_POST['do_watcher_remove_ids'] ?? null, 'array', []);
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.broadcast')) {
			$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'] ?? null, 'string',null);
			$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'] ?? null, 'integer',0);
			$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'] ?? null, 'integer',0);
			$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'] ?? null, 'array',[]);
			$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'] ?? null, 'string',null);
			$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'] ?? null, 'string',null);
			$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'] ?? null, 'string',null);
			$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'] ?? null, 'integer',0);
			$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'] ?? null, 'integer',0);
			$broadcast_status_id = DevblocksPlatform::importGPC($_POST['broadcast_status_id'] ?? null, 'integer',0);
			$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['broadcast_file_ids'] ?? null,'array',array()), 'integer', array('nonzero','unique'));
			
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
					'file_ids' => $broadcast_file_ids,
				];
			}
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
				$opp_ids_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string');
				$ids = DevblocksPlatform::parseCsvString($opp_ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ID, 'in', $ids)
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
}