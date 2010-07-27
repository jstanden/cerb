<?php
class ChReportTicketAssignment extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Table
		
		$sql = sprintf("SELECT worker.id worker_id, t.id ticket_id, t.mask, t.subject, t.created_date ".
			"FROM ticket t ".
			"INNER JOIN context_link ON (context_link.from_context = 'cerberusweb.contexts.ticket' AND context_link.from_context_id = t.id AND context_link.to_context = 'cerberusweb.contexts.worker') ".
			"INNER JOIN worker ON (worker.id = context_link.to_context_id) ".
			"WHERE t.is_deleted = 0 ". 
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ". 
			"AND is_waiting != 1 ".	
			"ORDER by worker.last_name DESC"
		);
		$rs = $db->Execute($sql);
	
		$ticket_assignments = array();
		while($row = mysql_fetch_assoc($rs)) {
			$worker_id = intval($row['worker_id']);
			$mask = $row['mask'];
			$subject = $row['subject'];
			$created_date = intval($row['created_date']);
			
			if(!isset($ticket_assignments[$worker_id]))
				$ticket_assignments[$worker_id] = array();
				
			unset($assignment);
			$assignment->mask = $mask;
			$assignment->subject = $subject;
			$assignment->created_date = $created_date; 
				
			$ticket_assignments[$worker_id][] = $assignment;
		}
		
		$tpl->assign('ticket_assignments', $ticket_assignments);

		mysql_free_result($rs);
		
		// Chart
		
		$sql = sprintf("SELECT worker.id worker_id, count(*) as hits ".
			"FROM ticket t ".
			"INNER JOIN context_link ON (context_link.from_context = 'cerberusweb.contexts.ticket' AND context_link.from_context_id = t.id AND context_link.to_context = 'cerberusweb.contexts.worker') ".
			"INNER JOIN worker ON (worker.id = context_link.to_context_id) ".
			"WHERE t.is_deleted = 0 ". 
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ". 
			"AND is_waiting != 1 ".	
			"GROUP by worker.id ".
			"ORDER by worker.last_name DESC"
		);
		$rs = $db->Execute($sql);

		$iter = 0;
		$data = array();
		
		while($row = mysql_fetch_assoc($rs)) {
	    	$hits = intval($row['hits']);
			$worker_id = $row['worker_id'];
			
			if(!isset($workers[$worker_id]))
				continue;
				
			$data[$iter++] = array('value'=>$workers[$worker_id]->getName(),'hits'=>$hits);
	    }
	    
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataAsc'));
	    
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/ticket_assignment/index.tpl');
	}
};