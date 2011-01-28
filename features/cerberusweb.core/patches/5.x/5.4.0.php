<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// attachment_link 
//
//if(!isset($tables['attachment_link'])) {
//	$sql = "
//		CREATE TABLE IF NOT EXISTS attachment_link (
//			guid VARCHAR(64) NOT NULL DEFAULT '',
//			attachment_id INT UNSIGNED NOT NULL,
//			context VARCHAR(128) DEFAULT '' NOT NULL,
//			context_id INT UNSIGNED NOT NULL,
//			PRIMARY KEY (attachment_id, context, context_id),
//			INDEX guid (guid),
//			INDEX attachment_id (attachment_id),
//			INDEX context (context),
//			INDEX context_id (context_id)
//		) ENGINE=MyISAM;
//	";
//	$db->Execute($sql);
//
//	$tables['attachment_link'] = 'attachment_link';
//}

// ===========================================================================
// Rename 'worker_event' to 'notification'

if(isset($tables['worker_event']) && !isset($tables['notification'])) {
	$db->Execute('ALTER TABLE worker_event RENAME notification');
	$db->Execute("DELETE FROM worker_view_model WHERE view_id = 'home_myevents'");
}

return TRUE;
