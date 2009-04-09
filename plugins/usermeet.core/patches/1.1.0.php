<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Clear the space used by the old 'theme' property

if(isset($tables['community_tool_property'])) {
	$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'theme'");
}

return TRUE;