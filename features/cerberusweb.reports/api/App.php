<?php
abstract class Extension_Report extends DevblocksExtension {
	function __construct($manifest) {
		parent::DevblocksExtension($manifest);
	}
	
	function render() {
		// Overload 
	}
};

abstract class Extension_ReportGroup extends DevblocksExtension {
	function __construct($manifest) {
		parent::DevblocksExtension($manifest);
	}
};

class ChReportGroupTickets extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupWorkers extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupGroups extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupCustomFields extends Extension_ReportGroup {
	const ID = 'report.group.custom_fields';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupOrgs extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportGroupSpam extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class ChReportCustomFieldUsage extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		// Custom Field sources (tickets, orgs, etc.)
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		uasort($source_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('source_manifests', $source_manifests);

		// Custom Fields
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Table + Chart
		@$field_id = DevblocksPlatform::importGPC($_REQUEST['field_id'],'integer',0);
		$tpl->assign('field_id', $field_id);
		
		if(!empty($field_id) && isset($custom_fields[$field_id])) {
			$field = $custom_fields[$field_id];
			$tpl->assign('field', $field);
		
			// Table
			
			$value_counts = self::_getValueCounts($field_id);
			$tpl->assign('value_counts', $value_counts);

			// Chart
			
			$data = array();
			$iter = 0;
			if(is_array($value_counts))
			foreach($value_counts as $value=>$hits) {
				$data[$iter++] = array('value'=>$value,'hits'=>$hits);
			}
			$tpl->assign('data', $data);
		}
		
		$tpl->display('file:' . $this->tpl_path . '/reports/custom_fields/usage/index.tpl');
	}
	
	private function _getValueCounts($field_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Selected custom field
		if(null == ($field = DAO_CustomField::get($field_id)))
			return;

		if(null == ($table = DAO_CustomFieldValue::getValueTableName($field_id)))
			return;
			
		$sql = sprintf("SELECT field_value, count(field_value) AS hits ".
			"FROM %s ".
			"WHERE source_extension = %s ".
			"AND field_id = %d ".
			"GROUP BY field_value",
			$table,
			$db->qstr($field->source_extension),
			$field->id
		);
		$rs = $db->Execute($sql);
	
		$value_counts = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$value = $row['field_value'];
			$hits = intval($row['hits']);

			switch($field->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 'Yes' : 'No';
					break;
				case Model_CustomField::TYPE_DATE:
					$value = gmdate("Y-m-d H:i:s", $value);
					break;
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_Worker::getAll();
					$value = (isset($workers[$value])) ? $workers[$value]->getName() : $value;
					break;
			}
			
			$value_counts[$value] = intval($hits);
		}
		
		mysql_free_result($rs);
		
		arsort($value_counts);
		return $value_counts;
	}
};

class ChReportGroupRoster extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$rosters = DAO_Group::getRosters();
		$tpl->assign('rosters', $rosters);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/group/group_roster/index.tpl');
	}
};

class ChReportNewTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
		$db = DevblocksPlatform::getDatabaseService();

		// Start + End
		@$start_time = strtotime($start);
		@$end_time = strtotime($end);
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// DAO
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);

		// Chart
		$sql = sprintf("SELECT team.id as group_id, ".
				"count(*) as hits ".
				"FROM ticket t inner join team on t.team_id = team.id ".
				"WHERE t.created_date > %d ".
				"AND t.created_date <= %d ".
				"AND t.is_deleted = 0 ".
				"AND t.spam_score < 0.9000 ".
				"AND t.spam_training != 'S' ".
				"GROUP BY group_id ORDER by team.name desc ",
				$start_time,
				$end_time
				);
		$rs = $db->Execute($sql);
		
		$data = array();
		$iter = 0;
		while($row = mysql_fetch_assoc($rs)) {
			$data[$iter++] = array('group_id'=>$row['group_id'],'hits'=>$row['hits']);
		}
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);

		// Table
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d ".
			"AND is_deleted = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"GROUP BY team_id, category_id ",
			$start_time,
			$end_time
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
		
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/new_tickets/index.tpl');
	}
}

