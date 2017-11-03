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

class PageSection_ProfilesCalendarRecurringProfile extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$context = CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING;
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_recurring_profile
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($calendar_recurring_profile = DAO_CalendarRecurringProfile::get($id))) {
			return;
		}
		$tpl->assign('calendar_recurring_profile', $calendar_recurring_profile);
		
		// Dictionary
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context, $calendar_recurring_profile, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Tab persistence
		
		$point = 'profiles.calendar_recurring_profile.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['calendar_id'] = array(
			'label' => mb_ucfirst($translate->_('common.calendar')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
			'value' => $calendar_recurring_profile->calendar_id,
		);
		
		$properties['event_start'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.event_start')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->event_start,
		);
		
		$properties['event_end'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.event_end')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->event_end,
		);
		
		$properties['tz'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.tz')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $calendar_recurring_profile->tz,
		);
		
		$properties['is_available'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.is_available')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $calendar_recurring_profile->is_available,
		);
		
		$properties['recur_start'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.recur_start')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar_recurring_profile->recur_start,
		);
		
		$properties['recur_end'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.recur_end')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar_recurring_profile->recur_end,
		);
		
		$properties['patterns'] = array(
			'label' => mb_ucfirst($translate->_('dao.calendar_recurring_profile.patterns')),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $calendar_recurring_profile->patterns,
		);
			
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $calendar_recurring_profile->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $calendar_recurring_profile->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Search buttons
		
		$owner_counts = array(
			'comments' => DAO_Comment::count($context, $id),
		);
		$tpl->assign('owner_counts', $owner_counts);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$calendar_recurring_profile->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$calendar_recurring_profile->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(!empty($calendar_recurring_profile->calendar_id)) {
			$properties_links[CerberusContexts::CONTEXT_CALENDAR] = array(
				$calendar_recurring_profile->calendar_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CALENDAR,
						$calendar_recurring_profile->calendar_id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, $context);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
	
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/calendar_recurring_profile/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$default_tz = DevblocksPlatform::getTimezone();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'], 'integer', 0);
		@$event_name = DevblocksPlatform::importGPC($_REQUEST['event_name'], 'string', '');
		@$event_start = DevblocksPlatform::importGPC($_REQUEST['event_start'], 'string', '');
		@$event_end = DevblocksPlatform::importGPC($_REQUEST['event_end'], 'string', '');
		@$tz = DevblocksPlatform::importGPC($_REQUEST['tz'], 'string', $default_tz);
		@$recur_start = intval(strtotime(DevblocksPlatform::importGPC($_REQUEST['recur_start'], 'string', '')));
		@$recur_end = intval(strtotime(DevblocksPlatform::importGPC($_REQUEST['recur_end'], 'string', '')));
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'], 'integer', 0);
		@$patterns = DevblocksPlatform::importGPC($_REQUEST['patterns'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR_EVENT_RECURRING, $id, $field_ids);
				
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
