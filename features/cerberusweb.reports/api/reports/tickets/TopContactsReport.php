<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/

class ChReportTopTicketsByContact extends Extension_Report {
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
		
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');

		@$by_address = DevblocksPlatform::importGPC($_REQUEST['by_address'],'integer',0);
		$tpl->assign('by_address', $by_address);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		// [TODO] _LIMIT_
		
		if($by_address) {
			$sql = sprintf("SELECT count(*) AS hits, a.id as contact_id, a.email as contact_name, t.group_id, t.bucket_id  ".
				"FROM ticket t  ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id  ".
				"WHERE created_date > %d AND created_date <= %d  ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S'  ".
				"AND t.group_id != 0 ".
				"GROUP BY a.email, t.group_id, t.bucket_id ".
				"ORDER BY hits DESC ",
				$start_time,
				$end_time
			);
		}
		else { //default is by org
			$sql = sprintf("SELECT count(*) AS hits, a.contact_org_id as contact_id, o.name as contact_name, t.group_id, t.bucket_id ".
				"FROM ticket t ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
				"INNER JOIN contact_org o ON a.contact_org_id = o.id ".
				"WHERE created_date > %d AND created_date <= %d ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND a.contact_org_id != 0 ".
				"AND t.group_id != 0 ".
				"GROUP BY a.contact_org_id, o.name, t.group_id, t.bucket_id ".
				"ORDER BY hits DESC ",
				$start_time,
				$end_time
			);
		}
		$rs = $db->Execute($sql);
	
		$group_counts = array();
		$max_orgs = 100;
		$current_orgs = 0;
		
		while(($row = mysql_fetch_assoc($rs)) && $current_orgs <= $max_orgs) {
			$org_id = intval($row['contact_id']);
			$org_name = $row['contact_name'];
			$group_id = intval($row['group_id']);
			$bucket_id = intval($row['bucket_id']);
			$hits = intval($row['hits']);
			
			if(!isset($group_counts[$org_id])) {
				$group_counts[$org_id] = array();
				$current_orgs++;
			}

			if(!isset($group_counts[$org_id]['groups']))
				$group_counts[$org_id]['groups'] = array();
				
			if(!isset($group_counts[$org_id]['groups'][$group_id]))
				$group_counts[$org_id]['groups'][$group_id] = array();
				
			if(!isset($group_counts[$org_id]['groups'][$group_id]['buckets']))
				$group_counts[$org_id]['groups'][$group_id]['buckets'] = array();
			
			$group_counts[$org_id]['name'] = $org_name;
			
			$group_counts[$org_id]['groups'][$group_id]['buckets'][$bucket_id] = $hits;
			@$group_counts[$org_id]['groups'][$group_id]['total'] = intval($group_counts[$org_id]['groups'][$group_id]['total']) + $hits;
			@$group_counts[$org_id]['total'] = intval($group_counts[$org_id]['total']) + $hits;
		}
		
		mysql_free_result($rs);
		
		uasort($group_counts, array("ChReportTopTicketsByContact", "sortCountsArrayByHits"));
		
		$tpl->assign('group_counts', $group_counts);
		
		// Chart

		if($by_address) {
			// Find our top 10
			$sql = sprintf("SELECT a.id AS id, count(*) AS hits ".
				"FROM ticket t ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
				"WHERE created_date > %d AND created_date <= %d ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND t.group_id != 0 " .
				"GROUP BY a.id ".
				"ORDER BY hits DESC ".
				"LIMIT 0,10 ",
				$start_time,
				$end_time
			);
			$top_results = $db->GetArray($sql);
			
			$top_ids = array();
			
			foreach($top_results as $row) {
				$top_ids[] = $row['id'];
			}
			
			if(empty($top_results))
				$top_ids[] = -1;
			
			$sql = sprintf("SELECT a.id AS id, DATE_FORMAT(FROM_UNIXTIME(t.created_date),'%s') as date_plot, ".
				"count(*) AS hits, a.email AS label ".
				"FROM ticket t ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
				"WHERE created_date > %d AND created_date <= %d ".
				"AND a.id IN (%s) ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND t.group_id != 0 " .
				"GROUP BY a.id, date_plot ",
				$date_group,
				$start_time,
				$end_time,
				implode(',', $top_ids)
			);
		} else { //default is by org
			// Find our top 10
			$sql = sprintf("SELECT o.id AS id, count(*) AS hits ".
				"FROM ticket t ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
				"INNER JOIN contact_org o ON a.contact_org_id = o.id ".
				"WHERE created_date > %d AND created_date <= %d ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND t.group_id != 0 " .
				"AND a.contact_org_id != 0 ".
				"GROUP BY a.contact_org_id ".
				"ORDER BY hits DESC ".
				"LIMIT 0,10 ",
				$start_time,
				$end_time
			);
			$top_results = $db->GetArray($sql);
			
			$top_ids = array();
			
			foreach($top_results as $row) {
				$top_ids[] = $row['id'];
			}
			
			if(empty($top_results))
				$top_ids[] = -1;
			
			$sql = sprintf("SELECT a.contact_org_id AS id, DATE_FORMAT(FROM_UNIXTIME(t.created_date),'%s') as date_plot, ".
				"count(*) AS hits, o.name AS label ".
				"FROM ticket t ".
				"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
				"INNER JOIN contact_org o ON a.contact_org_id = o.id ".
				"WHERE created_date > %d AND created_date <= %d ".
				"AND o.id IN (%s) ".
				"AND is_deleted = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND t.group_id != 0 " .
				"AND a.contact_org_id != 0 ".
				"GROUP BY a.contact_org_id, date_plot ",
				$date_group,
				$start_time,
				$end_time,
				implode(',', $top_ids)
			);
		};
		$rs = $db->Execute($sql);
		
		$data = array();
		$labels = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$id = intval($row['id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$id]))
				$data[$id] = $ticks;
				
			if(!isset($labels[$id]))
				$labels[$id] = $row['label'];
			
			$data[$id][$date_plot] = intval($row['hits']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('labels', $labels);
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);

		$tpl->display('devblocks:cerberusweb.reports::reports/ticket/top_contacts_tickets/index.tpl');
	}
	
	function sortCountsArrayByHits($a, $b) {
		if ($a['total'] == $b['total']) {
			return 0;
		}
		return ($a['total'] < $b['total']) ? 1 : -1;
	}
};