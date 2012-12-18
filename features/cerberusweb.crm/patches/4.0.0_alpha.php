<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ***** Application

if(!isset($tables['crm_opportunity'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS crm_opportunity (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			campaign_id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			source VARCHAR(255) DEFAULT '' NOT NULL,
			primary_email_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			updated_date INT UNSIGNED DEFAULT 0 NOT NULL,
			closed_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_won TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(!isset($columns['next_action'])) {
    $db->Execute("ALTER TABLE crm_opportunity ADD COLUMN next_action VARCHAR(255) DEFAULT '' NOT NULL");
}

if(!isset($columns['campaign_bucket_id'])) {
    $db->Execute("ALTER TABLE crm_opportunity ADD COLUMN campaign_bucket_id INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($indexes['campaign_id'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX campaign_id (campaign_id)');
}

if(!isset($indexes['campaign_bucket_id'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX campaign_bucket_id (campaign_bucket_id)');
}

if(!isset($indexes['primary_email_id'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX primary_email_id (primary_email_id)');
}

if(!isset($indexes['updated_date'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX updated_date (updated_date)');
}

if(!isset($indexes['worker_id'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['is_closed'])) {
    $db->Execute('ALTER TABLE crm_opportunity ADD INDEX is_closed (is_closed)');
}

if(!isset($tables['crm_opp_comment'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS crm_opp_comment (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			opportunity_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			content TEXT,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

if(!isset($indexes['opportunity_id'])) {
    $db->Execute('ALTER TABLE crm_opp_comment ADD INDEX opportunity_id (opportunity_id)');
}

if(!isset($tables['crm_campaign'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS crm_campaign (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

if(!isset($tables['crm_campaign_bucket'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS crm_campaign_bucket (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			campaign_id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

list($columns, $indexes) = $db->metaTable('crm_campaign_bucket');

if(!isset($indexes['campaign_id'])) {
    $db->Execute('ALTER TABLE crm_campaign_bucket ADD INDEX campaign_id (campaign_id)');
}

return TRUE;
