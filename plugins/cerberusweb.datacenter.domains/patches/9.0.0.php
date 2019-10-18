<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.datacenter.domain'");

if(!$result) {
	// Domain
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.datacenter.domain','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Domain',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.datacenter.domain\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Server',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.datacenter.server\",\"context_id\":\"{{record_server_id}}\",\"properties\":[[\"name\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.datacenter.domain\"],\"label_singular\":[\"Domain\"],\"label_plural\":[\"Domains\"],\"query\":[\"server.id:{{record_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recent activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.domain:(id:{{record_id}})\",\"query\":\"sort:-created subtotal:activity\",\"render_limit\":\"15\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.datacenter.domain\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.datacenter.domain',CONCAT('[',@last_tab_id,']'))");
}

// ===========================================================================
// Fix 'Server' widgets on domain profiles

$sql = "UPDATE profile_widget SET extension_params_json = REPLACE(extension_params_json,'{{record_id}}','{{record_server_id}}') WHERE name = 'Server' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.datacenter.domain' AND name = 'Overview')";
$db->ExecuteMaster($sql);

return TRUE;