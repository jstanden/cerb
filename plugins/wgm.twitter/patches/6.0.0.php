<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// twitter_account

if(!isset($tables['twitter_account'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS twitter_account (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			twitter_id VARCHAR(128) DEFAULT '',
			screen_name VARCHAR(128) DEFAULT '',
			oauth_token VARCHAR(128) DEFAULT '',
			oauth_token_secret VARCHAR(128) DEFAULT '',
			last_synced_at INT UNSIGNED NOT NULL,
			last_synced_msgid VARCHAR(128) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['twitter_account'] = 'twitter_account';
}

// ===========================================================================
// twitter_message

if(!isset($tables['twitter_message'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS twitter_message (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			account_id INT UNSIGNED NOT NULL,
			twitter_id VARCHAR(128) DEFAULT '',
			twitter_user_id VARCHAR(128) DEFAULT '',
			user_name VARCHAR(128) DEFAULT '',
			user_screen_name VARCHAR(128) DEFAULT '',
			user_followers_count INT UNSIGNED NOT NULL DEFAULT 0,
			user_profile_image_url VARCHAR(255) NOT NULL DEFAULT '',
			created_date INT UNSIGNED NOT NULL DEFAULT 0,
			is_closed TINYINT UNSIGNED NOT NULL DEFAULT 0,
			content VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			INDEX created_date (created_date),
			INDEX account_id (account_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['twitter_message'] = 'twitter_message';
}

// ===========================================================================
// Enable scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('wgmtwitter.cron', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 23:45'));
}

// ===========================================================================
// Fix MySQL strict_mode issue (missing default values)

if(!isset($tables['twitter_message']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('twitter_message');

if(isset($columns['account_id'])) {
	$db->ExecuteMaster("ALTER TABLE twitter_message MODIFY COLUMN account_id INT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($tables['twitter_account']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('twitter_account');

if(isset($columns['last_synced_at'])) {
	$db->ExecuteMaster("ALTER TABLE twitter_account MODIFY COLUMN last_synced_at INT UNSIGNED NOT NULL DEFAULT 0");
}

return TRUE;