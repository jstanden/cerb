<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
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

class ChReportGroupVirtualAttendants extends Extension_ReportGroup {
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
