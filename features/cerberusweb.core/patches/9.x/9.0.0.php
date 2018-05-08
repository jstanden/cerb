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
// Finish up

return TRUE;
