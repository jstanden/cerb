<?php
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

require_once($path . 'libs/advgraph/advgraph5.class.php');

//DevblocksPlatform::registerClasses($path. 'api/App.php', array(
//    'C4_TicketAuditLogView'
//));

class ChReportsPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChReportsPage extends CerberusPageExtension {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // reports
		
		switch(array_shift($stack)) {
		    default:
				$tpl->display('file:' . $this->tpl_path . '/reports/index.tpl.php');
		        break;
		}
	}
	
	function getNewTicketsReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		
		$tpl->assign('age', $age);
		$tpl->assign('age_dur', $age_dur);
		$tpl->assign('age_term', $age_term);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d AND is_deleted = 0 ".
			"GROUP BY team_id, category_id ",
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs_buckets = $db->Execute($sql);
	
		$group_counts = array();
		while(!$rs_buckets->EOF) {
			$team_id = intval($rs_buckets->fields['team_id']);
			$category_id = intval($rs_buckets->fields['category_id']);
			$hits = intval($rs_buckets->fields['hits']);
			
			if(!isset($group_counts[$team_id]))
				$group_counts[$team_id] = array();
				
			$group_counts[$team_id][$category_id] = $hits;
			@$group_counts[$team_id]['total'] = intval($group_counts[$team_id]['total']) + $hits;
			
			$rs_buckets->MoveNext();
		}
		$tpl->assign('group_counts', $group_counts);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/new_tickets.tpl.php');
	}
	
	function getAverageResponseTimeReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		
		$tpl->assign('age', $age);
		$tpl->assign('age_dur', $age_dur);
		$tpl->assign('age_term', $age_term);
		
	   	// First, we need the list of Workers
	   	$workers = DAO_Worker::getAll();
	   	$worker_responses = array();
	   	foreach($workers as $worker) {
	   		$worker_responses[$worker->id] = array();
	   		$worker_responses[$worker->id]['name'] = $worker->first_name.' '.$worker->last_name;
	   		$worker_responses[$worker->id]['count'] = 0;
	   		$worker_responses[$worker->id]['total_time'] = 0;
	   	}
	   	
		// Then the list of all outgoing messages for the desired time frame
	   	$sql = sprintf("SELECT id ".
			"FROM message m ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ",
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs_responses = $db->Execute($sql);
		
		$messages = array();
		while(!$rs_responses->EOF) {
			$messages[] = intval($rs_responses->fields['id']);
			$rs_responses->MoveNext();
		}
		
		// now we evaluate each message, find the timelapse, and assign it to a worker.
		$prev_message_timestamp = 0;
		$curr_message_timestamp = 0;
		foreach($messages as $id) {
			$message = DAO_Ticket::getMessage($id);
			$tkt_messages = DAO_Ticket::getMessagesByTicket($message->ticket_id);
			
			// get timestamps
			foreach($tkt_messages as $mess) {
				$curr_message_timestamp = $mess->created_date;
				if($mess->id == $id) break;
				$prev_message_timestamp = $mess->created_date;
			}
			
			// if this is the first message on the ticket, don't count it.
			if($prev_message_timestamp == $curr_message_timestamp
			|| $prev_message_timestamp == 0
			|| $curr_message_timestamp == 0) continue;
			
			// add it to the worker's totals
			$worker_responses[$message->worker_id]['count'] += 1;
			$worker_responses[$message->worker_id]['total_time'] += $curr_message_timestamp - $prev_message_timestamp;
		}
	   		   	
//echo("<pre>");
//print_r($worker_responses);
//echo("</pre>");
//
		$tpl->assign('worker_responses', $worker_responses);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/response_time.tpl.php');
	}
	
	function getWorkerRepliesReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string', '30d');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		
		$tpl->assign('age', $age);
		$tpl->assign('age_dur', $age_dur);
		$tpl->assign('age_term', $age_term);
		
		// Top Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$sql = sprintf("SELECT count(*) AS hits, t.team_id, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY t.team_id, m.worker_id ",
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs_workers = $db->Execute($sql);
		
		$worker_counts = array();
		while(!$rs_workers->EOF) {
			$hits = intval($rs_workers->fields['hits']);
			$team_id = intval($rs_workers->fields['team_id']);
			$worker_id = intval($rs_workers->fields['worker_id']);
			
			if(!isset($worker_counts[$worker_id]))
				$worker_counts[$worker_id] = array();
			
			$worker_counts[$worker_id][$team_id] = $hits;
			@$worker_counts[$worker_id]['total'] = intval($worker_counts[$worker_id]['total']) + $hits;
			$rs_workers->MoveNext();
		}
		$tpl->assign('worker_counts', $worker_counts);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/worker_replies.tpl.php');
	}
	
	function drawTicketGraphAction() {
		$path = realpath(dirname(__FILE__).'/../resources/font');
		
		$uri = DevblocksPlatform::getHttpRequest();
		$stack = $uri->path;
		@array_shift($stack); // report
		@array_shift($stack); // graph
		@$age = array_shift($stack); // age
		
		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		
		$db = DevblocksPlatform::getDatabaseService();
		$block = $age_term=='mo'?2629800:86400;
		$now_day = floor(time()/$block);
		
		$sql = sprintf("SELECT count(*) as hits, floor(created_date/%d) AS day ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d ".
			"GROUP BY day",
			$block,
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs = $db->Execute($sql);

	    $graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'Created Tickets (Past '.$age_dur." ".($age_term=='d'?'Days':'Months').')');
	    $graph->setProp('titlesize', 14);
//	    $graph->setProp('actwidth', 400);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
	    $graph->setProp('startdate', "-".$age_dur." ".($age_term=='d'?'days':'months'));
//	    $graph->setProp('enddate', '10/31/2007');
	    $graph->setProp('dateformat', 5);
//	    $graph->setProp('xlabel', 'Day');
//	    $graph->setProp('ylabel', 'Worker');
	    $graph->setProp('labelsize', 10);
//	    $graph->setProp('type', 'bar');
//	    $graph->setProp("colorlist",array(array(100,100,255),array(150,255,255),array(160,255,160),array(255,255,150),array(255,110,110)));
	    $graph->setProp('showgrid', true);
//	    $graph->setProp('showkey',true);
	    $graph->setProp('keywidspc',20);
	    $graph->setProp('keyinfo',3);
//	    $graph->setProp('key',array('Jeff Standen','Brenan Cavish','Mike Fogg','Dan Hildebrandt','Darren Sugita'));
	    $graph->setProp('keyfont', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('keysize', 10);

	    $days = array();
	    for($x=-1*$age_dur;$x<=0;$x++) {
	    	$days[$x] = 0;
	    }
	    
	    while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
	    	$d = -1*($now_day - intval($rs->fields['day']));
	    	$days[$d] = $hits;
		    $rs->MoveNext();
	    }
	    
	    foreach($days as $d => $hits) {
	    	$graph->addPoint($hits,'d:'.$d.' '.($age_term=='d'?'days':'months'),0);
	    }
	    
//		$graph->setProp("key","Jeff Standen",0);
		$graph->setColor('color',0,'red');
		
		$graph->graph();
		$graph->showGraph(true); 		    	
	}
	
	function drawRepliesGraphAction() {
		$path = realpath(dirname(__FILE__).'/../resources/font');
		
		$uri = DevblocksPlatform::getHttpRequest();
		$stack = $uri->path;
		@array_shift($stack); // report
		@array_shift($stack); // graph
		@$age = array_shift($stack); // age
		
		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		
		$db = DevblocksPlatform::getDatabaseService();
		$block = $age_term=='mo'?2629800:86400;
		$now_day = floor(time()/$block);
		
		$sql = sprintf("SELECT count(*) as hits, floor(created_date/%d) AS day ".
			"FROM message ".
			"WHERE created_date > %d AND created_date <= %d ".
			"AND is_outgoing = 1 ".
			"GROUP BY day",
			$block,
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs = $db->Execute($sql);
		
	    $graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'Outgoing Replies (Past '.$age_dur." ".($age_term=='d'?'Days':'Months').')');
	    $graph->setProp('titlesize', 14);
//	    $graph->setProp('actwidth', 400);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
	    $graph->setProp('startdate', "-".$age_dur." ".($age_term=='d'?'days':'months'));
//	    $graph->setProp('enddate', '10/31/2007');
	    $graph->setProp('dateformat', 5);
//	    $graph->setProp('xlabel', 'Day');
//	    $graph->setProp('ylabel', 'Worker');
//	    $graph->setProp('labelsize', 20);
//	    $graph->setProp('type', 'bar');
//	    $graph->setProp("colorlist",array(array(100,100,255),array(150,255,255),array(160,255,160),array(255,255,150),array(255,110,110)));
	    $graph->setProp('showgrid', true);
//	    $graph->setProp('showkey',true);
	    $graph->setProp('keywidspc',20);
	    $graph->setProp('keyinfo',3);
//	    $graph->setProp('key',array('Jeff Standen','Brenan Cavish','Mike Fogg','Dan Hildebrandt','Darren Sugita'));
	    $graph->setProp('keyfont', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('keysize', 10);
	    
	    $days = array();
	    for($x=-1*$age_dur;$x<=0;$x++) {
	    	$days[$x] = 0;
	    }
	    
	    while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
	    	$d = -1*($now_day - intval($rs->fields['day']));
	    	$days[$d] = $hits;
		    $rs->MoveNext();
	    }
	    
	    foreach($days as $d => $hits) {
	    	$graph->addPoint($hits,'d:'.$d.' '.($age_term=='d'?'days':'months'),0);
	    }
	    
//		$graph->setProp("key","Jeff Standen",0);
		$graph->setColor('color',0,'red');
		
		$graph->graph();
		$graph->showGraph(true); 		    	
	}

	function drawAverageResponseTimeGraphAction() {
		$path = realpath(dirname(__FILE__).'/../resources/font');
		
		$uri = DevblocksPlatform::getHttpRequest();
		$stack = $uri->path;
		@array_shift($stack); // report
		@array_shift($stack); // graph
		@$age = array_shift($stack); // age
		
		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30; // [TODO]: I'm pretty sure this is not gonna do months right...
		if(empty($age_term)) $age_term = 'd';
		
		$db = DevblocksPlatform::getDatabaseService();
		$block = $age_term=='mo'?2629800:86400;
		$now_day = floor(time()/$block);
		
		$graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'Average Worker Response Time (Past '.$age_dur." ".($age_term=='d'?'Days':'Months').')');
	    $graph->setProp('titlesize', 14);
