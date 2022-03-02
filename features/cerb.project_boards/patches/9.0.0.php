<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.project.board'");

if(!$result) {
	// Project Board
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.project.board','cerb.profile.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Board',@last_tab_id,'cerb.profile.tab.widget.project_board','{\"context_id\":\"{{record_id}}\"}','content',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.project.board',CONCAT('[',@last_tab_id,']'))");
	
	// Project Board Column
	$db->ExecuteMaster("INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.project.board.column','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP())");
	$db->ExecuteMaster("SET @last_tab_id = LAST_INSERT_ID()");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Project Board Column',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.project.board.column\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]],\"links\":{\"show\":\"1\"}}','',1,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Project Board',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.project.board\",\"context_id\":\"{{record_board_id}}\",\"properties\":[[\"name\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.project.board.column\"],\"label_singular\":[\"Column\"],\"label_plural\":[\"Columns\"],\"query\":[\"board.id:{{record_board_id}}\"]}}','',2,4,UNIX_TIMESTAMP())");
	$db->ExecuteMaster("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.project.board.column',CONCAT('[',@last_tab_id,']'))");
}

// ===========================================================================
// Migrate legacy project board tabs to dashboards

if(!array_key_exists('workspace_tab',$tables))
	return FALSE;

$sql = "SELECT id, params_json FROM workspace_tab WHERE extension_id = 'core.workspace.tab.board'";
$results = $db->GetArrayMaster($sql);

if(is_array($results))
foreach($results as $result) {
	$params = json_decode($result['params_json'], true);
	$board_id = $params['board_id'] ?? null;
	
	// Switch the tab to a dashboard
	$sql = sprintf("UPDATE workspace_tab SET extension_id = 'core.workspace.tab.dashboard', params_json = %s WHERE id = %d",
		$db->qstr('{"layout":""}'),
		$result['id']
	);
	$db->ExecuteMaster($sql);
	
	// Add a calendar widget
	$sql = sprintf("INSERT INTO workspace_widget (workspace_tab_id,extension_id,label,updated_at,params_json,width_units,zone,pos) ".
		"VALUES (%d,%s,%s,%d,%s,%d,%s,%d)",
		$result['id'],
		$db->qstr('cerb.workspace.widget.project_board'),
		$db->qstr('Project Board'),
		time(),
		$db->qstr(sprintf('{"project_board_id":%d}', $board_id)),
		4,
		$db->qstr('content'),
		1
	);
	$db->ExecuteMaster($sql);
}

return TRUE;