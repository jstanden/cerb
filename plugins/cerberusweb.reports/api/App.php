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
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'integer', 30);
		
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('age', $age);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d ".
			"GROUP BY team_id, category_id ".
			"ORDER BY team_id, category_id ",
			strtotime("-".$age." days"),
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
	
	function getWorkerRepliesReportAction() {
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'integer', 30);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);
		
		$tpl->assign('age', $age);
		
		// Top Workers
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$sql = sprintf("SELECT count(*) AS hits, worker_id ".
			"FROM message ".
			"WHERE created_date > %d AND created_date <= %d ".
			"AND is_outgoing = 1 ".
			"GROUP BY worker_id ".
			"ORDER BY hits desc ",
			strtotime("-".$age." days"),
			time()
		);
		$rs_workers = $db->Execute($sql);
		
		$worker_counts = array();
		while(!$rs_workers->EOF) {
			$hits = intval($rs_workers->fields['hits']);
			$worker_id = intval($rs_workers->fields['worker_id']);
			$worker_counts[$worker_id] = $hits;
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
		
		$db = DevblocksPlatform::getDatabaseService();
		$now_day = floor(time()/86400);
		
		$sql = sprintf("SELECT count(*) as hits, floor(created_date/86400) AS day ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d ".
			"GROUP BY day",
			strtotime("-".$age." days"),
			time()
		);
		$rs = $db->Execute($sql);

	    $graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'New Tickets (Past '.$age.' Days)');
	    $graph->setProp('titlesize', 14);
//	    $graph->setProp('actwidth', 400);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
	    $graph->setProp('startdate', '-'.$age.' days');
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
	    for($x=-1*$age;$x<=0;$x++) {
	    	$days[$x] = 0;
	    }
	    
	    while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
	    	$d = -1*($now_day - intval($rs->fields['day']));
	    	$days[$d] = $hits;
		    $rs->MoveNext();
	    }
	    
	    foreach($days as $d => $hits) {
	    	$graph->addPoint($hits,'d:'.$d.' days',0);
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
		
		$db = DevblocksPlatform::getDatabaseService();
		$now_day = floor(time()/86400);
		
		$sql = sprintf("SELECT count(*) as hits, floor(created_date/86400) AS day ".
			"FROM message ".
			"WHERE created_date > %d AND created_date <= %d ".
			"AND is_outgoing = 1 ".
			"GROUP BY day",
			strtotime("-".$age." days"),
			time()
		);
		$rs = $db->Execute($sql);
		
	    $graph = new graph();
	    $graph->setProp('font', $path.'/ryanlerch_-_Tuffy.ttf');
	    $graph->setProp('title', 'Outgoing Replies (Past '.$age.' Days)');
	    $graph->setProp('titlesize', 14);
//	    $graph->setProp('actwidth', 400);
	    $graph->setProp('actheight', 225);
	    $graph->setProp('xsclpts', 5);
		$graph->setProp('xincpts', 5);
	    $graph->setProp('ysclpts', 5);
	    $graph->setProp('yincpts', 5);
	    $graph->setProp('scale', 'date');
//	    $graph->setProp('sort', false);
	    $graph->setProp('startdate', '-'.$age.' days');
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
	    for($x=-1*$age;$x<=0;$x++) {
	    	$days[$x] = 0;
	    }
	    
	    while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
	    	$d = -1*($now_day - intval($rs->fields['day']));
	    	$days[$d] = $hits;
		    $rs->MoveNext();
	    }
	    
	    foreach($days as $d => $hits) {
	    	$graph->addPoint($hits,'d:'.$d.' days',0);
	    }
	    
//		$graph->setProp("key","Jeff Standen",0);
		$graph->setColor('color',0,'red');
		
		$graph->graph();
		$graph->showGraph(true); 		    	
	}

};
?>