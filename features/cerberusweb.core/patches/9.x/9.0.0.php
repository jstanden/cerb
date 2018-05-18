<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `profile_tab`

if(!isset($tables['profile_tab'])) {
	$sql = sprintf("
	CREATE TABLE `profile_tab` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		context VARCHAR(255) NOT NULL DEFAULT '',
		extension_id VARCHAR(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index (context),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_tab'] = 'profile_tab';
}

// ===========================================================================
// Add `profile_widget`

if(!isset($tables['profile_widget'])) {
	$sql = sprintf("
	CREATE TABLE `profile_widget` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		profile_tab_id int(10) unsigned NOT NULL DEFAULT 0,
		extension_id varchar(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		pos tinyint unsigned NOT NULL DEFAULT 0,
		width_units tinyint unsigned NOT NULL DEFAULT 1,
		updated_at int(10) unsigned NOT NULL DEFAULT 0,
		primary key (id),
		index (profile_tab_id),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_widget'] = 'profile_widget';
}

// ===========================================================================
// Drop `view_filters_preset` (this is now `context_saved_search`)

if(isset($tables['view_filters_preset'])) {
	$db->ExecuteMaster('DROP TABLE view_filters_preset');
	unset($tables['view_filters_preset']);
}

// ===========================================================================
// Remove old worker prefs

$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mail_display_inline_log'");

// ===========================================================================
// Finish up

return TRUE;
