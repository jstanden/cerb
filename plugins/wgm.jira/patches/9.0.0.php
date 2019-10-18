<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Import profile tab/widget defaults

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab WHERE context = 'cerberusweb.contexts.jira.project'");

if(!$result) {
	$sqls = <<< EOD
# Jira Project
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.jira.project','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Jira Project',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.jira.project\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"is_sync\",\"last_synced_at\",\"url\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Project Issues',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.jira.issue\",\"query_required\":\"project:(id:{{record_id}})\",\"query\":\"sort:-updated subtotal:status\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"j_jira_key\",\"j_project_id\",\"j_jira_versions\",\"j_jira_type_id\",\"j_jira_status_id\",\"j_updated\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.jira.project',CONCAT('[',@last_tab_id,']'));

# Jira Issue
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.jira.issue','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Jira Issue',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.jira.issue\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"jira_key\",\"jira_status_id\",\"jira_versions\",\"jira_type_id\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Jira Project',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.jira.project\",\"context_id\":\"{{record_project_id}}\",\"properties\":[[\"name\",\"is_sync\",\"last_synced_at\",\"url\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.jira.issue\"],\"label_singular\":[\"Issue\"],\"label_plural\":[\"Issues\"],\"query\":[\"project:(id:{{record_project_id}})\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Description',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"{{record_description|escape}}\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recent activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.jira_issue:(id:{{record_id}})\",\"query\":\"sort:-created\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.jira.issue\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',3,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.jira.issue',CONCAT('[',@last_tab_id,']'));
EOD;
	
	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Migrate `jira_issue.project_id` to `jira_issue.jira_project_id`

if(!isset($tables['jira_issue'])) {
	$logger->error("The 'jira_issue' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('jira_issue');

if(!isset($columns['jira_project_id'])) {
	$db->ExecuteMaster('ALTER TABLE jira_issue ADD COLUMN jira_project_id INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX (jira_project_id)');
	$db->ExecuteMaster('UPDATE jira_issue SET jira_project_id = project_id');
	$db->ExecuteMaster('UPDATE jira_issue ji INNER JOIN jira_project jp ON (ji.jira_project_id=jp.jira_id) SET ji.project_id = jp.id');
}

// ===========================================================================
// Drop `jira_issue_to_version`

if(isset($tables['jira_issue_to_version'])) {
	$db->ExecuteMaster("DROP TABLE jira_issue_to_version");
	unset($tables['jira_issue_to_version']);
}

// ===========================================================================
// Drop `jira_issue_description`

if(isset($tables['jira_issue_description'])) {
	$db->ExecuteMaster('ALTER TABLE jira_issue ADD COLUMN description TEXT');
	$db->ExecuteMaster('UPDATE jira_issue ji INNER JOIN jira_issue_description jid ON (ji.jira_id=jid.jira_issue_id) SET ji.description=jid.description');
	
	$db->ExecuteMaster("DROP TABLE jira_issue_description");
	unset($tables['jira_issue_description']);
}

// ===========================================================================
// Add `jira_issue_comment.id`

if(!isset($tables['jira_issue_comment'])) {
	$logger->error("The 'jira_issue_comment' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('jira_issue_comment');

if(!isset($columns['id'])) {
	$db->ExecuteMaster('ALTER TABLE jira_issue_comment DROP PRIMARY KEY, ADD COLUMN id INT UNSIGNED AUTO_INCREMENT FIRST, ADD PRIMARY KEY (id)');
}

if(!isset($columns['issue_id'])) {
	$db->ExecuteMaster('ALTER TABLE jira_issue_comment ADD COLUMN issue_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER id, ADD INDEX (issue_id)');
	$db->ExecuteMaster('UPDATE jira_issue_comment jic INNER JOIN jira_issue ji ON (jic.jira_issue_id=ji.jira_id) SET jic.issue_id=ji.id');
}

// ===========================================================================
// Add connected accounts to JIRA Project records

if(!isset($tables['jira_project'])) {
	$logger->error("The 'jira_project' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('jira_project');

if(!isset($columns['connected_account_id'])) {
	$db->ExecuteMaster("ALTER TABLE jira_project ADD COLUMN connected_account_id INT UNSIGNED NOT NULL DEFAULT 0");
	
	// Migrate sync setting to JIRA Project records
	
	if(false !== ($sync_account_id = $db->GetOneMaster("SELECT value FROM devblocks_setting WHERE plugin_id = 'wgm.jira' AND setting = 'sync_account_id'"))) {
		$sql = sprintf("UPDATE jira_project SET connected_account_id = %d WHERE is_sync = 1",
			$sync_account_id
		);
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.jira' AND setting = 'sync_account_id'");
	}
}

if(isset($columns['is_sync'])) {
	$db->ExecuteMaster("ALTER TABLE jira_project DROP COLUMN is_sync");
}

if(!isset($columns['updated_at'])) {
	$db->ExecuteMaster("ALTER TABLE jira_project ADD COLUMN updated_at int unsigned not null default 0");
	$db->ExecuteMaster(sprintf("UPDATE jira_project SET updated_at = %d", time()));
}

// ===========================================================================
// Finish

return TRUE;