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

class PageSection_ProfilesCalendarEvent extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_event
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
		$date_start = DevblocksPlatform::importGPC($_POST['date_start'] ?? null, 'string', '');
		$date_end = DevblocksPlatform::importGPC($_POST['date_end'] ?? null, 'string', '');
		$is_available = DevblocksPlatform::importGPC($_POST['is_available'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'integer', 0);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$calendar_id = DevblocksPlatform::importGPC($_POST['calendar_id'] ?? null, 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header("Content-type: application/json");
		
		try {
			// Delete
			if(!empty($do_delete) && !empty($id)) {
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CALENDAR_EVENT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_CalendarEvent::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_CalendarEvent::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CALENDAR_EVENT, $model->id, $model->name);
				
				DAO_CalendarEvent::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => intval($id),
					'event_id' => intval($id),
					'view_id' => $view_id,
					'action' => 'delete',
				));
				return;
			}
			
			// Start/end times
			
			@$timestamp_start = strtotime($date_start);
			
			if(empty($timestamp_start))
				$timestamp_start = time();
			
			@$timestamp_end = strtotime($date_end, $timestamp_start);
	
			if(empty($timestamp_end))
				$timestamp_end = $timestamp_start;
				
			// If the second timestamp is smaller, add a day
			if($timestamp_end < $timestamp_start)
				$timestamp_end = strtotime("+1 day", $timestamp_end);
			
			// Fields
			
			$fields = array(
				DAO_CalendarEvent::NAME => $name,
				DAO_CalendarEvent::DATE_START => $timestamp_start,
				DAO_CalendarEvent::DATE_END => $timestamp_end,
				DAO_CalendarEvent::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
				DAO_CalendarEvent::CALENDAR_ID => $calendar_id,
			);
			
			if(empty($id)) {
				if(!DAO_CalendarEvent::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_CalendarEvent::onBeforeUpdateByActor($active_worker, $fields, null, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				$id = DAO_CalendarEvent::create($fields);
				DAO_CalendarEvent::onUpdateByActor($active_worker, $fields, $id);
				
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR_EVENT, $id);
				}
				
			} else {
				if(!($calendar_event = DAO_CalendarEvent::get($id)))
					return;
				
				$changed_fields = Cerb_ORMHelper::uniqueFields($fields, $calendar_event);
				
				if(!DAO_CalendarEvent::validate($changed_fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_CalendarEvent::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!empty($changed_fields)) {
					DAO_CalendarEvent::update($id, $changed_fields);
					DAO_CalendarEvent::onUpdateByActor($active_worker, $fields, $id);
				}
			}
			
			// Custom field saves
			$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
			if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT, $id, $field_ids, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			echo json_encode(array(
				'status' => true,
				'id' => intval($id),
				'label' => $name,
				'view_id' => $view_id,
				'event_id' => intval($id),
				'action' => 'modify',
				'month' => intval(date('m', $timestamp_start)),
				'year' => intval(date('Y', $timestamp_start)),
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};