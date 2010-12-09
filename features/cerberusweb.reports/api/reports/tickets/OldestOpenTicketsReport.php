<?php
class ChReportOldestOpenTickets extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		
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
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-5 years');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-5 years";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);

		// Table
		
		$oldest_tickets = array();
		foreach($groups as $group_id=>$group) {
			$sql = sprintf("SELECT mask, subject, created_date ".
				"FROM ticket ".
				"WHERE created_date > %d AND created_date <= %d ".			
				"AND is_deleted = 0 ".
				"AND is_closed = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND is_waiting != 1 " .
				"AND team_id = %d " .
				"ORDER BY created_date LIMIT 10",
				$start_time,
				$end_time,
				$group_id);
			$rs = $db->Execute($sql);
		
			while($row = mysql_fetch_assoc($rs)) {
				$mask = $row['mask'];
				$subject = $row['subject'];
				$created_date = intval($row['created_date']);
				
				if(!isset($oldest_tickets[$group_id]))
					$oldest_tickets[$group_id] = array();
				
				unset($ticket_entry);
				$ticket_entry->mask = $mask;
				$ticket_entry->subject = $subject;
				$ticket_entry->created_date = $created_date;
				
				$oldest_tickets[$group_id][]=$ticket_entry;
			}
			
			mysql_free_result($rs);
		}
		$tpl->assign('oldest_tickets', $oldest_tickets);
				
		$tpl->display('devblocks:cerberusweb.reports::reports/ticket/oldest_open_tickets/index.tpl');
	}
};