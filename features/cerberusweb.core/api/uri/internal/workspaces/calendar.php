<?php
if(class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_Calendar extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);

		$this->_showCalendarTab($page, $tab);
	}
	
	public function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$context_mfts = Extension_DevblocksContext::getAll(false, array('workspace'));
		$tpl->assign('context_mfts', $context_mfts);

		if(isset($tab->params['context_extid'])) {
			$ctx = Extension_DevblocksContext::get($tab->params['context_extid']);
			if(null != ($view_class = $ctx->getViewClass())) { /* @var $view_class C4_AbstractView */
				if(null != ($view = new $view_class))
					$tpl->assign('ctx_fields', $view->getFields());
			}
			
			CerberusContexts::getContext($ctx->id, null, $labels, $values, null, true);
			$tpl->assign('placeholders', $labels);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/calendar/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array');
		
		DAO_WorkspaceTab::update($tab->id, array(
			DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
		));
	}
	
	private function _showCalendarTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		// [TODO] Validate month/year
		// [TODO] gmdate + gmmktime?

		if(empty($month) || empty($year)) {
			$month = date('m');
			$year = date('Y');
		}
		
		$calendar_date = mktime(0,0,0,$month,1,$year);
		
		$num_days = date('t', $calendar_date);
		$first_dow = date('w', $calendar_date);
		
		$prev_month_date = mktime(0,0,0,$month,0,$year);
		$prev_month = date('m', $prev_month_date);
		$prev_year = date('Y', $prev_month_date);

		$next_month_date = mktime(0,0,0,$month+1,1,$year);
		$next_month = date('m', $next_month_date);
		$next_year = date('Y', $next_month_date);
		
		$days = array();

		for($day = 1; $day <= $num_days; $day++) {
			$timestamp = mktime(0,0,0,$month,$day,$year);
			
			$days[$timestamp] = array(
				'dom' => $day,
				'dow' => (($first_dow+$day-1) % 7),
				'is_padding' => false,
				'timestamp' => $timestamp,
			);
		}
		
		// How many cells do we need to pad the first and last weeks?
		$first_day = reset($days);
		$left_pad = $first_day['dow'];
		$last_day = end($days);
		$right_pad = 6-$last_day['dow'];

		$calendar_cells = $days;
		
		if($left_pad > 0) {
			$prev_month_days = date('t', $prev_month_date);
			
			for($i=1;$i<=$left_pad;$i++) {
				$dom = $prev_month_days - ($i-1);
				$timestamp = mktime(0,0,0,$prev_month,$dom,$prev_year);
				$day = array(
					'dom' => $dom,
					'dow' => $first_dow - $i,
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		if($right_pad > 0) {
			for($i=1;$i<=$right_pad;$i++) {
				$timestamp = mktime(0,0,0,$next_month,$i,$next_year);
				
				$day = array(
					'dom' => $i,
					'dow' => (($first_dow + $num_days + $i - 1) % 7),
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		// Sort calendar
		ksort($calendar_cells);
		
		// Break into weeks
		$calendar_weeks = array_chunk($calendar_cells, 7, true);

		// Events
		$first_cell = array_slice($calendar_cells, 0, 1, false);
		$last_cell = array_slice($calendar_cells, -1, 1, false);
		$range_from = array_shift($first_cell);
		$range_to = array_shift($last_cell);
		
		unset($days);
		unset($calendar_cells);
		
		$date_range_from = strtotime('00:00', $range_from['timestamp']);
		$date_range_to = strtotime('23:59', $range_to['timestamp']);
		
		// Load the events
		
		$calendar_events = array();
		
		if(isset($tab->params['context_extid'])
			&& isset($tab->params['view_id'])
			&& isset($tab->params['view_model'])) {
			
			if(null != ($context_ext = Extension_DevblocksContext::get($tab->params['context_extid']))) {
				$view_model = unserialize(base64_decode($tab->params['view_model'])); /* @var $view C4_AbstractView */
				
				if(null != ($view = C4_AbstractViewLoader::unserializeAbstractView($view_model))) {
				
					$view->addParam(
						new DevblocksSearchCriteria($tab->params['field_start_date'], DevblocksSearchCriteria::OPER_BETWEEN, array($date_range_from, $date_range_to)),
						$tab->params['field_start_date']
					);
					
					$view->renderPage = 0;
					$view->renderLimit = -1;

					// [TODO] Paging
					// [TODO] Subtotals
					
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					@$template = $tab->params['label'];
					
					if(empty($template))
						$template = '{{_label}}';
					
					list($results, $count) = $view->getData();
					
					foreach($results as $id => $row) {
						$epoch = strtotime('midnight', $row[$tab->params['field_start_date']]);

						CerberusContexts::getContext($context_ext->id, $id, $labels, $values);

						// [TODO] This needs to be more efficient
						$dict = new DevblocksDictionaryDelegate($values);
						
						$calendar_events[$epoch][$id] = array(
							'id' => $id,
							'context' => @$tab->params['context_extid'],
							'label' => $tpl_builder->build($template, $dict),
						);
					}
					
					//var_dump($results);
					
					//foreach($results as $row_id => $row) {
						
						/*
						$day_range = range(strtotime('midnight', $tab->params['field_start_date']), strtotime('midnight', $tab->params['field_start_date']), 86400);
						
						foreach($day_range as $epoch) {
							if(!isset($calendar_events[$epoch]))
								$calendar_events[$epoch] = array();
							
							$calendar_events[$epoch][$row['id']] = array(
								'id' => $row[SearchFields_Task::ID],
								'name' => $row[SearchFields_Task::TITLE],
							);
						}
						*/
					//}
					
				}
			}
			
		}
		
		//var_dump($calendar_events);
		
		// [TODO] Convert to DAO
		/*
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf(
			"SELECT id, name, recurring_id, is_available, date_start, date_end ".
			"FROM calendar_event ".
			"WHERE owner_context = %s ".
			"AND owner_context_id = %d ".
			"AND ((date_start >= %d AND date_start <= %d) OR (date_end >= %d AND date_end <= %d)) ".
			"ORDER BY is_available DESC, date_start ASC",
			$db->qstr($context),
			$context_id,
			$date_range_from,
			$date_range_to,
			$date_range_from,
			$date_range_to
		);
		$results = $db->GetArray($sql);

		foreach($results as $row) {
			$day_range = range(strtotime('midnight', $row['date_start']), strtotime('midnight', $row['date_end']), 86400);
			
			foreach($day_range as $epoch) {
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$calendar_events[$epoch][$row['id']] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'is_available' => $row['is_available'],
				);
			}
		}
		*/

		// Template scope
		//$tpl->assign('context', $context);
		//$tpl->assign('context_id', $context_id);
		$tpl->assign('today', strtotime('today'));
		$tpl->assign('prev_month', $prev_month);
		$tpl->assign('prev_year', $prev_year);
		$tpl->assign('next_month', $next_month);
		$tpl->assign('next_year', $next_year);
		$tpl->assign('calendar_date', $calendar_date);
		$tpl->assign('calendar_weeks', $calendar_weeks);
		$tpl->assign('calendar_events', $calendar_events);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/calendar/tab.tpl');
	}
}
endif;