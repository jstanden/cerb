<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// rssmon_feed
if(!isset($tables['rssmon_feed'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS rssmon_feed (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// rssmon_item
if(!isset($tables['rssmon_item'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS rssmon_item (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			guid VARCHAR(64) DEFAULT '' NOT NULL,
			feed_id INT UNSIGNED DEFAULT 0 NOT NULL,
			title VARCHAR(255) DEFAULT '' NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('rssmon_item');

$changes = array();

if(!isset($indexes['guid'])) {
	$changes[] = 'ADD INDEX guid (guid(4))';
}

if(!isset($indexes['feed_id'])) {
	$changes[] = 'ADD INDEX feed_id (feed_id)';
}

if(!isset($indexes['created_date'])) {
    $changes[] = 'ADD INDEX created_date (created_date)';
}

if(!isset($indexes['is_closed'])) {
    $changes[] = 'ADD INDEX is_closed (is_closed)';
}

$db->Execute('ALTER TABLE rssmon_item ' . implode(' ', $changes));

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'rssmon_item_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->Execute(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'rssmon_feed',
	'rssmon_item',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->Execute(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE", $table));
	}
}
return TRUE;
