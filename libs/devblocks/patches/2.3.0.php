<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

// ===========================================================================
// Convert the translation strings to 'text'

list($columns, $indexes) = $db->metaTable('translation');

if(!isset($columns['string_default']) || !isset($columns['string_override']))
	return FALSE;

if(strtolower($columns['string_default']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_default TEXT NOT NULL");

if(strtolower($columns['string_override']['type']) == 'longtext')
	$db->ExecuteMaster("ALTER TABLE translation MODIFY COLUMN string_override TEXT NOT NULL");

return TRUE;