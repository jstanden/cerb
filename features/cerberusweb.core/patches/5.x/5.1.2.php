<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Fix comments 

if(!isset($tables['comment']))
	return FALSE;
	
list(, $indexes) = $db->metaTable('comment');
	
// Enforce autoincrement
$db->ExecuteMaster("ALTER TABLE comment MODIFY COLUMN id int unsigned NOT NULL AUTO_INCREMENT");
	
return TRUE;
