<?php
class ChReportGroupRoster extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$rosters = DAO_Group::getRosters();
		$tpl->assign('rosters', $rosters);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/group/group_roster/index.tpl');
	}
};