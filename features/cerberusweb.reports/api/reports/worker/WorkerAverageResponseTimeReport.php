<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerb.ai/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class ChReportAverageResponseTime extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		// init
		$db = DevblocksPlatform::getDatabaseService();

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start_time = strtotime("-30 days");
			$end_time = strtotime("now");
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
		
		// set up necessary reference arrays
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers',$workers);
		
		// pull data from db
		$sql = sprintf("SELECT m.id, m.ticket_id, m.created_date, m.worker_id, m.response_time, t.group_id, t.bucket_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND m.is_outgoing = 1 AND m.is_broadcast = 0 AND m.response_time > 0 ",
			$start_time,
			$end_time
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		// process and count results
		$group_responses = array();
		$worker_responses = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			// load current data
			$id = intval($row['id']);
			$ticket_id = intval($row['ticket_id']);
			$created_date = intval($row['created_date']);
			$worker_id = intval($row['worker_id']);
			$response_time = intval($row['response_time']);
			$group_id = intval($row['group_id']);
			$bucket_id = intval($row['bucket_id']);

			if(empty($worker_id))
				continue;
			
			if(!empty($worker_id) && !isset($workers[$worker_id]))
				continue;

			if(!empty($group_id) && !isset($groups[$group_id]))
				continue;
				
			if(!isset($group_responses[$group_id]))
				$group_responses[$group_id] = array();
			
			if(!isset($worker_responses[$worker_id]))
				$worker_responses[$worker_id] = array();
			
			@$group_responses[$group_id]['replies'] += 1;
			@$group_responses[$group_id]['time'] += $response_time;
			@$worker_responses[$worker_id]['replies'] += 1;
			@$worker_responses[$worker_id]['time'] += $response_time;
		}
		
		$tpl->assign('group_responses', $group_responses);
		$tpl->assign('worker_responses', $worker_responses);

		mysqli_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/average_response_time/index.tpl');
	}
};