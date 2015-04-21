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
// Add the `html_attachment_id` field to `message`

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

if(!isset($columns['html_attachment_id'])) {
	$db->ExecuteMaster("ALTER TABLE message ADD COLUMN html_attachment_id INT UNSIGNED NOT NULL DEFAULT 0");
	
	$db->ExecuteMaster("CREATE TEMPORARY TABLE _tmp_message_to_html SELECT al.attachment_id AS html_id, al.context_id AS message_id FROM attachment_link al INNER JOIN attachment a ON (a.id=al.attachment_id) WHERE al.context = 'cerberusweb.contexts.message' AND a.display_name = 'original_message.html'");
	$db->ExecuteMaster("ALTER TABLE _tmp_message_to_html ADD INDEX (message_id), ADD INDEX (html_id)");
	$db->ExecuteMaster("UPDATE message AS m INNER JOIN _tmp_message_to_html AS tmp ON (tmp.message_id=m.id) SET m.html_attachment_id = tmp.html_id");
	$db->ExecuteMaster("DROP TABLE _tmp_message_to_html");
}

// ===========================================================================
// Add the `skillset` table

if(!isset($tables['skillset'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS skillset (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['skillset'] = 'skillset';
}

// ===========================================================================
// Add the `skill` table

if(!isset($tables['skill'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS skill (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			skillset_id INT UNSIGNED NOT NULL DEFAULT 0,
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['skill'] = 'skill';
}

// ===========================================================================
// Add `importance` field to `ticket`

if(!isset($tables['ticket'])) {
	$logger->error("The 'ticket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('ticket');

if(!isset($columns['importance'])) {
	$db->ExecuteMaster("ALTER TABLE ticket ADD COLUMN importance TINYINT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX (importance)");
}

// ===========================================================================
// Remove `is_assignable` field from `bucket`

if(!isset($tables['bucket'])) {
	$logger->error("The 'bucket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(isset($columns['is_assignable'])) {
	$db->ExecuteMaster("ALTER TABLE bucket DROP COLUMN is_assignable");
}

// Add `updated_at` field to `bucket`

if(!isset($columns['updated_at'])) {
	$db->ExecuteMaster("ALTER TABLE bucket ADD COLUMN updated_at INT UNSIGNED NOT NULL DEFAULT 0");
	$db->ExecuteMaster("UPDATE bucket SET updated_at=UNIX_TIMESTAMP()");
}

// ===========================================================================
// Add `is_default` field to `bucket`

list($columns, $indexes) = $db->metaTable('bucket');

if(!isset($columns['is_default'])) {
	$db->ExecuteMaster("ALTER TABLE bucket ADD COLUMN is_default TINYINT UNSIGNED NOT NULL DEFAULT 0");
	
	// Convert virtual inbox buckets to actual records
	list($columns, $indexes) = $db->metaTable('worker_group');
	
	$group_inboxes = array();
	
	$results = $db->ExecuteMaster("SELECT id, reply_address_id, reply_personal, reply_signature, reply_html_template_id FROM worker_group");
	
	foreach($results as $row) {
		$db->ExecuteMaster(sprintf("UPDATE bucket SET pos = pos + 1 WHERE group_id = %d", $row['id']));
		
		$db->ExecuteMaster(sprintf("INSERT INTO bucket (group_id, name, pos, reply_address_id, reply_personal, reply_signature, reply_html_template_id, updated_at, is_default) ".
			"VALUES (%d, %s, %d, %d, %s, %s, %d, %d, %d)",
			$row['id'],
			$db->qstr('Inbox'),
			0,
			$row['reply_address_id'],
			$db->qstr($row['reply_personal']),
			$db->qstr($row['reply_signature']),
			$row['reply_html_template_id'],
			time(),
			1
		));
		
		$bucket_id = $db->LastInsertId();
		
		$group_inboxes[$row['id']] = $bucket_id;
		
		// Move all tickets into the new inbox buckets
		
		$db->ExecuteMaster(sprintf("UPDATE ticket SET bucket_id = %d WHERE group_id = %d AND bucket_id = 0",
			$bucket_id,
			$row['id']
		));
		
		$db->ExecuteMaster("ALTER TABLE worker_group DROP COLUMN reply_address_id, DROP COLUMN reply_personal, DROP COLUMN reply_signature, DROP COLUMN reply_html_template_id");
	}
	
	// Migrate VA outcomes
	
	$results = $db->GetArrayMaster("SELECT id, params_json FROM decision_node WHERE node_type = 'outcome'");
	
	foreach($results as $row) {
		$json = json_decode($row['params_json'], true);
		
		if(isset($json['groups']))
		foreach($json['groups'] as &$group) {
			
			if(isset($group['conditions']))
			foreach($group['conditions'] as &$condition) {
				switch($condition['condition']) {
					
					case 'group_and_bucket':
						if(is_array($condition['bucket_id']))
						foreach($condition['bucket_id'] as &$bucket_id) {
							$move_to_group_id = $condition['group_id'];
							
							// If this referenced a legacy group inbox, update it
							if(0 == $bucket_id && $move_to_group_id && isset($group_inboxes[$move_to_group_id]))
								$bucket_id = $group_inboxes[$move_to_group_id];
						}
						break;
						
				}
			}
			
			$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($json)),
				$row['id']
			));
		}
	}
	
	// Migrate VA actions
	
	$results = $db->GetArrayMaster("SELECT id, params_json FROM decision_node WHERE node_type = 'action'");
	
	foreach($results as $row) {
		$json = json_decode($row['params_json'], true);
		
		if(isset($json['actions']))
		foreach($json['actions'] as &$action) {
			switch($action['action']) {
				
				case 'move_to':
					// If the bucket is empty (legacy inbox)
					if(isset($action['bucket_id']) && empty($bucket_id)) {
						
						if(isset($action['group_id']) && $move_to_group_id = $action['group_id'] && isset($group_inboxes[$action['group_id']])) {
							$action['bucket_id'] = $group_inboxes[$action['group_id']];
						}
					}
					break;
					
			}
				
			$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($json)),
				$row['id']
			));
		}
	}
	
	// Migrate mail rules
	
	$mail_to_group_rules = $db->GetArrayMaster("SELECT id, actions_ser FROM mail_to_group_rule");
	
	foreach($mail_to_group_rules as $rule) {
		$actions = unserialize($rule['actions_ser']);
		
		// If this mail routing rule moves tickets
		if(isset($actions['move']) && isset($actions['move']['bucket_id'])) {
			// We don't care about the bucket (it was always zero)
			unset($actions['move']['bucket_id']);
			
			// Update the database row
			$db->ExecuteMaster(sprintf("UPDATE mail_to_group_rule SET actions_ser = %s WHERE id = %d",
					$db->qstr(serialize($actions)),
					$rule['id']
			));
		}
	}
}

// Finish up

return TRUE;
