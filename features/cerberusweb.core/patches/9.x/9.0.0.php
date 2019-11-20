<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `profile_tab`

if(!isset($tables['profile_tab'])) {
	$sql = sprintf("
	CREATE TABLE `profile_tab` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		context VARCHAR(255) NOT NULL DEFAULT '',
		extension_id VARCHAR(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index (context),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_tab'] = 'profile_tab';
}

// ===========================================================================
// Add `profile_widget`

if(!isset($tables['profile_widget'])) {
	$sql = sprintf("
	CREATE TABLE `profile_widget` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		profile_tab_id int(10) unsigned NOT NULL DEFAULT 0,
		extension_id varchar(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		zone varchar(255) NOT NULL DEFAULT '',
		pos tinyint unsigned NOT NULL DEFAULT 0,
		width_units tinyint unsigned NOT NULL DEFAULT 1,
		updated_at int(10) unsigned NOT NULL DEFAULT 0,
		primary key (id),
		index (profile_tab_id),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_widget'] = 'profile_widget';
}

// ===========================================================================
// Insert default tabs and widgets

$result = $db->GetOneMaster("SELECT COUNT(id) FROM profile_tab");

if(!$result) {
	$sqls = <<< EOD
# Address
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.address','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Email Address',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.address\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"contact\",\"num_spam\",\"num_nonspam\",\"is_banned\",\"is_defunct\",\"org\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Contact',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.contact\",\"context_id\":\"{{record_contact_id}}\",\"properties\":[[\"name\",\"title\",\"location\",\"language\",\"timezone\",\"phone\",\"mobile\",\"updated\",\"last_login\"]]}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Organization',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.org\",\"context_id\":\"{{record_org_id}}\",\"properties\":[[\"_label\",\"email\",\"country\",\"phone\",\"website\",\"created\"]]}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Ticket History',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"participant.id:{{record_id}}\",\"query\":\"subtotal:status sort:-updated\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_updated_date\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.address',CONCAT('[',@last_tab_id,']'));

# Attachment
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.attachment','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Attachment',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.attachment\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"mime_type\",\"storage_size\",\"storage_extension\",\"storage_key\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":[]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.attachment\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.attachment',CONCAT('[',@last_tab_id,']'));

# Behavior
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.behavior','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Behavior',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.behavior\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"bot_id\",\"event_point\",\"updated\",\"is_disabled\",\"is_private\",\"priority\"]],\"links\":{\"show\":\"1\"},\"search\":[]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Bot',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.bot\",\"context_id\":\"{{record_bot_id}}\",\"properties\":[[\"owner\",\"is_disabled\",\"updated\"]],\"links\":{\"show\":\"0\"},\"search\":{\"context\":[\"cerberusweb.contexts.behavior\"],\"label_singular\":[\"Behavior\"],\"label_plural\":[\"Behaviors\"],\"query\":[\"bot.id:{{record_bot_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Behavior Tree',@last_tab_id,'cerb.profile.tab.widget.behavior.tree','{\"behavior_id\":\"{{record_id}}\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Bot Behaviors',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.behavior\",\"query_required\":\"bot.id:{{record_bot_id}}\",\"query\":\"subtotal:event sort:priority\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_event_point\",\"t_priority\",\"t_updated_at\"]}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.behavior',CONCAT('[',@last_tab_id,']'));

# Bot
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.bot','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Bot',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.bot\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"is_disabled\",\"created\",\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Behaviors',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.behavior\",\"query_required\":\"bot.id:{{record_id}} subtotal:event\",\"query\":\"sort:-updated\",\"render_limit\":\"15\",\"header_color\":\"#6a87db\",\"columns\":[\"t_event_point\",\"t_priority\",\"t_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Scheduled Behaviors',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.behavior.scheduled\",\"query_required\":\"bot.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"b_behavior_name\",\"c_run_date\",\"*_target\"]}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.bot',CONCAT('[',@last_tab_id,']'));

# Bucket
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.bucket','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Bucket',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.bucket\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"is_default\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Group',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.group\",\"context_id\":\"{{record_group_id}}\",\"properties\":[[\"name\",\"reply_to\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.ticket\",\"cerberusweb.contexts.bucket\",\"cerberusweb.contexts.worker\"],\"label_singular\":[\"Ticket\",\"Bucket\",\"Member\"],\"label_plural\":[\"Tickets\",\"Buckets\",\"Members\"],\"query\":[\"group.id:{{record_group_id}}\",\"group.id:{{record_group_id}}\",\"group.id:{{record_group_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Tickets',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"bucket.id:{{record_id}}\",\"query\":\"sort:-updated\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_importance\",\"t_last_wrote_address_id\",\"t_updated_date\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.bucket',CONCAT('[',@last_tab_id,']'));

# Calendar
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.calendar','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Properties',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.calendar\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.calendar_event\",\"cerberusweb.contexts.calendar_event.recurring\"],\"label_singular\":[\"Event\",\"Recurring Event\"],\"label_plural\":[\"Events\",\"Recurring Events\"],\"query\":[\"calendar.id:{{record_id}}\",\"calendar.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Calendar',@last_tab_id,'cerb.profile.tab.widget.calendar','{\"context_id\":\"{{record_id}}\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.calendar',CONCAT('[',@last_tab_id,']'));

# Calendar Event
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.calendar_event','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Event',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.calendar_event\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"date_start\",\"date_end\",\"is_available\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Calendar',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.calendar\",\"context_id\":\"{{record_calendar_id}}\",\"properties\":[[\"name\",\"owner\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.calendar_event\",\"cerberusweb.contexts.calendar_event.recurring\"],\"label_singular\":[\"Event\",\"Recurring Event\"],\"label_plural\":[\"Events\",\"Recurring Events\"],\"query\":[\"calendar.id:{{record_calendar_id}}\",\"calendar.id:{{record_calendar_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Reminders',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.reminder\",\"query_required\":\"links.calendar_event:(id:{{record_id}})\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"r_name\",\"r_remind_at\",\"r_worker_id\",\"r_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.calendar_event\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.calendar_event',CONCAT('[',@last_tab_id,']'));

# Calendar Recurring Event
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.calendar_event.recurring','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recurring Event',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.calendar_event.recurring\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"event_start\",\"event_end\",\"tz\",\"is_available\",\"recur_start\",\"recur_end\",\"patterns\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Calendar',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.calendar\",\"context_id\":\"{{record_calendar_id}}\",\"properties\":[[\"name\",\"owner\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.calendar_event\",\"cerberusweb.contexts.calendar_event.recurring\"],\"label_singular\":[\"Event\",\"Recurring Event\"],\"label_plural\":[\"Events\",\"Recurring Events\"],\"query\":[\"calendar.id:{{record_calendar_id}}\",\"calendar.id:{{record_calendar_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.calendar_event.recurring\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.calendar_event.recurring',CONCAT('[',@last_tab_id,']'));

# Classifier
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifications',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.class\",\"query_required\":\"classifier.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_name\",\"c_training_count\",\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Examples',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.example\",\"query_required\":\"classifier.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_class_id\",\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.classifier',CONCAT('[',@last_tab_id,']'));

# Classifier Class
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.class','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classification',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.class\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"training_count\",\"dictionary_size\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_classifier_id}}\",\"properties\":[[\"name\",\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.classifier.class\",\"cerberusweb.contexts.classifier.example\"],\"label_singular\":[\"Classification\",\"Example\"],\"label_plural\":[\"Classifications\",\"Examples\"],\"query\":[\"classifier.id:{{record_classifier_id}}\",\"classifier.id:{{record_classifier_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Training Examples',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.classifier.example\",\"query_required\":\"classifier.id:{{record_classifier_id}} class.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.classifier.class',CONCAT('[',@last_tab_id,']'));

# Classifier Example
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.example','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Training Example',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.example\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classification',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.class\",\"context_id\":\"{{record_class_id}}\",\"properties\":[[\"name\",\"dictionary_size\",\"training_count\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Classifier',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier\",\"context_id\":\"{{record_classifier_id}}\",\"properties\":[[\"name\",\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.classifier.example\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.classifier.example',CONCAT('[',@last_tab_id,']'));

# Classifier Entity
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.classifier.entity','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Entity',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.classifier.entity\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.classifier.entity\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.classifier.entity',CONCAT('[',@last_tab_id,']'));

# Comment
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.comment','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Comment',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.comment\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"author\",\"target\",\"created\"]],\"links\":{\"show\":\"1\"}}','',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.comment',CONCAT('[',@last_tab_id,']'));

# Connected Account
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.connected_account','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Connected Account',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.connected_account\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"extension\",\"owner\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.connected_account\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.connected_account',CONCAT('[',@last_tab_id,']'));

# Contact
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.contact','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Contact',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.contact\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"title\",\"gender\",\"location\",\"language\",\"timezone\",\"phone\",\"mobile\",\"created\",\"updated\",\"last_login\",\"email\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.address\"],\"label_singular\":[\"Email Address\"],\"label_plural\":[\"Email Addresses\"],\"query\":[\"contact.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Organization',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.org\",\"context_id\":\"{{record_org_id}}\",\"properties\":[[\"_label\",\"email\",\"country\",\"phone\",\"website\",\"created\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.contact\",\"cerberusweb.contexts.address\"],\"label_singular\":[\"Contact\",\"Email Address\"],\"label_plural\":[\"Contacts\",\"Email Addresses\"],\"query\":[\"org.id:{{record_org_id}}\",\"org.id:{{record_org_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Ticket History',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"participant:(contact.id:{{record_id}})\",\"query\":\"subtotal:status\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_updated_date\",\"t_group_id\",\"t_bucket_id\",\"t_owner_id\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.contact\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.contact',CONCAT('[',@last_tab_id,']'));

# Saved Search
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.context.saved.search','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Saved Search',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.context.saved.search\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"tag\",\"context\",\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Query',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div id=\\\"widget{{widget_id}}\\\">\\r\\n\\t<div style=\\\"font-size:1.5em;font-weight:bold;margin-bottom:5px;\\\">\\r\\n\\t\\t{{record_query}}\\r\\n\\t<\\/div>\\r\\n\\t<div>\\r\\n\\t\\t<button type=\\\"button\\\" class=\\\"cerb-search-trigger\\\" data-context=\\\"{{record_context|escape}}\\\" data-query=\\\"{{record_query|escape}}\\\">Run search<\\/button>\\r\\n\\t<\\/div>\\r\\n<\\/div>\\r\\n\\r\\n<script type=\\\"text\\/javascript\\\">\\r\\n\$(function() {\\r\\n\\tvar \$widget = $(\'#widget{{widget_id}}\');\\r\\n\\t\$widget.find(\'.cerb-search-trigger\')\\r\\n\\t\\t.cerbSearchTrigger()\\r\\n\\t\\t;\\r\\n});\\r\\n<\\/script>\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.context.saved.search',CONCAT('[',@last_tab_id,']'));

# Currency
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.currency','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Currency',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.currency\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"symbol\",\"code\",\"decimal_at\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.currency\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.currency',CONCAT('[',@last_tab_id,']'));

