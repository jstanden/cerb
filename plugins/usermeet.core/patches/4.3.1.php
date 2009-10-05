<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Convert the SC login handler format

$sql = "SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'common.allow_logins'";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$tool_code = $rs->Fields('tool_code');
	$property_value = $rs->Fields('property_value');
	
	$login_handler = (0 == @intval($property_value)) ? '' : 'sc.login.auth.default';
	
	// Insert new login handler property
	$sql = sprintf("INSERT IGNORE INTO community_tool_property (tool_code, property_key, property_value) ".
		"VALUES (%s, %s, %s)",
		$db->qstr($tool_code),
		$db->qstr('common.login_handler'),
		$db->qstr($login_handler)
	);
	$db->Execute($sql);
	
	// Drop allow_logins property
	$db->Execute(sprintf("DELETE FROM community_tool_property WHERE tool_code = %s AND property_key = %s",
		$db->qstr($tool_code),
		$db->qstr('common.allow_logins')
	));
	
	$rs->MoveNext();
}

return TRUE;