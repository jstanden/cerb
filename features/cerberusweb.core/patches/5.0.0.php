<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Hand 'setting' over to 'devblocks_setting' (and copy)

if(isset($tables['setting']) && isset($tables['devblocks_setting'])) {
	$sql = "INSERT INTO devblocks_setting (plugin_id, setting, value) ".
		"SELECT 'cerberusweb.core', setting, value FROM setting";
	$db->Execute($sql);
	
	$db->Execute('DROP TABLE setting');

	$tables['devblocks_setting'] = 'devblocks_setting';
    unset($tables['setting']);
}

// ===========================================================================
// Fix BLOBS

list($columns, $indexes) = $db->metaTable('group_setting');

if(isset($columns['value'])
	&& 0 != strcasecmp('mediumtext',$columns['value']['type'])) {
		$db->Execute('ALTER TABLE group_setting MODIFY COLUMN value MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('message_header');

if(isset($columns['header_value'])
	&& 0 != strcasecmp('text',$columns['header_value']['type'])) {
		$db->Execute('ALTER TABLE message_header MODIFY COLUMN header_value TEXT');
}

list($columns, $indexes) = $db->metaTable('message_note');

if(isset($columns['content'])
	&& 0 != strcasecmp('mediumtext',$columns['content']['type'])) {
		$db->Execute('ALTER TABLE message_note MODIFY COLUMN content MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('team');

if(isset($columns['signature'])
	&& 0 != strcasecmp('text',$columns['signature']['type'])) {
		$db->Execute('ALTER TABLE team MODIFY COLUMN signature TEXT');
}

list($columns, $indexes) = $db->metaTable('view_rss');

if(isset($columns['params'])
	&& 0 != strcasecmp('mediumtext',$columns['params']['type'])) {
		$db->Execute('ALTER TABLE view_rss MODIFY COLUMN params MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['last_activity'])
	&& 0 != strcasecmp('text',$columns['last_activity']['type'])) {
		$db->Execute('ALTER TABLE worker MODIFY COLUMN last_activity MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('worker_pref');

if(isset($columns['value'])
	&& 0 != strcasecmp('mediumtext',$columns['value']['type'])) {
		$db->Execute('ALTER TABLE worker_pref MODIFY COLUMN value MEDIUMTEXT');
}

// ===========================================================================
// Fix View_* class name refactor

$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:14:\"C4_AddressView\"', 's:12:\"View_Address\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:17:\"C4_AttachmentView\"', 's:15:\"View_Attachment\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:17:\"C4_ContactOrgView\"', 's:15:\"View_ContactOrg\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:11:\"C4_TaskView\"', 's:9:\"View_Task\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:13:\"C4_TicketView\"', 's:11:\"View_Ticket\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:18:\"C4_TranslationView\"', 's:16:\"View_Translation\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:18:\"C4_WorkerEventView\"', 's:16:\"View_WorkerEvent\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:13:\"C4_WorkerView\"', 's:11:\"View_Worker\"') WHERE setting LIKE 'view%'");

// ===========================================================================
// Remove deprecated CloudGlue

if(isset($tables['tag'])) {
	$db->Execute('DROP TABLE tag');
}

if(isset($tables['tag_seq'])) {
	$db->Execute('DROP TABLE tag_seq');
}

if(isset($tables['tag_index'])) {
	$db->Execute('DROP TABLE tag_index');
}

if(isset($tables['tag_to_content'])) {
	$db->Execute('DROP TABLE tag_to_content');
}

// ===========================================================================
// Remove deprecated F&R

if(isset($tables['fnr_query'])) {
	$db->Execute('DROP TABLE fnr_query');
}

if(isset($tables['fnr_query_seq'])) {
	$db->Execute('DROP TABLE fnr_query_seq');
}

// ===========================================================================
// Migrate to attachment storage service

list($columns, $indexes) = $db->metaTable('attachment');

if(isset($columns['filepath'])) {
	$db->Execute("ALTER TABLE attachment CHANGE COLUMN filepath storage_key VARCHAR(255) DEFAULT '' NOT NULL");
}

if(!isset($columns['storage_extension'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN storage_extension VARCHAR(255) DEFAULT '' NOT NULL");
	$db->Execute("UPDATE attachment SET storage_extension='devblocks.storage.engine.disk' WHERE storage_extension=''");
}

return TRUE;