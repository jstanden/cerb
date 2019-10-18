<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.feed'");

if(!$result) {
	$sqls = <<< EOD
# Feed
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.feed','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Feed',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.feed\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"url\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Items',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.feed.item\",\"query_required\":\"feed.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"fi_url\",\"fi_created_date\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.feed',CONCAT('[',@last_tab_id,']'));

# Feed Item
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.feed.item','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Feed Item',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.feed.item\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"created_date\",\"is_closed\",\"url\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Feed',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.feed\",\"context_id\":\"{{record_feed_id}}\",\"properties\":[[\"name\",\"url\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.feed.item\"],\"label_singular\":[\"Item\"],\"label_plural\":[\"Items\"],\"query\":[\"feed.id:{{record_feed_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Content',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<iframe sandbox=\\\"allow-scripts\\\" src=\\\"{{record_url}}\\\" style=\\\"border:1px solid lightgray;;width:100%;height:500px;\\\"><\\/iframe>\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.feed.item',CONCAT('[',@last_tab_id,']'));
EOD;

	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

return TRUE;