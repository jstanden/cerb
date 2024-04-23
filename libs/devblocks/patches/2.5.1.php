<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Drop `cerb_acl`

if(isset($tables['cerb_acl'])) {
	$db->ExecuteWriter('DROP TABLE cerb_acl');
}

// ===========================================================================
// Finish

return TRUE;