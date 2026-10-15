<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Remove email address sharing

if(isset($tables['supportcenter_address_share'])) {
	$db->ExecuteMaster("DROP TABLE supportcenter_address_share");
	unset($tables['supportcenter_address_share']);
}

return TRUE;
