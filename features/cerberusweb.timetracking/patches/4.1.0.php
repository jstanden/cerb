<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `timetracking_entry` ========================
if(!isset($tables['timetracking_entry'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS timetracking_entry (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			time_actual_mins SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			log_date INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			activity_id INT UNSIGNED DEFAULT 0 NOT NULL, 
			debit_org_id INT UNSIGNED DEFAULT 0 NOT NULL,
			notes VARCHAR(255) DEFAULT '' NOT NULL,
			source_extension_id VARCHAR(255) DEFAULT '' NOT NULL,
			source_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('timetracking_entry');

if(isset($columns['is_closed'])) {
    $db->Execute('ALTER TABLE timetracking_entry DROP COLUMN is_closed');
}

if(!isset($indexes['activity_id'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX activity_id (activity_id)');
}

if(!isset($indexes['source_extension_id'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX source_extension_id (source_extension_id)');
}

if(!isset($indexes['source_id'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX source_id (source_id)');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['log_date'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX log_date (log_date)');
}

if(!isset($indexes['debit_org_id'])) {
	$db->Execute('ALTER TABLE timetracking_entry ADD INDEX debit_org_id (debit_org_id)');
}

// `timetracking_activity` ========================
if(!isset($tables['timetracking_activity'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS timetracking_activity (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			rate DECIMAL(8,2) DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

// ===========================================================================
// Ophaned timetracking_entry custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");

return TRUE;
