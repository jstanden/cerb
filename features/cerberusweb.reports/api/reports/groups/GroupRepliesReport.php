<?php
class ChReportGroupReplies extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Years
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM message WHERE created_date > 0 AND is_outgoing = 1 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);

		// Times
		
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
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
		
		$sql = sprintf("SELECT count(*) AS hits, t.team_id, category_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN team ON t.team_id = team.id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.team_id != 0 " .
			"GROUP BY t.team_id, category_id ORDER BY team.name ",
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
		$group_counts = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);
			$hits = intval($row['hits']);
			
			if(!isset($group_counts[$team_id]))
				$group_counts[$team_id] = array();
			
			$group_counts[$team_id][$category_id] = $hits;
			@$group_counts[$team_id]['total'] = intval($group_counts[$team_id]['total']) + $hits;
		}
		$tpl->assign('group_counts', $group_counts);

		mysql_free_result($rs);
		
		// Chart
		$sql = sprintf("SELECT t.team_id as group_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') as date_plot, ".
			"count(*) AS hits ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN team on t.team_id = team.id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.team_id != 0 " .			
			"GROUP BY group_id, date_plot ".
			"ORDER BY team.name DESC ",
			$date_group,
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
		
		$data = array();
		while($row = mysql_fetch_assoc($rs)) {
			$group_id = intval($row['group_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$group_id]))
				$data[$group_id] = $ticks;
			
			$data[$group_id][$date_plot] = intval($row['hits']);
		}
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);		
		
		$tpl->display('devblocks:cerberusweb.reports::reports/group/group_replies/index.tpl');
	}
	
};