<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `package_library`

if(!isset($tables['package_library'])) {
	$sql = sprintf("
		CREATE TABLE `package_library` (
		`id` INT unsigned NOT NULL AUTO_INCREMENT,
		`uri` VARCHAR(255) NOT NULL DEFAULT '',
		`name` VARCHAR(255) NOT NULL DEFAULT '',
		`description` VARCHAR(255) NOT NULL DEFAULT '',
		`point` VARCHAR(255) NOT NULL DEFAULT '',
		`updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
		`package_json` MEDIUMTEXT,
		PRIMARY KEY (id),
		UNIQUE `uri` (`uri`),
		KEY `point` (`point`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['package_library'] = 'package_library';
	
// ===========================================================================
// Finish up

return TRUE;
