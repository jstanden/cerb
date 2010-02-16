<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Add an IS_DISABLED bit to filters

list($columns, $indexes) = $db->metaTable('watcher_mail_filter');

if(!isset($columns['is_disabled'])) {
    $db->Execute('ALTER TABLE watcher_mail_filter ADD COLUMN is_disabled TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($indexes['is_disabled'])) {
	$db->Execute('ALTER TABLE watcher_mail_filter ADD INDEX is_disabled (is_disabled)');
}

// ===========================================================================
// Make sure deactivated workers have deactivated filters

$sql = "SELECT id FROM worker WHERE is_disabled = 1";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$worker_id = intval($row['id']);
	
	$sql = sprintf("UPDATE watcher_mail_filter SET is_disabled = 1 WHERE worker_id = %d", $worker_id);
	$db->Execute($sql);
}

mysql_free_result($rs);

return TRUE;
