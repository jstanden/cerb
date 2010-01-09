<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Add an IS_DISABLED bit to filters

$columns = $datadict->MetaColumns('watcher_mail_filter');
$indexes = $datadict->MetaIndexes('watcher_mail_filter',false);

if(!isset($columns['IS_DISABLED'])) {
    $sql = $datadict->AddColumnSQL('watcher_mail_filter', 'is_disabled I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_disabled'])) {
	$sql = $datadict->CreateIndexSQL('is_disabled','watcher_mail_filter','is_disabled');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Make sure deactivated workers have deactivated filters

$sql = "SELECT id FROM worker WHERE is_disabled = 1";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$worker_id = intval($rs->fields['id']);
	
	$sql = sprintf("UPDATE watcher_mail_filter SET is_disabled = 1 WHERE worker_id = %d", $worker_id);
	$db->Execute($sql);
	
	$rs->MoveNext();
}

return TRUE;
