<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(isset($columns['revision'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin DROP COLUMN revision");
}

if(!isset($columns['version'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin ADD COLUMN version SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `author`");
}

return TRUE;