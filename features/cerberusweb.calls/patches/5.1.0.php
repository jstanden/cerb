<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Adjust table

list($columns, $indexes) = $db->metaTable('call_entry');

if(isset($columns['name']))
	$db->Execute("ALTER TABLE call_entry DROP COLUMN name");

if(isset($columns['org_id']))
	$db->Execute("ALTER TABLE call_entry DROP COLUMN org_id");

if(isset($columns['worker_id']))
	$db->Execute("ALTER TABLE call_entry DROP COLUMN worker_id");

if(!isset($columns['subject']))
	$db->Execute("ALTER TABLE call_entry ADD COLUMN subject VARCHAR(255) NOT NULL DEFAULT ''");
	
if(!isset($columns['is_outgoing']))
	$db->Execute("ALTER TABLE call_entry ADD COLUMN is_outgoing TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");

if(!isset($indexes['is_outgoing']))
    $db->Execute('ALTER TABLE call_entry ADD INDEX is_outgoing (is_outgoing)');
	
if(!isset($indexes['is_closed']))
    $db->Execute('ALTER TABLE call_entry ADD INDEX is_closed (is_closed)');
	
// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'call_entry_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->Execute(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'call_entry',
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