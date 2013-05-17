<?php
class CalendarDatasource_Manual extends Extension_CalendarDatasource {
	function renderConfig(Model_Calendar $calendar) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('calendar', $calendar);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/manual/config.tpl');
	}
	
	function getData(Model_Calendar $calendar, $date_range_from, $date_range_to) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf(
			"SELECT id, name, recurring_id, is_available, date_start, date_end ".
			"FROM calendar_event ".
			"WHERE calendar_id = %d ".
			"AND ((date_start >= %d AND date_start <= %d) OR (date_end >= %d AND date_end <= %d)) ".
			"ORDER BY is_available DESC, date_start ASC",
			$calendar->id,
			$date_range_from,
			$date_range_to,
			$date_range_from,
			$date_range_to
		);
		
		$results = $db->GetArray($sql);

		$calendar_events = array();
		
		foreach($results as $row) {
			$day_range = range(strtotime('midnight', $row['date_start']), strtotime('midnight', $row['date_end']), 86400);
			
			foreach($day_range as $epoch) {
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$calendar_events[$epoch][$row['id']] = array(
					'context' => CerberusContexts::CONTEXT_CALENDAR_EVENT,
					'context_id' => $row['id'],
					'label' => $row['name'],
					'color' => $row['is_available'] ? '#A0D95B' : '#C8C8C8',
					'link' => sprintf("ctx://%s:%d",
						CerberusContexts::CONTEXT_CALENDAR_EVENT,
						$row['id']
					),
				);
			}
		}
		
		return $calendar_events;
	}
	
	function renderEvent(Model_Calendar $calendar, $event) {
	}
};

class CalendarDatasource_Worklist extends Extension_CalendarDatasource {
	function renderConfig(Model_Calendar $calendar) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('calendar', $calendar);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('context_mfts', $context_mfts);
		
		@$worklist_context = $calendar->params['worklist_model']['context'];
		
		// Load the initial fields from the context
		
		if(!empty($worklist_context)) {
			if(null != ($ctx = Extension_DevblocksContext::get($worklist_context))) {
				if(null != ($view_class = $ctx->getViewClass())) { /* @var $view_class C4_AbstractView */
					if(null != ($view = new $view_class))
						$tpl->assign('ctx_fields', $view->getFields());
				}
				
				CerberusContexts::getContext($ctx->id, null, $labels, $values, null, true);
				$tpl->assign('placeholders', $labels);
			}
		}
		
		// Prime the worklist view
		
		@$worklist_view_id = sprintf("calendar%d_worklist", $calendar->id);
		@$worklist_model = $calendar->params['worklist_model'];
		
		if(null != ($worklist_view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id)))
			C4_AbstractViewLoader::setView($worklist_view_id, $worklist_view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/worklist/config.tpl');
	}
	
	function getData(Model_Calendar $calendar, $date_range_from, $date_range_to) {
		$calendar_events = array();
		
		if(isset($calendar->params['worklist_model'])) {
			@$worklist_context = $calendar->params['worklist_model']['context'];
			
			if(!empty($worklist_context) && null != ($context_ext = Extension_DevblocksContext::get($worklist_context))) {
				$worklist_model = $calendar->params['worklist_model'];
				$view_id = sprintf("calendar%d_worklist", $calendar->id);
				
				if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id))) {
					/* @var $view C4_AbstractView */
					
					$view->addParam(
						new DevblocksSearchCriteria($calendar->params['field_start_date'], DevblocksSearchCriteria::OPER_BETWEEN, array($date_range_from, $date_range_to)),
						$calendar->params['field_start_date']
					);
					
					$view->renderPage = 0;
					$view->renderLimit = -1;

					// [TODO] Paging
					// [TODO] Subtotals
					
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					@$template = $calendar->params['label'];
					
					if(empty($template))
						$template = '{{_label}}';
					
					list($results, $count) = $view->getData();
					
					foreach($results as $id => $row) {
						$epoch = strtotime('midnight', $row[$calendar->params['field_start_date']]);

						// [TODO] This needs to be more efficient
						CerberusContexts::getContext($context_ext->id, $id, $labels, $values);

						$calendar_events[$epoch][$id] = array(
							'context' => $context_ext->id,
							'context_id' => $id,
							'label' => $tpl_builder->build($template, $values),
							'color' => $calendar->params['color'],
							'link' => sprintf("ctx://%s:%d",
								$context_ext->id,
								$id
							),
						);
					}
				}
			}
			
		}
		
		return $calendar_events;
	}
	
	function renderEvent(Model_Calendar $calendar, $event) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('event', $event);
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/datasources/worklist/render_cell.tpl');
	}
};

// [TODO] Convert existing workspace tabs to calendars?  Use this datasource? Aggregate?
