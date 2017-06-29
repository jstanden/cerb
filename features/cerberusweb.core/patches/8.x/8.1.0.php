<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
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
// Finish up

return TRUE;
