<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `bot_interaction_proactive` table

if(!isset($tables['bot_interaction_proactive'])) {
	$sql = sprintf("
	CREATE TABLE `bot_interaction_proactive` (
		id int unsigned auto_increment,
		actor_bot_id int unsigned not null default 0,
		worker_id int unsigned not null default 0,
		behavior_id int unsigned not null default 0,
		interaction varchar(255) not null default '',
		interaction_params_json mediumtext,
		run_at int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		expires_at int unsigned not null default 0,
		primary key (id),
		index (worker_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['bot_interaction_proactive'] = 'bot_interaction_proactive';
}

// ===========================================================================
// Add `worker_view_model.render_sort_json`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['render_sort_json'])) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model ADD COLUMN render_sort_json varchar(255) not null default ''");

	// Migrate sorting data to JSON format
$sql = <<< EOD
UPDATE worker_view_model SET render_sort_json = concat('{"',render_sort_by,'":',IF(0=render_sort_asc,'false','true'),'}') WHERE render_sort_json = '';
EOD;
	
	$db->ExecuteMaster($sql);

	// Drop the old columns
	$db->ExecuteMaster('ALTER TABLE worker_view_model DROP COLUMN render_sort_by, DROP COLUMN render_sort_asc');
}

// ===========================================================================
// Add `gpg_public_key` table

if(!isset($tables['gpg_public_key'])) {
	$sql = sprintf("
	CREATE TABLE `gpg_public_key` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		fingerprint VARCHAR(255) DEFAULT '',
		expires_at int unsigned not null default 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index fingerprint (fingerprint(4))
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['gpg_public_key'] = 'gpg_public_key';
}

// ===========================================================================
// Add `message.was_encrypted` and `message.was_signed`

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

$changes = [];

if(!isset($columns['was_encrypted'])) {
	$changes[] = 'ADD COLUMN was_encrypted tinyint(1) not null default 0';
	$changes[] = 'ADD INDEX (was_encrypted)';
}
	
if(!isset($columns['was_signed']))
	$changes[] = 'ADD COLUMN was_signed tinyint(1) not null default 0';
	
if(!empty($changes))
	$db->ExecuteMaster("ALTER TABLE message " . implode(', ', $changes));

// ===========================================================================
// Finish up

return TRUE;
