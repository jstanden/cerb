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

class PageSection_ProfilesCalendar extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($calendar = DAO_Calendar::get($id))) { /* @var $calenar Model_Calendar */
			return;
		}
		$tpl->assign('calendar', $calendar);
	
		// Tab persistence
		
		$point = 'profiles.calendar.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => $calendar->owner_context),
			'value' => $calendar->owner_context_id,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $calendar->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALENDAR, $calendar->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CALENDAR, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CALENDAR, $calendar->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Counts
		
		$counts = array(
			'events' => DAO_CalendarEvent::countByCalendar($calendar->id),
			'events_recurring' => DAO_CalendarRecurringProfile::countByCalendar($calendar->id),
		);
		$tpl->assign('counts', $counts);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CALENDAR => array(
				$calendar->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CALENDAR,
						$calendar->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
				
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		$macros = DAO_TriggerEvent::getUsableMacrosByWorker(
			$active_worker,
			'event.macro.calendar'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CALENDAR);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/calendar.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
		
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_Calendar::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.","name");
				
				// Owner
				
				@list($owner_context, $owner_context_id) = explode(':', $owner);
			
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}

				if(empty($owner_context))
					throw new Exception_DevblocksAjaxValidationError("The 'Owner' field is required.", 'owner');
				
				if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You don't have permission to use this owner.", 'owner');
				
				// Clean params
				// [TODO] Move this
				
				if(isset($params['series']))
				foreach($params['series'] as $series_idx => $series) {
					if(isset($series['worklist_model_json'])) {
						$series['worklist_model'] = json_decode($series['worklist_model_json'], true);
						unset($series['worklist_model_json']);
						$params['series'][$series_idx] = $series;
					}
				}
				
				// Model
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Calendar::UPDATED_AT => time(),
						DAO_Calendar::NAME => $name,
						DAO_Calendar::OWNER_CONTEXT => $owner_context,
						DAO_Calendar::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_Calendar::PARAMS_JSON => json_encode($params),
					);
					
					if(false == ($id = DAO_Calendar::create($fields)))
						return new Exception_DevblocksAjaxValidationError("An unexpected error occurred while saving the record.");
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CALENDAR, $id);
					
				} else { // Edit
					if(false == ($calendar = DAO_Calendar::get($id)))
						return;
					
					$fields = array(
						DAO_Calendar::UPDATED_AT => time(),
						DAO_Calendar::NAME => $name,
						DAO_Calendar::OWNER_CONTEXT => $owner_context,
						DAO_Calendar::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_Calendar::PARAMS_JSON => json_encode($params),
					);
					
					$change_fields = Cerb_ORMHelper::uniqueFields($fields, $calendar);
					
					DAO_Calendar::update($id, $change_fields);
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALENDAR, $id, $field_ids);
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $name,
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
};
