<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// jira_project

if(!isset($tables['jira_project'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS jira_project (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			jira_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_key VARCHAR(16) DEFAULT '',
			name VARCHAR(255) DEFAULT '',
			url VARCHAR(255) DEFAULT '',
			issuetypes_json MEDIUMTEXT,
			statuses_json MEDIUMTEXT,
			versions_json MEDIUMTEXT,
			is_sync TINYINT UNSIGNED NOT NULL DEFAULT 0,
			last_synced_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['jira_project'] = 'jira_project';
}

if(!isset($tables['jira_project'])) {
	$logger->error("The 'jira_project' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('jira_project');

if(isset($columns['issuetypes_json']) && $columns['issuetypes_json']['type'] == 'text') {
	$db->ExecuteMaster("ALTER TABLE jira_project MODIFY COLUMN issuetypes_json MEDIUMTEXT");
}

if(isset($columns['statuses_json']) && $columns['statuses_json']['type'] == 'text') {
	$db->ExecuteMaster("ALTER TABLE jira_project MODIFY COLUMN statuses_json MEDIUMTEXT");
}

if(isset($columns['versions_json']) && $columns['versions_json']['type'] == 'text') {
	$db->ExecuteMaster("ALTER TABLE jira_project MODIFY COLUMN versions_json MEDIUMTEXT");
}

if(!isset($columns['is_sync'])) {
	$db->ExecuteMaster("ALTER TABLE jira_project ADD COLUMN is_sync TINYINT UNSIGNED NOT NULL DEFAULT 0");
	$db->ExecuteMaster("UPDATE jira_project SET is_sync=1");
}

// ===========================================================================
// jira_issue

if(!isset($tables['jira_issue'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS jira_issue (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_key VARCHAR(32) DEFAULT '',
			jira_type_id SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			jira_status_id SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			jira_versions VARCHAR(255) NOT NULL DEFAULT '',
			summary VARCHAR(255) DEFAULT '',
			created INT UNSIGNED NOT NULL DEFAULT 0,
			updated INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX project_id (project_id),
			INDEX jira_id (jira_id),
			INDEX jira_status_id (jira_status_id),
			INDEX updated (updated)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['jira_issue'] = 'jira_issue';
}

if(!isset($tables['jira_issue'])) {
	$logger->error("The 'jira_issue' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('jira_issue');

// Drop the version column

if(isset($columns['jira_version_id'])) {
	$db->ExecuteMaster("ALTER TABLE jira_issue DROP COLUMN jira_version_id");
}

if(!isset($columns['jira_versions'])) {
	$db->ExecuteMaster("ALTER TABLE jira_issue ADD COLUMN jira_versions VARCHAR(255) NOT NULL DEFAULT ''");
}

// ===========================================================================
// jira_issue_to_version

if(!isset($tables['jira_issue_to_version'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS jira_issue_to_version (
			jira_issue_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_version_id INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (jira_issue_id, jira_version_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['jira_issue_to_version'] = 'jira_issue_to_version';
}

// ===========================================================================
// jira_issue_description

if(!isset($tables['jira_issue_description'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS jira_issue_description (
			jira_issue_id INT UNSIGNED NOT NULL DEFAULT 0,
			description TEXT,
			PRIMARY KEY (jira_issue_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['jira_issue_description'] = 'jira_issue_description';
}

// ===========================================================================
// jira_issue_comment

if(!isset($tables['jira_issue_comment'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS jira_issue_comment (
			jira_comment_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_issue_id INT UNSIGNED NOT NULL DEFAULT 0,
			jira_author VARCHAR(255) NOT NULL DEFAULT '',
			created INT UNSIGNED NOT NULL DEFAULT 0,
			body TEXT,
			PRIMARY KEY (jira_comment_id),
			INDEX jira_issue_id (jira_issue_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['jira_issue_comment'] = 'jira_issue_comment';
}

// ===========================================================================
// Enable scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('wgmjira.cron', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 23:45'));
}

return TRUE;