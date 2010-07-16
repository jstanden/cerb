<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'ticket_audit_log_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->Execute(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'ticket_audit_log',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->Execute(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE", $table));
	}
}

return TRUE;