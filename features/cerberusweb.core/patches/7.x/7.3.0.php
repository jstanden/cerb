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
// Finish up

return TRUE;
