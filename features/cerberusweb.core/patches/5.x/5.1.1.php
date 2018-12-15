<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Fix indexes 

// Comment
if(!isset($tables['comment']))
	return FALSE;
	
list(, $indexes) = $db->metaTable('comment');
	
if(isset($indexes['id']))
	$db->ExecuteMaster("ALTER TABLE comment DROP INDEX id");

// ===========================================================================
// Fix workflow view stacked t_team_id filters

if(!isset($tables['worker_view_model']))
	return FALSE;

$db->ExecuteMaster("UPDATE worker_view_model SET params_required_json = '' WHERE view_id = 'mail_workflow'");

return TRUE;
