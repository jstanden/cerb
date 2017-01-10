<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class ChReportGroupReplies extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		$date = DevblocksPlatform::getDateService();
		
		// Use the worker's timezone for MySQL date functions
		$db->ExecuteSlave(sprintf("SET time_zone = %s", $db->qstr($date->formatTime('P', time()))));
		
		// Filters
		
		@$filter_group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
		$tpl->assign('filter_group_ids', $filter_group_ids);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Years
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM message WHERE created_date > 0 AND is_outgoing = 1 GROUP BY year ORDER BY year desc limit 0,10";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysqli_free_result($rs);

		// Times
		
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
			
			$tpl->assign('invalidDate', true);
		}
		
		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
		// Calculate the # of ticks between the dates (and the scale -- day, month, etc)
		$range = $end_time - $start_time;
		$range_days = $range/86400;
		$plots = $range/15;
		
		@$report_date_grouping = DevblocksPlatform::importGPC($_REQUEST['report_date_grouping'],'string','');
		$date_group = '';
		$date_increment = '';
		
		// Did the user choose a specific grouping?
		switch($report_date_grouping) {
			case 'year':
				$date_group = '%Y';
				$date_increment = 'year';
				break;
			case 'month':
				$date_group = '%Y-%m';
				$date_increment = 'month';
				break;
			case 'week':
				$date_group = '%x-%v';
				$date_increment = 'week';
				break;
			case 'day':
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
				break;
			case 'hour':
				$date_group='%Y-%m-%d %H';
				$date_increment = 'hour';
				break;
		}
		
		// Fallback to automatic grouping
		if(empty($date_group) || empty($date_increment)) {
			if($range_days > 365) {
				$date_group = '%Y';
				$date_increment = 'year';
			} elseif($range_days > 32) {
				$date_group = '%Y-%m';
				$date_increment = 'month';
			} elseif($range_dates > 8) {
				$date_group = '%x-%v';
				$date_increment = 'week';
			} elseif($range_days > 1) {
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
			} else {
				$date_group = '%Y-%m-%d %H';
				$date_increment = 'hour';
			}
		}
		
		$tpl->assign('report_date_grouping', $date_increment);
		
		// Ticks
		
		$ticks = $date->getIntervals($date_increment, $start_time, $end_time);
		
		// Table
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Message');
		$defaults->id = 'report_group_history';
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->is_ephemeral = true;
			$view->removeAllParams();

			$view->view_columns = array(
				SearchFields_Message::TICKET_GROUP_ID,
				SearchFields_Message::CREATED_DATE,
				SearchFields_Message::WORKER_ID,
			);
			
			$params = array(
				new DevblocksSearchCriteria(SearchFields_Message::CREATED_DATE,DevblocksSearchCriteria::OPER_BETWEEN, array($start_time, $end_time)),
				new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING,DevblocksSearchCriteria::OPER_EQ, 1),
				new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID,DevblocksSearchCriteria::OPER_NEQ, 0)
			);
			
			if(!empty($filter_group_ids)) {
				$params[] = new DevblocksSearchCriteria(SearchFields_Message::TICKET_GROUP_ID,DevblocksSearchCriteria::OPER_IN, $filter_group_ids);
			}

			$view->addParamsRequired($params, true);
			
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Message::CREATED_DATE;
			$view->renderSortAsc = false;
			
			$tpl->assign('view', $view);
		}
		
		// Chart
				
		$query_parts = DAO_Message::getSearchQueryComponents($view->view_columns, $view->getParams());
		
		$sql = sprintf("SELECT t.group_id as group_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') as date_plot, ".
			"count(DISTINCT m.id) AS hits ".
			"%s ".
			"%s ".
			"GROUP BY group_id, date_plot ",
			$date_group,
			$query_parts['join'],
			$query_parts['where']
		);
		
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$data = array();
		while($row = mysqli_fetch_assoc($rs)) {
			$group_id = intval($row['group_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$group_id]))
				$data[$group_id] = $ticks;
			
			$data[$group_id][$date_plot] = intval($row['hits']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysqli_free_result($rs);
		
		$tpl->display('devblocks:cerberusweb.reports::reports/group/group_replies/index.tpl');
	}
	
};