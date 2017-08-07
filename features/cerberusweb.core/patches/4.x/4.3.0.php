<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Remove the message_header.ticket_id foreign key (inefficient)

list($columns, $indexes) = $db->metaTable('message_header');

if(isset($columns['ticket_id'])) {
	$db->ExecuteMaster('ALTER TABLE message_header DROP COLUMN ticket_id');
}

// ===========================================================================
// Add 'is_registered' and 'pass' fields to the 'address' table

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['is_registered'])) {
	$db->ExecuteMaster('ALTER TABLE address ADD COLUMN is_registered TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
	$db->ExecuteMaster('ALTER TABLE address ADD INDEX is_registered (is_registered)');
}

if(!isset($columns['pass'])) {
	$db->ExecuteMaster("ALTER TABLE address ADD COLUMN pass VARCHAR(32) DEFAULT '' NOT NULL");
}

// ===========================================================================
// Merge 'address_auth' into 'address'

if(isset($tables['address_auth'])) {
	$sql = "SELECT address_id, pass FROM address_auth WHERE pass != ''";
	$rs = $db->ExecuteMaster($sql);
	
	// Loop through 'address_auth' records and inject them into 'address'
	while($row = mysqli_fetch_assoc($rs)) {
		$address_id = $row['address_id'];
		$pass = $row['pass'];
		
		$db->ExecuteMaster(sprintf("UPDATE address SET is_registered=1, pass=%s WHERE id = %d",
			$db->qstr($pass),
			$address_id
		));
	}
	
	mysqli_free_result($rs);
	
	// Drop 'address_auth'
	$db->ExecuteMaster('DROP TABLE address_auth');
}

return TRUE;
