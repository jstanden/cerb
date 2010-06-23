<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Context Links

if(!isset($tables['context_link'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS context_link (
			from_context VARCHAR(128) DEFAULT '',
			from_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			to_context VARCHAR(128) DEFAULT '',
			to_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			INDEX from_context (from_context),
			INDEX from_context_id (from_context_id),
			INDEX to_context (to_context),
			INDEX to_context_id (to_context_id),
			UNIQUE from_and_to (from_context, from_context_id, to_context, to_context_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['context_link'] = 'context_link';
}

// ===========================================================================
// Task Sources->Context Links

if(!isset($tables['task']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('task');
	
if(isset($columns['source_extension']) && isset($columns['source_id'])) {
	$source_to_context = array(
		'cerberusweb.tasks.ticket' => 'cerberusweb.contexts.ticket',
		'cerberusweb.tasks.org' => 'cerberusweb.contexts.org',
		'cerberusweb.tasks.opp' => 'cerberusweb.contexts.opportunity',
	);
	
	if(is_array($source_to_context))
	foreach($source_to_context as $source => $context) {
		$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"SELECT 'cerberusweb.contexts.task', id, %s, source_id FROM task WHERE source_extension = %s ",
			$db->qstr($context),
			$db->qstr($source)
		));
	}
	
	// Insert reciprocals
	$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT to_context, to_context_id, from_context, from_context_id ".
		"FROM context_link"
	));
	
	$db->Execute('ALTER TABLE task DROP COLUMN source_extension');
	$db->Execute('ALTER TABLE task DROP COLUMN source_id');
}

// ===========================================================================
// Search filter presets

if(!isset($tables['view_filters_preset'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS view_filters_preset (
			id INT UNSIGNED NOT NULL DEFAULT 0,
			name VARCHAR(128) DEFAULT '',
			view_class VARCHAR(255) DEFAULT '',
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			params_json TEXT,
			PRIMARY KEY (id),
			INDEX view_class (view_class),
			INDEX worker_id (worker_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['view_filters_preset'] = 'view_filters_preset';
}

// ===========================================================================
// Make address autocompletion more efficient 

if(!isset($tables['address']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('address');
$changes = array();

if(!isset($indexes['first_name'])) {
	$changes[] = sprintf("ADD INDEX first_name (first_name(4))");
}
if(!isset($indexes['last_name'])) {
	$changes[] = sprintf("ADD INDEX last_name (last_name(4))");
}

if(!empty($changes))
	$db->Execute("ALTER TABLE address " . implode('', $changes));


return TRUE;
