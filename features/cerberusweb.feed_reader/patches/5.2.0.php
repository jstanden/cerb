<?php 
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// feed

if(!isset($tables['feed'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS feed (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			url VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

// ===========================================================================
// feed_item

if(!isset($tables['feed_item'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS feed_item (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			feed_id INT UNSIGNED NOT NULL,
			guid VARCHAR(64) DEFAULT '',
			title VARCHAR(255) DEFAULT '',
			url VARCHAR(255) DEFAULT '',
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id),
			INDEX feed_id (feed_id),
			INDEX guid (guid(4)),
			INDEX created_date (created_date),
			INDEX is_closed (is_closed)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

// ===========================================================================
// Enable feed reader scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('feeds.cron', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '15');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 23:30'));
}


return TRUE;