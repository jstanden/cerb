<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Convert the SC login handler format

if(isset($tables['community_tool_property'])) {
	$sql = "SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'common.allow_logins'";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$tool_code = $row['tool_code'];
		$property_value = $row['property_value'];
		
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
	}
	
	mysql_free_result($rs);
}

return TRUE;