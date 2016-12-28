<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add `connected_account` table

if(!isset($tables['connected_account'])) {
	$sql = sprintf("
	CREATE TABLE `connected_account` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		extension_id varchar(255) not null default '',
		owner_context varchar(255) not null default '',
		owner_context_id int unsigned not null default 0,
		params_json text,
		created_at int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (extension_id),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['connected_account'] = 'connected_account';
}

// ===========================================================================
// Add `classifier` table

if(!isset($tables['classifier'])) {
	$sql = sprintf("
	CREATE TABLE `classifier` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		owner_context varchar(255) not null default '',
		owner_context_id int unsigned not null default 0,
		created_at int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		dictionary_size int unsigned not null default 0,
		params_json text,
		primary key (id),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['classifier'] = 'classifier';
}

// ===========================================================================
// Add `classifier_class` table
if(!isset($tables['classifier_class'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_class` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		classifier_id int unsigned not null default 0,
		training_count int unsigned not null default 0,
		dictionary_size int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		attribs_json text,
		primary key (id),
		index (classifier_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['classifier_class'] = 'classifier_class';
}

// ===========================================================================
// Add `classifier_ngram` table

if(!isset($tables['classifier_ngram'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_ngram` (
		id int unsigned auto_increment,
		token varchar(255) not null default '',
		n tinyint unsigned not null default 0,
		primary key (id),
		unique (token)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['classifier_ngram'] = 'classifier_ngram';
}

if(!isset($tables['classifier_ngram_to_class'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_ngram_to_class` (
		token_id int unsigned not null default 0,
		class_id int unsigned not null default 0,
		training_count int unsigned not null default 0,
		primary key (token_id, class_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['classifier_ngram_to_class'] = 'classifier_ngram_to_class';
}

// ===========================================================================
// Add `classifier_example` table

if(!isset($tables['classifier_example'])) {
	$sql = sprintf("
	CREATE TABLE `classifier_example` (
		id int unsigned auto_increment,
		classifier_id int unsigned not null default 0,
		class_id int unsigned not null default 0,
		expression text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (classifier_id),
		index (class_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['classifier_example'] = 'classifier_example';
}

// Modify `decision_node` to add 'subroutine' and 'loop' types
// Add `status_id` field to nodes

if(!isset($tables['decision_node'])) {
	$logger->error("The 'decision_node' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('decision_node');

if(isset($columns['node_type']) && 0 != strcasecmp('varchar(16)', $columns['node_type']['type'])) {
	$db->ExecuteMaster("ALTER TABLE decision_node MODIFY COLUMN node_type varchar(16) not null default ''");
}

if(!isset($columns['status_id']))
	$db->ExecuteMaster("ALTER TABLE decision_node ADD COLUMN status_id tinyint(1) unsigned not null default 0");

// ===========================================================================
// Modify `trigger_event` to add 'updated_at'

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(!isset($columns['updated_at'])) {
	$db->ExecuteMaster("ALTER TABLE trigger_event ADD COLUMN updated_at int unsigned not null default 0");
	$db->ExecuteMaster("UPDATE trigger_event SET updated_at = UNIX_TIMESTAMP()");
}

if(isset($columns['pos'])) {
	$db->ExecuteMaster("ALTER TABLE trigger_event CHANGE COLUMN pos priority int unsigned not null default 0");
	$db->ExecuteMaster("UPDATE trigger_event SET priority = priority + 1");
}

if(isset($columns['virtual_attendant_id'])) {
	$db->ExecuteMaster("ALTER TABLE trigger_event CHANGE COLUMN virtual_attendant_id bot_id int unsigned not null default 0");
}

// ===========================================================================
// Rename table `virtual_attendant` to `bot`

if(isset($tables['virtual_attendant'])) {
	$db->ExecuteMaster("RENAME TABLE virtual_attendant TO bot");
	$db->ExecuteMaster("UPDATE cerb_property_store SET extension_id = 'cron.bot.scheduled_behavior' WHERE extension_id = 'cron.virtual_attendant.scheduled_behavior'");
	$db->ExecuteMaster("UPDATE trigger_event SET event_point = 'event.macro.bot' WHERE event_point = 'event.macro.virtual_attendant'");

	unset($tables['virtual_attendant']);
	$tables['bot'] = 'bot';
}

// ===========================================================================
// Fix `contact.location` (was varchar and default=0)

if(!isset($tables['contact'])) {
	$logger->error("The 'contact' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('contact');

if(isset($columns['location']) && 0 == strcasecmp('0', $columns['location']['default'])) {
	$db->ExecuteMaster("ALTER TABLE contact MODIFY COLUMN location varchar(255) not null default ''");
	$db->ExecuteMaster("UPDATE contact SET location = '' WHERE location = '0'");
}

// ===========================================================================
// Switch ticket worklists to the {first,last}_wrote_id fields w/o joins

$db->ExecuteMaster("UPDATE worker_view_model SET columns_json = replace(columns_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET columns_hidden_json = replace(columns_hidden_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_default_json = replace(params_default_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_editable_json = replace(params_editable_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_hidden_json = replace(params_hidden_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_required_json = replace(params_required_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET render_sort_by = replace(render_sort_by, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET render_subtotals = replace(render_subtotals, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where class_name = 'View_Ticket'");

$db->ExecuteMaster("UPDATE workspace_list SET list_view = replace(list_view, '\s:12:\"t_first_wrote\"', 's:15:\"t_first_wrote_id\"') where context = 'cerberusweb.contexts.ticket'");
$db->ExecuteMaster("UPDATE workspace_widget SET params_json = replace(params_json, '\"t_first_wrote\"', '\"t_first_wrote_id\"') where params_json like '%\"context\":\"cerberusweb.contexts.ticket\"%'");

$db->ExecuteMaster("UPDATE worker_view_model SET columns_json = replace(columns_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET columns_hidden_json = replace(columns_hidden_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_default_json = replace(params_default_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_editable_json = replace(params_editable_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_hidden_json = replace(params_hidden_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_required_json = replace(params_required_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET render_sort_by = replace(render_sort_by, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");
$db->ExecuteMaster("UPDATE worker_view_model SET render_subtotals = replace(render_subtotals, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where class_name = 'View_Ticket'");

$db->ExecuteMaster("UPDATE workspace_list SET list_view = replace(list_view, '\s:12:\"t_last_wrote\"', 's:15:\"t_last_wrote_id\"') where context = 'cerberusweb.contexts.ticket'");
$db->ExecuteMaster("UPDATE workspace_widget SET params_json = replace(params_json, '\"t_last_wrote\"', '\"t_last_wrote_id\"') where params_json like '%\"context\":\"cerberusweb.contexts.ticket\"%'");

// ===========================================================================
// Modify `attachment`

if(!isset($tables['attachment'])) {
	$logger->error("The 'attachment' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('attachment');

$changes = [];

if(isset($columns['display_name'])) {
	$changes[] = "CHANGE COLUMN display_name name VARCHAR(255) NOT NULL DEFAULT ''";
	$changes[] = "ADD INDEX name (name(6))";
}

if(!isset($indexes['mime_type'])) {
	$changes[] = "ADD INDEX (mime_type)";
}

if(!empty($changes))
	if(false == ($db->ExecuteMaster(sprintf("ALTER TABLE attachment %s", implode(',', $changes)))))
		return FALSE;

// ===========================================================================
// Update bot context + owner_context everywhere

$db->ExecuteMaster("UPDATE attachment_link SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE calendar SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE classifier SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE comment SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE comment SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_activity_log SET actor_context = 'cerberusweb.contexts.bot' WHERE actor_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_activity_log SET target_context = 'cerberusweb.contexts.bot' WHERE target_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_avatar SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_bulk_update SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_link SET from_context = 'cerberusweb.contexts.bot' WHERE from_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_link SET to_context = 'cerberusweb.contexts.bot' WHERE to_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_merge_history SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_recommendation SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_scheduled_behavior SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE context_to_skill SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_field SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_field_clobvalue SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_field_numbervalue SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_field_stringvalue SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_fieldset SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE custom_fieldset SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE file_bundle SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE fulltext_comment_content SET context_crc32 = 2977211304 WHERE context_crc32 = 381457798");
$db->ExecuteMaster("UPDATE mail_html_template SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE notification SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE snippet SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE snippet SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE workspace_list SET context = 'cerberusweb.contexts.bot' WHERE context = 'cerberusweb.contexts.virtual.attendant'");
$db->ExecuteMaster("UPDATE workspace_page SET owner_context = 'cerberusweb.contexts.bot' WHERE owner_context = 'cerberusweb.contexts.virtual.attendant'");

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");
$db->ExecuteMaster("UPDATE decision_node SET params_json = REPLACE(params_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");
$db->ExecuteMaster("UPDATE notification SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");

$db->ExecuteMaster("UPDATE IGNORE worker_view_model SET view_id = REPLACE(view_id, 'virtual_attendant', 'bot')");
$db->ExecuteMaster("UPDATE worker_view_model set title = 'Bots' WHERE title = 'Virtual Attendant' AND class_name = 'View_Bot'");
$db->ExecuteMaster("UPDATE worker_view_model SET view_id = 'bots' WHERE view_id = 'virtual_attendants'");
$db->ExecuteMaster("UPDATE worker_view_model SET class_name = 'View_Bot' WHERE class_name = 'View_Bot'");
$db->ExecuteMaster("UPDATE worker_view_model SET params_editable_json = REPLACE(params_editable_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");
$db->ExecuteMaster("UPDATE worker_view_model SET params_default_json = REPLACE(params_default_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");
$db->ExecuteMaster("UPDATE worker_view_model SET params_required_json = REPLACE(params_required_json, 'cerberusweb.contexts.virtual.attendant', 'cerberusweb.contexts.bot')");

// ===========================================================================
// Finish up

return TRUE;
