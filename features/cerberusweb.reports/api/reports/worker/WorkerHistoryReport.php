<?php
class ChReportWorkerHistory extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

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
		
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		// Find unique values
		$time = strtotime(sprintf("-1 %s", $date_increment), $start_time);
		while($time < $end_time) {
			$time = strtotime(sprintf("+1 %s", $date_increment), $time);
			if($time <= $end_time)
				$ticks[strftime($date_group, $time)] = 0;
		}		
		
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		if(!$worker_id) {
			$worker = CerberusApplication::getActiveWorker();
			$worker_id = $worker->id;
		}
		$tpl->assign('worker_id', $worker_id);
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
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
		
		// Find unique values
		$time = strtotime(sprintf("-1 %s", $date_increment), $start_time);
		while($time < $end_time) {
			$time = strtotime(sprintf("+1 %s", $date_increment), $time);
			if($time <= $end_time)
				$ticks[strftime($date_group, $time)] = 0;
		}		
		
		// Table
		
		$sql = sprintf("SELECT t.id, t.mask, t.subject, a.email as email, " . 
			"date_format(from_unixtime(m.created_date),'%%Y-%%m-%%d') as day ".
			"FROM ticket t ".
			"INNER JOIN message m ON t.id = m.ticket_id ".
			"INNER JOIN worker w ON m.worker_id = w.id ".
			"INNER JOIN address a on t.first_wrote_address_id = a.id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND w.id = %d ".
			"GROUP BY day, t.id ".
			"order by m.created_date",
			$start_time,
			$end_time,
			$worker_id
		);
		$rs = $db->Execute($sql);

		$tickets_replied = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$created_day = $row['day'];
			
			unset($reply_date_ticket);
			$reply_date_ticket->mask = $row['mask'];
			$reply_date_ticket->email = $row['email'];
			$reply_date_ticket->subject = $row['subject'];
			$reply_date_ticket->id = intval($row['id']);

			$tickets_replied[$created_day][] = $reply_date_ticket;
		}
		
		mysql_free_result($rs);

		$tpl->assign('tickets_replied', $tickets_replied);		
		
		// Chart
		
		$sql = sprintf("SELECT m.worker_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') AS date_plot, ".
			"count(*) AS hits ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN worker w ON w.id=m.worker_id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id, date_plot ",
			$date_group,
			$start_time,
			$end_time
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
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);		
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/worker_history/index.tpl');
	}
};