<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Consolidate ticket status fields

if(!isset($tables['ticket'])) {
	$logger->error("The 'ticket' table does not exist.");
	return FALSE;
}

list(, $indexes) = $db->metaTable('ticket');

if(!array_key_exists('closed_at', $indexes))
	$db->ExecuteMaster('ALTER TABLE ticket ADD INDEX closed_at (closed_at)');

// ===========================================================================
// Add `message_html_cache` table

if(!isset($tables['message_html_cache'])) {
	$sql = sprintf("
		CREATE TABLE `message_html_cache` (
		`message_id` int(10) unsigned NOT NULL,
		`expires_at` int(10) unsigned NOT NULL DEFAULT 0,
		`html_content` mediumtext character set utf8mb4,
		PRIMARY KEY (message_id),
		INDEX (expires_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['message_html_cache'] = 'message_html_cache';
	
} else {
	list($columns,) = $db->metaTable('message_html_cache');
	
	if('utf8_general_ci' == $columns['html_content']['collation']) {
		$db->ExecuteMaster("DELETE FROM message_html_cache");
		$db->ExecuteMaster("ALTER TABLE message_html_cache MODIFY COLUMN html_content MEDIUMTEXT CHARACTER SET utf8mb4");
	}
}

// ===========================================================================
// Update `message`

if(!isset($tables['message']))
	return FALSE;

list($columns,) = $db->metaTable('message');

// Add token column
if(!array_key_exists('token', $columns)) {
	$db->ExecuteMaster("ALTER TABLE message ADD COLUMN token VARCHAR(16) NOT NULL DEFAULT '', ADD INDEX token (token(4))");
}

// ===========================================================================
// Update `mail_queue`

if(!isset($tables['mail_queue']))
	return FALSE;

list($columns,) = $db->metaTable('mail_queue');

if(!array_key_exists('name', $columns))
	return FALSE;

// Convert `mail_queue.name` to utf8mb4
if('utf8_general_ci' == $columns['name']['collation']) {
	$db->ExecuteMaster("ALTER TABLE mail_queue MODIFY COLUMN name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE mail_queue");
	$db->ExecuteMaster("OPTIMIZE TABLE mail_queue");
}

// Add token column
if(!array_key_exists('token', $columns)) {
	$db->ExecuteMaster("ALTER TABLE mail_queue ADD COLUMN token VARCHAR(16) NOT NULL DEFAULT '', ADD INDEX token (token(4))");
	$db->ExecuteMaster("UPDATE mail_queue SET token = substr(sha1(concat(id,ifnull(name,''),worker_id,ifnull(params_json,''),rand())),1,10) WHERE token = ''");
}

// ===========================================================================
// Add `custom_field.uri`

list($columns,) = $db->metaTable('custom_field');

if(!array_key_exists('uri', $columns)) {
	$sql = "ALTER TABLE custom_field ADD COLUMN uri VARCHAR(128) NOT NULL DEFAULT '', ADD INDEX (uri)";
	$db->ExecuteMaster($sql);
	
	// Generate aliases for existing custom fields
	
	$fields = $db->GetArrayMaster("select id, name, (select name from custom_fieldset where id = custom_field.custom_fieldset_id) as custom_fieldset from custom_field");
	
	foreach($fields as $field) {
		$field_key = sprintf("%s%s",
			$field['custom_fieldset'] ? (DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['custom_fieldset'], MB_CASE_TITLE))) . '_') : '',
			DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['name'], MB_CASE_TITLE)))
		);
		
		$db->ExecuteMaster(sprintf("UPDATE custom_field SET uri = %s WHERE id = %d",
			$db->qstr($field_key),
			$field['id']
		));
	}
}

// ===========================================================================
// Add `connected_account.uri`

list($columns,) = $db->metaTable('connected_account');

