<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

// ===========================================================================
// Drop unused extension_point table

$table = $prefix.'extension_point';

if(isset($tables[$table])) {
	$sql = sprintf("DROP TABLE %s", $table);
	$db->ExecuteMaster($sql);
	unset($tables[$table]);
}

// ===========================================================================
// Drop unused uri_routing table

$table = $prefix.'uri_routing';

if(isset($tables[$table])) {
	$sql = sprintf("DROP TABLE %s", $table);
	$db->ExecuteMaster($sql);
	unset($tables[$table]);
}

// ===========================================================================
// Finish

return TRUE;