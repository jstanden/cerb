<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Log everybody out since this is a major upgrade

if(!isset($tables['devblocks_session'])) {
	$logger->error("The 'devblocks_session' table does not exist.");
	return FALSE;
}

$db->ExecuteMaster("DELETE FROM devblocks_session");

// ===========================================================================
// Fix the `plugin_library` latest_version field

if(!isset($tables['plugin_library'])) {
	$logger->error("The 'plugin_library' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('plugin_library');

if(isset($columns['latest_version']) && 0 != strcasecmp('int',substr($columns['latest_version']['type'], 0, 3))) {
	$db->ExecuteMaster("ALTER TABLE plugin_library MODIFY COLUMN latest_version INT UNSIGNED NOT NULL DEFAULT 0");
}

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
		$key = mb_convert_case(substr($row['setting'], 5), MB_CASE_LOWER);
		
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
// Add the `context_to_skill` table

if(!isset($tables['context_to_skill'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS context_to_skill (
			context VARCHAR(255) DEFAULT '',
			context_id INT UNSIGNED NOT NULL DEFAULT 0,
			skill_id INT UNSIGNED NOT NULL DEFAULT 0,
			skill_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (context, context_id, skill_id),
			INDEX skill_id (skill_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['context_to_skill'] = 'context_to_skill';
}

// ===========================================================================
// Add the `context_recommendation` table

if(!isset($tables['context_recommendation'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS context_recommendation (
			context VARCHAR(255) DEFAULT '',
			context_id INT UNSIGNED NOT NULL DEFAULT 0,
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (context, context_id, worker_id),
			INDEX context_and_id (context, context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['context_recommendation'] = 'context_recommendation';
}

// ===========================================================================
// Remove disabled workers from groups

$db->ExecuteMaster("DELETE FROM worker_to_group WHERE worker_id IN (SELECT id FROM worker WHERE is_disabled = 1)");

// ===========================================================================
// Add `importance` field to `ticket`

if(!isset($tables['ticket'])) {
	$logger->error("The 'ticket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('ticket');

if(!isset($columns['importance'])) {
	$db->ExecuteMaster("ALTER TABLE ticket ADD COLUMN importance TINYINT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX (importance)");
	$db->ExecuteMaster("UPDATE ticket SET importance = 50");
}

// ===========================================================================
// Add `is_private` field to `group`

if(!isset($tables['worker_group'])) {
	$logger->error("The 'worker_group' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_group');

if(!isset($columns['is_private'])) {
	$db->ExecuteMaster("ALTER TABLE worker_group ADD COLUMN is_private TINYINT UNSIGNED NOT NULL DEFAULT 0");
	$db->ExecuteMaster("UPDATE worker_group SET is_private=1");
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

// Remove `pos` field from `bucket`

if(isset($columns['pos'])) {
	$db->ExecuteMaster("ALTER TABLE bucket DROP COLUMN pos");
}

// Add `updated_at` field to `bucket`

if(!isset($columns['updated_at'])) {
	$db->ExecuteMaster("ALTER TABLE bucket ADD COLUMN updated_at INT UNSIGNED NOT NULL DEFAULT 0");
	$db->ExecuteMaster("UPDATE bucket SET updated_at=UNIX_TIMESTAMP()");
}

// ===========================================================================
// Add `is_default` field to `bucket`

function _700_migrate_inbox_buckets(&$params, $group_inboxes) {
	$filtered_group_ids = array();
	$replace_inboxes = array();
	$changed = false;
	
	// Find the referenced group IDs
	foreach($params as $param_key => &$param) {
		if($param instanceof DevblocksSearchCriteria) {
			switch($param->field) {
				case 't_group_id':
					if(is_array($param->value)) {
						$filtered_group_ids = array_merge($filtered_group_ids, $param->value);
					}
					break;
			}
			
		} elseif (is_array($param) && isset($param['field'])) {
			switch($param['field']) {
				case 't_group_id':
					if(is_array($param['value'])) {
						$filtered_group_ids = array_merge($filtered_group_ids, $param['value']);
					}
					break;
			}
		}
	}
	
	foreach($filtered_group_ids as $group_id)
		if(!isset($replace_inboxes[$group_id]) && isset($group_inboxes[$group_id]))
			$replace_inboxes[$group_id] = $group_inboxes[$group_id];
	
	foreach($params as $param_key => &$param) {
		if($param instanceof DevblocksSearchCriteria) {
			switch($param->field) {
				case 't_bucket_id':
					if(is_array($param->value))
					foreach($param->value as $idx => $bucket_id) {
						if(0 == $bucket_id) {
							unset($param->value[$idx]);
							$param->value = array_merge($param->value, $replace_inboxes);
							$changed = true;
						}
					}
					break;
			}
			
		} elseif(is_array($param) && isset($param['field'])) {
			switch($param['field']) {
				case 't_bucket_id':
					if(is_array($param['value']))
					foreach($param['value'] as $idx => $bucket_id) {
						if(0 == $bucket_id) {
							unset($param['value'][$idx]);
							$param['value'] = array_merge($param['value'], $replace_inboxes);
							$changed = true;
						}
					}
					break;
			}
		}
	}
	
	return $changed;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(!isset($columns['is_default'])) {
	$db->ExecuteMaster("ALTER TABLE bucket ADD COLUMN is_default TINYINT UNSIGNED NOT NULL DEFAULT 0");
	
	// Convert virtual inbox buckets to actual records
	list($columns, $indexes) = $db->metaTable('worker_group');
	
	$group_inboxes = array();
	
	$results = $db->GetArrayMaster("SELECT id, reply_address_id, reply_personal, reply_signature, reply_html_template_id FROM worker_group");
	
	foreach($results as $row) {
		$db->ExecuteMaster(sprintf("INSERT INTO bucket (group_id, name, reply_address_id, reply_personal, reply_signature, reply_html_template_id, updated_at, is_default) ".
			"VALUES (%d, %s, %d, %s, %s, %d, %d, %d)",
			$row['id'],
			$db->qstr('Inbox'),
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
	}

	$db->ExecuteMaster("ALTER TABLE worker_group DROP COLUMN reply_address_id, DROP COLUMN reply_personal, DROP COLUMN reply_signature, DROP COLUMN reply_html_template_id");
	
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
	
	// Reset worker_view_model filters
	
	$db->ExecuteMaster("DELETE FROM worker_view_model");
	
	// Migrate workspace_list filters
	
	require_once(APP_PATH . '/features/cerberusweb.core/api/dao/abstract_view.php');
	require_once(APP_PATH . '/features/cerberusweb.core/api/dao/workspace_list.php');
	
	$lists = $db->GetArrayMaster("SELECT id, list_view FROM workspace_list WHERE context = 'cerberusweb.contexts.ticket' and list_view like '%t_bucket_id%'");
	
	foreach($lists as $list) {
		$list_id = $list['id'];
		@$list_view = unserialize($list['list_view']);
		
		if(
			_700_migrate_inbox_buckets($list_view->params, $group_inboxes)
			|| _700_migrate_inbox_buckets($list_view->params_required, $group_inboxes)
		) {
			$db->ExecuteMaster(sprintf("UPDATE workspace_list SET list_view = %s WHERE id = %d",
				$db->qstr(serialize($list_view)),
				$list_id
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

// ===========================================================================
// Add the `worker_to_bucket` table

if(!isset($tables['worker_to_bucket'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS worker_to_bucket (
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			bucket_id INT UNSIGNED NOT NULL DEFAULT 0,
			responsibility_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (worker_id, bucket_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['worker_to_bucket'] = 'worker_to_bucket';
	
	// Default all current group members to 50% responsibility
	
	$sql = $db->ExecuteMaster("INSERT INTO worker_to_bucket (worker_id, bucket_id, responsibility_level) ".
		"SELECT wtg.worker_id, b.id AS bucket_id, 50 AS responsibility_level ".
		"FROM worker_to_group wtg ".
		"INNER JOIN bucket b ON (b.group_id=wtg.group_id)"
	);
}

// ===========================================================================
// Add `activity_point` and `entry_json` to `notification`

if(!isset($tables['notification'])) {
	$logger->error("The 'notification' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('notification');

if(!isset($columns['entry_json'])) {
	$db->ExecuteMaster("ALTER TABLE notification ADD COLUMN entry_json TEXT");
}

if(!isset($columns['activity_point'])) {
	$db->ExecuteMaster("ALTER TABLE notification ADD COLUMN activity_point VARCHAR(255) NOT NULL DEFAULT '', ADD INDEX (activity_point)");
	
	// Convert existing notifications to being activity-based using the activity log
	$db->ExecuteMaster("INSERT INTO notification (created_date, worker_id, is_read, context, context_id, activity_point, entry_json) ".
		"SELECT n.created_date, n.worker_id, 0, n.context, n.context_id, al.activity_point, al.entry_json ".
		"FROM notification n ".
		"INNER JOIN context_activity_log al ON (n.context=al.target_context AND n.context_id=al.target_context_id) ".
		"WHERE n.is_read = 0 AND al.created = n.created_date"
	);
	
	// Delete the old notifications
	$db->ExecuteMaster("DELETE FROM notification WHERE activity_point = ''");
}

if(isset($columns['message'])) {
	$db->ExecuteMaster("ALTER TABLE notification DROP COLUMN message");
}

if(isset($columns['url'])) {
	$db->ExecuteMaster("ALTER TABLE notification DROP COLUMN url");
}

// ===========================================================================
// Rename `pop3_account` to `mailbox`

if(isset($tables['pop3_account'])) {
	$sql = "RENAME TABLE pop3_account TO mailbox";
	$db->ExecuteMaster($sql);

	unset($tables['pop3_account']);
	$tables['mailbox'] = 'mailbox';
	
	$sql = "ALTER TABLE mailbox CHANGE COLUMN nickname name VARCHAR(255) NOT NULL DEFAULT ''";
	$db->ExecuteMaster($sql);
	
	$sql = "ALTER TABLE mailbox ADD COLUMN updated_at INT UNSIGNED NOT NULL DEFAULT 0";
	$db->ExecuteMaster($sql);
	
	// Re-insert the mailbox cronjob
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cron.pop3'");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'enabled', '1')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'duration', '5')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'term', 'm')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'lastrun', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'locked', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'max_messages', '50')");
}

// ===========================================================================
// Check that cron.mailbox is configured in the scheduler (a bug prevented this on some installs)

if(0 == $db->GetOneMaster("SELECT COUNT(*) FROM cerb_property_store WHERE extension_id = 'cron.mailbox'")) {
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'enabled', '1')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'duration', '5')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'term', 'm')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'lastrun', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'locked', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mailbox', 'max_messages', '50')");
}

// ===========================================================================
// Drop RSS table

if(isset($tables['view_rss'])) {
	$db->ExecuteMaster("DROP TABLE view_rss");
	unset($tables['view_rss']);
}

// ===========================================================================
// Add 'options_json' to worker_view_model

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['options_json'])) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model ADD COLUMN options_json TEXT AFTER title");
}

// ===========================================================================
// Add the `csrf_token` field to `community_session`

if(!isset($tables['community_session'])) {
	$logger->error("The 'community_session' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('community_session');

if(!isset($columns['csrf_token'])) {
	$db->ExecuteMaster("ALTER TABLE community_session ADD COLUMN csrf_token VARCHAR(255) NOT NULL DEFAULT ''");
	$db->ExecuteMaster("DELETE FROM community_session");
}

// ===========================================================================
// Clear unused view models

$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id = 'messages'");

// ===========================================================================
// Fix stale group filtering on messages worklists

$sql = "SELECT worker_id, view_id, params_required_json FROM worker_view_model WHERE class_name = 'View_Message'";
$results = $db->GetArrayMaster($sql);

if(is_array($results))
foreach($results as $result) {
	if(false == ($params_required = json_decode($result['params_required_json'], true)))
		continue;
	
	if(isset($params_required['t_group_id']) && !isset($params_required['*_in_groups_of_worker'])) {
		$worker_id = intval($result['worker_id']);
		$view_id = $result['view_id'];
		
		// Add a new dynamic filter based on the worker
		$params_required['*_in_groups_of_worker'] = array(
			'field' => '*_in_groups_of_worker',
			'operator' => '=',
			'value' => $worker_id,
		);
		
		// Nuke the old static filter
		unset($params_required['t_group_id']);
		
		// Update the model
		$db->ExecuteMaster(sprintf("UPDATE worker_view_model SET params_required_json = %s WHERE view_id = %s AND worker_id = %d",
			$db->qstr(json_encode($params_required)),
			$db->qstr($view_id),
			$worker_id
		));
	}
}

// ===========================================================================
// Finish up

return TRUE;