class ChReportWorkerReplies extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('path', $this->tpl_path);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Years
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM message WHERE created_date > 0 AND is_outgoing = 1 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string', '30d');
		
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
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));		
		
		// Table
		
		$sql = sprintf("SELECT count(*) AS hits, t.team_id, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY t.team_id, m.worker_id ",
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
		
		$worker_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$team_id = intval($row['team_id']);
			$worker_id = intval($row['worker_id']);
			
			if(!isset($worker_counts[$worker_id]))
				$worker_counts[$worker_id] = array();
			
			$worker_counts[$worker_id][$team_id] = $hits;
			@$worker_counts[$worker_id]['total'] = intval($worker_counts[$worker_id]['total']) + $hits;
		}
		$tpl->assign('worker_counts', $worker_counts);
		
		mysql_free_result($rs);
		
		// Chart
		
		$sql = sprintf("SELECT count(*) AS hits, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN worker w ON w.id=m.worker_id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id ORDER BY w.last_name DESC ",
			$start_time,
			$end_time
		);

		$rs = $db->Execute($sql);
		
		$worker_counts = array();
		
		$iter = 0;
		$data = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$worker_id = intval($row['worker_id']);
			
			if(isset($workers[$worker_id]))
				$data[$iter++] = array('value'=>$workers[$worker_id]->getName(),'hits'=>$hits);
		}
		$tpl->assign('data', $data);

		mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/worker/worker_replies/index.tpl');
	}

};

class ChReportOrgSharedEmailDomains extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(DISTINCT a.contact_org_id) AS num_orgs, substring(a.email,locate('@',a.email)+1) AS domain ".
			"FROM address a ".
			"INNER JOIN contact_org o ON (a.contact_org_id=o.id) ".
			"WHERE a.contact_org_id != 0 ".
			"GROUP BY domain ".
			"HAVING num_orgs > 1 ".
			"ORDER BY num_orgs desc ".
			"LIMIT 0,100"
		);
		$rs = $db->Execute($sql);
		
		$top_domains = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_domains[$row['domain']] = intval($row['num_orgs']);
		}
		$tpl->assign('top_domains', $top_domains);
		
		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/org/shared_email_domains/index.tpl');
	}
};

class ChReportSpamWords extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT spam, nonspam FROM bayes_stats";
		if(null != ($row = $db->GetRow($sql))) {
			$num_spam = $row['spam'];
			$num_nonspam = $row['nonspam'];
		}
		
		$tpl->assign('num_spam', intval($num_spam));
		$tpl->assign('num_nonspam', intval($num_nonspam));
		
		$top_spam_words = array();
		$top_nonspam_words = array();
		
		$sql = "SELECT word,spam,nonspam FROM bayes_words ORDER BY spam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_words[$row['word']] = array($row['spam'], $row['nonspam']);
		}
		$tpl->assign('top_spam_words', $top_spam_words);
		
		mysql_free_result($rs);
		
		$sql = "SELECT word,spam,nonspam FROM bayes_words ORDER BY nonspam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_words[$row['word']] = array($row['spam'], $row['nonspam']);
		}
		$tpl->assign('top_nonspam_words', $top_nonspam_words);
		
		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/spam/spam_words/index.tpl');
	}
};

class ChReportSpamAddys extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$top_spam_addys = array();
		$top_nonspam_addys = array();
		
		$sql = "SELECT email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_spam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_addys[$row['email']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_spam_addys', $top_spam_addys);
		
		mysql_free_result($rs);
		
		$sql = "SELECT email,num_spam,num_nonspam,is_banned FROM address WHERE num_spam+num_nonspam > 0 ORDER BY num_nonspam desc LIMIT 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_addys[$row['email']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_nonspam_addys', $top_nonspam_addys);
		
		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/spam/spam_addys/index.tpl');
	}
};

