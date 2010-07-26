<?php
if (class_exists('Extension_Report',true)):
class ChReportTimeSpentOrg extends Extension_Report {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();

		@$filter_worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		$tpl->assign('filter_worker_ids', $filter_worker_ids);
		
		@$filter_org_ids = DevblocksPlatform::importGPC($_REQUEST['org_id'],'array',array());
		if(!empty($filter_org_ids)) {
			$tpl->assign('orgs', DAO_ContactOrg::getWhere(sprintf("%s IN (%s)", DAO_ContactOrg::ID, implode(',', $filter_org_ids))));
			$tpl->assign('filter_org_ids', $filter_org_ids);
		}
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Dates
		
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
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
		
		$ticks = array();
		
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
			case 'day':
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
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
			} elseif($range_days > 1) {
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
			} else {
				$date_group = '%Y-%m-%d %H';
				$date_increment = 'hour';
			}
		}
		
		$tpl->assign('report_date_grouping', $date_increment);	
				
		// Find unique values
		$time = strtotime(sprintf("-1 %s", $date_increment), $start_time);
		while($time < $end_time) {
			$time = strtotime(sprintf("+1 %s", $date_increment), $time);
			if($time <= $end_time)
				$ticks[strftime($date_group, $time)] = 0;
		}
		
		// Table
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'report_timetracking_org';
		$defaults->class_name = 'View_TimeTracking';
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->is_ephemeral = true;
			$view->paramsDefault = array();
			$view->removeAllParams();

			$view->addParam(new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN, array($start_time, $end_time)));
			
			if(!empty($filter_worker_ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::WORKER_ID,DevblocksSearchCriteria::OPER_IN, $filter_worker_ids));
			} else {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::WORKER_ID,DevblocksSearchCriteria::OPER_NEQ, 0));
			}

			if(!empty($filter_org_ids)) {
				$view->addParam(array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::CONTEXT_LINK,DevblocksSearchCriteria::OPER_EQ, CerberusContexts::CONTEXT_ORG),
					new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::CONTEXT_LINK_ID,DevblocksSearchCriteria::OPER_IN, $filter_org_ids),
				),'context_orgs');
			}
			
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_TimeTrackingEntry::LOG_DATE;
			$view->renderSortAsc = true;
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		}		

		// Chart
		// [TODO] Limit to top 5-10, or aggregate non-top "Other"
		$sql = sprintf("SELECT contact_org.id AS org_id, DATE_FORMAT(FROM_UNIXTIME(tte.log_date),'%s') as date_plot, ".
			"SUM(tte.time_actual_mins) AS mins, contact_org.name AS org_name ".
			"FROM timetracking_entry tte ".
			"INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.timetracking' AND tte.id = context_link.to_context_id AND context_link.from_context = 'cerberusweb.contexts.org') ". 
			"INNER JOIN contact_org ON (context_link.from_context_id = contact_org.id) ". 
			"WHERE 1 ".
			"AND log_date > %d ".
			"AND log_date <= %d ".
			"%s ".
			"%s ".
			"GROUP BY contact_org.id, date_plot ".
			"ORDER BY contact_org.name asc",
			$date_group,
			$start_time,
			$end_time,
			(is_array($filter_worker_ids) && !empty($filter_worker_ids) ? sprintf("AND tte.worker_id IN (%s)", implode(',', $filter_worker_ids)) : ""),
			(is_array($filter_org_ids) && !empty($filter_org_ids) ? sprintf("AND context_link.to_context_id IN (%s)", implode(',', $filter_org_ids)) : "")
		);
		$rs = $db->Execute($sql);
		
		$data = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$org_id = intval($row['org_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$org_id]))
				$data[$org_id] = $ticks;
				
			$data[$org_id][$date_plot] = intval($row['mins']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.timetracking::reports/time_spent_org/index.tpl');
	}
};
endif;

