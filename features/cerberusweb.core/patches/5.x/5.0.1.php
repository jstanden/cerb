<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Fix CRLF and tabs in ticket subjects

$db->Execute("update ticket set subject = replace(replace(replace(subject,\"\\n\",' '),\"\\r\",' '),\"\\t\",' ') where subject regexp \"(\\r|\\n|\\t)\"");

// ===========================================================================
// Parent organizations

if(!isset($tables['contact_org']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('contact_org');

if(isset($columns['parent_org_id'])) {
	$db->Execute("ALTER TABLE contact_org DROP COLUMN parent_org_id");
}

return TRUE;
