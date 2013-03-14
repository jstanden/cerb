<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, Webgroup Media LLC
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

class ChReportSnippetPopularity extends Extension_Report {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$db = DevblocksPlatform::getDatabaseService();

		// Filter: Start + End
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$start_time = strtotime($start);
		$start_time -= $start_time % 86400;
		$tpl->assign('start', $start);
		
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		@$end_time = strtotime($end);
		$end_time -= $end_time % 86400;
		$tpl->assign('end', $end);

		// Filter: Limit
		@$limit = DevblocksPlatform::importGPC($_REQUEST['limit'], 'integer', 0);
		$tpl->assign('limit', $limit);
		
		// Filter: Workers
		
		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		$filter_worker_ids = DevblocksPlatform::sanitizeArray($filter_worker_ids, 'integer', array('unique','nonzero'));
		
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Data
		
		$sql = sprintf("SELECT snippet.id AS snippet_id, snippet.title AS snippet_title, SUM(snippet_use_history.uses) AS snippet_uses ".
			"FROM snippet_use_history ".
			"INNER JOIN snippet ON (snippet_use_history.snippet_id=snippet.id) ".
			"WHERE snippet_use_history.ts_day BETWEEN %d AND %d ".
			"%s ".
			"GROUP BY snippet_use_history.snippet_id ".
			"ORDER BY snippet_uses %%s ".
			"%s",
			$start_time,
			$end_time,
			(!empty($filter_worker_ids) && is_array($filter_worker_ids) ? sprintf("AND snippet_use_history.worker_id IN (%s)", implode(',', $filter_worker_ids)) : ''),
			(!empty($limit) ? sprintf("LIMIT %d", $limit) : '')
		);
		
		$most_popular = $db->GetArray(sprintf($sql, 'DESC'));
		$tpl->assign('most_popular', $most_popular);
		
		$least_popular = $db->GetArray(sprintf($sql, 'ASC'));
		$tpl->assign('least_popular', $least_popular);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/snippets/snippet_popularity/index.tpl');
	}
};