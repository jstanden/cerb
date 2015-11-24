<?php
if (class_exists('Extension_Report',true)):
class ChReportTimeSpentActivity extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		$date = DevblocksPlatform::getDateService();
		
		// Use the worker's timezone for MySQL date functions
		$db->ExecuteSlave(sprintf("SET time_zone = %s", $db->qstr($date->formatTime('P', time()))));
		
		// Filters
		
		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Dates

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
				
		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
			
			$tpl->assign('invalidDate', true);
		}
		
		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
			// Calculate the # of ticks between the dates (and the scale -- day, month, etc)
		$range = $end_time - $start_time;
		$range_days = $range/86400;
		$plots = $range/15;
		
		@$report_date_grouping = DevblocksPlatform::importGPC($_REQUEST['report_date_grouping'],'string','');
		$date_group = '';
		$date_increment = '';
		
		// Did the user choose a specific grouping?
		switch($report_date_grouping) {
			case 'year':
				$date_group = '%Y';
				$date_increment = 'year';
				break;
			case 'month':
				$date_group = '%Y-%m';
				$date_increment = 'month';
				break;
			case 'week':
				$date_group = '%x-%v';
				$date_increment = 'week';
				break;
			case 'day':
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
				break;
			case 'hour':
				$date_group = '%Y-%m-%d %H:00';
				$date_increment = 'hour';
				break;
		}
		
		// Fallback to automatic grouping
		if(empty($date_group) || empty($date_increment)) {
			if($range_days > 365) {
				$date_group = '%Y';
				$date_increment = 'year';
			} elseif($range_days > 32) {
				$date_group = '%Y-%m';
				$date_increment = 'month';
			} elseif($range_dates > 8) {
				$date_group = '%x-%v';
				$date_increment = 'week';
			} elseif($range_days > 1) {
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
			} else {
				$date_group = '%Y-%m-%d %H:00';
				$date_increment = 'hour';
			}
		}
		
		$tpl->assign('report_date_grouping', $date_increment);
		
		// Ticks
		
		$ticks = $date->getIntervals($date_increment, $start_time, $end_time);

		// Table
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_TimeTracking');
		$defaults->id = 'report_timetracking_activity';
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->is_ephemeral = true;
			$view->removeAllParams();

			$params = array(
				new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN, array($start_time, $end_time)),
			);
			
			if(!empty($filter_worker_ids)) {
				$params[] = new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::WORKER_ID,DevblocksSearchCriteria::OPER_IN, $filter_worker_ids);
			} else {
				$params[] = new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::WORKER_ID,DevblocksSearchCriteria::OPER_NEQ, 0);
			}
			
			$view->addParamsRequired($params, true);
			
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
			$view->renderSortAsc = true;
			
			$tpl->assign('view', $view);
		}
		
		// Chart
		$sql = sprintf("SELECT tta.id AS activity_id, tta.name activity_name, DATE_FORMAT(FROM_UNIXTIME(tte.log_date),'%s') as date_plot, ".
			"sum(tte.time_actual_mins) AS mins ".
			"FROM timetracking_entry tte ".
			"LEFT JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
			"WHERE 1 ".
			"AND log_date > %d ".
			"AND log_date <= %d ".
			"%s ".
			"GROUP BY activity_id, date_plot ".
			"ORDER BY activity_name ASC ",
			$date_group,
			$start_time,
			$end_time,
			(is_array($filter_worker_ids) && !empty($filter_worker_ids) ? sprintf("AND tte.worker_id IN (%s)", implode(',', $filter_worker_ids)) : "")
		);
		$rs = $db->ExecuteSlave($sql);
		
		$data = array();
		$activities = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$activity_id = intval($row['activity_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$activity_id]))
				$data[$activity_id] = $ticks;
				
			if(!isset($activities[$activity_id]))
				$activities[$activity_id] = !empty($row['activity_name']) ? $row['activity_name'] : '(no activity)';
			
			$data[$activity_id][$date_plot] = intval($row['mins']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('activities', $activities);
		$tpl->assign('data', $data);
		
		mysqli_free_result($rs);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.timetracking::reports/time_spent_activity/index.tpl');
	}
};
endif;
