<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add a 'params_json' field to the 'workspace_tab' table for arbitrary tab config params

if(!isset($tables['workspace_tab'])) {
	$logger->error("The 'workspace_tab' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_tab');

if(!isset($columns['params_json'])) {
	$db->Execute("ALTER TABLE workspace_tab ADD COLUMN params_json TEXT");
}

// ===========================================================================
// Add a 'created_at' field to the 'task' table

if(!isset($tables['task'])) {
	$logger->error("The 'task' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('task');

if(!isset($columns['created_at'])) {
	$db->Execute("ALTER TABLE task ADD COLUMN created_at INT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE task SET created_at = updated_date WHERE created_at = 0");
}

// ===========================================================================
// Add an aggregate 'hits' field to the 'snippet' table

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['total_uses'])) {
	$db->Execute("ALTER TABLE snippet ADD COLUMN total_uses INT UNSIGNED NOT NULL DEFAULT 0");
	
	if(isset($tables['snippet_usage'])) {
		$db->Execute("UPDATE snippet SET total_uses = (SELECT IFNULL(SUM(hits),0) FROM snippet_usage WHERE snippet_id=snippet.id)");
	}
}

// ===========================================================================
// Add a new 'snippet_use_history' table for time-based reports

if(!isset($tables['snippet_use_history'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS snippet_use_history (
			snippet_id INT UNSIGNED NOT NULL DEFAULT 0,
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			ts_day INT UNSIGNED NOT NULL DEFAULT 0,
			uses INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (snippet_id, worker_id, ts_day)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['snippet_use_history'] = 'snippet_use_history';
	
	// Copy over the previous per-worker stats
	if(isset($tables['snippet_usage'])) {
		$db->Execute("INSERT INTO snippet_use_history SELECT snippet_id, worker_id, 0 AS ts_day, hits FROM snippet_usage");
	}
}

if(isset($tables['snippet_usage'])) {
	$db->Execute("DROP TABLE snippet_usage");
	unset($tables['snippet_usage']);
}

// ===========================================================================
// Add a new 'trigger_event_history' table for time-based reports

if(!isset($tables['trigger_event_history'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS trigger_event_history (
			trigger_id INT UNSIGNED NOT NULL DEFAULT 0,
			ts_day INT UNSIGNED NOT NULL DEFAULT 0,
			uses INT UNSIGNED NOT NULL DEFAULT 0,
			elapsed_ms INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (trigger_id, ts_day)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['trigger_event_history'] = 'trigger_event_history';
}

// ===========================================================================
// Improve `attachment_link` efficiency for GUID column

if(!isset($tables['attachment_link'])) {
	$logger->error("The 'attachment_link' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('attachment_link');

if(!isset($columns['guid'])) {
	$logger->error("The 'attachment_link.guid' column does not exist.");
	return FALSE;
}

if($columns['guid']['type'] == 'varchar(64)') {
	$db->Execute("ALTER TABLE attachment_link DROP INDEX guid, MODIFY COLUMN guid char(36) NOT NULL DEFAULT '', ADD INDEX guid (guid(3))");;
}

// ===========================================================================
// Modify VA behaviors to support setting cross-record custom field values

if(!isset($tables['decision_node'])) {
	$logger->error("The 'decision_node' table does not exist.");
	return FALSE;
}

$rs = $db->Execute("SELECT decision_node.id, decision_node.params_json, trigger_event.event_point FROM decision_node INNER JOIN trigger_event ON (trigger_event.id=decision_node.trigger_id) WHERE decision_node.node_type = 'action'");

while($row = mysql_fetch_assoc($rs)) {
	$event_point = $row['event_point'];
	$json = $row['params_json'];
	$params = json_decode($json, true);
	$is_changed = false;
	
	if(!isset($params['actions']))
		continue;
	
	foreach($params['actions'] as $idx => $action_params) {
		if(!isset($action_params['action']))
			continue;
		
		if(preg_match('#set_cf_([0-9]+)#', $action_params['action'], $matches)) {
			if(!isset($matches[1]) || empty($matches[1]))
				continue;
			
			switch($event_point) {
				// Address
				case 'event.macro.address':
					$params['actions'][$idx]['action'] = sprintf("set_cf_email_custom_%d", $matches[1]);
					$is_changed = true;
					break;
				
				// Calendar Event
				case 'event.macro.calendar_event':
					$params['actions'][$idx]['action'] = sprintf("set_cf_event_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Call
				case 'event.macro.call':
					$params['actions'][$idx]['action'] = sprintf("set_cf_call_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Comment
				case 'event.comment.created.worker':
					$params['actions'][$idx]['action'] = sprintf("set_cf_comment_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Domain
				case 'event.macro.domain':
					$params['actions'][$idx]['action'] = sprintf("set_cf_domain_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Feed Item
				case 'event.macro.feeditem':
					$params['actions'][$idx]['action'] = sprintf("set_cf_item_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Group
				case 'event.macro.group':
					$params['actions'][$idx]['action'] = sprintf("set_cf_group_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// KB Article
				case 'event.macro.kb_article':
					$params['actions'][$idx]['action'] = sprintf("set_cf_article_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Message
				case 'event.mail.after.sent.group':
				case 'event.mail.received.group':
				case 'event.mail.reply.pre.ui.worker':
					$params['actions'][$idx]['action'] = sprintf("set_cf_ticket_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Opp
				case 'event.macro.crm.opportunity':
					$params['actions'][$idx]['action'] = sprintf("set_cf_opp_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Org
				case 'event.macro.org':
					$params['actions'][$idx]['action'] = sprintf("set_cf_org_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Sensor
				case 'event.macro.sensor':
					$params['actions'][$idx]['action'] = sprintf("set_cf_sensor_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Server
				case 'event.macro.server':
					$params['actions'][$idx]['action'] = sprintf("set_cf_server_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Task
				case 'event.macro.task':
				case 'event.task.created.worker':
					$params['actions'][$idx]['action'] = sprintf("set_cf_task_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Ticket
				case 'event.macro.ticket':
				case 'event.comment.ticket.group':
				case 'event.mail.assigned.group':
				case 'event.mail.closed.group':
				case 'event.mail.moved.group':
				case 'event.mail.sent.group':
				case 'event.ticket.viewed.worker':
					$params['actions'][$idx]['action'] = sprintf("set_cf_ticket_custom_%d", $matches[1]);
					$is_changed = true;
					break;

				// Time Tracking
				case 'event.macro.timetracking':
					$params['actions'][$idx]['action'] = sprintf("set_cf_time_custom_%d", $matches[1]);
					$is_changed = true;
					break;
					
				// Worker
				case 'event.macro.worker':
					$params['actions'][$idx]['action'] = sprintf("set_cf_worker_custom_%d", $matches[1]);
					$is_changed = true;
					break;
			}
		}
	}
	
	if($is_changed) {
		$db->Execute(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$row['id']
		));
	}
}

// ===========================================================================
// Finish

return TRUE;