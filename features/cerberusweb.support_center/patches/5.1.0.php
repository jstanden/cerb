<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Clear unused property keys

if(isset($tables['community_tool_property'])) {
	$db->ExecuteMaster("DELETE FROM community_tool_property WHERE property_key = 'common.header_html'");
	$db->ExecuteMaster("DELETE FROM community_tool_property WHERE property_key = 'common.footer_html'");
	$db->ExecuteMaster("DELETE FROM community_tool_property WHERE property_key = 'common.style_css'");
	$db->ExecuteMaster("DELETE FROM community_tool_property WHERE property_key = 'home.html'");
}

return TRUE;