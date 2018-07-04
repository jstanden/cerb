<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.timetracking'");

if(!$result) {
	// Time Tracking
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.timetracking','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Time Tracking',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.timetracking\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"status\",\"log_date\",\"worker_id\",\"time_spent\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Worker',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.worker\",\"context_id\":\"{{record_worker_id}}\",\"properties\":[[\"name\",\"location\",\"title\",\"timezone\",\"calendar_id\"]]}','sidebar',2,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.time_entry:(id:{{record_id}})\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.timetracking\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.timetracking',CONCAT('[',@last_tab_id,']'))");
}

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.timetracking.activity'");

if(!$result) {
	// Time Tracking Activity
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.timetracking.activity','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.timetracking.activity\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.timetracking.activity\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.timetracking.activity',CONCAT('[',@last_tab_id,']'))");
}

// ===========================================================================
// Drop `rate` and add `updated_at` to `timetracking_activity`

list($columns, $indexes) = $db->metaTable('timetracking_activity');

if(isset($columns['rate'])) {
	$sql = "ALTER TABLE timetracking_activity DROP COLUMN rate";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['updated_at'])) {
	$sql = "ALTER TABLE timetracking_activity ADD COLUMN updated_at INT UNSIGNED NOT NULL DEFAULT 0";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("UPDATE timetracking_activity SET updated_at = UNIX_TIMESTAMP() WHERE updated_at = 0");
}

return TRUE;