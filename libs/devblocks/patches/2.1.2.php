<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Expand the description column on the plugin table

list($columns,) = $db->metaTable('cerb_plugin');

if(!isset($columns['description']))
	return FALSE;

if(substr(mb_strtolower($columns['description']['type']),0,7) == 'varchar') {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin MODIFY COLUMN description TEXT");
}

// ===========================================================================
// devblocks_storage_deletes

if(!isset($tables['devblocks_storage_queue_delete'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_storage_queue_delete (
			storage_namespace VARCHAR(64) NOT NULL DEFAULT '',
			storage_key VARCHAR(255) NOT NULL DEFAULT '',
			storage_extension VARCHAR(128) NOT NULL DEFAULT '',
			storage_profile_id INT UNSIGNED NOT NULL DEFAULT 0,
			INDEX ns_ext_profile (storage_namespace, storage_extension, storage_profile_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['devblocks_storage_queue_delete'] = 'devblocks_storage_queue_delete';
}

return TRUE;