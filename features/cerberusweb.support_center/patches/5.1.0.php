<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Clear unused property keys 

if(isset($tables['community_tool_property'])) {
	$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.header_html'");
	$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.footer_html'");
	$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.style_css'");
	$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'home.html'");
}

return TRUE;