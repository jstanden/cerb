<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Convert the module format

if(isset($tables['community_tool_property'])) {
	$sql = "SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'common.enabled_modules'";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$tool_code = $row['tool_code'];
		$property_value = $row['property_value'];
		
		// Check the deprecated login bits
		$login_contact = $db->GetOne(sprintf("SELECT property_value FROM community_tool_property WHERE property_key = 'contact.require_login' AND tool_code = %s AND property_value='1'",
			$db->qstr($tool_code)
		));
		$login_kb = $db->GetOne(sprintf("SELECT property_value FROM community_tool_property WHERE property_key = 'contact.require_kb' AND tool_code = %s AND property_value='1'",
			$db->qstr($tool_code)
		));
		
		// Change the format
		$modules = array();
		$mods = DevblocksPlatform::parseCsvString($property_value);
		if(is_array($mods))
		foreach($mods as $mod) {
			switch($mod) {
				case 'sc.controller.contact':
					$modules[$mod] = !empty($login_contact) ? 1 : 0;
					break;
				case 'sc.controller.kb':
					$modules[$mod] = !empty($login_kb) ? 1 : 0;
					break;
				case 'sc.controller.history':
				case 'sc.controller.account':
					$modules[$mod] = '1'; // default to require login
					break;
				default:
					$modules[$mod] = '0'; // default to everybody
					break;
			}
		}
		
		// Insert a new property
		$sql = sprintf("INSERT IGNORE INTO community_tool_property (tool_code, property_key, property_value) ".
			"VALUES (%s, %s, %s)",
			$db->qstr($tool_code),
			$db->qstr('common.visible_modules'),
			$db->qstr(serialize($modules))
		);
		$db->Execute($sql);
		
		// Remove the old property
		$db->Execute(sprintf("DELETE FROM community_tool_property WHERE tool_code = %s AND property_key = %s",
			$db->qstr($tool_code),
			$db->qstr('common.enabled_modules')
		));
		
		// Drop deprecated options
		$db->Execute(sprintf("DELETE FROM community_tool_property WHERE tool_code = %s AND (property_key = 'contact.require_login' OR property_key = 'kb.require_login')",
			$db->qstr($tool_code)
		));
	}
	
	mysql_free_result($rs);
}

return TRUE;