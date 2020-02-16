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

class PageSection_ProfilesTimeTracking extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // time_tracking
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_TIMETRACKING;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// Make sure we're an active worker
			if(empty($active_worker) || empty($active_worker->id))
				throw new Exception_DevblocksAjaxValidationError("You must be logged in to edit records.");
			
			@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
			@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
			@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
				
			@$activity_id = DevblocksPlatform::importGPC($_POST['activity_id'],'integer',0);
			@$time_actual_mins = DevblocksPlatform::importGPC($_POST['time_actual_mins'],'integer',0);
			@$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'],'integer',0);
			
			@$comment = DevblocksPlatform::importGPC(@$_POST['comment'],'string','');
			
			// Date
			@$log_date = DevblocksPlatform::importGPC($_POST['log_date'],'string','now');
			if(false == (@$log_date = strtotime($log_date)))
				$log_date = time();
			
			// Delete entries
			if(!empty($id) && !empty($do_delete)) {
				if(false == (DAO_TimeTrackingEntry::get($id)))
					throw new Exception_DevblocksAjaxValidationError("Record not found.");
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_TIMETRACKING)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
					
				DAO_TimeTrackingEntry::delete($id);
						
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
			}
			
			$error = null;
			
			// New or modify
			$fields = array(
				DAO_TimeTrackingEntry::ACTIVITY_ID => intval($activity_id),
				DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => intval($time_actual_mins),
				DAO_TimeTrackingEntry::LOG_DATE => intval($log_date),
				DAO_TimeTrackingEntry::IS_CLOSED => intval($is_closed),
			);
	
			if(empty($id)) { // create
				$fields[DAO_TimeTrackingEntry::WORKER_ID] = intval($active_worker->id);
				
				if(!DAO_TimeTrackingEntry::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_TimeTrackingEntry::onBeforeUpdateByActor($active_worker, $fields, null, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				$id = DAO_TimeTrackingEntry::create($fields);
				DAO_TimeTrackingEntry::onUpdateByActor($active_worker, $fields, $id);
				
				$translate = DevblocksPlatform::getTranslationService();
				$url_writer = DevblocksPlatform::services()->url();
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_POST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_POST['link_context_id'],'integer','');
				
				// Procedurally create a comment
				// [TODO] Check context for 'comment' option
				switch($link_context) {
					// If ticket, add a comment about the timeslip to the ticket
					case CerberusContexts::CONTEXT_OPPORTUNITY:
					case CerberusContexts::CONTEXT_TICKET:
					case CerberusContexts::CONTEXT_TASK:
						if(null != ($active_worker->getEmailModel())) {
							if(!empty($activity_id)) {
								$activity = DAO_TimeTrackingActivity::get($activity_id);
							}
							
							// [TODO] This comment could be added to anything context now using DAO_Comment + Context_*
							$context_comment = sprintf(
								"== %s ==\n".
								"%s %s\n".
								"%s %d\n".
								"%s %s\n".
								"%s".
								"\n".
								"%s\n",
								$translate->_('timetracking.ui.timetracking'),
								$translate->_('timetracking.ui.worker'),
								$active_worker->getName(),
								$translate->_('timetracking.ui.comment.time_spent'),
								$time_actual_mins,
								$translate->_('timetracking.ui.comment.activity'),
								(!empty($activity) ? $activity->name : ''),
								(!empty($comment) ? sprintf("%s: %s\n", DevblocksPlatform::translateCapitalized('common.comment'), $comment) : ''),
								$url_writer->writeNoProxy(sprintf("c=profiles&type=time_tracking&id=%d", $id), true)
							);
							$fields = array(
								DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
								DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
								DAO_Comment::COMMENT => $context_comment,
								DAO_Comment::CREATED => time(),
								DAO_Comment::CONTEXT => $link_context,
								DAO_Comment::CONTEXT_ID => intval($link_context_id),
							);
							DAO_Comment::create($fields);
						}
						break;
						
					case '':
						unset($link_context);
						unset($link_context);
						break;
				}
				
				// Establishing a context link?
				if(isset($link_context) && isset($link_context_id)) {
					// Primary context
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, $link_context, $link_context_id);
					
					// Associated contexts
					switch($link_context) {
						case CerberusContexts::CONTEXT_OPPORTUNITY:
							if(!class_exists('DAO_CrmOpportunity', true))
								break;
								
							$labels = null;
							$values = null;
							CerberusContexts::getContext($link_context, $link_context_id, $labels, $values);
							
							if(is_array($values)) {
								// Is there an org associated with this context?
								if(isset($values['email_org_id']) && !empty($values['email_org_id'])) {
									DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, CerberusContexts::CONTEXT_ORG, $values['email_org_id']);
								}
							}
							break;
							
						case CerberusContexts::CONTEXT_TICKET:
							$labels = null;
							$values = null;
							CerberusContexts::getContext($link_context, $link_context_id, $labels, $values);
							
							if(is_array($values)) {
								// Try the ticket's org
								@$org_id = $values['org_id'];
								
								// Fallback to the initial sender's org
								if(empty($org_id))
									@$org_id = $values['initial_message_sender_org_id'];
								
								// Is there an org associated with this context?
								if(!empty($org_id)) {
									DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TIMETRACKING, $id, CerberusContexts::CONTEXT_ORG, $org_id);
								}
							}
							break;
					}
				}
	
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TIMETRACKING, $id);
				}
				
			} else { // modify
				if(!DAO_TimeTrackingEntry::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_TimeTrackingEntry::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				DAO_TimeTrackingEntry::update($id, $fields);
				DAO_TimeTrackingEntry::onUpdateByActor($active_worker, $fields, $id);
			}
			
			if($id) {
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TIMETRACKING, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Comments
				DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_TIMETRACKING, $id);
			}
			
			$model = new Model_TimeTrackingEntry();
			$model->time_actual_mins = $time_actual_mins;
			$model->worker_id = $active_worker->id;
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $model->getSummary(),
				'view_id' => $view_id,
			));
			return;
			
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
	
	function showBulkPopupAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		// Activities
		$activities = DAO_TimeTrackingActivity::getWhere();
		$tpl->assign('activities', $activities);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TIMETRACKING, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.timetracking::timetracking/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Time Tracking fields
		@$activity = DevblocksPlatform::importGPC($_POST['activity_id'],'string','');
		@$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'],'string','');

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: ...
		if(0 != strlen($is_closed))
			$do['is_closed'] = !empty($is_closed) ? 1 : 0;

		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		if(strlen($activity) > 0)
			$do['activity_id'] = $activity;
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_POST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_POST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

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
				new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::ID, 'in', $ids)
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