<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerb.ai/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class ChReportWorkerHistory extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::services()->database();
		$tpl = DevblocksPlatform::services()->template();
		$date = DevblocksPlatform::services()->date();
		
		// Use the worker's timezone for MySQL date functions
		$db->ExecuteSlave(sprintf("SET time_zone = %s", $db->qstr($date->formatTime('P', time()))));
		
		// Filters
		
		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		
		@$filter_group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
		$tpl->assign('filter_group_ids', $filter_group_ids);
		
		@$filter_status = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['status'],'array',array()), 'int');
		$tpl->assign('filter_status', $filter_status);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year ORDER BY year desc limit 0,10";
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysqli_free_result($rs);
		
		// Dates
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		if (empty($start) && empty($end)) {
			$start = "-7 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
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
				$date_group = '%Y-%m-%d %H';
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
		$defaults->id = 'report_worker_history';
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->is_ephemeral = true;
			$view->removeAllParams();

			$view->view_columns = array(
				SearchFields_Message::TICKET_GROUP_ID,
				SearchFields_Message::CREATED_DATE,
				SearchFields_Message::WORKER_ID,
				SearchFields_Message::RESPONSE_TIME,
			);
			
			$params = array(
				new DevblocksSearchCriteria(SearchFields_Message::CREATED_DATE,DevblocksSearchCriteria::OPER_BETWEEN, array($start_time, $end_time)),
				new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING,DevblocksSearchCriteria::OPER_EQ, 1),
				new DevblocksSearchCriteria(SearchFields_Message::IS_BROADCAST,DevblocksSearchCriteria::OPER_EQ, 0),
			);
			
			if(!empty($filter_worker_ids)) {
				$params[] = new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID,DevblocksSearchCriteria::OPER_IN, $filter_worker_ids);
			} else {
				$params[] = new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID,DevblocksSearchCriteria::OPER_NEQ, 0);
			}
			
			if(!empty($filter_group_ids)) {
				$params[] = new DevblocksSearchCriteria(
					SearchFields_Message::VIRTUAL_TICKET_SEARCH,
					DevblocksSearchCriteria::OPER_CUSTOM,
					sprintf('group:(id:[%s])',
						implode(',', DevblocksPlatform::sanitizeArray($filter_group_ids, 'int'))
					)
				);
			}
			
			if(!empty($filter_status)) {
				$statuses = [
					0 => 'o', 
					1 => 'w', 
					2 => 'c', 
					3 => 'd', 
				];
				
				$filter_status = array_intersect_key($statuses, array_flip($filter_status));
				
				if($filter_status) {
					$params[] = new DevblocksSearchCriteria(
						SearchFields_Message::VIRTUAL_TICKET_SEARCH,
						DevblocksSearchCriteria::OPER_CUSTOM,
						sprintf('status:[%s]',
							implode(',', $filter_status)
						)
					);
				}
			}
			
			$view->addParamsRequired($params, true);
			
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Message::CREATED_DATE;
			$view->renderSortAsc = false;
			
			$tpl->assign('view', $view);
		}
		
		// Chart
		
		$query_parts = DAO_Message::getSearchQueryComponents($view->view_columns, $view->getParams());
		
		$sql = sprintf("SELECT m.worker_id, DATE_FORMAT(FROM_UNIXTIME(m.created_date),'%s') AS date_plot, ".
			"count(*) AS hits ".
			"%s ".
			"%s ".
			"GROUP BY m.worker_id, date_plot ",
			$date_group,
			$query_parts['join'],
			$query_parts['where']
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$data = array();
		while($row = mysqli_fetch_assoc($rs)) {
			$worker_id = intval($row['worker_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($workers[$worker_id]))
				continue;
			
			if(!isset($data[$worker_id]))
				$data[$worker_id] = $ticks;
			
			$data[$worker_id][$date_plot] = intval($row['hits']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysqli_free_result($rs);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/worker/worker_history/index.tpl');
	}
};