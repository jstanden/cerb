<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

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
// Change 'session_data' to MEDIUMTEXT from TEXT

if(isset($tables['bot_session'])) {
	list($columns, $indexes) = $db->metaTable('bot_session');
	
	if(isset($columns['session_data'])) {
		if(0 != strcasecmp('mediumtext',$columns['session_data']['type'])) {
			$db->ExecuteMaster("ALTER TABLE bot_session CHANGE COLUMN `session_data` `session_data` MEDIUMTEXT");
			
			$db->ExecuteMaster(sprintf("UPDATE trigger_event SET event_point = %s WHERE event_point = %s",
				$db->qstr('event.message.chat.worker'),
				$db->qstr('event.interaction.chat.worker')
			));
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
// Verify 'address_to_worker' table

if(isset($tables['address_to_worker'])) {
	list($columns, $indexes) = $db->metaTable('address_to_worker');
	
	if(isset($columns['address'])) {
		$db->ExecuteMaster("DELETE FROM address_to_worker WHERE address_id = 0");
		$db->ExecuteMaster("ALTER TABLE address_to_worker DROP COLUMN address");
		$db->ExecuteMaster("ALTER TABLE address_to_worker ADD PRIMARY KEY (address_id)");
	}
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
// Increase the session_id field size on `community_session`

if(!isset($tables['community_session'])) {
	$logger->error("The 'community_session' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('community_session');

if(isset($columns['session_id'])) {
	if(0 != strcasecmp('varchar(64)',$columns['session_id']['type'])) {
		$db->ExecuteMaster("ALTER TABLE community_session CHANGE COLUMN `session_id` `session_id` varchar(64)");
	}
}

// ===========================================================================
// Remove ticket recommendations

if(isset($tables['context_recommendation'])) {
	$db->ExecuteMaster("DROP TABLE context_recommendation");
	unset($tables['context_recommendation']);
	
	$db->ExecuteMaster("DELETE FROM context_activity_log WHERE activity_point IN ('record.recommendation.removed','record.recommendation.added')");
}

// ===========================================================================
// Finish up

return TRUE;
