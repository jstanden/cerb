<?php
class CalendarDatasource_Calendar extends Extension_CalendarDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		$matches = [];
		
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}

	function renderConfig(Model_Calendar $calendar, $params, $params_prefix) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('calendar', $calendar);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		if($calendar->owner_context) {
			$calendars = DAO_Calendar::getReadableByActor([$calendar->owner_context, $calendar->owner_context_id]);
		} else {
			$calendars = DAO_Calendar::getReadableByActor([CerberusContexts::CONTEXT_WORKER, $active_worker->id]);
		}
		
		// We need to exclude the current calendar from being sync'd to itself
		unset($calendars[$calendar->id]);
		
		$tpl->assign('calendars', $calendars);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/calendar/config.tpl');
	}
	
	function getData(Model_Calendar $calendar, array $params=[], $params_prefix=null, $date_range_from=null, $date_range_to=null, $timezone=null) {
		if(
			!array_key_exists('sync_calendar_id', $params)
			|| $params['sync_calendar_id'] == $calendar->id // No infinite recursion
			|| false == ($sync_calendar = DAO_Calendar::get($params['sync_calendar_id']))
		) {
			return [];
		}
		
		$calendar_events = [];
		
		if(!$timezone)
			$timezone = $calendar->timezone;
		
		$sync_data = $sync_calendar->getEvents($date_range_from, $date_range_to,true, $timezone);
		
		if(is_array($sync_data))
		foreach($sync_data as $epoch => $sync_events) {
			if(is_array($sync_events))
			foreach($sync_events as $sync_event) {
				$sync_event['link'] = null;
				$sync_event['calendar_id'] = $sync_calendar->id;
				$calendar_events[$epoch][] = $sync_event;
			}
		}
		
		return $calendar_events;
	}
};

class CalendarDatasource_Worklist extends Extension_CalendarDatasource {
	private function _getSeriesIdxFromPrefix($params_prefix) {
		$matches = [];
		
		if(!empty($params_prefix) && preg_match("#\[series\]\[(\d+)\]#", $params_prefix, $matches) && count($matches) == 2) {
			return $matches[1];
		}
		
		return null;
	}
	
