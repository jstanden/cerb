<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.opportunity'");

if(!$result) {
	$sqls = <<< EOD
# Opportunity
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.opportunity','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Status',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div style=\\\"text-align:center;\\\">\\r\\n\\t{% if record_status == \'closed_won\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(102,172,87);font-weight:bold;\\\">\\r\\n\\t\\t{{\'crm.opp.status.closed.won\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% elseif record_status == \'closed_lost\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(211,53,43);font-weight:bold;\\\">\\r\\n\\t\\t{{\'crm.opp.status.closed.lost\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% else %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\\\">\\r\\n\\t\\t{{\'crm.opp.status.open\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% endif %}\\r\\n<\\/div>\"}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Opportunity',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.opportunity\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"currency_amount\",\"cf_178\",\"closed_date\",\"created_date\",\"updated_date\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.opp:(id:{{record_id}})\",\"query\":\"sort:-created\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.opportunity\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.opportunity',CONCAT('[',@last_tab_id,']'));
EOD;
	
	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

return TRUE;