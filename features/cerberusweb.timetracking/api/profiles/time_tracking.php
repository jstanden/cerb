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
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'clearEntry':
					return $this->_profileAction_clearEntry();
				case 'pauseTimerJson':
					return $this->_profileAction_pauseTimerJson();
				case 'startTimer':
					return $this->_profileAction_startTimer();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
				case 'viewMarkClosed':
					return $this->_profileAction_viewMarkClosed();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// Make sure we're an active worker
			if(empty($active_worker) || empty($active_worker->id))
				throw new Exception_DevblocksAjaxValidationError("You must be logged in to edit records.");
			
			$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
			$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string','');
			$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer',0);
				
			$activity_id = DevblocksPlatform::importGPC($_POST['activity_id'] ?? null, 'integer',0);
			$time_actual = DevblocksPlatform::importGPC($_POST['time_actual'] ?? null, 'string','');
			$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'] ?? null, 'integer',0);
			
			$comment = DevblocksPlatform::importGPC($_POST['comment'] ?? null,'string','');
			
			// Date
			$log_date = DevblocksPlatform::importGPC($_POST['log_date'] ?? null, 'string','now');
			if(false == (@$log_date = strtotime($log_date)))
				$log_date = time();
			
			// Delete entries
			if(!empty($id) && !empty($do_delete)) {
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_TIMETRACKING)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_TimeTrackingEntry::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_TimeTracking::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_TIMETRACKING, $model->id);
				
				DAO_TimeTrackingEntry::delete($id);
						
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
			}
			
			$error = null;
			
			if(!$time_actual || false === ($time_actual_ts = strtotime($time_actual)))
				throw new Exception_DevblocksAjaxValidationError(sprintf('`%s` is not a valid duration', $time_actual));
			
			$time_actual_secs = $time_actual_ts - time();
			
			// New or modify
			$fields = array(
				DAO_TimeTrackingEntry::ACTIVITY_ID => intval($activity_id),
				DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => ceil($time_actual_secs/60),
				DAO_TimeTrackingEntry::TIME_ACTUAL_SECS => $time_actual_secs,
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
				$link_context = DevblocksPlatform::importGPC($_POST['link_context'] ?? null, 'string','');
				$link_context_id = DevblocksPlatform::importGPC($_POST['link_context_id'] ?? null, 'integer','');
				
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
								$org_id = $values['org_id'] ?? null;
								
								// Fallback to the initial sender's org
								if(empty($org_id))
									$org_id = $values['initial_message_sender_org_id'] ?? null;
								
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
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TIMETRACKING, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Comments
				DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_TIMETRACKING, $id);
			}
			
			$model = new Model_TimeTrackingEntry();
			$model->time_actual_mins = ceil($time_actual_secs/60);
			$model->time_actual_secs = $time_actual_secs;
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
	
	private function _stopTimer() {
		@$time = intval($_SESSION['timetracking_started']);
		
		// If a timer was running
		if(!empty($time)) {
			$elapsed = time() - $time;
			unset($_SESSION['timetracking_started']);
			@$_SESSION['timetracking_total'] = intval($_SESSION['timetracking_total']) + $elapsed;
		}
		
		$total = $_SESSION['timetracking_total'] ?? null;
		if(empty($total))
			return false;
		
		return $total;
	}
	
	private function _destroyTimer() {
		unset($_SESSION['timetracking_id']);
		unset($_SESSION['timetracking_started']);
		unset($_SESSION['timetracking_total']);
	}
	
	private function _getTimer() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = $_SESSION['timetracking_id'] ?? 0;
		
		// If we have an invalid timer, create one
		if(
			!$id
			|| false == ($timer = DAO_TimeTrackingEntry::get($id))
			|| $timer->worker_id != $active_worker->id
		) {
			$id = DAO_TimeTrackingEntry::create([
				DAO_TimeTrackingEntry::IS_CLOSED => 0,
				DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => 0,
				DAO_TimeTrackingEntry::TIME_ACTUAL_SECS => 0,
				DAO_TimeTrackingEntry::WORKER_ID => $active_worker->id,
			]);
			$_SESSION['timetracking_id'] = $id;
			
			$timer = DAO_TimeTrackingEntry::get($id);
		}
		
		return $timer;
	}
	
	private function _profileAction_startTimer() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header("Content-Type: application/json");
		
		@$id = intval(DevblocksPlatform::importGPC($_POST['id'],'integer',0));
		
		$_SESSION['timetracking_id'] = $id;
		
		$timer = $this->_getTimer();
		$total_mins = $timer->time_actual_mins;
		$total_secs = $timer->time_actual_secs;
		
		$_SESSION['timetracking_total'] = $total_secs;
		
		if(!isset($_SESSION['timetracking_started'])) {
			$_SESSION['timetracking_started'] = time();
		}
		
		echo json_encode(array(
			'status' => true,
			'id' => $id,
			'total_mins' => $total_mins,
			'total_secs' => $total_secs,
		));
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_pauseTimerJson() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header("Content-Type: application/json");
		
		$total_secs = $this->_stopTimer();
		
		$timer = $this->_getTimer();
		
		DAO_TimeTrackingEntry::update($timer->id, [
			DAO_TimeTrackingEntry::TIME_ACTUAL_MINS => ceil($total_secs/60),
			DAO_TimeTrackingEntry::TIME_ACTUAL_SECS => $total_secs,
		]);
		
		echo json_encode(array(
			'status' => true,
			'id' => $timer->id,
			'total_mins' => ceil($total_secs/60),
			'total_secs' => $total_secs
		));
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_viewMarkClosed() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$row_ids = DevblocksPlatform::importGPC($_POST['row_id'] ?? null, 'array', []);
		
		$models = DAO_TimeTrackingEntry::getIds($row_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_TimeTracking::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		try {
			if(is_array($models)) {
				foreach($models as $model) {
					DAO_TimeTrackingEntry::update($model->id, array(
						DAO_TimeTrackingEntry::IS_CLOSED => 1,
					));
				}
			}
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_clearEntry() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$this->_destroyTimer();
	}
	
	private function _profileAction_showBulkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_TimeTracking::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null);

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
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_TimeTracking::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Filter: whole list or check
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');
		$ids = array();
		
		// View
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Time Tracking fields
		$activity = DevblocksPlatform::importGPC($_POST['activity_id'] ?? null, 'string','');
		$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'] ?? null, 'string','');

		// Scheduled behavior
		$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'string','');
		$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'] ?? null, 'string','');
		$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array', []);
		
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
		$watcher_params = [];
		
		$watcher_add_ids = DevblocksPlatform::importGPC($_POST['do_watcher_add_ids'] ?? null, 'array', []);
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		$watcher_remove_ids = DevblocksPlatform::importGPC($_POST['do_watcher_remove_ids'] ?? null, 'array', []);
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				$ids_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string');
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
}