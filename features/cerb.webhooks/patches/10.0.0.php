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

// ===========================================================================
// Migrate webhook portals to automations

$sql = "SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'webhook_behavior_id' AND tool_code IN (SELECT code FROM community_tool WHERE extension_id = 'webhooks.portal')";

$results = $db->GetArrayMaster($sql);

if(is_array($results)) {
	foreach ($results as $result) {
		$webhook_kata = [
			'behavior/' . $result['property_value'] => [
				'uri' => 'cerb:behavior:' . $result['property_value'],
				'disabled@bool' => 'no',
			]
		];
		
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO community_tool_property (tool_code, property_key, property_value) VALUES (%s, %s, %s)",
			$db->qstr($result['tool_code']),
			$db->qstr('automations_kata'),
			$db->qstr(DevblocksPlatform::services()->kata()->emit($webhook_kata))
		));
		
		$db->ExecuteMaster(sprintf("DELETE FROM community_tool_property WHERE tool_code = %s AND property_key = 'webhook_behavior_id'",
			$db->qstr($result['tool_code'])
		));
	}
}

return TRUE;