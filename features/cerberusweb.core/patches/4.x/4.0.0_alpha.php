<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `ticket` =============================
if(!isset($tables['ticket'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			mask VARCHAR(16) DEFAULT '' NOT NULL, 
			subject VARCHAR(255)  DEFAULT '' NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			is_deleted TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			team_id INT UNSIGNED DEFAULT 0 NOT NULL,
			category_id INT UNSIGNED DEFAULT 0 NOT NULL,
			first_message_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created_date INT UNSIGNED,
			updated_date INT UNSIGNED,
			due_date INT UNSIGNED,
			first_wrote_address_id INT UNSIGNED NOT NULL DEFAULT 0,
			last_wrote_address_id INT UNSIGNED NOT NULL DEFAULT 0,
			spam_score DECIMAL(4,4) NOT NULL DEFAULT 0,
			spam_training VARCHAR(1) NOT NULL DEFAULT '',
			interesting_words VARCHAR(255) NOT NULL DEFAULT '',
			next_action VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `message` =============================
if(!isset($tables['message'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS message (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_admin TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			message_type VARCHAR(1),
			created_date INT UNSIGNED,
			address_id INT UNSIGNED,
			headers MEDIUMBLOB,
			content MEDIUMBLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `attachment` =============================
if(!isset($tables['attachment'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS attachment (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			message_id INT UNSIGNED DEFAULT 0 NOT NULL,
			display_name VARCHAR(128) DEFAULT '' NOT NULL,
			mime_type VARCHAR(255) DEFAULT '' NOT NULL,
			file_size INT UNSIGNED DEFAULT 0 NOT NULL,
			filepath VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `team` =============================
if(!isset($tables['team'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS team (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(32) DEFAULT '' NOT NULL,
			signature BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `category` =============================
if(!isset($tables['category'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS category (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			team_id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(32) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `dashboard` =============================
if(!isset($tables['dashboard'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS dashboard (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(32) DEFAULT '' NOT NULL,
			agent_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `dashboard_view` =============================
if(!isset($tables['dashboard_view'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS dashboard_view (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			dashboard_id INT UNSIGNED DEFAULT 0 NOT NULL,
			type VARCHAR(1) DEFAULT 'D',
			name VARCHAR(32) DEFAULT '' NOT NULL,
			view_columns BLOB,
			sort_by VARCHAR(32) DEFAULT '' NOT NULL,
			sort_asc TINYINT(1) UNSIGNED DEFAULT 1 NOT NULL,
			num_rows SMALLINT UNSIGNED DEFAULT 10 NOT NULL,
			page SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			params BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `dashboard_view_action` =============================
if(!isset($tables['dashboard_view_action'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS dashboard_view_action (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			dashboard_view_id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED NOT NULL,
			params BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `address` =============================
if(!isset($tables['address'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS address (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			email VARCHAR(255) DEFAULT '' NOT NULL,
			personal VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `mail_routing` =============================
if(!isset($tables['mail_routing'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS mail_routing (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			pattern VARCHAR(255) DEFAULT '' NOT NULL,
			team_id INT UNSIGNED DEFAULT 0 NOT NULL,
			pos INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `requester` =============================
if(!isset($tables['requester'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS requester (
			address_id INT UNSIGNED DEFAULT 0 NOT NULL,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (address_id, ticket_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `worker_to_team` =============================
if(!isset($tables['worker_to_team'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_to_team (
			agent_id INT UNSIGNED DEFAULT 0 NOT NULL,
			team_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_manager TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (agent_id, team_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `pop3_account` =============================
if(!isset($tables['pop3_account'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS pop3_account (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			enabled TINYINT(1) UNSIGNED DEFAULT 1 NOT NULL,
			nickname VARCHAR(128) DEFAULT '' NOT NULL,
			protocol VARCHAR(32) DEFAULT 'pop3' NOT NULL,
			host VARCHAR(128) DEFAULT '' NOT NULL,
			username VARCHAR(128) DEFAULT '' NOT NULL,
			password VARCHAR(128) DEFAULT '' NOT NULL,
			port SMALLINT UNSIGNED DEFAULT 110 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `worker` =============================
if(!isset($tables['worker'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			first_name VARCHAR(32) DEFAULT '',
			last_name VARCHAR(64) DEFAULT '',
			title VARCHAR(64) DEFAULT '',
			email VARCHAR(128) DEFAULT '',
			pass VARCHAR(32) DEFAULT '',
			is_superuser TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			can_delete TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			last_activity_date INT UNSIGNED,
			last_activity BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `bayes_words` =============================
if(!isset($tables['bayes_words'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS bayes_words (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			word VARCHAR(64) DEFAULT '' NOT NULL,
			spam INT UNSIGNED DEFAULT 0,
			nonspam INT UNSIGNED DEFAULT 0,
			PRIMARY KEY (id),
			INDEX word (word)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `bayes_stats` =============================
if(!isset($tables['bayes_stats'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS bayes_stats (
			spam INT UNSIGNED DEFAULT 0,
			nonspam INT UNSIGNED DEFAULT 0
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `community` =============================
if(!isset($tables['community'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '',
			url VARCHAR(128) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `worker_pref` =============================
if(!isset($tables['worker_pref'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_pref (
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			setting VARCHAR(32) DEFAULT '' NOT NULL,
			value BLOB,
			PRIMARY KEY (worker_id, setting)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `team_routing_rule` =============================
if(!isset($tables['team_routing_rule'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS team_routing_rule (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			team_id INT UNSIGNED DEFAULT 0 NOT NULL,
			header VARCHAR(64) DEFAULT 'from',
			pattern VARCHAR(255) DEFAULT '' NOT NULL,
			pos SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			do_spam VARCHAR(1) DEFAULT '',
			do_status VARCHAR(1) DEFAULT '',
			do_move VARCHAR(16) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `setting` =============================
if(!isset($tables['setting'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS setting (
			setting VARCHAR(32) DEFAULT '' NOT NULL,
			value VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (setting)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

return TRUE;
