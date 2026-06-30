<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Create the file_bundle table

if(!array_key_exists('file_bundle', $tables)) {
	$db->ExecuteMaster(
		"CREATE TABLE IF NOT EXISTS file_bundle (".
		"id INT UNSIGNED NOT NULL AUTO_INCREMENT, ".
		"name VARCHAR(255) DEFAULT '', ".
		"tag VARCHAR(128) DEFAULT '', ".
		"updated_at INT UNSIGNED NOT NULL DEFAULT 0, ".
		"owner_context VARCHAR(255) DEFAULT '', ".
		"owner_context_id INT UNSIGNED NOT NULL DEFAULT 0, ".
		"PRIMARY KEY (id)".
		") ENGINE=InnoDB"
	);
}

// ===========================================================================
// Import card widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM card_widget WHERE record_type = 'cerberusweb.contexts.file_bundle'");

if(!$result) {
	$db->ExecuteMaster("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES ('Properties','cerberusweb.contexts.file_bundle','cerb.card.widget.fields','{\"context\":\"cerberusweb.contexts.file_bundle\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"tag\",\"updated\"]],\"links\":{\"show\":1},\"search\":[]}',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),0,4,'content')");
}

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.file_bundle'");

if(!$result) {
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.file_bundle','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('File Bundle',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.file_bundle\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"tag\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.file_bundle',CONCAT('[',@last_tab_id,']'))");
}

return TRUE;
