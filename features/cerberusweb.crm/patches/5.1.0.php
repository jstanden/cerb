<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Convert task assignments to contexts

if(!isset($tables['crm_opportunity']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(isset($columns['worker_id'])) {
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.opportunity', id, 'cerberusweb.contexts.worker', worker_id FROM crm_opportunity WHERE worker_id > 0"
	);
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.worker', worker_id, 'cerberusweb.contexts.opportunity', id FROM crm_opportunity WHERE worker_id > 0"
	);
	
	$db->Execute('ALTER TABLE crm_opportunity DROP COLUMN worker_id');
}

return TRUE;