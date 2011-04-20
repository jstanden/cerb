<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Contact 

if(!isset($tables['contact_person'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS contact_person (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			email_id INT UNSIGNED DEFAULT 0 NOT NULL,
			auth_salt VARCHAR(64) DEFAULT '' NOT NULL,
			auth_password VARCHAR(64) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			last_login INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['contact_person'] = 'contact_person';
}

// ===========================================================================
// OpenID to Contact Person 

if(!isset($tables['openid_to_contact_person'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS openid_to_contact_person (
			openid_claimed_id VARCHAR(255) DEFAULT '' NOT NULL,
			contact_person_id INT UNSIGNED DEFAULT 0 NOT NULL,
			hash_key VARCHAR(32) DEFAULT '' NOT NULL,
			PRIMARY KEY (openid_claimed_id),
			INDEX contact_person_id (contact_person_id),
			INDEX hash_key (hash_key(4))
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['openid_to_contact_person'] = 'openid_to_contact_person';
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
// Migrate custom fields to contexts

if(!isset($tables['custom_field']))
	return FALSE;
if(!isset($tables['custom_field_stringvalue']))
	return FALSE;
if(!isset($tables['custom_field_numbervalue']))
	return FALSE;
if(!isset($tables['custom_field_clobvalue']))
	return FALSE;

$mapping = array(
	'cerberusweb.fields.source.address' => 'cerberusweb.contexts.address',
	'cerberusweb.fields.source.kb_article' => 'cerberusweb.contexts.kb_article',
	'cerberusweb.fields.source.org' => 'cerberusweb.contexts.org',
	'cerberusweb.fields.source.task' => 'cerberusweb.contexts.task',
	'cerberusweb.fields.source.ticket' => 'cerberusweb.contexts.ticket',
	'cerberusweb.fields.source.worker' => 'cerberusweb.contexts.worker',
	'cerberusweb.datacenter.domains.fields.domain' => 'cerberusweb.contexts.datacenter.domain',
	'cerberusweb.datacenter.fields.server' => 'cerberusweb.contexts.datacenter.server',
	'crm.fields.source.opportunity' => 'cerberusweb.contexts.opportunity',
	'feedback.fields.source.feedback_entry' => 'cerberusweb.contexts.feedback',
	'timetracking.fields.source.time_entry' => 'cerberusweb.contexts.timetracking',
	'usermeet.fields.source.community_portal' => 'cerberusweb.contexts.portal',
);

// custom_field
list($columns, $indexes) = $db->metaTable('custom_field');

if(isset($columns['source_extension'])) {
	$db->Execute("ALTER TABLE custom_field ADD COLUMN context VARCHAR(255) NOT NULL DEFAULT ''");
	
	foreach($mapping as $map_from => $map_to) {
		$db->Execute(sprintf("UPDATE custom_field SET context = %s WHERE source_extension = %s",
			$db->qstr($map_to),
			$db->qstr($map_from)
		));
	}
	
	$db->Execute("ALTER TABLE custom_field DROP COLUMN source_extension, ADD INDEX context (context)");
}

// custom_field_stringvalue
list($columns, $indexes) = $db->metaTable('custom_field_stringvalue');

if(isset($columns['source_id'])) {
	$db->Execute("ALTER TABLE custom_field_stringvalue CHANGE COLUMN source_id context_id INT UNSIGNED NOT NULL DEFAULT 0");
}

if(isset($columns['source_extension'])) {
	$db->Execute("ALTER TABLE custom_field_stringvalue ADD COLUMN context VARCHAR(255) NOT NULL DEFAULT ''");
	
	foreach($mapping as $map_from => $map_to) {
		$db->Execute(sprintf("UPDATE custom_field_stringvalue SET context = %s WHERE source_extension = %s",
			$db->qstr($map_to),
			$db->qstr($map_from)
		));
	}
	
	$db->Execute("ALTER TABLE custom_field_stringvalue DROP COLUMN source_extension, ADD INDEX context (context)");
}

// custom_field_numbervalue
list($columns, $indexes) = $db->metaTable('custom_field_numbervalue');

if(isset($columns['source_id'])) {
	$db->Execute("ALTER TABLE custom_field_numbervalue CHANGE COLUMN source_id context_id INT UNSIGNED NOT NULL DEFAULT 0");
}

if(isset($columns['source_extension'])) {
	$db->Execute("ALTER TABLE custom_field_numbervalue ADD COLUMN context VARCHAR(255) NOT NULL DEFAULT ''");
	
	foreach($mapping as $map_from => $map_to) {
		$db->Execute(sprintf("UPDATE custom_field_numbervalue SET context = %s WHERE source_extension = %s",
			$db->qstr($map_to),
			$db->qstr($map_from)
		));
	}
	
	$db->Execute("ALTER TABLE custom_field_numbervalue DROP COLUMN source_extension, ADD INDEX context (context)");
}

// custom_field_clobvalue
list($columns, $indexes) = $db->metaTable('custom_field_clobvalue');

if(isset($columns['source_id'])) {
	$db->Execute("ALTER TABLE custom_field_clobvalue CHANGE COLUMN source_id context_id INT UNSIGNED NOT NULL DEFAULT 0");
}

if(isset($columns['source_extension'])) {
	$db->Execute("ALTER TABLE custom_field_clobvalue ADD COLUMN context VARCHAR(255) NOT NULL DEFAULT ''");
	
	foreach($mapping as $map_from => $map_to) {
		$db->Execute(sprintf("UPDATE custom_field_clobvalue SET context = %s WHERE source_extension = %s",
			$db->qstr($map_to),
			$db->qstr($map_from)
		));
	}
	
	$db->Execute("ALTER TABLE custom_field_clobvalue DROP COLUMN source_extension, ADD INDEX context (context)");
}

// ===========================================================================
// Confirmation registry

if(!isset($tables['confirmation_code'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS confirmation_code (
			id INT UNSIGNED AUTO_INCREMENT,
			namespace_key VARCHAR(255) DEFAULT '',
			created INT UNSIGNED NOT NULL DEFAULT 0,
			confirmation_code VARCHAR(64) DEFAULT '',
			meta_json TEXT,
			PRIMARY KEY (id),
			INDEX namespace_key (namespace_key),
			INDEX created (created),
			INDEX confirmation_code (confirmation_code)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['confirmation_code'] = 'confirmation_code';
}

return TRUE;