	function renderConfig(Model_Calendar $calendar, $params, $params_prefix) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('calendar', $calendar);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params_prefix);
		
		if(null !== ($series_idx = $this->_getSeriesIdxFromPrefix($params_prefix)))
			$tpl->assign('series_idx', $series_idx);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('context_mfts', $context_mfts);
		
		$worklist_context = $params['worklist_model']['context'] ?? null;
		
		// Load the initial fields from the context
		
		if(!empty($worklist_context)) {
			if(null != ($ctx = Extension_DevblocksContext::get($worklist_context))) {
				if(null != ($view_class = $ctx->getViewClass())) { /* @var $view_class C4_AbstractView */
					if(null != ($view = new $view_class))
						$tpl->assign('ctx_fields', $view->getFields());
				}
				
				$labels = $values = [];
				
				CerberusContexts::getContext($ctx->id, null, $labels, $values, null, true);
				$tpl->assign('placeholders', $labels);
			}
		}
		
		// Prime the worklist views
		
		$worklist_view_id = sprintf("calendar%d_worklist%d", $calendar->id ?? 0, $series_idx ?? 0);
		$worklist_model = $params['worklist_model'] ?? null;
		
		C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $worklist_view_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/worklist/config.tpl');
	}
	
	function getData(Model_Calendar $calendar, array $params=array(), $params_prefix=null, $date_range_from=null, $date_range_to=null, $timezone=null) {
		$calendar_events = array();

		@$series_idx = $this->_getSeriesIdxFromPrefix($params_prefix);
		
		if(isset($params['worklist_model'])) {
			$worklist_context = $params['worklist_model']['context'] ?? null;
			
			if(!empty($worklist_context) && null != ($context_ext = Extension_DevblocksContext::get($worklist_context))) {
				$worklist_model = $params['worklist_model'];
				$view_id = sprintf("calendar%d_worklist%d", $calendar->id, $series_idx);
				
				if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id))) {
					/* @var $view C4_AbstractView */
					
					@$field_start_date = $params['field_start_date'] ?: '';
					@$field_end_date = $params['field_end_date'] ?: $field_start_date;
					$field_start_date_offset = 0;
					$field_end_date_offset = 0;
					
					$view->removeParam($field_start_date);
					$view->removeParam($field_end_date);
					
					if(isset($params['field_start_date_offset'])) {
						if(false !== ($offset = strtotime($params['field_start_date_offset'])))
							$field_start_date_offset = $offset - time();
					}
					
					if(isset($params['field_end_date_offset'])) {
						if(false !== ($offset = strtotime($params['field_end_date_offset'])))
							$field_end_date_offset = $offset - time();
					}
					
					// Include any events that start before the calendar ends, and end after it starts
					
					$view->addParam(
						new DevblocksSearchCriteria($field_start_date, DevblocksSearchCriteria::OPER_CUSTOM,
							array('where' => sprintf("%s <= %d",
								(!empty($field_start_date_offset) ? (' + ' . $field_start_date_offset) : ''),
								$date_range_to
							))
						),
						'filter_range_start' // $params['field_start_date']
					);
					
					$view->addParam(
						new DevblocksSearchCriteria($field_end_date, DevblocksSearchCriteria::OPER_CUSTOM,
							array('where' => sprintf("%s >= %d",
								(!empty($field_end_date_offset) ? (' + ' . $field_end_date_offset) : ''),
								$date_range_from
							))
						),
						'filter_range_end' // $params['field_end_date']
					);
					
					$view->view_columns = array_unique([
						$field_start_date,
						$field_end_date
					]);
					$view->renderPage = 0;
					$view->renderLimit = 0;
					$view->setAutoPersist(false);

					// [TODO] Paging
					// [TODO] Subtotals
					
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					$template = $params['label'] ?? null;
					
					if(empty($template))
						$template = '{{_label}}';
					
					list($results,) = $view->getData();

					if(is_array($results))
					foreach($results as $id => $row) {
						$ts_offset = @$params['field_start_date_offset'] ?: 'now';
						$ts = strtotime($ts_offset, $row[$field_start_date]);
						
						$labels = $values = [];
						
						// [TODO] This should be more efficient
						// [TODO] It can be with the new lazy loader (we can preload the requested tokens)
						CerberusContexts::getContext($context_ext->id, $id, $labels, $values);
						
						$ts_end_offset = @$params['field_end_date_offset'] ?: 'now';
						$ts_end = strtotime($ts_end_offset, $row[$field_end_date]);
						
						if(empty($ts_end))
							$ts_end = $ts;
						
						// If the worklist-fed event spans multiple days, split them up into distinct events
						$ts_pointer = strtotime('midnight', $ts);
						$day_range = array();
						
						while($ts_pointer < $ts_end) {
							$day_range[] = $ts_pointer;
							$ts_pointer = strtotime('tomorrow', $ts_pointer);
						}
						
						foreach($day_range as $epoch) {
							$day_start = strtotime('midnight', $epoch);
							$day_end = strtotime('23:59:59', $epoch);

							$event_start = $ts;
							$event_end = $ts_end;
							
							if($event_start < $day_start)
								$event_start = $day_start;
							
							if($event_end > $day_end)
								$event_end = $day_end;
							
							$calendar_events[$epoch][] = array(
								'context' => $context_ext->id,
								'context_id' => $id,
								'label' => $tpl_builder->build($template, $values),
								'color' => $params['color'],
								'ts' => $event_start,
								'ts_end' => $event_end,
								'ts_range_start' => $ts,
								'ts_range_end' => $ts_end,
								'is_available' => ($params['is_available'] ?? null) ?: 0,
								'link' => sprintf("ctx://%s:%d",
									$context_ext->id,
									$id
								),
							);
						}
					}
				}
			}
			
		}
		
		return $calendar_events;
	}
};