# Custom Field
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.custom_field','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Custom Field',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.custom_field\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"type\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.custom_field\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Custom Fieldset',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.custom_fieldset\",\"context_id\":\"{{record_custom_fieldset_id}}\",\"properties\":[[\"name\",\"owner\",\"updated_date\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.custom_field\"],\"label_singular\":[\"Field\"],\"label_plural\":[\"Fields\"],\"query\":[\"fieldset.id:{{record_custom_fieldset_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.custom_field',CONCAT('[',@last_tab_id,']'));

# Custom Fieldset
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.custom_fieldset','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Fieldset',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.custom_fieldset\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"updated_date\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Custom Fields',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.custom_field\",\"query_required\":\"fieldset.id:{{record_id}}\",\"query\":\"sort:pos,name\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_name\",\"c_context\",\"c_type\",\"c_updated_at\",\"c_pos\"]}','content',1,1,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.custom_fieldset',CONCAT('[',@last_tab_id,']'));

# Custom Record
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.custom_record','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Custom Record',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.custom_record\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"id\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Custom Fields',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.custom_field\",\"query_required\":\"context:contexts.custom_record.{{record_id}}\",\"query\":\"sort:pos,name subtotal:fieldset.id\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_name\",\"c_type\",\"c_custom_fieldset_id\",\"c_updated_at\",\"c_pos\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Records',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div id=\\\"widget{{widget_id}}\\\">\\r\\n\\t<button type=\\\"button\\\" class=\\\"cerb-search-trigger\\\" data-context=\\\"contexts.custom_record.{{record_id}}\\\" data-query=\\\"\\\">Search {{record_name|lower}} records<\\/button>\\r\\n<\\/div>\\r\\n<script type=\\\"text\\/javascript\\\">\\r\\n\$(function() {\\r\\n\\tvar \$widget = \$(\'#widget{{widget_id}}\');\\r\\n\\t\$widget.find(\'.cerb-search-trigger\')\\r\\n\\t\\t.cerbSearchTrigger()\\r\\n\\t\\t;\\r\\n});\\r\\n<\\/script>\"}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.custom_record',CONCAT('[',@last_tab_id,']'));

# Community Portal
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.portal','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Portal',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.portal\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"extension\",\"path\",\"code\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.portal\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Configure','cerberusweb.contexts.portal','cerb.profile.tab.portal.config','[]',UNIX_TIMESTAMP());
SET @tab_configure_id = LAST_INSERT_ID();
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Deploy','cerberusweb.contexts.portal','cerb.profile.tab.portal.deploy','[]',UNIX_TIMESTAMP());
SET @tab_deploy_id = LAST_INSERT_ID();
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.portal',CONCAT('[',CONCAT_WS(',',@last_tab_id,@tab_configure_id,@tab_deploy_id),']'));

# Email Signature
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.email.signature','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Properties',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.email.signature\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"is_default\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.group\",\"cerberusweb.contexts.bucket\"],\"label_singular\":[\"Group\",\"Bucket\"],\"label_plural\":[\"Groups\",\"Buckets\"],\"query\":[\"signature.id:{{record_id}}\",\"signature.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Signature',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<pre>\\r\\n{{record_signature}}\\r\\n<\\/pre>\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.email.signature\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.email.signature',CONCAT('[',@last_tab_id,']'));

# File Bundle
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.file_bundle','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('File Bundle',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.file_bundle\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"tag\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Files',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.attachment\",\"query_required\":\"bundle:(id:{{record_id}})\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"a_mime_type\",\"a_storage_size\",\"a_storage_extension\",\"a_storage_key\",\"a_updated\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.file_bundle',CONCAT('[',@last_tab_id,']'));

# Group
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.group','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Group',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.group\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"send_from\",\"send_as\",\"is_private\",\"template_id\",\"signature_id\",\"is_default\",\"created_at\",\"updated_at\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Buckets',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.bucket\",\"query_required\":\"group.id:{{record_id}}\",\"query\":\"sort:name\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"b_name\",\"b_is_default\",\"b_reply_address_id\",\"b_reply_personal\",\"b_reply_signature_id\",\"b_reply_html_template_id\",\"b_updated_at\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Members',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.worker\",\"query_required\":\"group:(id:{{record_id}})\",\"query\":\"sort:firstName\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"w_title\",\"a_address_email\",\"w_is_superuser\",\"w_at_mention_name\",\"w_language\",\"w_timezone\"]}','content',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Tickets',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.ticket\",\"query_required\":\"group.id:{{record_id}}\",\"query\":\"sort:-updated subtotal:status\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"t_last_wrote_address_id\",\"t_bucket_id\",\"t_owner_id\",\"t_updated_date\"]}','content',3,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Responsibilities','cerberusweb.contexts.group','cerb.profile.tab.dashboard','{}',UNIX_TIMESTAMP());
SET @tab_responsibilities_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Responsibilities',@tab_responsibilities_id,'cerb.profile.tab.widget.responsibilities','[]','',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.group',CONCAT('[',CONCAT_WS(',',@last_tab_id,@tab_responsibilities_id),']'));

