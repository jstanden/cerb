<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Remove `is_private` from `trigger_event`

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(isset($columns['is_private'])) {
	$db->ExecuteMaster(sprintf("UPDATE trigger_event SET event_params_json = %s WHERE is_private = 1 AND event_point LIKE 'event.macro.%%'",
		$db->qstr(json_encode(['visibility' => 'bots']))
	));
	$db->ExecuteMaster("ALTER TABLE trigger_event DROP COLUMN is_private");
}

// ===========================================================================
// Add `context_saved_search` table

if(!isset($tables['context_saved_search'])) {
	$sql = sprintf("
	CREATE TABLE `context_saved_search` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		context varchar(255) not null default '',
		tag varchar(128) not null default '',
		owner_context varchar(255) not null default '',
		owner_context_id int unsigned not null default 0,
		`query` text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (context),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['context_saved_search'] = 'context_saved_search';
}

// ===========================================================================
// Change 'setting' values to X (text) rather than B (blob)

if(isset($tables['bot_session'])) {
	list($columns, $indexes) = $db->metaTable('bot_session');
	
	if(isset($columns['session_data'])) {
		if(0 != strcasecmp('mediumtext',$columns['session_data']['type'])) {
			$db->ExecuteMaster("ALTER TABLE bot_session CHANGE COLUMN `session_data` `session_data` MEDIUMTEXT");
		}
	}
}

// ===========================================================================
// Add `bot_datastore` table

if(!isset($tables['bot_datastore'])) {
	$sql = sprintf("
	CREATE TABLE `bot_datastore` (
		bot_id int unsigned NOT NULL,
		data_key varchar(255) NOT NULL DEFAULT '',
		data_value mediumtext,
		expires_at int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (bot_id, data_key)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['bot_datastore'] = 'bot_datastore';
}

// ===========================================================================
// Add 'status_id' to tasks (waiting/reopen)

if(isset($tables['task'])) {
	list($columns, $indexes) = $db->metaTable('task');
	
	if(!isset($columns['status_id'])) {
		$db->ExecuteMaster("ALTER TABLE task ADD COLUMN status_id TINYINT UNSIGNED NOT NULL DEFAULT 0");
		$db->ExecuteMaster("UPDATE task SET status_id = 1 WHERE is_completed = 1");
		
		// Update bot 'set status' actions on task behaviors
		$db->ExecuteMaster('UPDATE decision_node SET params_json = REPLACE(params_json, \'"action":"set_status","status":"active"\', \'"action":"set_status","status_id":0\')');
		$db->ExecuteMaster('UPDATE decision_node SET params_json = REPLACE(params_json, \'"action":"set_status","status":"completed"\', \'"action":"set_status","status_id":1\')');
		
		// Update worker view models (params editable)
		$db->ExecuteMaster('UPDATE worker_view_model SET params_editable_json = REPLACE(params_editable_json, \'{"field":"t_is_completed","operator":"equals or null","value":false}\', \'{"field":"t_status_id","operator":"=","value":0}\') where class_name = \'View_Task\' and params_editable_json like \'%t_is_completed%\'');
		$db->ExecuteMaster('UPDATE worker_view_model SET params_editable_json = REPLACE(params_editable_json, \'{"field":"t_is_completed","operator":"=","value":0}\', \'{"field":"t_status_id","operator":"=","value":0}\') where class_name = \'View_Task\' and params_editable_json like \'%t_is_completed%\'');
		$db->ExecuteMaster('UPDATE worker_view_model SET params_editable_json = REPLACE(params_editable_json, \'{"field":"t_is_completed","operator":"=","value":1}\', \'{"field":"t_status_id","operator":"=","value":1}\') where class_name = \'View_Task\' and params_editable_json like \'%t_is_completed%\'');
		
		// Update worker view models (params default)
		$db->ExecuteMaster('UPDATE worker_view_model SET params_default_json = REPLACE(params_default_json, \'{"field":"t_is_completed","operator":"equals or null","value":false}\', \'{"field":"t_status_id","operator":"=","value":0}\') where class_name = \'View_Task\' and params_default_json like \'%t_is_completed%\'');
		$db->ExecuteMaster('UPDATE worker_view_model SET params_default_json = REPLACE(params_default_json, \'{"field":"t_is_completed","operator":"=","value":0}\', \'{"field":"t_status_id","operator":"=","value":0}\') where class_name = \'View_Task\' and params_default_json like \'%t_is_completed%\'');
		$db->ExecuteMaster('UPDATE worker_view_model SET params_default_json = REPLACE(params_default_json, \'{"field":"t_is_completed","operator":"=","value":1}\', \'{"field":"t_status_id","operator":"=","value":1}\') where class_name = \'View_Task\' and params_default_json like \'%t_is_completed%\'');
	}
	
	if(isset($columns['is_completed'])) {
		$db->ExecuteMaster("ALTER TABLE task DROP COLUMN is_completed");
	}
	
	if(!isset($columns['reopen_at'])) {
		$db->ExecuteMaster("ALTER TABLE task ADD COLUMN reopen_at INT UNSIGNED NOT NULL DEFAULT 0");
	}
}

// ===========================================================================
// Finish up

return TRUE;
