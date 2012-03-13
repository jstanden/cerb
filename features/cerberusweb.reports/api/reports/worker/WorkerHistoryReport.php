<?php
class ChReportWorkerHistory extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		@$filter_group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
		$tpl->assign('filter_group_ids', $filter_group_ids);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		if (empty($start) && empty($end)) {
			$start = "-7 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		// Calculate the # of ticks between the dates (and the scale -- day, month, etc)
		$range = $end_time - $start_time;
		$range_days = $range/86400;
		$plots = $range/15;
		
		$ticks = array();

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
			case 'day':
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
				break;
			case 'hour':
				$date_group='%Y-%m-%d %H';
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
			} elseif($range_days > 1) {
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
			} else {
				$date_group = '%Y-%m-%d %H';
				$date_increment = 'hour';
			}
		}
		
		$tpl->assign('report_date_grouping', $date_increment);		
		
		// Find unique values
		$time = strtotime(sprintf("-1 %s", $date_increment), $start_time);
		while($time < $end_time) {
			$time = strtotime(sprintf("+1 %s", $date_increment), $time);
			if($time <= $end_time)
				$ticks[strftime($date_group, $time)] = 0;
		}		
		
		// Table
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'report_worker_history';
		$defaults->class_name = 'View_Message';
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->is_ephemeral = true;
			$view->removeAllParams();

			$view->view_columns = array(
				SearchFields_Message::TICKET_GROUP_ID,
				SearchFields_Message::CREATED_DATE,
				SearchFields_Message::WORKER_ID,
			);
			
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Message::CREATED_DATE,DevblocksSearchCriteria::OPER_BETWEEN, array($start_time, $end_time)));
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING,DevblocksSearchCriteria::OPER_EQ, 1));
			
			if(!empty($filter_worker_ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID,DevblocksSearchCriteria::OPER_IN, $filter_worker_ids));
			} else {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID,DevblocksSearchCriteria::OPER_NEQ, 0));
			}
			
			if(!empty($filter_group_ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Message::TICKET_GROUP_ID,DevblocksSearchCriteria::OPER_IN, $filter_group_ids));
			}
			
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Message::CREATED_DATE;
			$view->renderSortAsc = false;
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		}
		
		// Chart
		
		$sql = sprintf("SELECT m.worker_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') AS date_plot, ".
			"count(*) AS hits ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"%s ".
			"%s ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND m.worker_id != 0 ".
			"GROUP BY m.worker_id, date_plot ",
			$date_group,
			$start_time,
			$end_time,
			(is_array($filter_worker_ids) && !empty($filter_worker_ids) ? sprintf("AND m.worker_id IN (%s)", implode(',', $filter_worker_ids)) : ""),
			(is_array($filter_group_ids) && !empty($filter_group_ids) ? sprintf("AND t.group_id IN (%s)", implode(',', $filter_group_ids)) : "")
		);
		$rs = $db->Execute($sql);
		
		$data = array();
		while($row = mysql_fetch_assoc($rs)) {
			$worker_id = intval($row['worker_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($workers[$worker_id]))
				continue;
			
			if(!isset($data[$worker_id]))
				$data[$worker_id] = $ticks;
			
			$data[$worker_id][$date_plot] = intval($row['hits']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);		
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/worker_history/index.tpl');
	}
};