# HTML Template
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.mail.html_template','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Email Template',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.mail.html_template\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.group\",\"cerberusweb.contexts.bucket\"],\"label_singular\":[\"Group\",\"Bucket\"],\"label_plural\":[\"Groups\",\"Buckets\"],\"query\":[\"signature.id:{{record_id}}\",\"signature.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Template',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<pre>\\r\\n{{record_content|escape}}\\r\\n<\/pre>\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.mail.html_template',CONCAT('[',@last_tab_id,']'));

# Mail Transport
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.mail.transport','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Mail Transport',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.mail.transport\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"extension\",\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Sender Addresses',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.address\",\"query_required\":\"mailTransport.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"a_contact_id\",\"o_name\",\"a_num_nonspam\",\"a_num_spam\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.mail.transport',CONCAT('[',@last_tab_id,']'));

# Mailbox
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.mailbox','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Mailbox',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.mailbox\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"enabled\",\"protocol\",\"host\",\"username\",\"port\",\"num_fails\",\"delay_until\",\"max_msg_size_kb\",\"ssl_ignore_validation\",\"auth_disable_plain\",\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recent Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.mailbox:(id:{{record_id}})\",\"query\":\"created:\\\"-1 year\\\" sort:-created\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.mailbox',CONCAT('[',@last_tab_id,']'));

# Organization
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.org','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Organization',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.org\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"email\",\"country\",\"phone\",\"website\",\"created\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.ticket\",\"cerberusweb.contexts.address\"],\"label_singular\":[\"Ticket\",\"Email Address\"],\"label_plural\":[\"Tickets\",\"Email Addresses\"],\"query\":[\"org.id:{{record_id}} subtotal:status\",\"org.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Contacts',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.contact\",\"query_required\":\"org.id:{{record_id}}\",\"query\":\"\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_title\",\"c_primary_email_id\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.org\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Log','cerberusweb.contexts.org','cerb.profile.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP());
SET @tab_log_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity Log',@tab_log_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.org:(id:{{record_id}})\",\"query\":\"sort:-created subtotal:activity\",\"render_limit\":\"15\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.org',CONCAT('[',CONCAT_WS(',',@last_tab_id,@tab_log_id),']'));

# Profile Tab
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.profile.tab','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Profile Tab',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.profile.tab\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"context\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.profile.widget\"],\"label_singular\":[\"Widget\"],\"label_plural\":[\"Widgets\"],\"query\":[\"tab.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.profile.tab\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.profile.tab',CONCAT('[',@last_tab_id,']'));

# Profile Widget
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.profile.widget','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Profile Widget',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.profile.widget\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"type\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Profile Tab',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.profile.tab\",\"context_id\":\"{{record_profile_tab_id}}\",\"properties\":[[\"name\",\"context\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.profile.widget\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.profile.widget',CONCAT('[',@last_tab_id,']'));

# Reminder
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.reminder','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Reminder',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.reminder\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"remind_at\",\"is_closed\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Worker',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.worker\",\"context_id\":\"{{record_worker_id}}\",\"properties\":[[\"name\",\"title\",\"email\",\"location\",\"timezone\"]]}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.reminder:(id:{{record_id}})\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.reminder',CONCAT('[',@last_tab_id,']'));

# Role
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.role','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Role',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.role\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"updated_at\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Members',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.worker\",\"query_required\":\"role:(id:{{record_id}})\",\"query\":\"\",\"render_limit\":\"10\",\"header_color\":\"#6a87db\",\"columns\":[\"w_title\",\"w_location\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Privileges',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"...\"}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.role',CONCAT('[',@last_tab_id,']'));

# Snippet
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.snippet','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Snippet',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.snippet\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"owner\",\"context\",\"total_uses\",\"updated\"]]}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Content',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<pre>\\r\\n{{record_content|escape}}\\r\\n<\\/pre>\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.snippet',CONCAT('[',@last_tab_id,']'));

# Task
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.task','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Status',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div style=\\\"text-align:center;\\\">\\r\\n\\t{% if record_status == \'waiting\' %}\\r\\n\\t\\t<div style=\\\"font-size:2em;color:rgb(72,125,179);font-weight:bold;\\\">\\r\\n\\t\\t\\t{{record_status|capitalize}}\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% if record_reopen %}\\r\\n\\t\\t<div style=\\\"font-size:1em;\\\">\\r\\n\\t\\t\\t(<abbr title=\\\"{{record_reopen|date(\'F d, Y g:ia\')}}\\\">{{record_reopen|date_pretty}}<\\/abbr>)\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% endif %}\\t\\t\\r\\n\\t{% elseif record_status == \'closed\' %}\\r\\n\\t\\t<div style=\\\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\\\">\\r\\n\\t\\t\\t{{record_status|capitalize}}\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% if record_completed %}\\r\\n\\t\\t<div style=\\\"font-size:1em;\\\">\\r\\n\\t\\t\\t(<abbr title=\\\"{{record_completed|date(\'F d, Y g:ia\')}}\\\">{{record_completed|date_pretty}}<\\/abbr>)\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% endif %}\\r\\n\\t{% else %}\\r\\n\\t\\t<div style=\\\"font-size:2em;color:rgb(102,172,87);font-weight:bold;\\\">\\r\\n\\t\\t\\t{{record_status|capitalize}}\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% if record_due %}\\r\\n\\t\\t<div style=\\\"font-size:1em;\\\">\\r\\n\\t\\t\\t(due <abbr title=\\\"{{record_due|date(\'F d, Y g:ia\')}}\\\">{{record_due|date_pretty}}<\\/abbr>)\\r\\n\\t\\t<\\/div>\\r\\n\\t\\t{% endif %}\\t\\t\\r\\n\\t{% endif %}\\r\\n<\\/div>\"}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Task',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.task\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"due_date\",\"importance\",\"created_at\",\"updated_date\"]],\"links\":{\"show\":\"1\"}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Owner',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.worker\",\"context_id\":\"{{record_owner_id}}\",\"properties\":[[\"name\",\"location\",\"timezone\",\"calendar_id\"]]}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Recent Activity',@last_tab_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.task:(id:{{record_id}})\",\"query\":\"sort:-created\",\"render_limit\":\"5\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"cerberusweb.contexts.task\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.task',CONCAT('[',@last_tab_id,']'));

