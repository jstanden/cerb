<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Remove the message_header.ticket_id foreign key (inefficient)

list($columns, $indexes) = $db->metaTable('message_header');

if(isset($columns['ticket_id'])) {
	$db->Execute('ALTER TABLE message_header DROP COLUMN ticket_id');
}

// ===========================================================================
// Add 'is_registered' and 'pass' fields to the 'address' table

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['is_registered'])) {
	$db->Execute('ALTER TABLE address ADD COLUMN is_registered TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
	$db->Execute('ALTER TABLE address ADD INDEX is_registered (is_registered)');
}

if(!isset($columns['pass'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN pass VARCHAR(32) DEFAULT '' NOT NULL");
}

// ===========================================================================
// Merge 'address_auth' into 'address'

if(isset($tables['address_auth'])) {
	$sql = "SELECT address_id, pass FROM address_auth WHERE pass != ''";
	$rs = $db->Execute($sql);
	
	// Loop through 'address_auth' records and inject them into 'address'
	while($row = mysql_fetch_assoc($rs)) {
		$address_id = $row['address_id'];
		$pass = $row['pass'];
		
		$db->Execute(sprintf("UPDATE address SET is_registered=1, pass=%s WHERE id = %d",
			$db->qstr($pass),
			$address_id
		));
	}
	
	mysql_free_result($rs);
	
	// Drop 'address_auth'
	$db->Execute('DROP TABLE address_auth');
}

return TRUE;