class ChReportSpamDomains extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$top_spam_domains = array();
		$top_nonspam_domains = array();
		
		$sql = "select count(*) as hits, substring(email,locate('@',email)+1) as domain, sum(num_spam) as num_spam, sum(num_nonspam) as num_nonspam from address where num_spam+num_nonspam > 0 group by domain order by num_spam desc limit 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_spam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_spam_domains', $top_spam_domains);

		mysql_free_result($rs);
		
		$sql = "select count(*) as hits, substring(email,locate('@',email)+1) as domain, sum(num_spam) as num_spam, sum(num_nonspam) as num_nonspam from address where num_spam+num_nonspam > 0 group by domain order by num_nonspam desc limit 0,100";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$top_nonspam_domains[$row['domain']] = array($row['num_spam'], $row['num_nonspam'], $row['is_banned']);
		}
		$tpl->assign('top_nonspam_domains', $top_nonspam_domains);
		
		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/spam/spam_domains/index.tpl');
	}
};

class ChReportAverageResponseTime extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);

		// init
		$db = DevblocksPlatform::getDatabaseService();

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start_time = strtotime("-30 days");
			$end_time = strtotime("now");
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
		
		// set up necessary reference arrays
	   	$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
	   	$workers = DAO_Worker::getAll();
	   	$tpl->assign('workers',$workers);
		
	   	// pull data from db
	   	$sql = sprintf("SELECT mm.id, mm.ticket_id, mm.created_date, mm.worker_id, mm.is_outgoing, t.team_id, t.category_id ".
			"FROM message m ".
	   		"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	   		"INNER JOIN message mm ON (mm.ticket_id=t.id) ".
			"WHERE m.created_date > %d AND m.created_date <= %d AND m.is_outgoing = 1 ".
	   		"ORDER BY ticket_id,id ",
			$start_time,
			$end_time
		);
		$rs = $db->Execute($sql);
		
		// process and count results
	   	$group_responses = array();
	   	$worker_responses = array();
	   	$prev = array();
		while($row = mysql_fetch_assoc($rs)) {
			// load current data
			$id = intval($row['id']);
			$ticket_id = intval($row['ticket_id']);
			$created_date = intval($row['created_date']);
			$worker_id = intval($row['worker_id']);
			$is_outgoing = intval($row['is_outgoing']);
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);

			if(!empty($worker_id) && !isset($workers[$worker_id]))
				continue;

			if(!empty($team_id) && !isset($groups[$team_id]))
				continue;
				
			// we only add data if it's a worker reply to the same ticket as $prev
			if ($is_outgoing==1 && !empty($prev) && $ticket_id==$prev['ticket_id']) {
				// Initialize, if necessary
				if (!isset($group_responses[$team_id])) $group_responses[$team_id] = array();
				if (!isset($worker_responses[$worker_id])) $worker_responses[$worker_id] = array();
				
				// log reply and time
				@$group_responses[$team_id]['replies'] += 1;
				@$group_responses[$team_id]['time'] += $created_date - $prev['created_date'];
				@$worker_responses[$worker_id]['replies'] += 1;
				@$worker_responses[$worker_id]['time'] += $created_date - $prev['created_date'];
			}
			
			// Save this one as "previous" and move on
			$prev = array(
				'id'=>$id,
				'ticket_id'=>$ticket_id,
				'created_date'=>$created_date,
				'worker_id'=>$worker_id,
				'is_outgoing'=>$is_outgoing,
				'team_id'=>$team_id,
				'category_id'=>$category_id,
				);
		}
		$tpl->assign('group_responses', $group_responses);
		$tpl->assign('worker_responses', $worker_responses);		

		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/worker/average_response_time/index.tpl');
	}
}

class ChReportGroupReplies extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Years
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM message WHERE created_date > 0 AND is_outgoing = 1 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);

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
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
		// Table
		
		$sql = sprintf("SELECT count(*) AS hits, t.team_id, category_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN team ON t.team_id = team.id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.team_id != 0 " .
			"GROUP BY t.team_id, category_id ORDER BY team.name ",
			$start_time,
			$end_time
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
		
		// Chart
		
		$sql = sprintf("SELECT count(*) AS hits, t.team_id as group_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN team on t.team_id = team.id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"AND t.team_id != 0 " .			
			"GROUP BY group_id ORDER BY team.name DESC ",
			$start_time,
			$end_time
		);

		$rs = $db->Execute($sql);
		
		$worker_counts = array();
		
		$iter = 0;
		$data = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$group_id = intval($row['group_id']);
			
			if(!isset($groups[$group_id]))
				continue;

			$data[$iter++] = array('value'=>$groups[$group_id]->name, 'hits'=>$hits);
		}
		$tpl->assign('data', $data);

		mysql_free_result($rs);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/group/group_replies/index.tpl');
	}
	
};

class ChReportOpenTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);

		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// Start + End
		@$start_time = strtotime($start);
		@$end_time = strtotime($end);
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// DAO
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Chart
		$sql = sprintf("SELECT team.id as group_id, ".
			"count(*) as hits ".
			"FROM ticket t inner join team on t.team_id = team.id ".
			"WHERE t.created_date > %d AND t.created_date <= %d ".
			"AND t.is_deleted = 0 ".
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ".
			"AND is_waiting != 1 " .				
			"GROUP BY group_id ORDER by team.name desc ",
			$start_time,
			$end_time
			);
		$rs = $db->Execute($sql);
		
		$data = array();
		$iter = 0;
		while($row = mysql_fetch_assoc($rs)) {
			$data[$iter++] = array('group_id'=>$row['group_id'],'hits'=>$row['hits']);
		}
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);
		
		// Table
		
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE created_date > %d AND created_date <= %d ".			
			"AND is_deleted = 0 ".
			"AND is_closed = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"AND is_waiting != 1 " .
			"GROUP BY team_id, category_id ",
			$start_time,
			$end_time);
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
		
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/open_tickets/index.tpl');
	}
}

class ChReportOldestOpenTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-5 years');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		
		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		
		if (empty($start) && empty($end)) {
			$start = "-5 years";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);

		// Table
		
		$oldest_tickets = array();
		foreach($groups as $group_id=>$group) {
			$sql = sprintf("SELECT mask, subject, created_date ".
				"FROM ticket ".
				"WHERE created_date > %d AND created_date <= %d ".			
				"AND is_deleted = 0 ".
				"AND is_closed = 0 ".
				"AND spam_score < 0.9000 ".
				"AND spam_training != 'S' ".
				"AND is_waiting != 1 " .
				"AND team_id = %d " .
				"ORDER BY created_date LIMIT 10",
				$start_time,
				$end_time,
				$group_id);
			$rs = $db->Execute($sql);
		
			while($row = mysql_fetch_assoc($rs)) {
				$mask = $row['mask'];
				$subject = $row['subject'];
				$created_date = intval($row['created_date']);
				
				if(!isset($oldest_tickets[$group_id]))
					$oldest_tickets[$group_id] = array();
				
				unset($ticket_entry);
				$ticket_entry->mask = $mask;
				$ticket_entry->subject = $subject;
				$ticket_entry->created_date = $created_date;
				
				$oldest_tickets[$group_id][]=$ticket_entry;
			}
			
			mysql_free_result($rs);
		}
		$tpl->assign('oldest_tickets', $oldest_tickets);
				
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/oldest_open_tickets/index.tpl');
	}

}

class ChReportWaitingTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Date
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
		// Table
		
		$sql = "SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE is_deleted = 0 ".
			"AND is_closed = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"AND is_waiting = 1 " .
			"GROUP BY team_id, category_id ";
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
		
		// Chart
		
		$sql = "SELECT team.id as group_id, ".
				"count(*) as hits ".
				"FROM ticket t inner join team on t.team_id = team.id ".
				"WHERE t.is_deleted = 0 ".
				"AND t.is_closed = 0 ".
				"AND t.spam_score < 0.9000 ".
				"AND t.spam_training != 'S' ".
				"AND is_waiting = 1 " .				
				"GROUP BY group_id ORDER by team.name desc ";

		$rs = $db->Execute($sql);

		$iter = 0;
		$data = array();
	    
	    while($row = mysql_fetch_assoc($rs)) {
	    	$hits = intval($row['hits']);
			$group_id = $row['group_id'];
			
			if(!isset($groups[$group_id]))
				continue;
			
			$data[$iter++] = array('value'=>$groups[$group_id]->name, 'hits'=> $hits);
	    }
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/waiting_tickets/index.tpl');
	}
	
}

