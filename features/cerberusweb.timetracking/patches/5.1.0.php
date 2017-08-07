<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// TimeEntry Sources->Context Links

if(!isset($tables['timetracking_entry']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('timetracking_entry');
	
if(isset($columns['source_extension_id']) && isset($columns['source_id'])) {
	$source_to_context = array(
		'timetracking.source.ticket' => 'cerberusweb.contexts.ticket',
	);
	
	if(is_array($source_to_context))
	foreach($source_to_context as $source => $context) {
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"SELECT 'cerberusweb.contexts.timetracking', id, %s, source_id FROM timetracking_entry WHERE source_extension_id = %s ",
			$db->qstr($context),
			$db->qstr($source)
		));
	}
	
	// Insert reciprocals
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT to_context, to_context_id, from_context, from_context_id ".
		"FROM context_link"
	));
	
	$db->ExecuteMaster('ALTER TABLE timetracking_entry DROP COLUMN source_extension_id');
	$db->ExecuteMaster('ALTER TABLE timetracking_entry DROP COLUMN source_id');
}

// ===========================================================================
// Convert orgs to contexts

if(!isset($tables['timetracking_entry']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('timetracking_entry');

if(isset($columns['debit_org_id'])) {
	$db->ExecuteMaster("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.timetracking', id, 'cerberusweb.contexts.org', debit_org_id FROM timetracking_entry WHERE debit_org_id > 0"
	);
	$db->ExecuteMaster("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.org', debit_org_id, 'cerberusweb.contexts.timetracking', id FROM timetracking_entry WHERE debit_org_id > 0"
	);
	
	$db->ExecuteMaster('ALTER TABLE timetracking_entry DROP COLUMN debit_org_id');
}

// ===========================================================================
// Convert notes to comments

if(!isset($tables['timetracking_entry']) || !isset($tables['comment']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('timetracking_entry');

list($comment_columns, $comment_indexes) = $db->metaTable('comment');


if(isset($columns['notes'])) {
	$count = $db->GetOneMaster("SELECT count(id) from timetracking_entry");
	
	if($count) {
		if(isset($comment_columns['address_id'])) {
			$db->ExecuteMaster("INSERT INTO comment (context, context_id, created, address_id, comment) ".
				"SELECT 'cerberusweb.contexts.timetracking', timetracking_entry.id, timetracking_entry.log_date, address.id, timetracking_entry.notes ".
				"FROM timetracking_entry ".
				"INNER JOIN worker ON (worker.id=timetracking_entry.worker_id) ".
				"INNER JOIN address ON (address.email=worker.email) ".
				"WHERE timetracking_entry.notes !='' ".
				"ORDER BY timetracking_entry.id "
				) or die($db->ErrorMsg());
			
		} elseif($comment_columns['owner_context']) {
			$db->ExecuteMaster("INSERT INTO comment (context, context_id, created, owner_context, owner_context_id, comment) ".
				"SELECT 'cerberusweb.contexts.timetracking', timetracking_entry.id, timetracking_entry.log_date, 'cerberusweb.contexts.worker', worker.id AS context_id, timetracking_entry.notes ".
				"FROM timetracking_entry ".
				"INNER JOIN worker ON (worker.id=timetracking_entry.worker_id) ".
				"INNER JOIN address ON (address.email=worker.email) ".
				"WHERE timetracking_entry.notes !='' ".
				"ORDER BY timetracking_entry.id "
				) or die($db->ErrorMsg());
		}
	}

	// Drop column
	$db->ExecuteMaster('ALTER TABLE timetracking_entry DROP COLUMN notes');
}

// ===========================================================================
// Add 'is_closed' field

if(!isset($tables['timetracking_entry']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('timetracking_entry');
	
if(!isset($columns['is_closed'])) {
	$db->ExecuteMaster('ALTER TABLE timetracking_entry ADD COLUMN is_closed TINYINT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX is_closed (is_closed)');
}

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'timetracking_entry_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->ExecuteMaster(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT
$tables_autoinc = array(
	'timetracking_activity',
	'timetracking_entry',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id'])
		&& ('int(10) unsigned' != $columns['id']['type']
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->ExecuteMaster(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT", $table));
	}
}
return TRUE;