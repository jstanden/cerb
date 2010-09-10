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

// ===========================================================================
// Fix workflow view stacked t_team_id filters

if(!isset($tables['worker_view_model']))
	return FALSE;

$db->Execute("UPDATE worker_view_model SET params_required_json = '' WHERE view_id = 'mail_workflow'");

return TRUE;
