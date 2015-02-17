<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add the `reply_mail_transport_id` field to `address_outgoing`

if(!isset($tables['address_outgoing'])) {
	$logger->error("The 'address_outgoing' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address_outgoing');

if(!isset($columns['reply_mail_transport_id'])) {
	$db->ExecuteMaster("ALTER TABLE address_outgoing ADD COLUMN reply_mail_transport_id INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add the `mail_transport` table

if(!isset($tables['mail_transport'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS mail_transport (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			extension_id VARCHAR(255) DEFAULT '',
			is_default TINYINT UNSIGNED NOT NULL DEFAULT 0,
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			params_json TEXT,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['mail_transport'] = 'mail_transport';
	
	$smtp_params = array(
		'auth_enabled' => '0',
		'auth_user' => '',
		'auth_pass' => '',
		'encryption' => 'None',
		'host' => 'localhost',
		'port' => '25',
		'max_sends' => '20',
		'timeout' => '30',
	);
	
	// Move the devblocks_setting rows into the first SMTP
	
	$sql = "SELECT setting, value FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting LIKE 'smtp_%'";
	$previous_params = $db->GetArrayMaster($sql);
	
	if(is_array($previous_params))
	foreach($previous_params as $row) {
		// Strip the smtp_ prefix off the key
		$key = strtolower(substr($row['setting'], 5));
		
		switch($key) {
			case 'enc';
				$key = 'encryption';
				break;
		}
		
		// Override the default values
		$smtp_params[$key] = $row['value'];
	}
	
	// Insert the existing settings as the default mail transport record
	
	if(!empty($previous_params)) {
		$sql = sprintf("INSERT INTO mail_transport (name, extension_id, is_default, created_at, updated_at, params_json) ".
			"VALUES (%s, %s, %d, %d, %d, %s)",
			$db->qstr('Default SMTP'),
			$db->qstr('core.mail.transport.smtp'),
			1,
			time(),
			time(),
			$db->qstr(json_encode($smtp_params))
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		// Add this mail transport to the default reply-to
		$db->ExecuteMaster(sprintf("UPDATE address_outgoing SET reply_mail_transport_id = %d WHERE is_default = 1",
			$id
		));
	}
	
	// Drop the old settings
	$sql = "DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting LIKE 'smtp_%'";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return TRUE;