class ChReportClosedTickets extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 AND is_deleted = 0 AND is_closed = 1 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-30 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
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
		
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
		// Table

		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
			"FROM ticket ".
			"WHERE updated_date > %d AND updated_date <= %d ".
			"AND is_deleted = 0 ".
			"AND is_closed = 1 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"GROUP BY team_id, category_id" ,
			$start_time,
			$end_time);
			
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
				
		// Chart
		
			$sql = sprintf("SELECT team.id as group_id, ".
				"count(*) as hits ".
				"FROM ticket t inner join team on t.team_id = team.id ".
				"WHERE updated_date > %d AND updated_date <= %d ".
				"AND t.is_deleted = 0 ".
				"AND t.is_closed = 1 ".
				"AND t.spam_score < 0.9000 ".
				"AND t.spam_training != 'S' ".
				"GROUP BY group_id ORDER by team.name desc ",
				$start_time,
				$end_time);

		$rs = $db->Execute($sql);

		$iter = 0;
		$data = array();
		
	    while($row = mysql_fetch_assoc($rs)) {
	    	$hits = intval($row['hits']);
			$group_id = $row['group_id'];
			
			if(!isset($groups[$group_id]))
				continue;
			
			$data[$iter++] = array('value'=>$groups[$group_id]->name, 'hits'=>$hits);
	    }		
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/closed_tickets/index.tpl');
	}
}

class ChReportTicketAssignment extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Table
		
		$sql = sprintf("SELECT w.id worker_id, t.id ticket_id, t.mask, t.subject, t.created_date ".
				"FROM ticket t inner join worker w on t.next_worker_id = w.id ".
				"WHERE t.is_deleted = 0 ". 
				"AND t.is_closed = 0 ".
				"AND t.spam_score < 0.9000 ".
				"AND t.spam_training != 'S' ". 
				"AND is_waiting != 1 ".		
				"ORDER by w.last_name");
		$rs = $db->Execute($sql);
	
		$ticket_assignments = array();
		while($row = mysql_fetch_assoc($rs)) {
			$worker_id = intval($row['worker_id']);
			$mask = $row['mask'];
			$subject = $row['subject'];
			$created_date = intval($row['created_date']);
			
			if(!isset($ticket_assignments[$worker_id]))
				$ticket_assignments[$worker_id] = array();
				
			unset($assignment);
			$assignment->mask = $mask;
			$assignment->subject = $subject;
			$assignment->created_date = $created_date; 
				
			$ticket_assignments[$worker_id][] = $assignment;
		}
		
		$tpl->assign('ticket_assignments', $ticket_assignments);

		mysql_free_result($rs);
		
		// Chart
		
		$sql = sprintf("SELECT w.id worker_id ,count(*) as hits ".
				"FROM ticket t inner join worker w on t.next_worker_id = w.id ".
				"WHERE t.is_deleted = 0 ". 
				"AND t.is_closed = 0 ".
				"AND t.spam_score < 0.9000 ".
				"AND t.spam_training != 'S' ". 
				"AND is_waiting != 1 ".	
				"GROUP by w.id ".
				"ORDER by w.last_name");
		$rs = $db->Execute($sql);

		$iter = 0;
		$data = array();
		
		while($row = mysql_fetch_assoc($rs)) {
	    	$hits = intval($row['hits']);
			$worker_id = $row['worker_id'];
			
			if(!isset($workers[$worker_id]))
				continue;
				
			$data[$iter++] = array('value'=>$workers[$worker_id]->getName(),'hits'=>$hits);
	    }
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/worker/ticket_assignment/index.tpl');
	}
};

