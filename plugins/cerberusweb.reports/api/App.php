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

abstract class Extension_Report extends DevblocksExtension {
	function __construct($manifest) {
		parent::DevblocksExtension($manifest);
	}
	
	function render() {
		// Overload 
	}
};

abstract class Extension_ReportGroup extends DevblocksExtension {
	function __construct($manifest) {
		parent::DevblocksExtension($manifest);
	}
};

class ChReportGroupTickets extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupWorkers extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupRoster extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$rosters = DAO_Group::getRosters();
		$tpl->assign('rosters', $rosters);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/group_roster/index.tpl.php');
	}
};

class ChReportNewTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/new_tickets/index.tpl.php');
	}
	
	function getNewTicketsReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

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
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d AND is_deleted = 0 ".
			"GROUP BY team_id, category_id ",
			$start_time,
			$end_time
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
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/new_tickets/html.tpl.php');
	}
	
	function getTicketChartDataAction() {
		$path = realpath(dirname(__FILE__).'/../resources/font');
		
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
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$groups = DAO_Group::getAll();
		
		$sql = sprintf("SELECT team.id as group_id, ".
				"count(*) as hits ".
				"FROM ticket t inner join team on t.team_id = team.id ".
				"WHERE t.created_date > %d ".
				"AND t.created_date <= %d ".
				"AND t.is_deleted = 0 ".
				"GROUP BY group_id ORDER by team.name desc ",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);
	    
		
	    while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
			$group_id = $rs->fields['group_id'];
			
			echo $groups[$group_id]->name, "\t", $hits . "\n";
			
		    $rs->MoveNext();
	    }
	}
	
	function getTicketChartDataCountAction() {
		$path = realpath(dirname(__FILE__).'/../resources/font');
		
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
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT team.id as group_id, ".
				"count(*) as hits ".
				"FROM ticket t inner join team on t.team_id = team.id ".
				"WHERE t.created_date > %d ".
				"AND t.created_date <= %d ".
				"AND t.is_deleted = 0 ".
				"GROUP BY group_id ORDER BY team.name",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);
	    echo $rs->RecordCount();
	}	
}



class ChReportWorkerReplies extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/worker_replies/index.tpl.php');
	}
	
	function getWorkerRepliesReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string', '30d');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
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
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
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
			$start_time,
			$end_time
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
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/worker_replies/html.tpl.php');
	}
	
	function getWorkerRepliesChartAction() {
		header("content-type: text/plain");
	
		$db = DevblocksPlatform::getDatabaseService();
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
		
		// Top Workers
		$workers = DAO_Worker::getAll();
		
		$sql = sprintf("SELECT count(*) AS hits, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN worker w ON w.id=m.worker_id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id ORDER BY w.last_name DESC ",
			$start_time,
			$end_time
		);

		$rs_workers = $db->Execute($sql);
		
		$worker_counts = array();
		
		while(!$rs_workers->EOF) {
			$hits = intval($rs_workers->fields['hits']);
			$worker_id = intval($rs_workers->fields['worker_id']);
			
			if(!isset($workers[$worker_id]))
				continue;

			echo $workers[$worker_id]->getName() , "\t" , $hits , "\n";
			$rs_workers->MoveNext();
		}
	}
	
	function getTotalWorkersForChartAction() {
		header("content-type: text/plain");
	
		$db = DevblocksPlatform::getDatabaseService();
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
		
		$sql = sprintf("SELECT count(*) AS hits, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id ",
			$start_time,
			$end_time
		);
		$rs_workers = $db->Execute($sql);	
		echo $rs_workers->RecordCount();
	
	}
	
}

