<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `project_board` table

if(!isset($tables['project_board'])) {
	$sql = sprintf("
	CREATE TABLE `project_board` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		owner_context VARCHAR(255) DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		columns_json VARCHAR(255),
		params_json TEXT,
		primary key (id),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['project_board'] = 'project_board';
}

// ===========================================================================
// Add `project_board_column` table

if(!isset($tables['project_board_column'])) {
	$sql = sprintf("
	CREATE TABLE `project_board_column` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		board_id INT UNSIGNED NOT NULL DEFAULT 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		params_json TEXT,
		cards_json MEDIUMTEXT,
		primary key (id),
		index board_id (board_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['project_board_column'] = 'project_board_column';
}

// ===========================================================================
// Finish up

return TRUE;
