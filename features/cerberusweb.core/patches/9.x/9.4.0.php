<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Update package library

$packages = [
	'cerb_workspace_page_home.json',
	'card_widget/cerb_card_widget_address_tickets.json',
	'card_widget/cerb_card_widget_attachments_viewer.json',
	'card_widget/cerb_card_widget_bucket_tickets.json',
	'card_widget/cerb_card_widget_conversation.json',
	'card_widget/cerb_card_widget_fields.json',
	'card_widget/cerb_card_widget_gpg_public_key_subkeys.json',
	'card_widget/cerb_card_widget_gpg_public_key_uids.json',
	'card_widget/cerb_card_widget_group_tickets.json',
	'card_widget/cerb_card_widget_org_tickets.json',
	'card_widget/cerb_card_widget_snippet_content.json',
	'card_widget/cerb_card_widget_worker_tickets.json',
	'cerb_connected_service_google.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Add `email_signature.signature_html` field

list($columns,) = $db->metaTable('email_signature');

if(!isset($columns['signature_html'])) {
	$sql = "ALTER TABLE email_signature ADD COLUMN signature_html TEXT AFTER signature";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `comment.is_markdown` field

list($columns,) = $db->metaTable('comment');

if(!isset($columns['is_markdown'])) {
	$sql = "ALTER TABLE comment ADD COLUMN is_markdown TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove deprecated worker preferences

$sql = sprintf("DELETE FROM worker_pref WHERE setting IN (%s,%s)",
	$db->qstr('mail_reply_textbox_size_auto'),
	$db->qstr('mail_reply_textbox_size_px')
);
$db->ExecuteMaster($sql);

// ===========================================================================
// Drop plugin library tables

if(array_key_exists('plugin_library', $tables)) {
	$db->ExecuteMaster("DROP TABLE plugin_library");

	// Drop retired plugins
	
	$migrate940_recursiveDelTree = function($dir) use (&$migrate940_recursiveDelTree) {
		if(file_exists($dir) && is_dir($dir)) {
			$files = glob($dir . '*', GLOB_MARK);
			foreach($files as $file) {
				if(is_dir($file)) {
					$migrate940_recursiveDelTree($file);
				} else {
					unlink($file);
				}
			}
			
			if(file_exists($dir) && is_dir($dir))
				rmdir($dir);
		}
	};
	
	$migrated_plugins = [
		'cerb.legacy.print',
		'cerb.legacy.profile.attachments',
		'cerb.profile.ticket.moveto',
		'cerberusweb.calls',
		'cerberusweb.datacenter.domains',
		'cerberusweb.datacenter.sensors',
		'cerberusweb.datacenter.servers',
		'cerberusweb.feed_reader',
		'wgm.jira',
		'wgm.ldap',
		'wgm.notifications.emailer',
		'wgm.storage.s3.gatekeeper',
		'wgm.twitter',
	];
	
	foreach($migrated_plugins as $plugin_id) {
		$dir = APP_STORAGE_PATH . '/plugins/' . $plugin_id . '/';
		
		if(file_exists($dir) && is_dir($dir))
			$migrate940_recursiveDelTree($dir);
	}
}

if(array_key_exists('fulltext_plugin_library', $tables))
	$db->ExecuteMaster("DROP TABLE fulltext_plugin_library");

// ===========================================================================
// Confirm utf8mb4 encoding with better tests than 9.2

if(!isset($tables['comment']))
	return FALSE;

list($columns,) = $db->metaTable('comment');

if(!array_key_exists('comment', $columns))
	return FALSE;

if('utf8mb4_unicode_ci' != $columns['comment']['collation']) {
	$db->ExecuteMaster("ALTER TABLE comment MODIFY COLUMN comment MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE comment");
	$db->ExecuteMaster("OPTIMIZE TABLE comment");
}

if(!isset($tables['ticket']))
	return FALSE;

list($columns,) = $db->metaTable('ticket');

if('utf8mb4_unicode_ci' != $columns['subject']['collation']) {
	$db->ExecuteMaster("ALTER TABLE ticket MODIFY COLUMN subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE ticket");
	$db->ExecuteMaster("OPTIMIZE TABLE ticket");
}

if(!isset($tables['worker']))
	return FALSE;

list($columns,) = $db->metaTable('worker');

if('utf8mb4_unicode_ci' != $columns['location']['collation']) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE worker");
	$db->ExecuteMaster("OPTIMIZE TABLE worker");
}

if('utf8mb4_unicode_ci' != $columns['title']['collation']) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE worker");
	$db->ExecuteMaster("OPTIMIZE TABLE worker");
}

// ===========================================================================
// Add the `card_widget` table

if(!isset($tables['card_widget'])) {
	$sql = sprintf("
		CREATE TABLE `card_widget` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`record_type` varchar(255) NOT NULL DEFAULT '',
		`extension_id` varchar(255) NOT NULL DEFAULT '',
		`extension_params_json` TEXT,
		`created_at` int(10) unsigned NOT NULL DEFAULT '0',
		`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
		`pos` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`width_units` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`zone` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		KEY (record_type),
		KEY (extension_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['card_widget'] = 'card_widget';
}

// ===========================================================================
// Migrate record settings to card widgets

$results = $db->GetArrayMaster('SELECT * FROM devblocks_setting WHERE setting LIKE "card:%"');

if($results) {
	$card_prefs = [];
	
	foreach($results as $result) {
		$parts = explode(':', $result['setting'], 3);
		
		if(2 == count($parts)) {
			if(empty($parts[1]))
				continue;
			
			if(!array_key_exists($parts[1], $card_prefs))
				$card_prefs[$parts[1]] = [
					'fields' => [],
					'search' => [],
				];
			
			$json = str_replace(
				[
					'"custom_',
					'"__label"',
					'"owner__label"',
					'__label',
				],
				[
					'"cf_',
					'"name"',
					'"owner"',
					'_id',
				],
				$result['value']
			);
			
			$card_prefs[$parts[1]]['fields'] = json_decode($json, true);
			
		} elseif(3 == count($parts)) {
			if(empty($parts[2]))
				continue;
			
			if(!array_key_exists($parts[2], $card_prefs))
				$card_prefs[$parts[2]] = [
					'fields' => [],
					'search' => [],
				];
			
			$json = str_replace(
				[
					'{{'
				],
				[
					'{{record_'
				],
				$result['value']
			);
			
			$search = json_decode($json, true);
			
			$card_prefs[$parts[2]]['search'] = [
				'context' => array_column($search, 'context'),
				'query' => array_column($search, 'query'),
				'label_singular' => array_column($search, 'label_singular'),
				'label_plural' => array_column($search, 'label_plural'),
			];
		}
	}
	
	if(is_array($card_prefs))
	foreach($card_prefs as $card_context => $card_data) {
		$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
			"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
			$db->qstr('Properties'),
			$db->qstr($card_context),
			$db->qstr('cerb.card.widget.fields'),
			$db->qstr(json_encode([
				'context' => $card_context,
				'context_id' => '{{record_id}}',
				'properties' => [
					'0' => $card_data['fields'],
				],
				'links' => [
					'show' => 1,
				],
				'search' => $card_data['search'],
			])),
			time(),
			time(),
			0,
			4,
			$db->qstr('content')
		));
	}
	
	$db->ExecuteMaster('DELETE FROM devblocks_setting WHERE setting LIKE "card:%"');
}

// ===========================================================================
// Increase `worker_auth_hash.pass_hash` length

list($columns,) = $db->metaTable('worker_auth_hash');

if(array_key_exists('pass_hash', $columns) && 0 != strcasecmp('varchar(255)', $columns['pass_hash']['type'])) {
	$sql = "ALTER TABLE worker_auth_hash MODIFY COLUMN pass_hash VARCHAR(255) DEFAULT ''";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add indexes for `ticket.first_message_id` and `ticket.last_message_id`

list(,$indexes) = $db->metaTable('ticket');

$changes = [];

if(!array_key_exists('last_wrote_address_id', $indexes))
	$changes[] = 'ADD INDEX (last_wrote_address_id)';

if(!array_key_exists('first_message_id', $indexes))
	$changes[] = 'ADD INDEX (first_message_id)';

if(!array_key_exists('last_message_id', $indexes))
	$changes[] = 'ADD INDEX (last_message_id)';

if($changes)
	$db->ExecuteMaster('ALTER TABLE ticket '. implode(', ', $changes));

// ===========================================================================
// Drop `mail_queue.body`

list($columns,) = $db->metaTable('mail_queue');

if(array_key_exists('subject', $columns) && !array_key_exists('name', $columns)) {
	$sql = "ALTER TABLE mail_queue CHANGE COLUMN subject name varchar(255) not null default ''";
	$db->ExecuteMaster($sql);
}

if(array_key_exists('body', $columns)) {
	$sql = "ALTER TABLE mail_queue DROP COLUMN body";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("DELETE FROM worker_view_model WHERE class_name = 'View_MailQueue'");
}

// ===========================================================================
// Drop skills and skillsets

if(array_key_exists('context_to_skill', $tables)) {
	$db->ExecuteMaster('DROP TABLE context_to_skill');
}

if(array_key_exists('skillset', $tables)) {
	$db->ExecuteMaster('DROP TABLE skillset');
}

if(array_key_exists('skill', $tables)) {
	$db->ExecuteMaster('DROP TABLE skill');
}

// ===========================================================================
// Migrate feedback entries to a custom record

if(array_key_exists('feedback_entry', $tables)) {
	if($db->GetOneMaster("SELECT count(id) FROM feedback_entry") > 0) {
		$db->ExecuteMaster(sprintf("INSERT INTO custom_record (name, name_plural, uri, params_json, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d)",
			$db->qstr('Feedback'),
			$db->qstr('Feedback'),
			$db->qstr('feedback'),
			$db->qstr(json_encode([
				'owners' => [
					'contexts' => [
						'cerberusweb.contexts.app',
					],
					'options' => [
						'comments',
					],
				],
			])),
			time()
		));
		
		$custom_record_id = $db->LastInsertId();
		$custom_record_ctx_id = sprintf('contexts.custom_record.%d', $custom_record_id);
		$custom_record_table = sprintf('custom_record_%d', $custom_record_id);
		
		$sql = sprintf("
			CREATE TABLE `%s` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) DEFAULT '',
			`owner_context` varchar(255) DEFAULT '',
			`owner_context_id` int(10) unsigned NOT NULL DEFAULT '0',
			`created_at` int(10) unsigned NOT NULL DEFAULT '0',
			`updated_at` int(10) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`),
			KEY `created_at` (`created_at`),
			KEY `updated_at` (`updated_at`),
			KEY `owner` (`owner_context`,`owner_context_id`)
			) ENGINE=%s
			",
			$custom_record_table,
			APP_DB_ENGINE
		);
		$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
		
		$tables[$custom_record_table] = $custom_record_table;
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field (name, type, pos, params_json, context, custom_fieldset_id, updated_at) " .
			"VALUES (%s, %s, %d, %s, %s, %d, %d)",
			$db->qstr('Quote Text'),
			$db->qstr('T'),
			1,
			$db->qstr(json_encode([])),
			$db->qstr($custom_record_ctx_id),
			0,
			time()
		));
		$cfield_id_quote_text = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field (name, type, pos, params_json, context, custom_fieldset_id, updated_at) " .
			"VALUES (%s, %s, %d, %s, %s, %d, %d)",
			$db->qstr('Quote Mood'),
			$db->qstr('D'),
			2,
			$db->qstr(json_encode([
				'options' => [
					'Praise',
					'Neutral',
					'Criticism',
				],
			])),
			$db->qstr($custom_record_ctx_id),
			0,
			time()
		));
		$cfield_id_quote_mood = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field (name, type, pos, params_json, context, custom_fieldset_id, updated_at) " .
			"VALUES (%s, %s, %d, %s, %s, %d, %d)",
			$db->qstr('Quote Author'),
			$db->qstr('L'),
			3,
			$db->qstr(json_encode([
				'context' => 'cerberusweb.contexts.address',
			])),
			$db->qstr($custom_record_ctx_id),
			0,
			time()
		));
		$cfield_id_quote_author = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field (name, type, pos, params_json, context, custom_fieldset_id, updated_at) " .
			"VALUES (%s, %s, %d, %s, %s, %d, %d)",
			$db->qstr('Source URL'),
			$db->qstr('U'),
			4,
			$db->qstr(json_encode([])),
			$db->qstr($custom_record_ctx_id),
			0,
			time()
		));
		$cfield_id_source_url = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field (name, type, pos, params_json, context, custom_fieldset_id, updated_at) " .
			"VALUES (%s, %s, %d, %s, %s, %d, %d)",
			$db->qstr('Worker'),
			$db->qstr('W'),
			5,
			$db->qstr(json_encode([])),
			$db->qstr($custom_record_ctx_id),
			0,
			time()
		));
		$cfield_id_worker = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("INSERT INTO %s (id, name, owner_context, owner_context_id, created_at, updated_at) " .
			"SELECT id, CASE WHEN length(quote_text) > 128 THEN concat(substring(quote_text,1,125),'...') ELSE quote_text END AS name, 'cerberusweb.contexts.app', 0, log_date, log_date FROM feedback_entry",
			$db->escape($custom_record_table)
		));
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field_clobvalue (field_id, context_id, field_value, context) " .
			"SELECT %d, id, quote_text, %s FROM feedback_entry",
			$cfield_id_quote_text,
			$db->qstr($custom_record_ctx_id)
		));
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field_stringvalue (field_id, context_id, field_value, context) " .
			"SELECT %d, id, CASE WHEN quote_mood = 0 THEN 'Neutral' WHEN quote_mood = 1 THEN 'Praise' WHEN quote_mood = 2 THEN 'Criticism' END AS quote_mood, %s FROM feedback_entry",
			$cfield_id_quote_mood,
			$db->qstr($custom_record_ctx_id)
		));
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field_numbervalue (field_id, context_id, field_value, context) " .
			"SELECT %d, id, quote_address_id, %s FROM feedback_entry",
			$cfield_id_quote_author,
			$db->qstr($custom_record_ctx_id)
		));
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field_stringvalue (field_id, context_id, field_value, context) " .
			"SELECT %d, id, source_url, %s FROM feedback_entry",
			$cfield_id_source_url,
			$db->qstr($custom_record_ctx_id)
		));
		
		$db->ExecuteMaster(sprintf("INSERT INTO custom_field_numbervalue (field_id, context_id, field_value, context) " .
			"SELECT %d, id, worker_id, %s FROM feedback_entry",
			$cfield_id_worker,
			$db->qstr($custom_record_ctx_id)
		));
		
		// Migrate contexts
		$db->ExecuteMaster(sprintf("UPDATE attachment_link SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE card_widget SET record_type = 'contexts.custom_record.%d' WHERE record_type = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE comment SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_activity_log SET target_context = 'contexts.custom_record.%d' WHERE target_context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_alias SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_avatar SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_bulk_update SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_link SET from_context = 'contexts.custom_record.%d' WHERE from_context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_link SET to_context = 'contexts.custom_record.%d' WHERE to_context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_merge_history SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_saved_search SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_scheduled_behavior SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE context_to_custom_fieldset SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE custom_field SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE custom_field_clobvalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE custom_field_numbervalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE custom_field_stringvalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE notification SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE snippet SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE workspace_list SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.feedback'", $custom_record_id));
		
		$db->ExecuteMaster(sprintf("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d')", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = REPLACE(params_json, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d')", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET setting = REPLACE(setting, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d') WHERE setting LIKE '%%cerberusweb.contexts.feedback%%'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET value = REPLACE(value, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d') WHERE value LIKE '%%cerberusweb.contexts.feedback%%'", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE notification SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d')", $custom_record_id));
		$db->ExecuteMaster(sprintf("UPDATE workspace_widget SET params_json = REPLACE(params_json, 'cerberusweb.contexts.feedback', 'contexts.custom_record.%d')", $custom_record_id));
		
		$db->ExecuteMaster("DELETE FROM worker_view_model WHERE class_name = 'View_FeedbackEntry'");
	}
	
	$db->ExecuteMaster('DROP TABLE feedback_entry');
	
	unset($tables['feedback_entry']);
}

// ===========================================================================
// Fix profile tab comments

$db->ExecuteMaster("UPDATE profile_widget SET extension_params_json=replace(extension_params_json,'{[record_id}}','{{record_id}}') WHERE extension_params_json like '%{[record_id}}%'");

// ===========================================================================
// Finish up

return TRUE;