class ChReportAverageResponseTime extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = realpath(dirname(__FILE__).'/../templates');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/average_response_time/index.tpl.php');
	}
	
	function getAverageResponseTimeReportAction() {
		// init
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

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
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
	   	$workers = DAO_Worker::getAll();
	   	$tpl->assign('workers',$workers);
		
	   	// pull data from db
	   	$sql = sprintf("SELECT mm.id, mm.ticket_id, mm.created_date, mm.worker_id, mm.is_outgoing, t.team_id, t.category_id ".
			"FROM message m ".
	   		"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	   		"INNER JOIN message mm ON (mm.ticket_id=t.id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND m.is_outgoing = 1 ".
	   		"ORDER BY ticket_id,id ",
			$start_time,
			$end_time
		);
		$rs_responses = $db->Execute($sql);
		
		// process and count results
	   	$group_responses = array();
	   	$worker_responses = array();
	   	$prev = array();
		while(!$rs_responses->EOF) {
			// load current data
			$id = intval($rs_responses->fields['id']);
			$ticket_id = intval($rs_responses->fields['ticket_id']);
			$created_date = intval($rs_responses->fields['created_date']);
			$worker_id = intval($rs_responses->fields['worker_id']);
			$is_outgoing = intval($rs_responses->fields['is_outgoing']);
			$team_id = intval($rs_responses->fields['team_id']);
			$category_id = intval($rs_responses->fields['category_id']);
			
			// we only add data if it's a worker reply to the same ticket as $prev
			if ($is_outgoing==1 && !empty($prev) && $ticket_id==$prev['ticket_id']) {
				// Initialize, if necessary
				if (!isset($group_responses[$team_id])) $group_responses[$team_id] = array();
				if (!isset($worker_responses[$worker_id])) $worker_responses[$worker_id] = array();
				
				// log reply and time
				@$group_responses[$team_id]['replies'] += 1;
				@$group_responses[$team_id]['time'] += $created_date - $prev['created_date'];
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
				'team_id'=>$team_id,
				'category_id'=>$category_id,
				);
			$rs_responses->MoveNext();
		}
		$tpl->assign('group_responses', $group_responses);
		$tpl->assign('worker_responses', $worker_responses);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/report/average_response_time/html.tpl.php');
	}
	
}

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
	
	/**
	 * Proxy page actions from an extension's render() to the extension's scope.
	 *
	 */
	function actionAction() {
		@$extid = $_REQUEST['extid'];
		@$extid_a = DevblocksPlatform::strAlphaNumDash($_REQUEST['extid_a']);
		
		$action = $extid_a.'Action';
		
		$reportMft = DevblocksPlatform::getExtension($extid);
		
		// If it's a value report extension, proxy the action
		if(null != ($reportInst = DevblocksPlatform::getExtension($extid, true)) 
			&& $reportInst instanceof Extension_Report) {
				
			// If we asked for a value method on the extension, call it
			if(method_exists($reportInst, $action)) {
				call_user_method($action, $reportInst);
			}
		}
		
		return;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // reports
		@$reportId = array_shift($stack);
		$report = null;

		// We're given a specific report to display
		if(!empty($reportId)) {
			if(null != ($reportMft = DevblocksPlatform::getExtension($reportId))) {
				if(null != ($report = $reportMft->createInstance()) && $report instanceof Extension_Report) { /* @var $report Extension_Report */
					$report->render();
					return;
				}
			}
		}
		
		// If we don't have a selected report yet
		if(empty($report)) {
			// Organize into report groups
			$report_groups = array();
			$reportGroupMfts = DevblocksPlatform::getExtensions('cerberusweb.report.group', false);
			
			// [TODO] Alphabetize groups and nested reports
			
			// Load report groups
			if(!empty($reportGroupMfts))
			foreach($reportGroupMfts as $reportGroupMft) {
				$report_groups[$reportGroupMft->id] = array(
					'name' => $reportGroupMft->name,
					'reports' => array()
				);
			}
			
			$reportMfts = DevblocksPlatform::getExtensions('cerberusweb.report', false);
			
			// Load reports and file them under groups according to manifest
			if(!empty($reportMfts))
			foreach($reportMfts as $reportMft) {
				$report_group = $reportMft->params['report_group'];
				if(isset($report_group)) {
					$report_groups[$report_group]['reports'][] = $reportMft;
				}
			}
			
			$tpl->assign('report_groups', $report_groups);
		}

		$tpl->display('file:' . $this->tpl_path . '/reports/index.tpl.php');
	}
	
	/*
	function getAverageResponseTimeReportAction() {
		// init
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		@$start = DevblocksPlatform::importGPC($_REQUEST['startART'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['endART'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		@list($age_dur, $age_term) = sscanf($age,"%d%s");
		if(empty($age_dur)) $age_dur = 30;
		if(empty($age_term)) $age_term = 'd';
		if (empty($start) && empty($end)) {
			$start_time = strtotime("-".$age_dur." ".($age_term=='d'?'days':'months'));
			$end_time = time();
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		// reload variables in template
		$tpl->assign('age', $age);
		$tpl->assign('age_dur', $age_dur);
		$tpl->assign('age_term', $age_term);
		$tpl->assign('startART', $start);
		$tpl->assign('endART', $end);
		
		// set up necessary reference arrays
	   	$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
	   	$workers = DAO_Worker::getAll();
	   	$tpl->assign('workers',$workers);
		
	   	// pull data from db
	   	$sql = sprintf("SELECT mm.id, mm.ticket_id, mm.created_date, mm.worker_id, mm.is_outgoing, t.team_id, t.category_id ".
			"FROM message m ".
	   		"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	   		"INNER JOIN message mm ON (mm.ticket_id=t.id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND m.is_outgoing = 1 ".
	   		"ORDER BY ticket_id,id ",
			$start_time,
			$end_time
		);
		$rs_responses = $db->Execute($sql);
//echo("<pre>");
//print_r($sql);
//echo("</pre>");
		
		// process and count results
	   	$group_responses = array();
	   	$worker_responses = array();
	   	$prev = array();
		while(!$rs_responses->EOF) {
			// load current data
			$id = intval($rs_responses->fields['id']);
			$ticket_id = intval($rs_responses->fields['ticket_id']);
			$created_date = intval($rs_responses->fields['created_date']);
			$worker_id = intval($rs_responses->fields['worker_id']);
			$is_outgoing = intval($rs_responses->fields['is_outgoing']);
			$team_id = intval($rs_responses->fields['team_id']);
			$category_id = intval($rs_responses->fields['category_id']);
			
			// we only add data if it's a worker reply to the same ticket as $prev
			if ($is_outgoing==1 && $ticket_id==$prev['ticket_id']) {
				// Initialize, if necessary
				if (!isset($group_responses[$team_id])) $group_responses[$team_id] = array();
				if (!isset($worker_responses[$worker_id])) $worker_responses[$worker_id] = array();
				
				// log reply and time
				$group_responses[$team_id]['replies'] += 1;
				$group_responses[$team_id]['time'] += $created_date - $prev['created_date'];
				$worker_responses[$worker_id]['replies'] += 1;
				$worker_responses[$worker_id]['time'] += $created_date - $prev['created_date'];
			}
			
			// Save this one as "previous" and move on
			$prev = array(
				'id'=>$id,
				'ticket_id'=>$ticket_id,
				'created_date'=>$created_date,
				'worker_id'=>$worker_id,
				'is_outgoing'=>$is_outgoing,
				'team_id'=>$team_id,
				'category_id'=>$category_id,
				);
			$rs_responses->MoveNext();
		}
		$tpl->assign('group_responses', $group_responses);
		$tpl->assign('worker_responses', $worker_responses);
		
//echo("<pre>");
//print_r($group_responses);
//print_r($worker_responses);
//echo("</pre>");

		$tpl->display('file:' . $this->tpl_path . '/reports/response_time.tpl.php');
	}
	
	function getNewEmailsReportAction() {
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
		
		$sql = sprintf("SELECT count(m.id) AS hits, t.team_id, t.category_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND t.is_deleted = 0 AND m.is_outgoing = 0 ".
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
		
		$tpl->display('file:' . $this->tpl_path . '/reports/new_emails.tpl.php');
	}
	*/
	
