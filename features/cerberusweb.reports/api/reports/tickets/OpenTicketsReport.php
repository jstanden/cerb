<?php
class ChReportOpenTickets extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$db = DevblocksPlatform::getDatabaseService();
		
		// DAO
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Chart
		$sql = sprintf("SELECT team.id as group_id, ".
			"count(*) as hits ".
			"FROM ticket t inner join team on t.team_id = team.id ".
			"WHERE 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ".
			"AND is_waiting != 1 " .				
			"GROUP BY group_id ORDER by team.name desc "
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
		
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE 1 ".			
			"AND is_deleted = 0 ".
			"AND is_closed = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"AND is_waiting != 1 " .
			"GROUP BY team_id, category_id "
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
		
		$tpl->display('devblocks:cerberusweb.reports::reports/ticket/open_tickets/index.tpl');
	}
};