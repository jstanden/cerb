<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Fix comments 

if(!isset($tables['comment']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('comment');
	
// Enforce autoincrement
$db->Execute("ALTER TABLE comment MODIFY COLUMN id int unsigned NOT NULL AUTO_INCREMENT");
	
return TRUE;
