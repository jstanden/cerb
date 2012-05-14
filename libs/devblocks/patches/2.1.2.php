<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(!isset($columns['description']))
	return FALSE;

if(substr(strtolower($columns['description']['type']),0,7) == 'varchar') {
	$db->Execute("ALTER TABLE ${prefix}plugin MODIFY COLUMN description TEXT");
}

return TRUE;