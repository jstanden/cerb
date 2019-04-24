<?php
class ProfileWidget_CalendarAvailability extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.calendar.availability';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$target_context_id = $model->extension_params['calendar_id'];
		$calendar = null;
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$tpl->assign('widget', $model);
		
		$dao_class = $context_ext->getDaoClass();
		
		if(false == ($record = $dao_class::get($context_id)))
			return;
		
		// Are we showing fields for a different record?
		
		if($target_context_id) {
			$labels = $values = $merge_token_labels = $merge_token_values = [];
			
			CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'record_',
				'Record:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			CerberusContexts::getContext(CerberusContexts::CONTEXT_PROFILE_WIDGET, $model, $merge_token_labels, $merge_token_values, null, true, true);
			
			CerberusContexts::merge(
				'widget_',
				'Widget:',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
			
			$values['widget__context'] = CerberusContexts::CONTEXT_PROFILE_WIDGET;
			$values['widget_id'] = $model->id;
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$context_id = $tpl_builder->build($target_context_id, $dict);
			
			if(false == ($calendar = DAO_Calendar::get($context_id))) {
				return;
			}
		}
		
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if($calendar) {
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
	
			unset($calendar_events);
			
			// Convert availability back to abstract calendar events
	
			$calendar_events = $availability->getAsCalendarEvents($calendar_properties);
			
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
		} else {
			//$calendars = DAO_Calendar::getOwnedByWorker($active_worker);
			//$tpl->assign('calendars', $calendars);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/calendar.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/config.tpl');
	}
	
	function showCalendarAvailabilityTabAction(Model_ProfileWidget $model) {
		@$calendar_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();

		$calendar = DAO_Calendar::get($calendar_id);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('widget', $model);
		
		$start_on_mon = @$calendar->params['start_on_mon'] ? true : false;
		
		$calendar_properties = DevblocksCalendarHelper::getCalendar($month, $year, $start_on_mon);
		$tpl->assign('calendar_properties', $calendar_properties);
		
		if($calendar) {
			$calendar_events = $calendar->getEvents($calendar_properties['date_range_from'], $calendar_properties['date_range_to']);
			
			$availability = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $calendar_events);
	
			unset($calendar_events);
			
			// Convert availability back to abstract calendar events
	
			$calendar_events = $availability->getAsCalendarEvents($calendar_properties);
			
			$tpl->assign('calendar', $calendar);
			$tpl->assign('calendar_events', $calendar_events);
			
		} else {
			$calendars = DAO_Calendar::getOwnedByWorker($active_worker);
			$tpl->assign('calendars', $calendars);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/calendar_availability/calendar.tpl');
	}
}