# Ticket
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.ticket','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_right\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Conversation',@last_tab_id,'cerb.profile.tab.widget.ticket.convo','[]','content',1,4,UNIX_TIMESTAMP());
SET @widget_convo_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Status',@last_tab_id,'cerb.profile.tab.widget.html','{\"template\":\"<div style=\\\"text-align:center;\\\">\\r\\n\\t{% if record_status == \'waiting\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(85,132,204);font-weight:bold;\\\">\\r\\n\\t\\t{{\'status.waiting.abbr\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% elseif record_status == \'closed\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\\\">\\r\\n\\t\\t{{\'status.closed\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% elseif record_status == \'deleted\' %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(211,53,43);font-weight:bold;\\\">\\r\\n\\t\\t{{\'status.deleted\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% else %}\\r\\n\\t<div style=\\\"font-size:2em;color:rgb(102,172,87);font-weight:bold;\\\">\\r\\n\\t\\t{{\'status.open\'|cerb_translate|capitalize}}\\r\\n\\t<\\/div>\\r\\n\\t{% endif %}\\r\\n\\t\\r\\n\\t{% if record_status_id in [1,2] and record_reopen_date %}\\r\\n\\t<div style=\\\"font-size:1em;\\\">\\r\\n\\t\\t(<abbr title=\\\"{{record_reopen_date|date(\'F d, Y g:ia\')}}\\\">{{record_reopen_date|date_pretty}}<\\/abbr>)\\r\\n\\t<\\/div>\\r\\n\\t{% endif %}\\r\\n<\\/div>\"}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Actions',@last_tab_id,'cerb.profile.tab.widget.html',REPLACE('{\"template\":\"{% if not cerb_record_writeable(record__context, record_id) %}\\r\\n\\t<div style=\\\"color:rgb(120,120,120);text-align:center;font-size:1.2em;\\\">\\r\\n\\t\\t(you do not have permission to edit this record)\\r\\n\\t<\\/div>\\r\\n\\t\\r\\n{% else %}\\r\\n\\t{% set is_closed = \'closed\' == record_status %}\\r\\n\\t{% set is_deleted = \'deleted\' == record_status %}\\r\\n\\t\\r\\n\\t<div id=\\\"widget{{widget_id}}\\\" style=\\\"padding:0px 5px 5px 5px;\\\">\\r\\n\\t\\t{% if is_closed or is_deleted %}\\r\\n\\t\\t<button type=\\\"button\\\" class=\\\"cerb-peek-editor\\\" data-context=\\\"{{record__context}}\\\" data-context-id=\\\"{{record_id}}\\\" data-edit=\\\"status:o\\\" data-shortcut=\\\"reopen\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-upload\\\"><\\/span> {{\'common.reopen\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t\\t{% endif %}\\r\\n\\t\\t\\r\\n\\t\\t{% if not is_deleted and not is_closed and cerb_has_priv(\'core.ticket.actions.close\') %}\\r\\n\\t\\t\\t<button type=\\\"button\\\" class=\\\"cerb-peek-editor\\\" data-context=\\\"{{record__context}}\\\" data-context-id=\\\"{{record_id}}\\\" data-edit=\\\"status:c\\\" data-shortcut=\\\"close\\\" title=\\\"(C)\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-circle-ok\\\"><\\/span> {{\'common.close\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t\\t{% endif %}\\r\\n\\t\\t\\r\\n\\t\\t{% if record_spam_training is empty and not is_deleted and cerb_has_priv(\'core.ticket.actions.spam\') %}\\r\\n\\t\\t<button type=\\\"button\\\" class=\\\"cerb-peek-editor\\\" data-context=\\\"{{record__context}}\\\" data-context-id=\\\"{{record_id}}\\\" data-edit=\\\"status:d spam:y\\\" data-shortcut=\\\"spam\\\" title=\\\"(S)\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-ban\\\"><\\/span> {{\'common.spam\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t\\t{% endif %}\\r\\n\\r\\n\\t\\t{% if not is_deleted and cerb_has_priv(\'cerberusweb.contexts.ticket.delete\') %}\\r\\n\\t\\t<button type=\\\"button\\\" class=\\\"cerb-peek-editor\\\" data-context=\\\"{{record__context}}\\\" data-context-id=\\\"{{record_id}}\\\" data-edit=\\\"status:d\\\" data-shortcut=\\\"delete\\\" title=\\\"(X)\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-circle-remove\\\"><\\/span> {{\'common.delete\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t\\t{% endif %}\\r\\n\\t\\t\\r\\n\\t\\t{%if cerb_has_priv(\'core.ticket.actions.merge\') %}\\r\\n\\t\\t<button type=\\\"button\\\" data-shortcut=\\\"merge\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-git-merge\\\"><\\/span> {{\'common.merge\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t\\t{% endif %}\\r\\n\\t\\t\\r\\n\\t\\t<button type=\\\"button\\\" data-shortcut=\\\"read-all\\\" title=\\\"(A)\\\">\\r\\n\\t\\t\\t<span class=\\\"glyphicons glyphicons-book-open\\\"><\\/span> {{\'display.button.read_all\'|cerb_translate|capitalize}}\\r\\n\\t\\t<\\/button>\\r\\n\\t<\\/div>\\r\\n\\t\\r\\n\\t<script type=\\\"text\\/javascript\\\">\\r\\n\\t\$(function() {\\r\\n\\t\\tvar \$widget = \$(\'#widget{{widget_id}}\');\\r\\n\\t\\tvar \$parent = \$widget.closest(\'.cerb-profile-widget\')\\r\\n\\t\\t\\t.off(\'.widget{{widget_id}}\')\\r\\n\\t\\t\\t;\\r\\n\\t\\tvar \$tab = \$parent.closest(\'.cerb-profile-layout\');\\r\\n\\t\\t\\r\\n\\t\\t\$widget.find(\'button.cerb-peek-editor\')\\r\\n\\t\\t\\t.cerbPeekTrigger()\\r\\n\\t\\t\\t.on(\'cerb-peek-saved\', function(e) {\\r\\n\\t\\t\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\t\\t\\tdocument.location.reload();\\r\\n\\t\\t\\t})\\r\\n\\t\\t\\t;\\r\\n\\t\\t\\r\\n\\t\\tvar \$button_close = \$widget.find(\'button[data-shortcut=\\\"close\\\"]\');\\r\\n\\t\\tvar \$button_delete = \$widget.find(\'button[data-shortcut=\\\"delete\\\"]\');\\r\\n\\t\\tvar \$button_spam = \$widget.find(\'button[data-shortcut=\\\"spam\\\"]\');\\r\\n\\t\\tvar \$button_merge = \$widget.find(\'button[data-shortcut=\\\"merge\\\"]\');\\r\\n\\t\\tvar \$button_readall = \$widget.find(\'button[data-shortcut=\\\"read-all\\\"]\');\\r\\n\\t\\r\\n\\t\\t{% if cerb_has_priv(\'core.ticket.actions.merge\') %}\\r\\n\\t\\t\$button_merge\\r\\n\\t\\t\\t.on(\'click\', function(e) {\\r\\n\\t\\t\\t\\tvar \$merge_popup = genericAjaxPopup(\'peek\',\'c=internal&a=showRecordsMergePopup&context={{record__context}}&ids={{record_id}}\',null,false,\'50%\');\\r\\n\\t\\t\\t\\t\\r\\n\\t\\t\\t\\t\$merge_popup.on(\'record_merged\', function(e) {\\r\\n\\t\\t\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\t\\t\\tdocument.location.reload();\\r\\n\\t\\t\\t\\t});\\r\\n\\t\\t\\t})\\r\\n\\t\\t\\t;\\r\\n\\t\\t{% endif %}\\r\\n\\t\\t\\r\\n\\t\\t\$button_readall.on(\'click\', function(e) {\\r\\n\\t\\t\\tvar evt = \$.Event(\'cerb-widget-refresh\');\\r\\n\\t\\t\\tevt.widget_id = {{WIDGET_CONVO_ID}};\\r\\n\\t\\t\\tevt.refresh_options = {\'expand_all\': 1};\\r\\n\\t\\t\\t\$tab.triggerHandler(evt);\\r\\n\\t\\t});\\r\\n\\t\\t\\r\\n\\t\\t\$parent.on(\'keydown.widget{{widget_id}}\', null, \'A\', function(e) {\\r\\n\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\te.preventDefault();\\r\\n\\t\\t\\t\$button_readall.click();\\r\\n\\t\\t});\\r\\n\\r\\n\\t\\t\$parent.on(\'keydown.widget{{widget_id}}\', null, \'C\', function(e) {\\r\\n\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\te.preventDefault();\\r\\n\\t\\t\\t\$button_close.click();\\r\\n\\t\\t});\\r\\n\\t\\t\\r\\n\\t\\t\$parent.on(\'keydown.widget{{widget_id}}\', null, \'S\', function(e) {\\r\\n\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\te.preventDefault();\\r\\n\\t\\t\\t\$button_spam.click();\\r\\n\\t\\t});\\r\\n\\r\\n\\t\\t\$parent.on(\'keydown.widget{{widget_id}}\', null, \'X\', function(e) {\\r\\n\\t\\t\\te.stopPropagation();\\r\\n\\t\\t\\te.preventDefault();\\r\\n\\t\\t\\t\$button_delete.click();\\r\\n\\t\\t});\\r\\n\\t\\t\\r\\n\\t});\\r\\n\\t<\\/script>\\r\\n{% endif %}\"}', '{{WIDGET_CONVO_ID}}', @widget_convo_id),'sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Ticket',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.ticket\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"group_id\",\"bucket_id\",\"importance\",\"cf_180\",\"created\",\"updated\",\"closed\",\"elapsed_response_first\",\"elapsed_resolution_first\",\"spam_score\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.message\",\"cerberusweb.contexts.address\",\"cerberusweb.contexts.ticket\"],\"label_singular\":[\"Message\",\"Participant\",\"Participant History\"],\"label_plural\":[\"Message\",\"Participants\",\"Participant History\"],\"query\":[\"ticket.id:{{record_id}}\",\"ticket.id:{{record_id}}\",\"participant.id:[{{record_requesters|keys|join(\',\')}}] subtotal:status\"]}}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Organization',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.org\",\"context_id\":\"{{record_org_id}}\",\"properties\":{\"0\":[\"_label\",\"country\",\"phone\"],\"12\":[\"cf_42\",\"cf_43\"]},\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.ticket\"],\"label_singular\":[\"Ticket\"],\"label_plural\":[\"Tickets\"],\"query\":[\"org.id:{{record_org_id}}\"]}}','sidebar',4,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Owner',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.worker\",\"context_id\":\"{{record_owner_id}}\",\"properties\":[[\"name\",\"location\",\"timezone\",\"calendar_id\"]]}','sidebar',5,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Log','cerberusweb.contexts.ticket','cerb.profile.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP());
SET @tab_log_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Activity Log',@tab_log_id,'cerb.profile.tab.widget.worklist','{\"context\":\"cerberusweb.contexts.activity_log\",\"query_required\":\"target.ticket:(id:{{record_id}})\",\"query\":\"sort:-created subtotal:activity\",\"render_limit\":\"15\",\"header_color\":\"#6a87db\",\"columns\":[\"c_created\"]}','',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Anti-Spam','cerberusweb.contexts.ticket','cerb.profile.tab.dashboard','{\"layout\":\"\"}',UNIX_TIMESTAMP());
SET @tab_spam_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Spam analysis',@tab_spam_id,'cerb.profile.tab.widget.ticket.spam_analysis','[]','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.ticket',CONCAT('[',CONCAT_WS(',',@last_tab_id,@tab_log_id),']'));

# Worker
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.worker','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Worker',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.worker\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"email\",\"location\",\"is_superuser\",\"mobile\",\"phone\",\"language\",\"timezone\",\"calendar_id\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.group\"],\"label_singular\":[\"Group\"],\"label_plural\":[\"Groups\"],\"query\":[\"member.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Calendar',@last_tab_id,'cerb.profile.tab.widget.calendar','{\"context_id\":\"{{record_calendar_id}}\"}','content',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Availability','cerberusweb.contexts.worker','cerb.profile.tab.dashboard','{}',UNIX_TIMESTAMP());
SET @tab_avail_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Availability',@tab_avail_id,'cerb.profile.tab.widget.calendar.availability','{\"calendar_id\":\"{{record_calendar_id}}\"}','',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Responsibilities','cerberusweb.contexts.worker','cerb.profile.tab.dashboard','{}',UNIX_TIMESTAMP());
SET @tab_resp_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Responsibilities',@tab_resp_id,'cerb.profile.tab.widget.responsibilities','[]','',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Settings','cerberusweb.contexts.worker','cerb.profile.tab.worker.settings','[]',UNIX_TIMESTAMP());
SET @tab_settings_id = LAST_INSERT_ID();
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.worker',CONCAT('[',CONCAT_WS(',',@last_tab_id,@tab_avail_id,@tab_resp_id,@tab_settings_id),']'));

# Workspace List
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.workspace.list','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace Worklist',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.list\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace Tab',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.tab\",\"context_id\":\"{{record_tab_id}}\",\"properties\":[[\"name\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.widget\",\"cerberusweb.contexts.workspace.list\"],\"label_singular\":[\"Widget\",\"Worklist\"],\"label_plural\":[\"Widgets\",\"Worklists\"],\"query\":[\"tab.id:{{record_tab_id}}\",\"tab.id:{{record_tab_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.page\",\"context_id\":\"{{record_tab_page_id}}\",\"properties\":[[\"name\",\"owner\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.tab\"],\"label_singular\":[\"Tab\"],\"label_plural\":[\"Tabs\"],\"query\":[\"page.id:{{record_tab_page_id}}\"]}}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.workspace.list',CONCAT('[',@last_tab_id,']'));

# Workspace Tab
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.workspace.tab','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace Tab',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.tab\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.widget\",\"cerberusweb.contexts.workspace.list\"],\"label_singular\":[\"Widget\",\"Worklist\"],\"label_plural\":[\"Widgets\",\"Worklists\"],\"query\":[\"tab.id:{{record_id}}\",\"tab.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.page\",\"context_id\":\"{{record_page_id}}\",\"properties\":[[\"name\",\"owner\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.tab\"],\"label_singular\":[\"Tab\"],\"label_plural\":[\"Tabs\"],\"query\":[\"page.id:{{record_page_id}}\"]}}','sidebar',2,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.workspace.tab',CONCAT('[',@last_tab_id,']'));

# Workspace Widget
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.workspace.widget','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Dashboard Widget',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.widget\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"extension_id\",\"cache_ttl\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Dashboard Tab',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.tab\",\"context_id\":\"{{record_tab_id}}\",\"properties\":[[\"name\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.widget\",\"cerberusweb.contexts.workspace.list\"],\"label_singular\":[\"Widget\",\"Worklist\"],\"label_plural\":[\"Widgets\",\"Worklists\"],\"query\":[\"tab.id:{{record_tab_id}}\",\"tab.id:{{record_tab_id}}\"]}}','sidebar',2,1,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Workspace',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.workspace.page\",\"context_id\":\"{{record_tab_page_id}}\",\"properties\":[[\"name\",\"owner\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.workspace.tab\"],\"label_singular\":[\"Tab\"],\"label_plural\":[\"Tabs\"],\"query\":[\"page.id:{{record_tab_page_id}}\"]}}','sidebar',3,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.workspace.widget',CONCAT('[',@last_tab_id,']'));
EOD;
	
	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
	
	// Create default profiles for custom records
	
	$results = $db->GetArrayMaster("SELECT id,name FROM custom_record");
	
	foreach($results as $result) {
		$context_ext_id = sprintf("contexts.custom_record.%d", $result['id']);
		$record_name = $result['name'];
		
		$sqls = <<< EOD
SET @context = %s;
SET @record_name = %s;
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview',@context,'cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES (@record_name,@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"%s\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"created\",\"updated\"]],\"links\":{\"show\":\"1\"}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Discussion',@last_tab_id,'cerb.profile.tab.widget.comments','{\"context\":\"%s\",\"context_id\":\"{{record_id}}\",\"height\":\"\"}','content',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:%s',CONCAT('[',@last_tab_id,']'));
EOD;
		
		$sqls = sprintf($sqls,
			$db->qstr($context_ext_id),
			$db->qstr($record_name),
			$context_ext_id,
			$context_ext_id,
			$context_ext_id
		);
		
		foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
			$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
			$db->ExecuteMaster($sql);
		}
	}
}

// ===========================================================================
// Insert default search buttons

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.profile.tab'),
	$db->qstr('[{"context":"cerberusweb.contexts.profile.widget","label_singular":"Widget","label_plural":"Widgets","query":"tab.id:{{id}}"}]')
));

