<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Drop unused extension_point table

$table = 'cerb_extension_point';

if(isset($tables[$table])) {
	$sql = sprintf("DROP TABLE %s", $table);
	$db->ExecuteMaster($sql);
	unset($tables[$table]);
}

// ===========================================================================
// Drop unused uri_routing table

$table = 'cerb_uri_routing';

if(isset($tables[$table])) {
	$sql = sprintf("DROP TABLE %s", $table);
	$db->ExecuteMaster($sql);
	unset($tables[$table]);
}

// ===========================================================================
// Increase devblocks_setting 'setting' length

if(!isset($tables['devblocks_setting'])) {
	$logger->error("The 'devblocks_setting' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('devblocks_setting');

$changes = array();

if(isset($columns['plugin_id']) && 'varchar(128)' != $columns['plugin_id']['type'])
	$changes[] = "change column plugin_id plugin_id varchar(128) not null default ''";

if(isset($columns['setting']) && 'varchar(128)' != $columns['setting']['type'])
	$changes[] = "change column setting setting varchar(128) not null default ''";

if(!empty($changes)) {
	$sql = sprintf("ALTER TABLE devblocks_setting %s", implode(', ', $changes));
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
}

// ===========================================================================
// Finish

return TRUE;