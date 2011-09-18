<?php
class ChReportOpenTickets extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$db = DevblocksPlatform::getDatabaseService();
		
		// DAO
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Chart
		$sql = sprintf("SELECT worker_group.id as group_id, ".
			"count(*) as hits ".
			"FROM ticket t INNER JOIN worker_group ON t.group_id = worker_group.id ".
			"WHERE 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ".
			"AND is_waiting != 1 " .				
			"GROUP BY group_id ORDER by worker_group.name desc "
			);
		$rs = $db->Execute($sql);
		
		$data = array();
		$iter = 0;
		while($row = mysql_fetch_assoc($rs)) {
			if(!isset($groups[$row['group_id']]))
				continue;
			
			$data[$iter++] = array('value'=>$groups[$row['group_id']]->name,'hits'=>$row['hits']);
		}
		
		// Sort the data in descending order (chart reverses)
		uasort($data, array('ChReportSorters','sortDataAsc'));
		
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);
		
		// Table
		
		$sql = sprintf("SELECT count(*) AS hits, group_id, bucket_id ".
			"FROM ticket ".
			"WHERE 1 ".			
			"AND is_deleted = 0 ".
			"AND is_closed = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"AND is_waiting != 1 " .
			"GROUP BY group_id, bucket_id "
			);
		$rs = $db->Execute($sql);
	
		$group_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$group_id = intval($row['group_id']);
			$bucket_id = intval($row['bucket_id']);
			$hits = intval($row['hits']);
			
			if(!isset($group_counts[$group_id]))
				$group_counts[$group_id] = array();
				
			$group_counts[$group_id][$bucket_id] = $hits;
			@$group_counts[$group_id]['total'] = intval($group_counts[$group_id]['total']) + $hits;
		}
		$tpl->assign('group_counts', $group_counts);

		mysql_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/ticket/open_tickets/index.tpl');
	}
};