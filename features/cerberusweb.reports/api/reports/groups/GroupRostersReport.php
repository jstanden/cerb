<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/

class ChReportGroupRoster extends Extension_Report {
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