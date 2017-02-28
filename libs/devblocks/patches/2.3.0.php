<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

// ===========================================================================
// Convert the translation strings to 'text'

list($columns, $indexes) = $db->metaTable('translation');

if(!isset($columns['string_default']) || !isset($columns['string_override']))
	return FALSE;

if(mb_strtolower($columns['string_default']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_default TEXT NOT NULL");

if(mb_strtolower($columns['string_override']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_override TEXT NOT NULL");

// ===========================================================================
// Fix the `cerb_plugin` version field

if(!isset($tables[$prefix.'plugin'])) {
	$logger->error("The 'cerb_plugin' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(isset($columns['version']) && 0 != strcasecmp('int',substr($columns['version']['type'], 0, 3))) {
	$db->ExecuteMaster(sprintf("ALTER TABLE %splugin MODIFY COLUMN version INT UNSIGNED NOT NULL DEFAULT 0", $prefix));
}

// ===========================================================================
// Fix strict mode incompatibility in the `translation` table

if(!isset($tables['translation'])) {
	$logger->error("The 'translation' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('translation');

if(isset($columns['string_default']) && 'NO' == $columns['string_default']['null']) {
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_default TEXT");
}

if(isset($columns['string_override']) && 'NO' == $columns['string_override']['null']) {
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_override TEXT");
}

// ===========================================================================
// Finish

return TRUE;