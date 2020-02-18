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

class PageSection_ProfilesCalendarRecurringProfile extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_recurring_profile
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		$default_tz = DevblocksPlatform::getTimezone();
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$calendar_id = DevblocksPlatform::importGPC($_POST['calendar_id'], 'integer', 0);
		@$event_name = DevblocksPlatform::importGPC($_POST['event_name'], 'string', '');
		@$event_start = DevblocksPlatform::importGPC($_POST['event_start'], 'string', '');
		@$event_end = DevblocksPlatform::importGPC($_POST['event_end'], 'string', '');
		@$tz = DevblocksPlatform::importGPC($_POST['tz'], 'string', $default_tz);
		@$recur_start = intval(strtotime(DevblocksPlatform::importGPC($_POST['recur_start'], 'string', '')));
		@$recur_end = intval(strtotime(DevblocksPlatform::importGPC($_POST['recur_end'], 'string', '')));
		@$is_available = DevblocksPlatform::importGPC($_POST['is_available'], 'integer', 0);
		@$patterns = DevblocksPlatform::importGPC($_POST['patterns'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		header("Content-type: application/json");
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_CalendarRecurringProfile::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => intval($id),
					'view_id' => $view_id,
					'action' => 'delete',
				));
				return;
				
			} else {
				if(empty($id)) { // New
					$fields = array(
						DAO_CalendarRecurringProfile::CALENDAR_ID => $calendar_id,
						DAO_CalendarRecurringProfile::EVENT_NAME => $event_name,
						DAO_CalendarRecurringProfile::EVENT_START => $event_start ?: 'midnight',
						DAO_CalendarRecurringProfile::EVENT_END => $event_end ?: '',
						DAO_CalendarRecurringProfile::TZ => $tz,
						DAO_CalendarRecurringProfile::RECUR_START => $recur_start,
						DAO_CalendarRecurringProfile::RECUR_END => $recur_end,
						DAO_CalendarRecurringProfile::IS_AVAILABLE => $is_available ? 1 : 0,
						DAO_CalendarRecurringProfile::PATTERNS => $patterns,
					);
					
					if(!DAO_CalendarRecurringProfile::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CalendarRecurringProfile::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_CalendarRecurringProfile::create($fields)))
						return false;
					
					DAO_CalendarRecurringProfile::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id);
					
				} else { // Edit
					$fields = array(
						DAO_CalendarRecurringProfile::EVENT_NAME => $event_name,
						DAO_CalendarRecurringProfile::EVENT_START => $event_start ?: 'midnight',
						DAO_CalendarRecurringProfile::EVENT_END => $event_end ?: '',
						DAO_CalendarRecurringProfile::TZ => $tz,
						DAO_CalendarRecurringProfile::RECUR_START => $recur_start,
						DAO_CalendarRecurringProfile::RECUR_END => $recur_end,
						DAO_CalendarRecurringProfile::IS_AVAILABLE => $is_available ? 1 : 0,
						DAO_CalendarRecurringProfile::PATTERNS => $patterns,
					);
					
					if(!DAO_CalendarRecurringProfile::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CalendarRecurringProfile::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CalendarRecurringProfile::update($id, $fields);
					DAO_CalendarRecurringProfile::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => intval($id),
					'label' => $event_name,
					'view_id' => $view_id,
					'action' => 'modify',
					'month' => intval(date('m', time())),
					'year' => intval(date('Y', time())),
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
};
