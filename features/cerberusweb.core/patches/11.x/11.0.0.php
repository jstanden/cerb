<?php /** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Automation Event Listener

if(!isset($tables['automation_event_listener'])) {
	$sql = sprintf("
		CREATE TABLE `automation_event_listener` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`event_name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`event_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (event_name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_event_listener'] = 'automation_event_listener';
}

// ===========================================================================
// Drop `automation_event.automations_kata`

list($columns, ) = $db->metaTable('automation_event');

if(array_key_exists('automations_kata', $columns)) {
	// Migrate automation_event KATA to event listeners
	$db->ExecuteMaster("INSERT IGNORE INTO automation_event_listener (name, event_name, priority, created_at, updated_at, event_kata) SELECT 'Default', name, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), automations_kata FROM automation_event");
	
	// Migrate event changeset history to the new 'Default' listener records
	$db->ExecuteMaster("UPDATE IGNORE record_changeset SET record_type = 'automation_event_listener', record_id = (select id from automation_event_listener where name = 'Default' and event_name = (select cast(name as binary) from automation_event where id = record_changeset.record_id)) where record_type = 'automation_event'");
	
	// Drop the old column
	$db->ExecuteMaster('ALTER TABLE automation_event DROP COLUMN automations_kata');
}

// ===========================================================================
// Toolbar Section

if(!isset($tables['toolbar_section'])) {
	$sql = sprintf("
		CREATE TABLE `toolbar_section` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`toolbar_name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`toolbar_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (toolbar_name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['toolbar_section'] = 'toolbar_section';
}

// ===========================================================================
// Drop `toolbar.toolbar_kata`

list($columns, ) = $db->metaTable('toolbar');

if(array_key_exists('toolbar_kata', $columns)) {
	// Migrate automation_event KATA to event listeners
	$db->ExecuteMaster("INSERT IGNORE INTO toolbar_section (name, toolbar_name, priority, created_at, updated_at, toolbar_kata) SELECT 'Default', name, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), toolbar_kata FROM toolbar");
	
	// Migrate event changeset history to the new 'Default' listener records
	$db->ExecuteMaster("UPDATE IGNORE record_changeset SET record_type = 'toolbar_section', record_id = (select id from toolbar_section where name = 'Default' and toolbar_name = (select cast(name as binary) from toolbar where id = record_changeset.record_id)) where record_type = 'toolbar'");
	
	// Drop the old column
	$db->ExecuteMaster('ALTER TABLE toolbar DROP COLUMN toolbar_kata');
}

// ===========================================================================
// Add new toolbars

if(!$db->GetOneMaster("SELECT 1 FROM toolbar_section WHERE name = 'Impersonate' and toolbar_name = 'record.card'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO toolbar_section (name, toolbar_name, priority, toolbar_kata, created_at, updated_at) VALUES (%s,%s,%s,%s,%d,%d)',
		$db->qstr('Impersonate'),
		$db->qstr('record.card'),
		255,
		$db->qstr("interaction/impersonate:\n  label: Impersonate\n  icon: user\n  uri: cerb:automation:cerb.interaction.echo\n  hidden@bool:\n    {{\n      not worker_is_superuser \n      or record__type is not record type ('worker') \n      or record_is_superuser\n      or record_id == worker_id\n    }}\n  inputs:\n    outputs:\n      impersonate@int: {{record_id}}\n  after:\n    refresh_toolbar@bool: no\n    refresh_widgets@bool: no"),
		time(),
		time()
	));
}

if(!$db->GetOneMaster("SELECT 1 FROM toolbar_section WHERE name = 'Impersonate' and toolbar_name = 'record.profile'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO toolbar_section (name, toolbar_name, priority, toolbar_kata, created_at, updated_at) VALUES (%s,%s,%s,%s,%d,%d)',
		$db->qstr('Impersonate'),
		$db->qstr('record.profile'),
		255,
		$db->qstr("interaction/impersonate:\n  label: Impersonate\n  uri: cerb:automation:cerb.interaction.echo\n  icon: user\n  hidden@bool:\n    {{\n      not worker_is_superuser \n      or record__type is not record type ('worker') \n      or record_is_superuser\n      or record_id == worker_id\n    }}\n  inputs:\n    outputs:\n      impersonate@int: {{record_id}}\n  after:\n    refresh_toolbar@bool: no\n    refresh_widgets@bool: no"),
		time(),
		time()
	));
}

// ===========================================================================
// Mail Routing Rule

if(!isset($tables['mail_routing_rule'])) {
	$sql = sprintf("
		CREATE TABLE `mail_routing_rule` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`routing_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['mail_routing_rule'] = 'mail_routing_rule';
}

// ===========================================================================
// If 10.5 beta rules, migrate format

if($routing_kata = $db->GetOneMaster("SELECT value FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' and setting = 'routing_kata'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO mail_routing_rule (name, priority, created_at, updated_at, routing_kata) VALUES(%s,%d,%d,%d,%s)",
		$db->qstr('Default'),
		0,
		time(),
		time(),
		$db->qstr($routing_kata)
	));
	
	$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting = 'routing_kata'");
}

// ===========================================================================
// Remove the legacy 'Impersonate' worker profile widget

if(($widget_id = $db->GetOneMaster("SELECT id FROM profile_widget WHERE name = 'Actions' AND extension_id = 'cerb.profile.tab.widget.html' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.worker')"))) {
	$db->ExecuteMaster(sprintf("DELETE FROM profile_widget WHERE id = %d", $widget_id));
}

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automationBuilder.action.recordGet.json',
	'ai.cerb.automationBuilder.action.recordUpsert.json',
	'ai.cerb.automationBuilder.autocomplete.d3TimeFormat.json',
	'ai.cerb.chooser.toolbar.json',
	'ai.cerb.routingRuleBuilder.inputChooser.json',
	'cerb.reply.isBannedDefunct.json',
	'cerb.ticket.assign.json',
	'cerb.ticket.spam.json',
	'cerb.ticket.status.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Cleanup ACL and translations for old plugins

$db->ExecuteWriter("DELETE FROM translation WHERE string_id IN ('acl.reports.group.bots','acl.reports.group.snippets')");

// ===========================================================================
// Remove legacy worker prefs

$db->ExecuteWriter("DELETE FROM worker_pref WHERE setting IN ('assist_mode')");

// ===========================================================================
// Update package library

$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_workspace_widget_chart_categories'");
$db->ExecuteMaster("DELETE FROM package_library WHERE uri = 'cerb_workspace_widget_chart_time_series'");

$packages = [
	'card_widget/cerb_card_widget_gpg_public_key_subkeys.json',
	'cerb_connected_service_anthropic.json',
	'cerb_connected_service_deepl.json',
	'cerb_connected_service_groq.json',
	'cerb_connected_service_ipstack.json',
	'cerb_connected_service_slack.json',
	'cerb_profile_tab_ticket_overview.json',
	'cerb_profile_widget_ticket_status.json',
	'cerb_profile_widget_ticket_participants.json',
	'cerb_project_board_kanban.json',
	'cerb_workspace_page_home.json',
	'cerb_workspace_widget_chart.json',
	'cerb_workspace_widget_worklist.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

$db->ExecuteWriter("DELETE FROM package_library WHERE uri IN ('cerb_connected_service_nest')");

// ===========================================================================
// Add event listener for cerb.reply.isBannedDefunct

if(!$db->GetOneMaster("SELECT id FROM automation_event_listener WHERE event_name = 'mail.reply.validate' AND name = 'Banned'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT IGNORE INTO automation_event_listener (name, event_name, priority, created_at, updated_at, event_kata) ".
		"VALUES (%s, %s, %d, %d, %d, %s)",
		$db->qstr('Banned'),
		$db->qstr('mail.reply.validate'),
		100,
		time(),
		time(),
		$db->qstr("automation/isBannedDefunct:\n  uri: cerb:automation:cerb.reply.isBannedDefunct\n  inputs:\n    message: {{message_id}}\n  disabled@bool: {{not(message_sender_is_banned or message_sender_is_defunct)}}")
	));
}

// ===========================================================================
// Add Routing KATA to groups

list($columns,) = $db->metaTable('worker_group');

if(!array_key_exists('routing_kata', $columns)) {
	$sql = "ALTER TABLE worker_group ADD COLUMN routing_kata mediumtext";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove routing KATA on buckets (10.5 beta)

list($columns,) = $db->metaTable('bucket');

if(array_key_exists('routing_kata', $columns)) {
	$sql = "UPDATE worker_group AS g INNER JOIN bucket ON (bucket.is_default=1 AND bucket.group_id=g.id) SET g.routing_kata = bucket.routing_kata WHERE bucket.routing_kata != ''";
	$db->ExecuteMaster($sql);
	
	$sql = "ALTER TABLE bucket DROP COLUMN routing_kata";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Modify card_widget.options_kata for conditionality

list($columns, ) = $db->metaTable('card_widget');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE card_widget ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// Modify workspace_widget.options_kata for conditionality

list($columns, ) = $db->metaTable('workspace_widget');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE workspace_widget ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// Modify profile_tab.pos for conditionality

list($columns, ) = $db->metaTable('profile_tab');

if(!array_key_exists('pos', $columns)) {
	$db->ExecuteMaster('ALTER TABLE profile_tab ADD COLUMN pos SMALLINT UNSIGNED NOT NULL DEFAULT 0');
	$db->ExecuteMaster('UPDATE profile_tab SET pos = 100');
	
	// Convert the old preferences to the `profile_tab.pos` field
	$results = $db->GetArrayMaster("SELECT setting, value FROM devblocks_setting WHERE setting LIKE 'profile:tabs:%'");
	
	$values = [];
	
	foreach($results as $result) {
		if (false === ($tab_ids = json_decode($result['value'], true)))
			continue;
		
		if (!is_array($tab_ids))
			continue;
		
		foreach ($tab_ids as $i => $tab_id) {
			$values[] = sprintf("(%d,%d)", $tab_id, $i);
		}
	}
	
	if($values) {
		$sql = sprintf("INSERT INTO profile_tab (id, pos) VALUES %s ON DUPLICATE KEY UPDATE pos=VALUES(pos)",
			implode(',', $values)
		);
		$db->ExecuteMaster($sql);
	}
	
	$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE setting LIKE 'profile:tabs:%'");
}

// ===========================================================================
// Modify profile_tab.options_kata for conditionality

list($columns, ) = $db->metaTable('profile_tab');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE profile_tab ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// Modify workspace_tab.options_kata for conditionality

list($columns, ) = $db->metaTable('workspace_tab');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE workspace_tab ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// OAuth App

list($columns, ) = $db->metaTable('oauth_app');

if(!array_key_exists('access_token_ttl', $columns)) {
	$db->ExecuteMaster("ALTER TABLE oauth_app ADD COLUMN access_token_ttl varchar(32) not null default '1 hour'");
	$db->ExecuteMaster("UPDATE oauth_app SET access_token_ttl = '1 hour' WHERE access_token_ttl = ''");
}

if(!array_key_exists('refresh_token_ttl', $columns)) {
	$db->ExecuteMaster("ALTER TABLE oauth_app ADD COLUMN refresh_token_ttl varchar(32) not null default '1 month'");
	$db->ExecuteMaster("UPDATE oauth_app SET refresh_token_ttl = '1 month' WHERE refresh_token_ttl = ''");
}

// ===========================================================================
// Workflows

if(!isset($tables['workflow'])) {
	$sql = sprintf("
		CREATE TABLE `workflow` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`version` bigint unsigned NOT NULL DEFAULT 0,
		`builder_kata` mediumtext,
		`workflow_kata` mediumtext,
		`config_kata` mediumtext,
		`resources_kata` mediumtext,
		`has_extensions` tinyint unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['workflow'] = 'workflow';
}

list($columns, ) = $db->metaTable('workflow');

if(!array_key_exists('version', $columns)) {
	$db->ExecuteMaster("ALTER TABLE workflow ADD COLUMN version bigint unsigned NOT NULL DEFAULT 0");
}

if(!array_key_exists('builder_kata', $columns)) {
	$db->ExecuteMaster("ALTER TABLE workflow ADD COLUMN builder_kata MEDIUMTEXT");
}

if(array_key_exists('website', $columns)) {
	$db->ExecuteMaster("ALTER TABLE workflow DROP COLUMN website");
}

// ===========================================================================
// Convert PHP serialization in the Support Center portal to JSON

if (!array_key_exists('community_tool_property', $tables)) {
	$logger->error("The 'community_tool_property' table does not exist.");
	return FALSE;
}

$results = $db->GetArrayMaster(
	"SELECT tool_code, property_key, property_value FROM community_tool_property " .
	"WHERE property_key IN ('announcements.rss','common.visible_modules','contact.situations','kb.roots') " .
	"AND property_value NOT LIKE '{%'"
) ?? [];

foreach ($results as $result) {
	// Convert arrays from serialize/unserialize to JSON
	$property_value = unserialize($result['property_value'], ['allowed_classes' => false]);
	
	if(!is_array($property_value))
		$property_value = [];
	
	$db->ExecuteMaster(sprintf("UPDATE community_tool_property SET property_value = %s WHERE tool_code = %s AND property_key = %s",
		$db->qstr(json_encode($property_value)),
		$db->qstr($result['tool_code']),
		$db->qstr($result['property_key']),
	));
}

// ===========================================================================
// Convert PHP serialization in mail_to_group_rule

if (!array_key_exists('mail_to_group_rule', $tables)) {
	$logger->error("The 'mail_to_group_rule' table does not exist.");
	return FALSE;
}

$results = $db->GetArrayMaster(
	"SELECT id, criteria_ser, actions_ser FROM mail_to_group_rule WHERE (criteria_ser LIKE 'a:%' OR actions_ser LIKE 'a:%')"
) ?? [];

foreach ($results as $result) {
	// Convert arrays from serialize/unserialize to JSON
	$criteria_ser = unserialize($result['criteria_ser'], ['allowed_classes' => false]);
	$actions_ser = unserialize($result['actions_ser'], ['allowed_classes' => false]);
	
	if(!is_array($criteria_ser))
		$criteria_ser = [];
	if(!is_array($actions_ser))
		$actions_ser = [];
	
	$db->ExecuteMaster(sprintf("UPDATE mail_to_group_rule SET criteria_ser = %s, actions_ser = %s WHERE id = %d",
		$db->qstr(json_encode($criteria_ser)),
		$db->qstr(json_encode($actions_ser)),
		intval($result['id']),
	));
}

if($revision < 1462) {
	$db->ExecuteMaster("DELETE FROM community_session");
}

// ===========================================================================
// Add metrics

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %s, %d, %d)",
	$db->qstr('cerb.mail.routing.matches'),
	$db->qstr('Match count for global mail routing rules'),
	$db->qstr('counter'),
	$db->qstr("record/rule_id:\n  record_type: mail_routing_rule\ntext/rule_key:\ntext/node_key:\n"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %s, %d, %d)",
	$db->qstr('cerb.mail.routing.group.matches'),
	$db->qstr('Match count for group inbox routing rules'),
	$db->qstr('counter'),
	$db->qstr("record/group_id:\n  record_type: group\ntext/rule_key:\ntext/node_key:\n"),
	time(),
	time()
));

// ===========================================================================
// Update default card widgets

// Public key subkeys
if(($id = $db->GetOneMaster("SELECT id FROM card_widget WHERE name = 'Subkeys' AND record_type = 'cerberusweb.contexts.gpg_public_key'"))) {
	$db->ExecuteMaster(sprintf("UPDATE card_widget SET extension_params_json=%s, updated_at=%d WHERE id=%d",
		$db->qstr(json_encode([
			"data_query" => "type:gpg.keyinfo\nfilter:subkeys\nfingerprint:{{record_fingerprint}}\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_yaml" => "",
			"sheet_kata" => "layout:\n  style: table\n  headings@bool: yes\n  paging@bool: yes\n  #title_column: _label\n\ncolumns:\n  text/fingerprint:\n    label: Fingerprint\n\n  text/key_bits:\n    label: Bits\n\n  text/algorithm_name:\n    label: Algo\n\n  text/hash_algorithm_name:\n    label: Hash\n\n  date/expires:\n    label: Expires\n    params:\n      #image@bool: yes\n      #bold@bool: yes\n\n  icon/can_sign:\n    label: Sign\n    params:\n      image_template@raw:\n        {% if can_sign %}\n        circle-ok\n        {% endif %}\n\n  icon/can_encrypt:\n    label: Encrypt\n    params:\n      image_template@raw:\n        {% if can_encrypt %}\n        circle-ok\n        {% endif %}\n\n"
		])),
		time(),
		$id
	));
}

// Public key ascii
if(($id = $db->GetOneMaster("SELECT id FROM card_widget WHERE name = 'Public Key' AND record_type = 'cerberusweb.contexts.gpg_public_key'"))) {
	$db->ExecuteMaster(sprintf("UPDATE card_widget SET extension_params_json=%s, updated_at=%d WHERE id=%d",
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\nof:gpg_public_key\nquery:(\n  id:{{record_id}}\n  limit:1\n  sort:[id]\n)\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_yaml" => "",
			"sheet_kata" => "layout:\n  style: fieldset\n  headings@bool: no\n  paging@bool: no\n\ncolumns:\n  text/_label:\n    label: Label\n    params:\n      value_template@raw:\n        <pre>\n        {{key_text}}\n        </pre>"
		])),
		time(),
		$id
	));
} else {
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Public Key'),
		$db->qstr(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY),
		$db->qstr('cerb.card.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\nof:gpg_public_key\nquery:(\n  id:{{record_id}}\n  limit:1\n  sort:[id]\n)\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_yaml" => "",
			"sheet_kata" => "layout:\n  style: fieldset\n  headings@bool: no\n  paging@bool: no\n\ncolumns:\n  text/_label:\n    label: Label\n    params:\n      value_template@raw:\n        <pre>\n        {{key_text}}\n        </pre>"
		])),
		time(),
		time(),
		4,
		4,
		$db->qstr('content')
	));
}

// ===========================================================================
// Mail Delivery Log

if(!isset($tables['mail_delivery_log'])) {
	$sql = sprintf("
		CREATE TABLE `mail_delivery_log` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`type` varchar(32) NOT NULL DEFAULT '',
		`status_id` tinyint unsigned NOT NULL DEFAULT 0,
		`status_message` varchar(255) NOT NULL DEFAULT '',
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`to` varchar(255) NOT NULL DEFAULT '',
		`from_id` int unsigned NOT NULL DEFAULT 0,
		`subject` varchar(255) NOT NULL DEFAULT '' COLLATE utf8mb4_unicode_ci,
		`header_message_id` varchar(255) NOT NULL DEFAULT '',
		`mail_transport_id` int unsigned NOT NULL DEFAULT 0,
		`properties_json` mediumtext,
		PRIMARY KEY (id),
		INDEX (type),
		INDEX (created_at),
		INDEX header_message_id (header_message_id(8))
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['mail_delivery_log'] = 'mail_delivery_log';
	
	// Default card widgets
	
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.mail.delivery.log'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
            "context" => "cerb.contexts.mail.delivery.log",
            "context_id" => "{{record_id}}",
            "properties" => [
				[
					"id",
					"to",
					"created",
					"mail_transport_id",
					"from_id",
					"status_id",
					"header_message_id",
					"type",
					"status_message"
				]
			],
            "toolbar_kata" => "",
		])),
		time(),
		time(),
		1,
		4,
		$db->qstr('content')
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s, %s)",
		$db->qstr('Raw Message'),
		$db->qstr('cerb.contexts.mail.delivery.log'),
		$db->qstr('cerb.card.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:mail_delivery_log\r\nexpand:[properties]\r\nquery:(\r\n  limit:1\r\n  id:{{record_id}}\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
            "cache_secs" => "",
            "placeholder_simulator_kata" => "",
            "sheet_kata" => "layout:\r\n  style: fieldsets\r\n  headings@bool: no\r\n  paging@bool: no\r\n  colors:\r\n    code_dark@csv: #000000, #FFFFFF\r\n    code@csv: #F3EFEC, #333333\r\n\r\ncolumns:\r\n  code/properties:\r\n    label: Properties\r\n    params:\r\n      color: code:0\r\n      text_color: code:1\r\n      value_template@raw:\r\n        {{properties|kata_encode}}\r\n  ",
            "toolbar_kata" => "",
		])),
		time(),
		time(),
		2,
		4,
		$db->qstr('content'),
		$db->qstr('hidden@bool: {{not worker_is_superuser}}')
	));
	
	
	// Default profile page
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.mail.delivery.log'),
		$db->qstr('cerb.profile.tab.dashboard'),
		time(),
		$db->qstr(json_encode([
			"layout" => "sidebar_left",
		])),
		1
	));
	
	$new_profile_tab_id = $db->LastInsertId();
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Email Delivery Log'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.mail.delivery.log",
            "context_id" => "{{record_id}}",
            "properties" => [
				[
					"created",
					"from_id",
					"mail_transport_id",
					"subject",
					"id",
					"header_message_id",
					"status_id",
					"status_message",
					"to",
					"type"
				]
			],
            "toolbar_kata" => "",
		])),
		time(),
		1,
		4,
		$db->qstr('sidebar'),
		$db->qstr('')
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Raw Message'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:mail_delivery_log\r\nexpand:[properties]\r\nquery:(\r\n  limit:1\r\n  id:{{record_id}}\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
            "cache_secs" => "",
            "placeholder_simulator_kata" => "",
            "sheet_kata" => "layout:\r\n  style: fieldsets\r\n  headings@bool: no\r\n  paging@bool: no\r\n  colors:\r\n    code_dark@csv: #000000, #FFFFFF\r\n    code@csv: #F3EFEC, #333333\r\n\r\ncolumns:\r\n  code/properties:\r\n    label: Properties\r\n    params:\r\n      color: code:0\r\n      text_color: code:1\r\n      value_template@raw:\r\n        {{properties|kata_encode}}\r\n  ",
            "toolbar_kata" => ""
		])),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Mail Inbound Log

if(!isset($tables['mail_inbound_log'])) {
	$sql = sprintf("
		CREATE TABLE `mail_inbound_log` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`status_id` tinyint unsigned NOT NULL DEFAULT 0,
		`status_message` varchar(255) NOT NULL DEFAULT '',
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`to` varchar(255) NOT NULL DEFAULT '',
		`from_id` int unsigned NOT NULL DEFAULT 0,
		`subject` varchar(255) NOT NULL DEFAULT '' COLLATE utf8mb4_unicode_ci,
		`header_message_id` varchar(255) NOT NULL DEFAULT '',
		`ticket_id` int unsigned NOT NULL DEFAULT 0,
		`message_id` int unsigned NOT NULL DEFAULT 0,
		`mailbox_id` int unsigned NOT NULL DEFAULT 0,
		`parse_time_ms` int unsigned NOT NULL DEFAULT 0,
		`events_log_json` mediumtext,
		PRIMARY KEY (id),
		INDEX (created_at),
		INDEX (parse_time_ms),
		INDEX (from_id),
		INDEX (message_id),
		INDEX header_message_id (header_message_id(8))
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['mail_inbound_log'] = 'mail_inbound_log';
	
	// Default card widgets
	
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.mail.inbound.log'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.mail.inbound.log",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"id",
					"created",
					"to",
					"from_id",
					"status_id",
					"status_message",
					"parse_time_ms",
					"header_message_id",
					"message_id",
					"mailbox_id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(),
		time(),
		1,
		4,
		$db->qstr('content')
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s, %s)",
		$db->qstr('Events Log'),
		$db->qstr('cerb.contexts.mail.inbound.log'),
		$db->qstr('cerb.card.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:mail_inbound_log\r\nexpand:[events_log]\r\nquery:(\r\n  limit:1\r\n  id:{{record_id}}\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
            "cache_secs" => "",
            "placeholder_simulator_kata" => "",
            "sheet_kata" => "layout:\r\n  style: fieldsets\r\n  headings@bool: no\r\n  paging@bool: no\r\n  colors:\r\n    code_dark@csv: #000000, #FFFFFF\r\n    code@csv: #F3EFEC, #333333\r\n\r\ncolumns:\r\n  code/properties:\r\n    label: Events Log\r\n    params:\r\n      color: code:0\r\n      text_color: code:1\r\n      value_template@raw:\r\n        {{events_log|kata_encode}}\r\n  ",
            "toolbar_kata" => "",
		])),
		time(),
		time(),
		2,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
	
	// Default profile page
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.mail.inbound.log'),
		$db->qstr('cerb.profile.tab.dashboard'),
		time(),
		$db->qstr(json_encode([
			"layout" => "sidebar_left",
		])),
		1
	));
	
	$new_profile_tab_id = $db->LastInsertId();
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Email Inbound Log'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.mail.inbound.log",
            "context_id" => "{{record_id}}",
            "properties" => [
				[
					"id",
					"from_id",
					"status_id",
					"status_message",
					"created",
					"header_message_id",
					"mailbox_id",
					"message_id",
					"parse_time_ms",
					"ticket_id",
					"to"
				]
			],
            "toolbar_kata" => "",
		])),
		time(),
		1,
		4,
		$db->qstr('sidebar'),
		$db->qstr('')
	));
	
	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Events Log'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:mail_inbound_log\r\nexpand:[events_log]\r\nquery:(\r\n  limit:1\r\n  id:{{record_id}}\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
            "cache_secs" => "",
            "placeholder_simulator_kata" => "",
            "sheet_kata" => "layout:\r\n  style: fieldsets\r\n  headings@bool: no\r\n  paging@bool: no\r\n  colors:\r\n    code_dark@csv: #000000, #FFFFFF\r\n    code@csv: #F3EFEC, #333333\r\n\r\ncolumns:\r\n  code/properties:\r\n    label: Events Log\r\n    params:\r\n      color: code:0\r\n      text_color: code:1\r\n      value_template@raw:\r\n        {{events_log|kata_encode}}\r\n  ",
            "toolbar_kata" => ""
		])),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Enable the new classifiers plugin by default on upgrades

if($revision < 1467) { // 10.5
	$plugin_classifiers = DevblocksPlatform::getPlugin('cerb.classifiers');
	$plugin_classifiers->setEnabled(true);
}

// ===========================================================================
// Drop old unused tables

if(array_key_exists('rssexp_feed', $tables)) {
	$db->ExecuteMaster('DROP TABLE rssexp_feed');
}

if(array_key_exists('rssexp_item', $tables)) {
	$db->ExecuteMaster('DROP TABLE rssexp_item');
}

if(array_key_exists('watcher_mail_filter', $tables)) {
	$db->ExecuteMaster('DROP TABLE watcher_mail_filter');
}

if(array_key_exists('webapi_key', $tables)) {
	$db->ExecuteMaster('DROP TABLE webapi_key');
}

if(array_key_exists('wgm_google_cse', $tables)) {
	$db->ExecuteMaster('DROP TABLE wgm_google_cse');
}

// ===========================================================================
// Convert `automation.script` to utf8mb4

if(!isset($tables['automation']))
	return FALSE;

list($columns,) = $db->metaTable('automation');

if(!array_key_exists('script', $columns))
	return FALSE;

if('utf8mb4_unicode_ci' != $columns['script']['collation']) {
	$db->ExecuteMaster("ALTER TABLE automation MODIFY COLUMN script MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE automation");
	$db->ExecuteMaster("OPTIMIZE TABLE automation");
}

// ===========================================================================
// Finish up

return TRUE;