// ===========================================================================
// Drop `view_filters_preset` (this is now `context_saved_search`)

if(isset($tables['view_filters_preset'])) {
	$db->ExecuteMaster('DROP TABLE view_filters_preset');
	unset($tables['view_filters_preset']);
}

// ===========================================================================
// Add `params_query` and remove `params_hidden_json` from `worker_view_model`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('worker_view_model');

if(!isset($columns['params_query'])) {
	$sql = 'ALTER TABLE worker_view_model ADD COLUMN params_query TEXT AFTER columns_hidden_json';
	$db->ExecuteMaster($sql);
}

if(isset($columns['params_hidden_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN params_hidden_json';
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove old worker prefs

$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mail_display_inline_log'");
$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mail_always_show_all'");

// ===========================================================================
// Add `context_to_custom_fieldset`

if(!isset($tables['context_to_custom_fieldset'])) {
	$sql = sprintf("
	CREATE TABLE `context_to_custom_fieldset` (
		context VARCHAR(255) DEFAULT '',
		context_id INT UNSIGNED NOT NULL,
		custom_fieldset_id INT UNSIGNED NOT NULL,
		primary key (context,custom_fieldset_id, context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['context_to_custom_fieldset'] = 'context_to_custom_fieldset';
	
	// Populate `context_to_custom_fieldset`
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_stringvalue.context, context_id, custom_field.custom_fieldset_id from custom_field_stringvalue inner join custom_field on (custom_field_stringvalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_numbervalue.context, context_id, custom_field.custom_fieldset_id from custom_field_numbervalue inner join custom_field on (custom_field_numbervalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_clobvalue.context, context_id, custom_field.custom_fieldset_id from custom_field_clobvalue inner join custom_field on (custom_field_clobvalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
}

// ===========================================================================
// Remove `context_link` records for custom fieldsets

//$db->ExecuteMaster("DELETE FROM context_link WHERE from_context = 'cerberusweb.contexts.custom_fieldset'");
//$db->ExecuteMaster("DELETE FROM context_link WHERE to_context = 'cerberusweb.contexts.custom_fieldset'");

// ===========================================================================
// Clean up custom fieldset link logs

//$db->ExecuteMaster("DELETE FROM context_activity_log WHERE target_context = 'cerberusweb.contexts.custom_fieldset'");
//$db->ExecuteMaster("DELETE FROM context_activity_log WHERE activity_point IN ('connection.link','connection.unlink') AND entry_json like '%\"link_object\":\"custom fieldset\"%'");

// ===========================================================================
// Add `created_at` to all custom record tables

foreach(array_keys($tables) as $table_name) {
	if(!DevblocksPlatform::strStartsWith($table_name, 'custom_record_'))
		continue;
	
	list($columns,) = $db->metaTable($table_name);
	
	if(!isset($columns['created_at'])) {
		$sql = sprintf("ALTER TABLE %s ADD COLUMN created_at INT UNSIGNED NOT NULL DEFAULT 0",
			$db->escape($table_name)
		);
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster(sprintf("UPDATE %s SET created_at=updated_at WHERE created_at=0",
			$db->escape($table_name)
		));
	}
}

// ===========================================================================
// Add `width_units` and `zone` to all `workspace_widget`

list($columns,) = $db->metaTable('workspace_widget');

if($columns['pos'] && 0 == strcasecmp('char(4)', $columns['pos']['type'])) {
	$sql = "ALTER TABLE workspace_widget CHANGE COLUMN pos pos_legacy char(4)";
	$db->ExecuteMaster($sql);
	
	$sql = "ALTER TABLE workspace_widget ADD COLUMN pos tinyint(255) default 0";
	$db->ExecuteMaster($sql);
	
	$sql = "set @rank := 0, @tab_id := ''";
	$db->ExecuteMaster($sql);
	
	$sql = "create temporary table _tmp_widget_pos select workspace_tab.id as workspace_tab_id, blah.id as widget_id, blah.rank, blah.col_pos, blah.col_num, blah.id from workspace_tab inner join (select @rank:=if(@tab_id = workspace_widget.workspace_tab_id,@rank+1,1) as rank, @tab_id:=workspace_widget.workspace_tab_id, workspace_widget.id, workspace_widget.workspace_tab_id, workspace_widget.label, workspace_widget.pos, substring(workspace_widget.pos_legacy,1,1) as col_num, substring(workspace_widget.pos_legacy,2) as col_pos from workspace_widget order by workspace_tab_id, col_pos, col_num) as blah on (workspace_tab.extension_id = 'core.workspace.tab' and blah.workspace_tab_id=workspace_tab.id)";
	$db->ExecuteMaster($sql);
	
	$sql = "update workspace_widget inner join _tmp_widget_pos on (_tmp_widget_pos.widget_id=workspace_widget.id) SET pos = _tmp_widget_pos.rank";
	$db->ExecuteMaster($sql);
	
	$sql = "ALTER TABLE workspace_widget DROP COLUMN pos_legacy";
	$db->ExecuteMaster($sql);
	
	$sql = "drop table _tmp_widget_pos";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['width_units'])) {
	$sql = "ALTER TABLE workspace_widget ADD COLUMN width_units TINYINT UNSIGNED NOT NULL DEFAULT 1";
	$db->ExecuteMaster($sql);
	
	$sql = "UPDATE workspace_widget SET width_units = 2";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['zone'])) {
	$sql = "ALTER TABLE workspace_widget ADD COLUMN zone VARCHAR(255) NOT NULL DEFAULT ''";
	$db->ExecuteMaster($sql);
	
	$sql = "UPDATE workspace_widget SET zone = 'content' ";
	$db->ExecuteMaster($sql);
}

if(isset($columns['cache_ttl'])) {
	$sql = "ALTER TABLE workspace_widget DROP COLUMN cache_ttl";
	$db->ExecuteMaster($sql);
}

// Migrate legacy dashboards to the new format
$sql = "UPDATE workspace_tab SET extension_id = 'core.workspace.tab.dashboard', params_json='{\"layout\":\"\"}' WHERE extension_id = 'core.workspace.tab'";
$db->ExecuteMaster($sql);

// ===========================================================================
// Migrate legacy workspace worklist widgets to the new config format

list($columns,) = $db->metaTable('workspace_widget');

$sql = "SELECT id, params_json FROM workspace_widget WHERE extension_id = 'core.workspace.widget.worklist' AND params_json like '%\"worklist_model\"%'";
$results = $db->ExecuteMaster($sql);

foreach($results as $result) {
	if(false == ($old_json = json_decode($result['params_json'], true)))
		continue;
	
	@$worklist_context = $old_json['worklist_model']['context'] ?: '';
	@$worklist_columns = $old_json['worklist_model']['columns'] ?: [];
	@$worklist_query = $old_json['quick_search'] ?: '';
	@$worklist_search_mode = $old_json['worklist_model']['search_mode'] ?: '';
	@$worklist_params = $old_json['worklist_model']['params'] ?: [];
	@$worklist_limit = $old_json['worklist_model']['limit'] ?: 5;
	
	$new_json = [
		'context' => $worklist_context,
		'query_required' => $worklist_query,
		'query' => '',
		'render_limit' => $worklist_limit,
		'header_color' => '#6a87db',
		'columns' => $worklist_columns,
	];
	
	$changes = [];
	
	// Store a remidner of the old params
	if($worklist_search_mode != 'quick_search') {
		$changes['label'] = "CONCAT_WS(' ',label,'(!)')";
		$new_json['query'] = '{# ' . json_encode($worklist_params) . ' #}';
	}
	
	$changes['params_json'] = $db->qstr(json_encode($new_json));
	
	$sql = sprintf("UPDATE workspace_widget SET %s WHERE id = %d",
		implode(', ', array_map(function($k,$v) {
			return sprintf("%s = %s", $k, $v);
		}, array_keys($changes), $changes)),
		$result['id']
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove legacy reports pages

$db->ExecuteMaster("DELETE FROM workspace_page WHERE extension_id = 'reports.workspace.page'");

// ===========================================================================
// Convert logo URLs to custom stylesheets

$logo_url = $db->GetOneMaster("SELECT value FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' and setting = 'helpdesk_logo_url'");

if($logo_url) {
	$custom_css = sprintf("#cerb-logo {\n\tbackground: url(%s) no-repeat;\n\tbackground-size: contain;\n\twidth: 500px;\n\theight: 80px;\n}", $logo_url);
	
	$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','ui_user_stylesheet', %s), ('cerberusweb.core','ui_user_stylesheet_updated_at', UNIX_TIMESTAMP())",
		$db->qstr($custom_css)
	));
	
	$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' and setting = 'helpdesk_logo_url'");
}

// ===========================================================================
// Migrate legacy workspace calendar tabs to dashboards

list($columns,) = $db->metaTable('workspace_tab');

$sql = "SELECT id, params_json FROM workspace_tab WHERE extension_id = 'core.workspace.tab.calendar'";
$results = $db->GetArrayMaster($sql);

if(is_array($results))
foreach($results as $result) {
	@$params = json_decode($result['params_json'], true);
	$calendar_id = $params['calendar_id'];
	
	// Switch the tab to a dashboard
	$sql = sprintf("UPDATE workspace_tab SET extension_id = 'core.workspace.tab.dashboard', params_json = %s WHERE id = %d",
		$db->qstr('{"layout":""}'),
		$result['id']
	);
	$db->ExecuteMaster($sql);
	
	// Add a calendar widget
	$sql = sprintf("INSERT INTO workspace_widget (workspace_tab_id,extension_id,label,updated_at,params_json,width_units,zone,pos) ".
		"VALUES (%d,%s,%s,%d,%s,%d,%s,%d)",
		$result['id'],
		$db->qstr('core.workspace.widget.calendar'),
		$db->qstr('Calendar'),
		time(),
		$db->qstr(sprintf('{"calendar_id":%d}', $calendar_id)),
		4,
		$db->qstr('content'),
		1
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add an 'Actions' widget to worker profiles

// Find the tab ID for 'Overview' on worker profiles
$sql = "SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.worker' AND name = 'Overview' LIMIT 1";
$worker_overview_tab_id = $db->GetOneMaster($sql);

// Add the 'Actions' widget to worker profiles
if($worker_overview_tab_id) {
	$sql = sprintf("SELECT id FROM profile_widget WHERE profile_tab_id = %d AND name = 'Actions'", $worker_overview_tab_id);
	$actions_widget_id = $db->GetOneMaster($sql);
	
	if(!$actions_widget_id) {
		$sql = sprintf("INSERT IGNORE INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone) ".
			"VALUES (%s, %d, %s, %s, %d, %d, %d, %s)",
			$db->qstr('Actions'),
			$worker_overview_tab_id,
			$db->qstr('cerb.profile.tab.widget.html'),
			$db->qstr('{"template":"{% set is_editable = cerb_record_writeable(record__context, record_id) %}\r\n{% if is_editable %}\r\n\t<div style=\"padding:0px 5px 5px 5px;\">\r\n\t\t<button type=\"button\" data-shortcut=\"impersonate\">\r\n\t\t\t<span class=\"glyphicons glyphicons-user\"><\/span> {{\'common.impersonate\'|cerb_translate|capitalize}}\r\n\t\t<\/button>\r\n\t<\/div>\r\n{% endif %}\r\n\r\n<script type=\"text\/javascript\">\r\n$(function() {\r\n\tvar $widget = $(\'#profileWidget{{widget_id}}\');\r\n\r\n\t{% if is_editable %}\r\n\t\tvar $button_impersonate = $widget.find(\'button[data-shortcut=\"impersonate\"]\');\r\n\t\r\n\t\t$button_impersonate\r\n\t\t\t.on(\'click\', function(e) {\r\n\t\t\t\tgenericAjaxGet(\'\',\'c=internal&a=su&worker_id={{record_id}}\',function(o) {\r\n\t\t\t\t\twindow.location = window.location;\r\n\t\t\t\t});\r\n\t\t\t})\r\n\t\t\t;\r\n\t{% else %}\r\n\t\t$widget\r\n\t\t\t.closest(\'.cerb-profile-widget\')\r\n\t\t\t.remove()\r\n\t\t\t;\r\n\t{% endif %}\r\n});\r\n<\/script>"}'),
			time(),
			2,
			4,
			$db->qstr('sidebar')
		);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add 'move to' functionality to the 'Status' widget on ticket profiles

$checksum = $db->GetOneMaster("SELECT sha1(extension_params_json) from profile_widget WHERE name = 'Status' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')");

if(in_array($checksum, ['35967c4eda4357c74aa13add87711fe7f868d69a','77d1fe2740a8868d02d4c000ef84abdfce979645'])) {
	$json = '{"template":"{% set is_writeable = cerb_record_writeable(record__context,record_id,current_worker__context,current_worker_id) %}\r\n<div id=\"widget{{widget_id}}\">\r\n\t<div style=\"float:left;padding:0 10px 5px 5px;\">\r\n\t\t<img src=\"{{cerb_avatar_url(record_group__context,record_group_id,record_group_updated)}}\" width=\"50\" style=\"border-radius:50px;\">\r\n\t<\/div>\r\n\t<div style=\"position:relative;\">\r\n\t\t<div>\r\n\t\t\t<a href=\"javascript:;\" class=\"cerb-peek-trigger no-underline\" style=\"font-size:1.3em;color:rgb(150,150,150);font-weight:bold;\" data-context=\"cerberusweb.contexts.group\" data-context-id=\"{{record_group_id}}\">{{record_group__label}}<\/a>\r\n\t\t<\/div>\r\n\t\t<div>\r\n\t\t\t<a href=\"javascript:;\" class=\"cerb-peek-trigger no-underline\" style=\"font-size:2em;color:rgb(100,100,100);font-weight:bold;\" data-context=\"cerberusweb.contexts.bucket\" data-context-id=\"{{record_bucket_id}}\">{{record_bucket__label}}<\/a>\r\n\t\t\t\r\n\t\t\t{% if is_writeable %}\r\n\t\t\t<div class=\"cerb-buttons-toolbar\" style=\"display:none;position:absolute;top:0;right:0;\">\r\n\t\t\t\t<button type=\"button\" title=\"{{\'common.move\'|cerb_translate|capitalize}}\" class=\"cerb-button-move cerb-chooser-trigger\" data-context=\"cerberusweb.contexts.bucket\" data-single=\"true\" data-query=\"subtotal:group.id\">\r\n\t\t\t\t\t<span class=\"glyphicons glyphicons-send\"><\/span>\r\n\t\t\t\t<\/button>\r\n\t\t\t<\/div>\r\n\t\t\t{% endif %}\r\n\t\t<\/div>\r\n\t<\/div>\r\n\t<div style=\"margin-top:5px;font-size:1.5em;font-weight:bold;\">\r\n\t\t{% if record_status == \'waiting\' %}\r\n\t\t<div style=\"color:rgb(85,132,204);\">\r\n\t\t\t{{\'status.waiting.abbr\'|cerb_translate|capitalize}}\r\n\t\t\t{% if record_reopen_date %}\r\n\t\t\t<span style=\"font-size:0.8em;font-weight:normal;color:black;\">\r\n\t\t\t\t(<abbr title=\"{{record_reopen_date|date(\'F d, Y g:ia\')}}\">{{record_reopen_date|date_pretty}}<\/abbr>)\r\n\t\t\t<\/span>\r\n\t\t\t{% endif %}\r\n\t\t<\/div>\r\n\t\t{% elseif record_status == \'closed\' %}\r\n\t\t<div style=\"color:rgb(100,100,100);\">\r\n\t\t\t{{\'status.closed\'|cerb_translate|capitalize}}\r\n\t\t\t{% if record_reopen_date %}\r\n\t\t\t<span style=\"font-size:0.8em;font-weight:normal;color:black;\">\r\n\t\t\t\t(<abbr title=\"{{record_reopen_date|date(\'F d, Y g:ia\')}}\">{{record_reopen_date|date_pretty}}<\/abbr>)\r\n\t\t\t<\/span>\r\n\t\t\t{% endif %}\r\n\t\t<\/div>\r\n\t\t{% elseif record_status == \'deleted\' %}\r\n\t\t<div style=\"color:rgb(211,53,43);\">\r\n\t\t\t{{\'status.deleted\'|cerb_translate|capitalize}}\r\n\t\t<\/div>\r\n\t\t{% else %}\r\n\t\t<div style=\"color:rgb(102,172,87);\">\r\n\t\t\t{{\'status.open\'|cerb_translate|capitalize}}\r\n\t\t<\/div>\r\n\t\t{% endif %}\r\n\t<\/div>\r\n<\/div>\r\n\r\n<script type=\"text\/javascript\">\r\n$(function() {\r\n\tvar $widget = $(\'#widget{{widget_id}}\');\r\n\tvar $parent = $widget.closest(\'.cerb-profile-widget\')\r\n\t\t.off(\'.widget{{widget_id}}\')\r\n\t\t;\r\n\tvar $toolbar = $widget.find(\'div.cerb-buttons-toolbar\');\r\n\tvar $tab = $parent.closest(\'.cerb-profile-layout\');\r\n\r\n\t$widget.find(\'.cerb-peek-trigger\')\r\n\t\t.cerbPeekTrigger()\r\n\t\t;\r\n\t\t\r\n\t{% if is_writeable %}\r\n\t$widget\r\n\t\t.on(\'mouseover\', function() {\r\n\t\t\t$toolbar.show();\r\n\t\t})\r\n\t\t.on(\'mouseout\', function() {\r\n\t\t\t$toolbar.hide();\r\n\t\t})\r\n\t\t;\r\n\t{% endif %}\r\n\t\r\n\t{% if is_writeable %}\r\n\t$widget.find(\'.cerb-chooser-trigger\')\r\n\t\t.cerbChooserTrigger()\r\n\t\t.on(\'cerb-chooser-selected\', function(e) {\r\n\t\t\tif(!e.values || !$.isArray(e.values))\r\n\t\t\t\treturn;\r\n\t\t\t\t\r\n\t\t\tif(e.values.length != 1)\r\n\t\t\t\treturn;\r\n\r\n\t\t\tvar bucket_id = e.values[0];\r\n\t\t\t\r\n\t\t\tvar params = {\r\n\t\t\t\t\'c\': \'display\',\r\n\t\t\t\t\'a\': \'doMove\',\r\n\t\t\t\t\'ticket_id\': {{record_id}},\r\n\t\t\t\t\'bucket_id\': bucket_id\r\n\t\t\t};\r\n\r\n\t\t\tgenericAjaxGet(\'\',$.param(params), function(response) {\r\n\t\t\t\t\/\/ Refresh the entire page\r\n\t\t\t\tdocument.location.reload();\r\n\t\t\t});\r\n\t\t})\r\n\t\t;\r\n\t\t{% endif %}\r\n});\r\n<\/script>"}';
	
	$sql = sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE name = 'Status' AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview')",
		$db->qstr($json)
	);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Fix privs on the 'Actions' widget

if(false != ($row = $db->GetRowMaster("SELECT id, extension_params_json FROM profile_widget WHERE name = 'Actions' ".
	"AND extension_params_json LIKE '%core.ticket%' ".
	"AND profile_tab_id IN (SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.ticket' AND name = 'Overview') "
	))) {
	
	$json = $row['extension_params_json'];
	
	$new_json = str_replace(
		[
			'core.ticket.actions.close',
			'core.ticket.actions.spam',
			'core.ticket.actions.merge',
			'cerberusweb.contexts.ticket.delete',
		],
		[
			'contexts.cerberusweb.contexts.ticket.update',
			'contexts.cerberusweb.contexts.ticket.update',
			'contexts.cerberusweb.contexts.ticket.merge',
			'contexts.cerberusweb.contexts.ticket.delete',
		],
		$json
	);
	
	if($new_json != $json) {
		$db->ExecuteMaster(sprintf("UPDATE profile_widget SET extension_params_json = %s WHERE id= %d",
			$db->qstr($new_json),
			$row['id']
		));
	}
}

// ===========================================================================
// Add `worker_dashboard_pref`

if(!isset($tables['worker_dashboard_pref'])) {
	$sql = sprintf("
	CREATE TABLE `worker_dashboard_pref` (
		tab_context varchar(128) NOT NULL,
		tab_context_id int(10) unsigned NOT NULL,
		worker_id int(10) unsigned NOT NULL DEFAULT '0',
		widget_id int(10) unsigned NOT NULL DEFAULT '0',
		pref_key varchar(128) NOT NULL,
		pref_value varchar(255) DEFAULT NULL,
		PRIMARY KEY (`tab_context`,`tab_context_id`,`worker_id`,`widget_id`,`pref_key`)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['worker_dashboard_pref'] = 'worker_dashboard_pref';
}

// ===========================================================================
// Fix selectors on the customer satisfaction behaviors (if exist)

$db->ExecuteMaster("UPDATE decision_node SET params_json=replace(params_json,'#widget{{','#workspaceWidget{{') WHERE node_type = 'action' AND title = 'Render' AND trigger_id IN (SELECT id FROM trigger_event WHERE title IN ('NPS: Render recent ratings on dashboard','CSAT: Render recent ratings on dashboard','CES: Render recent ratings on dashboard'))");

// ===========================================================================
// Change `mail_queue.body` from longtext to blob

list($columns,) = $db->metaTable('mail_queue');

if($columns['body'] && 0 == strcasecmp('longtext', $columns['body']['type'])) {
	$db->ExecuteMaster("ALTER TABLE mail_queue MODIFY COLUMN body BLOB");
}

// ===========================================================================
// Finish up

return TRUE;