//	
//	function getGroupSummaryReportAction() {
//		
//	}
	
//	function drawAverageResponseTimeGraphAction() {
//		$path = realpath(dirname(__FILE__).'/../resources/font');
//		
//		$uri = DevblocksPlatform::getHttpRequest();
//		$stack = $uri->path;
//		@array_shift($stack); // report
//		@array_shift($stack); // graph
//		@$age = array_shift($stack); // age
//		
//		@list($age_dur, $age_term) = sscanf($age,"%d%s");
//		if(empty($age_dur)) $age_dur = 30; // [TODO]: I'm pretty sure this is not gonna do months right...
//		if(empty($age_term)) $age_term = 'd';
//		
//		$db = DevblocksPlatform::getDatabaseService();
//		$block = $age_term=='mo'?2629800:86400;
//		$now_day = floor(time()/$block);
//		
//		$graph = new graph();
//	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
//	    $graph->setProp('title', 'Average Worker Response Time (Past '.$age_dur." ".($age_term=='d'?'Days':'Months').')');
//	    $graph->setProp('titlesize', 14);
////	    $graph->setProp('actwidth', 400);
//	    $graph->setProp('actheight', 225);
//	    $graph->setProp('xsclpts', 5);
//		$graph->setProp('xincpts', 5);
//	    $graph->setProp('ysclpts', 5);
//	    $graph->setProp('yincpts', 5);
//	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
//	    $graph->setProp('startdate', "-".$age_dur." ".($age_term=='d'?'days':'months'));
////	    $graph->setProp('enddate', '10/31/2007');
//	    $graph->setProp('dateformat', 5);
////	    $graph->setProp('xlabel', 'Day');
////	    $graph->setProp('ylabel', 'Minutes');
////	    $graph->setProp('labelsize', 20);
////	    $graph->setProp('type', 'bar');
////	    $graph->setProp("colorlist",array(array(100,100,255),array(150,255,255),array(160,255,160),array(255,255,150),array(255,110,110)));
//	    $graph->setProp('showgrid', true);
//	    $graph->setProp('showkey',true);
//	    $graph->setProp('keywidspc',20);
//	    $graph->setProp('keyinfo',3);
////	    $graph->setProp('key',$worker_names);
//	    $graph->setProp('keyfont', $path.'/ryanlerch_-_Tuffy.ttf');
//	    $graph->setProp('keysize', 10);
//	    
//	   	// First, we need the list of Workers
//	   	$workers = DAO_Worker::getAll();
//	   	$worker_names = array();
//	   	$worker_responses = array();
//	   	// interestingly, datasets must begin with 0 and increase by 1.  random ids DO NOT work.
//	   	$i = 0;
//	   	foreach($workers as $worker) {
//	   		$graph->setProp("key",$worker->first_name.' '.$worker->last_name,$i);
//	   		$graph->setColor("color",$i,rand(0,255),rand(0,255),rand(0,255));
//	   		$worker_responses[$worker->id] = array();
//	   		$worker_responses[$worker->id]['sequence'] = $i;
//	   		$i++; 
//	   		$worker_responses[$worker->id]['days'] = array();
//		    for($x=-1*$age_dur;$x<=0;$x++) {
//		    	$worker_responses[$worker->id]['days'][$now_day + $x] = array();
////		   		$worker_responses[$worker->id]['days'][$now_day + $x]['count'] = rand(1,20);
////		   		$worker_responses[$worker->id]['days'][$now_day + $x]['total_time'] = rand(500,50000);
//		   		$worker_responses[$worker->id]['days'][$now_day + $x]['count'] = 0;
//		   		$worker_responses[$worker->id]['days'][$now_day + $x]['total_time'] = 0;
//		    }
//	   	}
//	   	
//	    // Then the list of all outgoing messages for the desired time frame
//	   	$sql = sprintf("SELECT id ".
//			"FROM message m ".
//			"WHERE m.created_date > %d AND m.created_date <= %d ".
//			"AND m.is_outgoing = 1 ",
//			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
//			time()
//		);
//		$rs_responses = $db->Execute($sql);
//		
//		$messages = array();
//		while(!$rs_responses->EOF) {
//			$messages[] = intval($rs_responses->fields['id']);
//			$rs_responses->MoveNext();
//		}
//		
//		// now we evaluate each message, find the timelapse, and assign it to a worker.
//		$prev_message_timestamp = 0;
//		$curr_message_timestamp = 0;
//		foreach($messages as $id) {
//			$message = DAO_Ticket::getMessage($id);
//			$tkt_messages = DAO_Ticket::getMessagesByTicket($message->ticket_id);
//			
//			// get timestamps
//			foreach($tkt_messages as $mess) {
//				$curr_message_timestamp = $mess->created_date;
//				if($mess->id == $id) break;
//				$prev_message_timestamp = $mess->created_date;
//			}
//			
//			// if this is the first message on the ticket, don't count it.
//			if($prev_message_timestamp == $curr_message_timestamp
//			|| $prev_message_timestamp == 0
//			|| $curr_message_timestamp == 0) continue;
//			
//			// identify day in question
//			$message_day = floor($curr_message_timestamp/$block);
//			
//			// add it to the worker's totals
//			$worker_responses[$message->worker_id]['days'][$message_day + $x]['count'] += 1;
//			$worker_responses[$message->worker_id]['days'][$message_day + $x]['total_time'] += $curr_message_timestamp - $prev_message_timestamp;
//		}
////echo("<pre>");
////print_r($worker_responses);
////echo("</pre>");
////return;
//		
//		// go through and add the data points for each worker
//		foreach($worker_responses as $worker_id => $responses) {
//			foreach($responses['days'] as $day => $totals) {
//				if ($totals['count'] == 0) {
//					$graph->addPoint(0,$day,$responses['sequence']);
//				} else {
//					$graph->addPoint($totals['total_time']/$totals['count']/60,$day,$responses['sequence']);
//				}
//			}
//		}
//
////		$graph->setColor('color',0,'red');
////		$graph->setColor('color',1,'blue');
////		unset($graph->iVars[0]);
////		unset($graph->dVars[0]);
//		
////echo("<pre>");
////print_r($graph);
////echo("</pre>");
////return;
//		$graph->colorlist = true;
//
//		$graph->graph();
////		$graph->showGraph('e:\dev\tmp\graph.png'); 		    	
//		$graph->showGraph(true); 		    	
//	}

	/*
	function drawEmailGraphAction() {
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
			"WHERE created_date > %d AND created_date <= %d AND is_outgoing = 0 ".
			"GROUP BY day",
			$block,
			strtotime("-".$age_dur." ".($age_term=='d'?'days':'months')),
			time()
		);
		$rs = $db->Execute($sql);

	    $graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'Incoming Emails (Past '.$age_dur." ".($age_term=='d'?'Days':'Months').')');
	    $graph->setProp('titlesize', 14);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
	    $graph->setProp('startdate', "-".$age_dur." ".($age_term=='d'?'days':'months'));
	    $graph->setProp('dateformat', 5);
	    $graph->setProp('labelsize', 10);
	    $graph->setProp('showgrid', true);
	    $graph->setProp('keywidspc',20);
	    $graph->setProp('keyinfo',3);
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
	    
		$graph->setColor('color',0,'red');
		
		$graph->graph();
		$graph->showGraph(true); 		    	
	}
	*/
	
};

?>
