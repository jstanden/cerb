<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Convert the translation strings to 'text'

list($columns,) = $db->metaTable('translation');

if(!isset($columns['string_default']) || !isset($columns['string_override']))
	return FALSE;

if(mb_strtolower($columns['string_default']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_default TEXT NOT NULL");

if(mb_strtolower($columns['string_override']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_override TEXT NOT NULL");

// ===========================================================================
// Fix the `cerb_plugin` version field

if(!isset($tables['cerb_plugin'])) {
	$logger->error("The 'cerb_plugin' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('cerb_plugin');

if(isset($columns['version']) && 0 != strcasecmp('int',substr($columns['version']['type'], 0, 3))) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin MODIFY COLUMN version INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Fix strict mode incompatibility in the `translation` table

if(!isset($tables['translation'])) {
	$logger->error("The 'translation' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('translation');

if(isset($columns['string_default']) && 'NO' == $columns['string_default']['null']) {
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_default TEXT");
}

if(isset($columns['string_override']) && 'NO' == $columns['string_override']['null']) {
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_override TEXT");
}

// ===========================================================================
// Finish

return TRUE;