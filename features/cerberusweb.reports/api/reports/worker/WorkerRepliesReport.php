<?php
class ChReportWorkerReplies extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();

		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Years
		$years = array();
		$sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(created_date),'%Y') AS year FROM message WHERE created_date > 0 AND is_outgoing = 1 GROUP BY year HAVING year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string', '30d');
		
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
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));		
		
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
		
		// Chart
		
		$sql = sprintf("SELECT m.worker_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') as date_plot, count(*) AS hits ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN worker w ON (w.id=m.worker_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"%s ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id, date_plot ".
			"ORDER BY w.last_name DESC ",
			$date_group,
			$start_time,
			$end_time,
			(is_array($filter_worker_ids) && !empty($filter_worker_ids) ? sprintf("AND m.worker_id IN (%s)", implode(',', $filter_worker_ids)) : "")
		);
		$rs = $db->Execute($sql);
		
		$data = array();
		while($row = mysql_fetch_assoc($rs)) {
			$worker_id = intval($row['worker_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$worker_id]))
				$data[$worker_id] = $ticks;
			
			$data[$worker_id][$date_plot] = intval($row['hits']);
		}

		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$xaxis_ticks = array_keys($ticks);
		$tpl->assign('xaxis_ticks', $xaxis_ticks);
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);		
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/worker_replies/index.tpl');
	}
};