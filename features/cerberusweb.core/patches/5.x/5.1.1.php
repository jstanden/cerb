<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Fix indexes 

// Comment
if(!isset($tables['comment']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('comment');
	
if(isset($indexes['id']))
	$db->Execute("ALTER TABLE comment DROP INDEX id");

return TRUE;
