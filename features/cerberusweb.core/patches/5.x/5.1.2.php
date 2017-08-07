<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Fix comments 

if(!isset($tables['comment']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('comment');
	
// Enforce autoincrement
$db->ExecuteMaster("ALTER TABLE comment MODIFY COLUMN id int unsigned NOT NULL AUTO_INCREMENT");
	
return TRUE;
