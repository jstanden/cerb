<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.webapi.credential'");

if(!$result) {
	// Web Api
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.webapi.credential','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('API Credentials',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.webapi.credential\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"access_key\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":[]}','sidebar',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.webapi.credential\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.webapi.credential',CONCAT('[',@last_tab_id,']'))");
}

return TRUE;