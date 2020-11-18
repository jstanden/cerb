<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add automations to webhooks

list($columns,) = $db->metaTable('webhook_listener');

if(!array_key_exists('automations_kata', $columns)) {
	$sql = "ALTER TABLE webhook_listener ADD COLUMN automations_kata mediumtext";
	$db->ExecuteMaster($sql);
}

// Migrate behaviors to automations
if(array_key_exists('extension_params_json', $columns)) {
	$sql = "select id, extension_params_json from webhook_listener where extension_id = 'cerb.webhooks.listener.engine.va'";
	$rows = $db->GetArrayMaster($sql);
	
	foreach($rows as $row) {
		@$extension_params = json_decode($row['extension_params_json'], true);
		
		if(!is_array($extension_params) || !array_key_exists('behavior_id', $extension_params))
			continue;
		
		$kata = sprintf("# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%d\n  #disabled@bool: yes\n",
			uniqid(),
			$extension_params['behavior_id']
		);
		
		$db->ExecuteMaster(sprintf('update webhook_listener SET automations_kata = %s WHERE id = %d',
			$db->qstr($kata),
			$row['id']
		));
	}
	
	$db->ExecuteMaster('alter table webhook_listener drop column extension_id');
	$db->ExecuteMaster('alter table webhook_listener drop column extension_params_json');
}

return TRUE;