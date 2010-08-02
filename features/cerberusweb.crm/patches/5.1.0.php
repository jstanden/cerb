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

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'crm_opportunity_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->Execute(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'crm_opportunity',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->Execute(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT", $table));
	}
}

return TRUE;