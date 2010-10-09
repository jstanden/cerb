<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Contact 

if(!isset($tables['contact_person'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS contact_person (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			email_id INT UNSIGNED DEFAULT 0 NOT NULL,
			auth_salt VARCHAR(64) DEFAULT '' NOT NULL,
			auth_password VARCHAR(64) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			last_login INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['contact_person'] = 'contact_person';
}

// ===========================================================================
// Migrate address Support Center info to Contacts

if(!isset($tables['address']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('address');

// Add address.contact_id
if(!isset($columns['contact_person_id'])) {
	$sql = "ALTER TABLE address ADD COLUMN contact_person_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX contact_person_id (contact_person_id)";
	$db->Execute($sql);
}

if(isset($columns['is_registered']) && isset($columns['pass'])) {
	$sql = "SELECT id,pass FROM address WHERE is_registered=1";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$salt = CerberusApplication::generatePassword(8);
		
		// Insert the contact
		$sql = sprintf("INSERT IGNORE INTO contact_person (email_id, auth_salt, auth_password) ".
			"VALUES (%d, %s, %s)",
			$row['id'],
			$db->qstr($salt),
			$db->qstr(md5($salt.$row['pass']))
		);
		$db->Execute($sql);
	}
	
	mysql_free_result($rs);
	
	// Link created dates from ticket table
	$sql = "UPDATE contact_person SET contact_person.created = (SELECT min(ticket.created_date) FROM ticket WHERE ticket.first_wrote_address_id = contact_person.email_id) WHERE contact_person.created = 0";
	$db->Execute($sql);
	
	// And set anybody we can't identify by ticket to having been created today
	$sql = sprintf("UPDATE contact_person SET contact_person.created = %d WHERE contact_person.created = 0", time());
	$db->Execute($sql);
	
	// Associate the email addresses to the new contacts
	$sql = "UPDATE address INNER JOIN contact_person ON (contact_person.email_id=address.id) SET address.contact_person_id = contact_person.id";
	$db->Execute($sql);
	
	// Drop address.is_registered and pass columns
	$db->Execute("ALTER TABLE address DROP COLUMN is_registered, DROP COLUMN pass");
}

// ===========================================================================
// Contact lists 

if(!isset($tables['contact_list'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS contact_list (
			id INT UNSIGNED AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['contact_list'] = 'contact_list';
}

return TRUE;