class ChReportTopTicketsByContact extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);

		// Dates
		
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
		
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		
		// Table
		
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');

		@$by_address = DevblocksPlatform::importGPC($_REQUEST['by_address'],'integer',0);
		$tpl->assign('by_address', $by_address);
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		if($by_address) {
			$sql = sprintf("SELECT count(*) AS hits, a.id as contact_id, a.email as contact_name, t.team_id, t.category_id  ".
					"FROM ticket t  ".
					"INNER JOIN address a ON t.first_wrote_address_id = a.id  ".
					"WHERE created_date > %d AND created_date <= %d  ".
					"AND is_deleted = 0 ".
					"AND spam_score < 0.9000 ".
					"AND spam_training != 'S'  ".
					"AND t.team_id != 0 ".
					"GROUP BY a.email, t.team_id, t.category_id ORDER BY hits DESC ",
					$start_time,
					$end_time);
		}
		else { //default is by org 
			$sql = sprintf("SELECT count(*) AS hits, a.contact_org_id as contact_id, o.name as contact_name, t.team_id, t.category_id ".
					"FROM ticket t ".
					"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
					"INNER JOIN contact_org o ON a.contact_org_id = o.id ".
					"WHERE created_date > %d AND created_date <= %d ".
					"AND is_deleted = 0 ".
					"AND spam_score < 0.9000 ".
					"AND spam_training != 'S' ".
					"AND a.contact_org_id != 0 ".
					"AND t.team_id != 0 ".
					"GROUP BY a.contact_org_id, o.name, t.team_id, t.category_id ".
					"ORDER BY hits DESC  ",
					$start_time,
					$end_time);
		}
				
		$rs = $db->Execute($sql);
	
		$group_counts = array();
		$max_orgs = 100;
		$current_orgs = 0;
		
		while($row = mysql_fetch_assoc($rs) && $current_orgs <= $max_orgs) {
			$org_id = intval($row['contact_id']);
			$org_name = $row['contact_name'];
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);
			$hits = intval($row['hits']);
			
			if(!isset($group_counts[$org_id])) {
				$group_counts[$org_id] = array();
				$current_orgs++;
			}

			if(!isset($group_counts[$org_id]['teams']))
				$group_counts[$org_id]['teams'] = array();
				
			if(!isset($group_counts[$org_id]['teams'][$team_id]))
				$group_counts[$org_id]['teams'][$team_id] = array();
				
			if(!isset($group_counts[$org_id]['teams'][$team_id]['buckets']))
				$group_counts[$org_id]['teams'][$team_id]['buckets'] = array();
			
			$group_counts[$org_id]['name'] = $org_name;
			
			$group_counts[$org_id]['teams'][$team_id]['buckets'][$category_id] = $hits;
			@$group_counts[$org_id]['teams'][$team_id]['total'] = intval($group_counts[$org_id]['teams'][$team_id]['total']) + $hits;
			@$group_counts[$org_id]['total'] = intval($group_counts[$org_id]['total']) + $hits;
		}
		
		mysql_free_result($rs);
		
		uasort($group_counts, array("ChReportTopTicketsByContact", "sortCountsArrayByHits"));
		
		$tpl->assign('group_counts', $group_counts);		
		
		// Chart
		
		if($by_address) {
			$sql = sprintf("SELECT count(*) AS hits, a.id, a.email as name ".
					"FROM ticket t ".
					"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
					"WHERE created_date > %d AND created_date <= %d ".
					"AND is_deleted = 0 ".
					"AND spam_score < 0.9000 ".
					"AND spam_training != 'S' ".
					"AND t.team_id != 0 " .
					"GROUP BY a.id, a.email ".
					"ORDER BY hits DESC LIMIT 25 ",
					$start_time,
					$end_time);
		}
		else {//default is by org
			$sql = sprintf("SELECT count(*) AS hits, a.contact_org_id, o.name ".
					"FROM ticket t ".
					"INNER JOIN address a ON t.first_wrote_address_id = a.id ".
					"INNER JOIN contact_org o ON a.contact_org_id = o.id ".
					"WHERE created_date > %d AND created_date <= %d ".
					"AND is_deleted = 0 ".
					"AND spam_score < 0.9000 ".
					"AND spam_training != 'S' ".
					"AND t.team_id != 0 " .
					"AND a.contact_org_id != 0 ".
					"GROUP BY a.contact_org_id, o.name ".
					"ORDER BY hits DESC LIMIT 25 ",
					$start_time,
					$end_time);
		};
		$rs = $db->Execute($sql);

		$sorted_result = array();
		$i=0;
	    
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$name = $row['name'];
			
			$sorted_result[$i]['name'] = $name;
			$sorted_result[$i]['hits'] = $hits;
			
			$i++;
		}
		
		mysql_free_result($rs);

		//reverse the descending result because yui charts draws from the bottom up on the y-axis
		$iter = 0;
		$data = array();
		
		$reversed = array_reverse($sorted_result);
		foreach($reversed AS $result) {
			$data[$iter++] = array('value'=>$result['name'], 'hits'=>$result['hits']);
		}
		$tpl->assign('data', $data);
		
		$tpl->display('file:' . $this->tpl_path . '/reports/ticket/top_contacts_tickets/index.tpl');
	}
	
	function sortCountsArrayByHits($a, $b) {
		if ($a['total'] == $b['total']) {
			return 0;
		}
		return ($a['total'] < $b['total']) ? 1 : -1;
	}
	
};