if(!array_key_exists('uri', $columns)) {
	$sql = "ALTER TABLE connected_account ADD COLUMN uri VARCHAR(128) NOT NULL DEFAULT '', ADD INDEX (uri)";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Disable behaviors on retired events

$db->ExecuteMaster("UPDATE trigger_event SET is_disabled = 1 WHERE event_point = 'event.api.mobile_behavior'");
$db->ExecuteMaster("UPDATE trigger_event SET is_disabled = 1 WHERE event_point = 'event.message.chat.mobile.worker'");

// ===========================================================================
// Disable behaviors on disabled bots

$db->ExecuteMaster("UPDATE trigger_event SET is_disabled = 1 WHERE bot_id IN (SELECT id FROM bot WHERE bot.is_disabled = 1)");

// ===========================================================================
// Add `automation` table

$automation_files = [];

if(!isset($tables['automation'])) {
	$sql = sprintf("
		CREATE TABLE `automation` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`extension_id` varchar(255) NOT NULL DEFAULT '',
		`extension_params_json` mediumtext,
		`is_unlisted` tinyint(1) unsigned NOT NULL DEFAULT 0,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`script` mediumtext,
		`policy_kata` text,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (extension_id),
		INDEX (is_unlisted),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation'] = 'automation';

	// ===========================================================================
	// Update automations
	
	$automation_files = [
		'ai.cerb.automationBuilder.action.dataQuery.json',
		'ai.cerb.automationBuilder.action.emailParser.json',
		'ai.cerb.automationBuilder.action.function.json',
		'ai.cerb.automationBuilder.action.httpRequest.json',
		'ai.cerb.automationBuilder.action.pgpDecrypt.json',
		'ai.cerb.automationBuilder.action.pgpEncrypt.json',
		'ai.cerb.automationBuilder.action.recordCreate.json',
		'ai.cerb.automationBuilder.action.recordDelete.json',
		'ai.cerb.automationBuilder.action.recordGet.json',
		'ai.cerb.automationBuilder.action.recordSearch.json',
		'ai.cerb.automationBuilder.action.recordUpdate.json',
		'ai.cerb.automationBuilder.action.recordUpsert.json',
		'ai.cerb.automationBuilder.action.storageDelete.json',
		'ai.cerb.automationBuilder.action.storageGet.json',
		'ai.cerb.automationBuilder.action.storageSet.json',
		'ai.cerb.automationBuilder.help.json',
		'ai.cerb.automationBuilder.input.array.json',
		'ai.cerb.automationBuilder.input.record.json',
		'ai.cerb.automationBuilder.input.records.json',
		'ai.cerb.automationBuilder.input.text.json',
		'ai.cerb.automationBuilder.interaction.worker.await.map.json',
		'ai.cerb.automationBuilder.interaction.worker.await.promptEditor.json',
		'ai.cerb.automationBuilder.interaction.worker.await.promptSheet.json',
		'ai.cerb.automationBuilder.interaction.worker.await.promptText.json',
		'ai.cerb.automationBuilder.interaction.worker.await.say.json',
		'ai.cerb.cardEditor.automation.triggerChooser.json',
		'ai.cerb.editor.mapBuilder.json',
		'ai.cerb.eventHandler.automation.json',
		'ai.cerb.timerEditor.schedule.add',
		'ai.cerb.toolbarBuilder.interaction.json',
		'ai.cerb.toolbarBuilder.menu.json',
		'cerb.data.platform.extensions.json',
		'cerb.data.record.fields.json',
		'cerb.data.record.types.json',
		'cerb.data.records.json',
		'cerb.data.ui.icons.json',
		'cerb.editor.toolbar.indentSelection.json',
		'cerb.editor.toolbar.markdownImage.json',
		'cerb.editor.toolbar.markdownLink.json',
		'cerb.editor.toolbar.markdownTable.json',
		'cerb.editor.toolbar.wrapSelection.json',
		'cerb.mail.compose.json',
		'cerb.mailFilterBlacklist.json',
		'cerb.mailRouting.moveToGroup.json',
		'cerb.mailRouting.recipientRules.json',
		'cerb.map.clicked.sheet.json',
		'cerb.projectBoard.action.task.close.json',
		'cerb.projectBoard.card.done.json',
		'cerb.projectBoard.card.sheet.json',
		'cerb.projectBoard.record.create.json',
		'cerb.projectBoard.toolbar.task.find.json',
		'cerb.reminder.remind.notification.json',
		'cerb.ticket.move.json',
		'cerb.ticket.participants.add.json',
		'cerb.ticket.participants.manage.json',
		'cerb.ticket.participants.remove.json',
	];
	
} else {
	// ===========================================================================
	// Rename 10.0-beta automations
	
	$db->ExecuteMaster("UPDATE automation SET extension_id = 'cerb.trigger.interaction.worker' WHERE extension_id = 'cerb.trigger.interaction.web.worker'");
	$db->ExecuteMaster("UPDATE automation SET name = REPLACE(name, 'interaction.web.worker', 'interaction.worker') WHERE name LIKE '%interaction.web.worker%'");
	$db->ExecuteMaster("UPDATE automation SET script = REPLACE(script, 'interaction.web.worker', 'interaction.worker') WHERE script LIKE '%interaction.web.worker%'");
	$db->ExecuteMaster("UPDATE automation SET policy_kata = REPLACE(policy_kata, 'interaction.web.worker', 'interaction.worker') WHERE policy_kata LIKE '%interaction.web.worker%'");
	
	$automation_files = [
		'ai.cerb.automationBuilder.action.function.json',
		'ai.cerb.automationBuilder.action.httpRequest.json',
		'ai.cerb.automationBuilder.action.pgpDecrypt.json',
		'ai.cerb.automationBuilder.action.pgpEncrypt.json',
		'ai.cerb.automationBuilder.action.recordCreate.json',
		'ai.cerb.automationBuilder.action.recordDelete.json',
		'ai.cerb.automationBuilder.action.recordGet.json',
		'ai.cerb.automationBuilder.action.recordSearch.json',
		'ai.cerb.automationBuilder.action.recordUpdate.json',
		'ai.cerb.automationBuilder.action.recordUpsert.json',
		'ai.cerb.automationBuilder.action.storageDelete.json',
		'ai.cerb.automationBuilder.action.storageGet.json',
		'ai.cerb.automationBuilder.action.storageSet.json',
		'ai.cerb.automationBuilder.help.json',
		'ai.cerb.automationBuilder.input.record.json',
		'ai.cerb.automationBuilder.input.records.json',
		'ai.cerb.automationBuilder.interaction.worker.await.promptSheet.json',
		'ai.cerb.cardEditor.automation.triggerChooser.json',
		'ai.cerb.editor.mapBuilder.json',
		'ai.cerb.eventHandler.automation.json',
		'ai.cerb.timerEditor.schedule.add',
		'ai.cerb.toolbarBuilder.interaction.json',
		'ai.cerb.toolbarBuilder.menu.json',
		'cerb.data.platform.extensions.json',
		'cerb.data.record.fields.json',
		'cerb.data.record.types.json',
		'cerb.data.records.json',
		'cerb.data.ui.icons.json',
		'cerb.mailFilterBlacklist.json',
		'cerb.mailRouting.moveToGroup.json',
		'cerb.mailRouting.recipientRules.json',
		'cerb.map.clicked.sheet.json',
		'cerb.projectBoard.record.create.json',
		'cerb.projectBoard.toolbar.task.find.json',
		'cerb.ticket.move.json',
		'cerb.ticket.participants.add.json',
		'cerb.ticket.participants.manage.json',
	];
}

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Add `automation_datastore` table

if(!isset($tables['automation_datastore'])) {
	$sql = sprintf("
		CREATE TABLE `automation_datastore` (
		`data_key` varchar(255) NOT NULL DEFAULT '',
		`data_value` mediumtext,
		`expires_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (`data_key`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['automation_datastore'] = 'automation_datastore';
}

// ===========================================================================
// Add `automation_continuation` table

if(!isset($tables['automation_continuation'])) {
	$sql = sprintf("
	CREATE TABLE `automation_continuation` (
		token varchar(64) NOT NULL DEFAULT '',
		uri varchar(255) NOT NULL DEFAULT '',
		state varchar(8) NOT NULL DEFAULT '',
		state_data mediumtext,
		parent_token varchar(64) NOT NULL DEFAULT '',
		root_token varchar(64) NOT NULL DEFAULT '',
		expires_at int(10) unsigned NOT NULL DEFAULT 0,
		updated_at int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (token),
		INDEX parent_token (parent_token(4)),
		INDEX root_token (root_token(4)),
		INDEX (uri),
		INDEX (state),
		INDEX (expires_at),
		INDEX (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_continuation'] = 'automation_continuation';
}

// ===========================================================================
// Add `automation_event` table

if(!isset($tables['automation_event'])) {
	$sql = sprintf("
		CREATE TABLE `automation_event` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		description varchar(255) NOT NULL DEFAULT '',
		extension_id varchar(255) NOT NULL DEFAULT '',
		automations_kata text,
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_event'] = 'automation_event';
	
	// =====================
	// Insert default events
	
	// mail.filter
	
	$automations_kata = '';
	
	$sql = "SELECT id, title, uri, is_disabled FROM trigger_event WHERE event_point = 'event.mail.received.app' ORDER BY priority, id";
	$behaviors = $db->GetArrayMaster($sql);
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$automations_kata .= sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%d\n%s\n",
				$behavior['title'],
				$behavior['uri'] ?: uniqid(),
				$behavior['id'],
				$behavior['is_disabled'] ? "  disabled@bool: yes\n" : "  # disabled@bool: yes\n",
			);
		}
	}
	
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('mail.filter'),
		$db->qstr('cerb.trigger.mail.filter'),
		$db->qstr('Modify or reject inbound mail before it\'s accepted'),
		$db->qstr($automations_kata),
		time()
	));
	
	// cerb.trigger.mail.route
	
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, updated_at) VALUES (%s,%s,%s,%d)',
		$db->qstr('mail.route'),
		$db->qstr('cerb.trigger.mail.route'),
		$db->qstr('Route accepted inbound mail to a group inbox'),
		time()
	));
	
	// record.changed
	
	$automations_kata = '';
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.record.changed' ORDER BY priority, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata .= sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				sprintf("{{record__context is not record type ('%s')}}", $behavior_params['context'])
			);
		}
	}
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.task.created.worker' ORDER BY priority, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata .= sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('task') or not is_new}}"
			);
		}
	}
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.comment.created.worker' ORDER BY priority, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata .= sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('comment') or not is_new}}"
			);
		}
	}
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.comment.ticket.group' ORDER BY priority, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata .= sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('comment') or record_target__type is not record type ('ticket') or not is_new}}"
			);
		}
	}
	
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('record.changed'),
		$db->qstr('cerb.trigger.record.changed'),
		$db->qstr('Actions in response to changes in record field values'),
		$db->qstr($automations_kata),
		time()
	));
	
} else {
	// ===========================================================================
	// Rename 10.0-beta automation events
	
	$db->ExecuteMaster("UPDATE automation_event SET automations_kata = REPLACE(automations_kata, 'interaction.web.worker', 'interaction.worker') WHERE automations_kata LIKE '%interaction.web.worker%'");
}

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'record.merge'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('record.merge'),
		$db->qstr('cerb.trigger.record.merge'),
		$db->qstr('Approve or deny record merge requests'),
		$db->qstr(''),
		time()
	));
}

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'reminder.remind'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('reminder.remind'),
		$db->qstr('cerb.trigger.reminder.remind'),
		$db->qstr('Send notifications about a reminder'),
		$db->qstr("automation/notification:\n  uri: cerb:automation:cerb.reminder.remind.notification\n"),
		time()
	));
}

// ===========================================================================
// Add `automation_timer` table

if(!isset($tables['automation_timer'])) {
	$sql = sprintf("
		CREATE TABLE `automation_timer` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		automations_kata text,
		is_disabled tinyint(1) NOT NULL DEFAULT '0',
		last_ran_at int(10) unsigned NOT NULL DEFAULT '0',
		next_run_at int(10) unsigned NOT NULL DEFAULT '0',
		is_recurring tinyint(1) NOT NULL DEFAULT '0',
		recurring_patterns mediumtext,
		recurring_timezone varchar(255) NOT NULL DEFAULT '',
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		continuation_id varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		INDEX (next_run_at),
		INDEX continuation_id (continuation_id(4)),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_timer'] = 'automation_timer';
	
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.automations', 'enabled', '1')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.automations', 'duration', '2')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.automations', 'term', 'm')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.automations', 'lastrun', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.automations', 'locked', '0')");
	
} else {
	list($columns,) = $db->metaTable('automation_timer');
	
	$changes = [];
	
	if(!array_key_exists('last_ran_at', $columns)) {
		$changes[] = "ADD COLUMN last_ran_at int(10) unsigned NOT NULL DEFAULT '0'";
	}
	
	if(array_key_exists('resume_at', $columns)) {
		$changes[] = "CHANGE COLUMN resume_at next_run_at int(10) unsigned NOT NULL DEFAULT '0'";
	}
	
	if(!array_key_exists('is_disabled', $columns)) {
		$changes[] = "ADD COLUMN is_disabled tinyint(1) NOT NULL DEFAULT '0'";
	}
	
	if(!array_key_exists('is_recurring', $columns)) {
		$changes[] = "ADD COLUMN is_recurring tinyint(1) NOT NULL DEFAULT '0'";
	}
	
	if(!array_key_exists('recurring_patterns', $columns)) {
		$changes[] = "ADD COLUMN recurring_patterns mediumtext";
	}
	
	if(!array_key_exists('recurring_timezone', $columns)) {
		$changes[] = "ADD COLUMN recurring_timezone varchar(255) NOT NULL DEFAULT ''";
	}
	
	if($changes) {
		$sql = "ALTER TABLE automation_timer " . implode(', ', $changes);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `automation_log` table

if(!isset($tables['automation_log'])) {
	$sql = sprintf("
		CREATE TABLE `automation_log` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		automation_name varchar(255) NOT NULL DEFAULT '',
		automation_node varchar(1024) NOT NULL DEFAULT '',
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		log_level tinyint(1) unsigned NOT NULL DEFAULT '7',
		log_message varchar(1024) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		INDEX (automation_name),
		INDEX (created_at),
		INDEX (log_level)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_log'] = 'automation_log';
}

// ===========================================================================
// Drop `email_signature.is_default`

list($columns,) = $db->metaTable('email_signature');

if(array_key_exists('is_default', $columns)) {
	/** @noinspection SqlResolve */
	$sql = "ALTER TABLE email_signature DROP COLUMN is_default";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove automations on reminders

list($columns,) = $db->metaTable('reminder');

if(array_key_exists('automations_kata', $columns)) {
	/** @noinspection SqlResolve */
	$sql = "ALTER TABLE reminder DROP COLUMN automations_kata";
	$db->ExecuteMaster($sql);
}

if(array_key_exists('params_json', $columns)) {
	/** @noinspection SqlResolve */
	$db->ExecuteMaster('ALTER TABLE reminder DROP COLUMN params_json');
}

// ===========================================================================
// Convert form interaction `prompt_sheet.selection_key` to sheet/selection col

$sql = "select id, params_json from decision_node where params_json like '%prompt_sheet%' and params_json like '%selection_key%' and trigger_id in (select id from trigger_event where event_point = 'event.form.interaction.worker')";
$nodes = $db->GetArrayMaster($sql);

foreach($nodes as $node) {
	$actions = json_decode($node['params_json'], true);
	$is_changed = false;
	
	foreach($actions['actions'] as $action_idx => $action) {
		if($action['action'] == 'prompt_sheet') {
			@$selection_key = $action['selection_key'];
			@$selection_mode = $action['mode'] ?: 'single';
			@$sheet_kata = $action['schema'];
			
			if($selection_key && $sheet_kata) {
				$sheet_kata = preg_replace(
					'#^columns:#m',
					sprintf("columns:\n  selection/%s:\n    params:\n      mode: %s",
						$selection_key,
						$selection_mode
					),
					$sheet_kata
				);
				
				$actions['actions'][$action_idx]['schema'] = $sheet_kata;
				unset($actions['actions'][$action_idx]['selection_key']);
				unset($actions['actions'][$action_idx]['mode']);
				$is_changed = true;
			}
		}
	}
	
	if($is_changed) {
		$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($actions)),
			$node['id']
		));
	}
}

// ===========================================================================
// Add `toolbar` table

if(!isset($tables['toolbar'])) {
	$sql = sprintf("
		CREATE TABLE `toolbar` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		extension_id varchar(255) NOT NULL DEFAULT '',
		description varchar(255) NOT NULL DEFAULT '',
		toolbar_kata mediumtext,
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (extension_id),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['toolbar'] = 'toolbar';

	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('global.menu'),
		$db->qstr('cerb.toolbar.global.menu'),
		$db->qstr('Global interactions from the floating icon in the lower right'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('mail.compose'),
		$db->qstr('cerb.toolbar.mail.compose'),
		$db->qstr('Composing new email messages'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('mail.read'),
		$db->qstr('cerb.toolbar.mail.read'),
		$db->qstr('Reading email messages'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('mail.reply'),
		$db->qstr('cerb.toolbar.mail.reply'),
		$db->qstr('Replying to email messages'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('record.card'),
		$db->qstr('cerb.toolbar.record.card'),
		$db->qstr('Viewing a record card popup'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('record.profile'),
		$db->qstr('cerb.toolbar.record.profile'),
		$db->qstr('Viewing a record profile page'),
		$db->qstr(''),
		time(),
		time()
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s, %s, %s, %s, %d, %d)",
		$db->qstr('automation.editor'),
		$db->qstr('cerb.toolbar.automation.editor'),
		$db->qstr('Editing an automation'),
		$db->qstr(''),
		time(),
		time()
	));
	
} else {
	// ===========================================================================
	// Rename 10.0-beta toolbars
	
	$db->ExecuteMaster("UPDATE toolbar SET toolbar_kata = REPLACE(toolbar_kata, 'interaction.web.worker', 'interaction.worker') WHERE toolbar.toolbar_kata LIKE '%interaction.web.worker%'");
}

// ===========================================================================
// Add `resource`

if(!isset($tables['resource'])) {
	$sql = sprintf("
		CREATE TABLE `resource` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		description varchar(255) NOT NULL DEFAULT '',
		extension_id varchar(255) NOT NULL DEFAULT '',
		expires_at int(10) unsigned NOT NULL DEFAULT 0,
		is_dynamic tinyint(1) NOT NULL DEFAULT 0,
		automation_kata text,
		storage_size int(10) unsigned NOT NULL DEFAULT '0',
		storage_key varchar(255) NOT NULL DEFAULT '',
		storage_extension varchar(255) NOT NULL DEFAULT '',
		storage_profile_id int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		UNIQUE KEY `name` (`name`),
		KEY `extension_id` (`extension_id`),
		KEY `storage_extension` (`storage_extension`),
		KEY `expires_at` (`expires_at`),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['resource'] = 'resource';

	// ===========================================================================
	// Update resources
	
	$resource_files = [
		'map.world.countries.json',
		'map.country.usa.states.json',
		'map.country.usa.counties.json',
		'mapPoints.usaStateCapitals.json',
		'mapPoints.worldCapitalCities.json',
	];
	
	foreach($resource_files as $resource_file) {
		$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/resources/') . '/' . $resource_file;
		
		if(!file_exists($path) || false === ($resource_data = json_decode(file_get_contents($path), true)))
			continue;
		
		DAO_Resource::importFromJson($resource_data);
		
		unset($resource_data);
	}
}

// ===========================================================================
// Update package library

// Delete retired packages
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_bot_reminder'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_bot_behavior_interaction_worker'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_bot_behavior_form_interaction_worker'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_bot_behavior_action_interaction_start_convo'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_profile_widget_ticket_draft_interaction'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_bot_behavior_action_ui_execute_jquery_script'");

$packages = [
	'card_widget/cerb_card_widget_address_compose.json',
	'card_widget/cerb_card_widget_contact_compose.json',
	'card_widget/cerb_card_widget_org_compose.json',
	'cerb_profile_widget_ticket_participants.json',
	'cerb_project_board_kanban.json',
	'cerb_workspace_widget_map_usa.json',
	'cerb_workspace_widget_map_world.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Finish up

return TRUE;
