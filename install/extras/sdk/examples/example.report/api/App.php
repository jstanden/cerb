<?php
class ExReportsGroup extends Extension_ReportGroup {
};

class ExReport extends Extension_Report {
	function render() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'], 'integer', 0);
		@$date_from = DevblocksPlatform::importGPC($_REQUEST['date_from'], 'string', 'big bang');
		@$date_to = DevblocksPlatform::importGPC($_REQUEST['date_to'], 'string', 'now');
		
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$tpl->assign('group_id', $group_id);
		$tpl->assign('date_from', $date_from);
		$tpl->assign('date_to', $date_to);
		
		// Count tickets by status
		$sql = "SELECT ".
			"count(id) as num_total, ".
			"sum(IF(is_closed=1,1,0)) as num_closed, ".
			"sum(IF(is_waiting=1 AND is_closed=0,1,0)) as num_waiting ".
			"FROM ticket ".
			"WHERE is_deleted=0 "
			;
			
		if(!empty($group_id))
			$sql .= sprintf("AND group_id = %d ", $group_id);
		
		if(!empty($date_from) && !empty($date_to)) {
			if(false != ($date_from = @strtotime($date_from)) && false != ($date_to = @strtotime($date_to)))
				$sql .= sprintf("AND created_date >= %d and created_date <= %d ", $date_from, $date_to);
		}
		
		$ticket_stats = $db->GetRow($sql);
		
		$num_total = intval($ticket_stats['num_total']);
		$num_closed = intval($ticket_stats['num_closed']);
		$num_waiting = intval($ticket_stats['num_waiting']);
		$num_open = $num_total - $num_waiting - $num_closed;
		
		$tpl->assign('ticket_stats', array(
			'total' => $num_total,
			'open' => $num_open,
			'waiting' => $num_waiting,
			'closed' => $num_closed,
		));
		
		$tpl->display('devblocks:example.report::reports/example.tpl');
	}
};