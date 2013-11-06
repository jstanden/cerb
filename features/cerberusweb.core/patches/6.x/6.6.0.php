<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Convert `custom_field.options` to `params_json`

if(!isset($tables['custom_field'])) {
	$logger->error("The 'custom_field' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_field');

if(!isset($columns['params_json'])) {
	$db->Execute("ALTER TABLE custom_field ADD COLUMN params_json TEXT AFTER pos");
	
	$results = $db->GetArray("SELECT id, options FROM custom_field WHERE options != ''");
	
	foreach($results as $result) {
		$params = array(
			'options' => DevblocksPlatform::parseCrlfString($result['options'])
		);
		
		// Migrate the `options` field on `custom_field` to `params_json`
		$db->Execute(sprintf("UPDATE custom_field SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$result['id']
		));
	}
}

// Drop the `options` field on `custom_field`
if(isset($columns['options'])) {
	$db->Execute("ALTER TABLE custom_field DROP COLUMN options");
}

// ===========================================================================
// Finish up

return TRUE;
