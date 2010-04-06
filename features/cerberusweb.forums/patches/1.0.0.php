<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `forums_source` ========================
if(!isset($tables['forums_source'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS forums_source (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			secret_key VARCHAR(64) DEFAULT '' NOT NULL,
			last_postid INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `forums_thread` ========================
if(!isset($tables['forums_thread'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS forums_thread (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			forum_id INT UNSIGNED DEFAULT 0 NOT NULL,
			thread_id INT UNSIGNED DEFAULT 0 NOT NULL,
			last_updated INT UNSIGNED DEFAULT 0 NOT NULL,
			title VARCHAR(255) DEFAULT '' NOT NULL,
			last_poster VARCHAR(64) DEFAULT '' NOT NULL,
			link VARCHAR(255) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('forums_thread');

if(!isset($indexes['last_updated'])) {
	$db->Execute('ALTER TABLE forums_thread ADD INDEX last_updated (last_updated)');
}

if(!isset($indexes['is_closed'])) {
	$db->Execute('ALTER TABLE forums_thread ADD INDEX is_closed (is_closed)');
}

return TRUE;