//	    $graph->setProp('actwidth', 400);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
	    $graph->setProp('startdate', "-".$age_dur." ".($age_term=='d'?'days':'months'));
//	    $graph->setProp('enddate', '10/31/2007');
	    $graph->setProp('dateformat', 5);
//	    $graph->setProp('xlabel', 'Day');
//	    $graph->setProp('ylabel', 'Worker');
//	    $graph->setProp('labelsize', 20);
//	    $graph->setProp('type', 'bar');
//	    $graph->setProp("colorlist",array(array(100,100,255),array(150,255,255),array(160,255,160),array(255,255,150),array(255,110,110)));
	    $graph->setProp('showgrid', true);
//	    $graph->setProp('showkey',true);
	    $graph->setProp('keywidspc',20);
	    $graph->setProp('keyinfo',3);
//	    $graph->setProp('key',$worker_names);
	    $graph->setProp('keyfont', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('keysize', 10);
	    
	   	// First, we need the list of Workers
	   	$workers = DAO_Worker::getAll();
	   	$worker_names = array();
	   	$worker_responses = array();
	   	foreach($workers as $worker) {
	   		$graph->setProp("key",$worker->first_name.' '.$worker->last_name,$worker->id);
	   		$worker_responses[$worker->id] = array();
		    for($x=-1*$age_dur;$x<=0;$x++) {
		    	$worker_responses[$worker->id][$now_day + $x] = array();
		   		$worker_responses[$worker->id][$now_day + $x]['count'] = 0;
		   		$worker_responses[$worker->id][$now_day + $x]['total_time'] = 0;
		    }
	   	}
	   	
	    // Then the list of all outgoing messages for the desired time frame
	   	$sql = sprintf("SELECT id ".
			"FROM message m ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ",
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs_responses = $db->Execute($sql);
		
		$messages = array();
		while(!$rs_responses->EOF) {
			$messages[] = intval($rs_responses->fields['id']);
			$rs_responses->MoveNext();
		}
		
		// now we evaluate each message, find the timelapse, and assign it to a worker.
		$prev_message_timestamp = 0;
		$curr_message_timestamp = 0;
		foreach($messages as $id) {
			$message = DAO_Ticket::getMessage($id);
			$tkt_messages = DAO_Ticket::getMessagesByTicket($message->ticket_id);
			
			// get timestamps
			foreach($tkt_messages as $mess) {
				$curr_message_timestamp = $mess->created_date;
				if($mess->id == $id) break;
				$prev_message_timestamp = $mess->created_date;
			}
			
			// if this is the first message on the ticket, don't count it.
			if($prev_message_timestamp == $curr_message_timestamp
			|| $prev_message_timestamp == 0
			|| $curr_message_timestamp == 0) continue;
			
			// identify day in question
			$message_day = floor($curr_message_timestamp/$block);
			
			// add it to the worker's totals
			$worker_responses[$message->worker_id][$message_day + $x]['count'] += 1;
			$worker_responses[$message->worker_id][$message_day + $x]['total_time'] += $curr_message_timestamp - $prev_message_timestamp;
		}
		
		// go through and add the data points for each worker
		foreach($worker_responses as $worker_id => $responses) {
			foreach($days as $day => $totals) {
				$graph->addPoint($totals['total_time']/$totals['count']/60,$day,$worker_id);
			}
		}

//		$graph->setColor('color',0,'red');
		
echo("<pre>");
print_r($graph);
echo("</pre>");

		$graph->graph();
		$graph->showGraph(true); 		    	
	}

};
?>