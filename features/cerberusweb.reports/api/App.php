<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

abstract class Extension_Report extends DevblocksExtension {
	function render() {
		// Overload
	}
};

abstract class Extension_ReportGroup extends DevblocksExtension {
};

class ChReportSorters {
	static function sortDataDesc($a, $b) {
		$sum1 = is_array($a) ? array_sum($a) : $a;
		$sum2 = is_array($b) ? array_sum($b) : $b;
		
		if($sum1 == $sum2)
			return 0;
		
		return ($sum1 > $sum2) ? -1 : 1; // desc order
	}
	static function sortDataAsc($a, $b) {
		$sum1 = is_array($a) ? array_sum($a) : $a;
		$sum2 = is_array($b) ? array_sum($b) : $b;
		
		if($sum1 == $sum2)
			return 0;
		
		return ($sum1 > $sum2) ? 1 : -1; // asc order
	}
};

class ChReportGroupTickets extends Extension_ReportGroup {
};

class ChReportGroupWorkers extends Extension_ReportGroup {
};

class ChReportGroupGroups extends Extension_ReportGroup {
};

class ChReportGroupCustomFields extends Extension_ReportGroup {
};

class ChReportGroupOrgs extends Extension_ReportGroup {
};

class ChReportGroupSnippets extends Extension_ReportGroup {
};

class ChReportGroupSpam extends Extension_ReportGroup {
};

if(class_exists('Extension_WorkspacePage')):
class ChReportsWorkspacePage extends Extension_WorkspacePage {
	function renderPage(Model_WorkspacePage $page) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		@array_shift($stack); // pages
		@array_shift($stack); // reports
		@$reportId = array_shift($stack);

		$report = null;
		
		$tpl->assign('page', $page);

		// We're given a specific report to display
		if(!empty($reportId)) {
			if(null != ($reportMft = DevblocksPlatform::getExtension($reportId))) {
				// Make sure we have a report group
				if(null == ($report_group_mft_id = $reportMft->params['report_group']))
					return;
					
				// Make sure the report group exists
				if(null == ($report_group_mft = DevblocksPlatform::getExtension($report_group_mft_id)))
					return;
					
				// Check our permissions on the parent report group before rendering the report
				if(isset($report_group_mft->params['acl']) && !$active_worker->hasPriv($report_group_mft->params['acl']))
					return;
					
				// Render
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
					'manifest' => $reportGroupMft,
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

		$tpl->display('devblocks:cerberusweb.reports::reports/index.tpl');
	}
};
endif;
