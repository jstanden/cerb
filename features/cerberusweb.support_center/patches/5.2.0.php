<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Migrate templates to the new plugin 

$db->Execute("UPDATE devblocks_template SET plugin_id = 'cerberusweb.support_center' WHERE plugin_id = 'usermeet.core'");

return TRUE;