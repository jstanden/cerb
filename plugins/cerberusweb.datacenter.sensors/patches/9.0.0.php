<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.datacenter.sensor'");

if(!$result) {
	$sqls = <<< EOD
# Sensor
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.datacenter.sensor','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Status',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div style=\\\"text-align:center;\\\">\\r\\n\\t{% if record_status == \'Warning\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(240,190,53);font-weight:bold;\\\">\\r\\n\\t\\tWarning\\r\\n\\t<\\/div>\\r\\n\\t{% elseif record_status == \'Critical\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(211,53,43);font-weight:bold;\\\">\\r\\n\\t\\tCritical\\r\\n\\t<\\/div>\\r\\n\\t{% else %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(102,172,87);font-weight:bold;\\\">\\r\\n\\t\\tOK\\r\\n\\t<\\/div>\\r\\n\\t{% endif %}\\r\\n<\\/div>\"}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Sensor',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.datacenter.sensor\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\",\"type\",\"tag\",\"is_disabled\",\"fail_count\",\"metric_type\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recent Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.sensor:(id:{{record_id}})\",\"query\":\"sort:-created subtotal:activity\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.datacenter.sensor\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.datacenter.sensor',CONCAT('[',@last_tab_id,']'));
EOD;

	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

return TRUE;