<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Fix any mixed case addresses in the address_to_worker table

list($columns, $indexes) = $db->metaTable('address_to_worker');

if(isset($columns['address'])) {
	$db->Execute("UPDATE IGNORE address_to_worker SET address=LOWER(address)");
}

return TRUE;

