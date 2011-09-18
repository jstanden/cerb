<?php
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
	   	$sql = sprintf("SELECT mm.id, mm.ticket_id, mm.created_date, mm.worker_id, mm.is_outgoing, t.group_id, t.bucket_id ".
			"FROM message m ".
	   		"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	   		"INNER JOIN message mm ON (mm.ticket_id=t.id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND m.is_outgoing = 1 ".
	   		"ORDER BY ticket_id,id ",
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
		
		// process and count results
	   	$group_responses = array();
	   	$worker_responses = array();
	   	$prev = array();
		while($row = mysql_fetch_assoc($rs)) {
			// load current data
			$id = intval($row['id']);
			$ticket_id = intval($row['ticket_id']);
			$created_date = intval($row['created_date']);
			$worker_id = intval($row['worker_id']);
			$is_outgoing = intval($row['is_outgoing']);
			$group_id = intval($row['group_id']);
			$bucket_id = intval($row['bucket_id']);

			if(!empty($worker_id) && !isset($workers[$worker_id]))
				continue;

			if(!empty($group_id) && !isset($groups[$group_id]))
				continue;
				
			// we only add data if it's a worker reply to the same ticket as $prev
			if ($is_outgoing==1 && !empty($prev) && $ticket_id==$prev['ticket_id']) {
				// Initialize, if necessary
				if (!isset($group_responses[$group_id])) $group_responses[$group_id] = array();
				if (!isset($worker_responses[$worker_id])) $worker_responses[$worker_id] = array();
				
				// log reply and time
				@$group_responses[$group_id]['replies'] += 1;
				@$group_responses[$group_id]['time'] += $created_date - $prev['created_date'];
				@$worker_responses[$worker_id]['replies'] += 1;
				@$worker_responses[$worker_id]['time'] += $created_date - $prev['created_date'];
			}
			
			// Save this one as "previous" and move on
			$prev = array(
				'id'=>$id,
				'ticket_id'=>$ticket_id,
				'created_date'=>$created_date,
				'worker_id'=>$worker_id,
				'is_outgoing'=>$is_outgoing,
				'group_id'=>$group_id,
				'bucket_id'=>$bucket_id,
			);
		}
		$tpl->assign('group_responses', $group_responses);
		$tpl->assign('worker_responses', $worker_responses);		

		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/average_response_time/index.tpl');
	}
};