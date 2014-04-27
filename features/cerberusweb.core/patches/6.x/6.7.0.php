<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Set the new default number of columns to 3 on existing dashboard tabs

$params_json = json_encode(array('num_columns' => 3));
$db->Execute(sprintf("UPDATE workspace_tab SET params_json = %s WHERE extension_id = 'core.workspace.tab' AND params_json IS NULL",
	$db->qstr($params_json)
));

// ===========================================================================
// Add `cache_ttl` to `workspace_widget`

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_widget');

if(!isset($columns['cache_ttl'])) {
	$db->Execute("ALTER TABLE workspace_widget ADD COLUMN cache_ttl MEDIUMINT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE workspace_widget SET cache_ttl = 60");
}

// ===========================================================================
// Add `context_crc32` to `fulltext_comment_content`

if(isset($tables['fulltext_comment_content'])) {
	list($columns, $indexes) = $db->metaTable('fulltext_comment_content');

	if(!isset($columns['context_crc32'])) {
		$db->Execute("ALTER TABLE fulltext_comment_content ADD COLUMN context_crc32 INT UNSIGNED");
		$db->Execute("UPDATE fulltext_comment_content INNER JOIN comment ON (fulltext_comment_content.id=comment.id) SET fulltext_comment_content.context_crc32 = CRC32(comment.context)");
	}
}

// ===========================================================================
// Drop redundant indexes (id vs pkey)

$check_tables = array(
	'address',
	'attachment',
	'bayes_words',
	'bucket',
	'custom_field',
	'devblocks_storage_profile',
	'devblocks_template',
	'kb_category',
	'mail_to_group_rule',
	'message',
	'pop3_account',
	'snippet',
	'ticket',
	'worker',
	'worker_group',
);

foreach($check_tables as $check_table) {
	if(isset($tables[$check_table])) {
		list($columns, $indexes) = $db->metaTable($check_table);
		
		if(isset($indexes['id']))
			$db->Execute(sprintf('ALTER TABLE %s DROP INDEX id', $db->escape($check_table)));
	}
}

// ===========================================================================
// Add a more secure worker hash table for passwords

if(!isset($tables['worker_auth_hash'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS worker_auth_hash (
			worker_id INT UNSIGNED NOT NULL,
			pass_hash VARCHAR(128),
			pass_salt VARCHAR(64),
			method TINYINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (worker_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['worker_auth_hash'] = 'worker_auth_hash';

	// Insert newly salted rows for workers with existing passwords
	$db->Execute("INSERT INTO worker_auth_hash (worker_id, pass_salt, pass_hash, method) SELECT id, @salt := substr(sha1(rand()),1,12) AS salt, sha1(concat(@salt,pass)) AS hash, 0 AS method FROM worker WHERE pass != ''");
	
	// Drop the `pass` field on `worker`'
	$db->Execute("ALTER TABLE worker DROP column pass");
}

// ===========================================================================
// Clean up the old style cache files in temp filesystem

$cache_dir = APP_TEMP_PATH . '/';
$files = scandir($cache_dir);
unset($files['.']);
unset($files['..']);

if(is_array($files))
foreach($files as $file) {
	if(0==strcmp('devblocks_cache---', substr($file, 0, 18))) {
		if(file_exists($cache_dir . $file) && is_writeable($cache_dir . $file))
			@unlink($cache_dir . $file);
	}
}

// ===========================================================================
// Modify `devblocks_session` to include `refreshed_at`

if(!isset($tables['devblocks_session'])) {
	$logger->error("The 'devblocks_session' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('devblocks_session');

if(!isset($columns['refreshed_at'])) {
	$db->Execute("ALTER TABLE devblocks_session ADD COLUMN refreshed_at INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Modify `trigger_event` to include `event_params_json`

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(!isset($columns['event_params_json'])) {
	$db->Execute("ALTER TABLE trigger_event ADD COLUMN event_params_json TEXT");
}

// ===========================================================================
// Fix incorrectly created worker addresses

$sql = "UPDATE address_to_worker SET worker_id=is_confirmed, is_confirmed=1 WHERE is_confirmed > 1";
$db->Execute($sql);

// ===========================================================================
// Fix VA 'On:' targets in behavior actions

$sql = "UPDATE decision_node SET params_json=replace(params_json, '\"on\":\"va_id\"', '\"on\":\"_trigger_va_id\"') WHERE params_json LIKE '%\"on\":\"va_id\"%' AND trigger_id IN (SELECT id FROM trigger_event WHERE event_point != 'event.macro.virtual_attendant')";
$db->Execute($sql);

// ===========================================================================
// Finish up

return TRUE;