class ChReportWorkerHistory extends Extension_Report {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Dates
		
		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		if(!$worker_id) {
			$worker = CerberusApplication::getActiveWorker();
			$worker_id = $worker->id;
		}
		$tpl->assign('worker_id', $worker_id);
		
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}
		
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		// Table
		
		$sql = sprintf("SELECT t.id, t.mask, t.subject, a.email as email, " . 
				"date_format(from_unixtime(m.created_date),'%%Y-%%m-%%d') as day ".
				"FROM ticket t ".
				"INNER JOIN message m ON t.id = m.ticket_id ".
				"INNER JOIN worker w ON m.worker_id = w.id ".
				"INNER JOIN address a on t.first_wrote_address_id = a.id ".
				"WHERE m.created_date > %d AND m.created_date <= %d ".
				"AND m.is_outgoing = 1 ".
				"AND t.is_deleted = 0 ".
				"AND w.id = %d ".
				"GROUP BY day, t.id ".
				"order by m.created_date",
				$start_time,
				$end_time,
				$worker_id);
				
		$rs = $db->Execute($sql);

		$tickets_replied = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$created_day = $row['day'];
			
			unset($reply_date_ticket);
			$reply_date_ticket->mask = $row['mask'];
			$reply_date_ticket->email = $row['email'];
			$reply_date_ticket->subject = $row['subject'];
			$reply_date_ticket->id = intval($row['id']);

			$tickets_replied[$created_day][] = $reply_date_ticket;
		}
		
		mysql_free_result($rs);

		$tpl->assign('tickets_replied', $tickets_replied);		
		
		// Chart
		
		$sql = sprintf("SELECT count(*) AS hits, m.worker_id ".
			"FROM message m ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"INNER JOIN worker w ON w.id=m.worker_id ".
			"WHERE m.created_date > %d AND m.created_date <= %d ".
			"AND m.is_outgoing = 1 ".
			"AND t.is_deleted = 0 ".
			"GROUP BY m.worker_id ORDER BY w.last_name DESC ",
			$start_time,
			$end_time
		);

		$rs = $db->Execute($sql);
		
		$worker_counts = array();
		$data = array();
		$iter = 0;
		
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$worker_id = intval($row['worker_id']);
			
			if(!isset($workers[$worker_id]))
				continue;

			$data[$iter++] = array('value'=>$workers[$worker_id]->getName(),'hits'=>$hits);
		}
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);
		
		// Template
		
		$tpl->display('file:' . $this->tpl_path . '/reports/worker/worker_history/index.tpl');
	}
}

class ChReportsPage extends CerberusPageExtension {
	private $tpl_path = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);

		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}

	function getActivity() {
		return new Model_Activity('reports.activity');
	}
	
	/**
	 * Proxy page actions from an extension's render() to the extension's scope.
	 *
	 */
	function actionAction() {
		@$extid = DevblocksPlatform::importGPC($_REQUEST['extid']);
		@$extid_a = DevblocksPlatform::strAlphaNumDash($_REQUEST['extid_a']);
		
		$action = $extid_a.'Action';
		
		$reportMft = DevblocksPlatform::getExtension($extid);
		
		// If it's a value report extension, proxy the action
		if(null != ($reportInst = DevblocksPlatform::getExtension($extid, true)) 
			&& $reportInst instanceof Extension_Report) {
				
			// If we asked for a value method on the extension, call it
			if(method_exists($reportInst, $action)) {
				call_user_func(array(&$reportInst, $action));
			}
		}
		
		return;
	}
	
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // reports
		@$reportId = array_shift($stack);
		$report = null;

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

		$tpl->display('file:' . $this->tpl_path . '/reports/index.tpl');
	}
		
};

