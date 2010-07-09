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

// ===========================================================================
// Comment

if(!isset($tables['comment'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS comment (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			context VARCHAR(128) DEFAULT '',
			context_id INT UNSIGNED NOT NULL DEFAULT 0,
			created INT UNSIGNED NOT NULL DEFAULT 0,
			address_id INT UNSIGNED NOT NULL DEFAULT 0,
			comment MEDIUMTEXT,
			PRIMARY KEY (id),
			INDEX context (context),
			INDEX context_id (context_id),
			INDEX address_id (address_id),
			INDEX created (created)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	// ===========================================================================
	// Migrate 'ticket_comment' to 'comment'
	
	if(isset($tables['ticket_comment'])) {
		$db->Execute("INSERT INTO comment (context, context_id, created, address_id, comment) ".
			"SELECT 'cerberusweb.contexts.ticket', ticket_id, created, address_id, comment ".
			"FROM ticket_comment ORDER BY id"
		) or die($db->ErrorMsg());
		
		$db->Execute("DROP TABLE ticket_comment");
		$db->Execute("DROP TABLE ticket_comment_seq");
		unset($tables['ticket_comment']);
		unset($tables['ticket_comment_seq']);
	}
	
	// ===========================================================================
	// Migrate 'note' to 'comment'

	// cerberusweb.notes.source.org
	if(isset($tables['note'])) {
		$db->Execute("INSERT INTO comment (context, context_id, created, address_id, comment) ".
			"SELECT 'cerberusweb.contexts.org', note.source_id, note.created, address.id, note.content ".
			"FROM note ".
			"INNER JOIN worker ON (worker.id=note.worker_id) ".
			"INNER JOIN address ON (address.email=worker.email) ".
			"WHERE note.source_extension_id = 'cerberusweb.notes.source.org' ".
			"ORDER BY note.id "
		) or die($db->ErrorMsg());
	}
	
	// cerberusweb.notes.source.task
	if(isset($tables['note'])) {
		$db->Execute("INSERT INTO comment (context, context_id, created, address_id, comment) ".
			"SELECT 'cerberusweb.contexts.task', note.source_id, note.created, address.id, note.content ".
			"FROM note ".
			"INNER JOIN worker ON (worker.id=note.worker_id) ".
			"INNER JOIN address ON (address.email=worker.email) ".
			"WHERE note.source_extension_id = 'cerberusweb.notes.source.task' ".
			"ORDER BY note.id "
		) or die($db->ErrorMsg());
	}
	
	// crm.notes.source.opportunity
	if(isset($tables['note'])) {
		$db->Execute("INSERT INTO comment (context, context_id, created, address_id, comment) ".
			"SELECT 'cerberusweb.contexts.opportunity', note.source_id, note.created, address.id, note.content ".
			"FROM note ".
			"INNER JOIN worker ON (worker.id=note.worker_id) ".
			"INNER JOIN address ON (address.email=worker.email) ".
			"WHERE note.source_extension_id = 'crm.notes.source.opportunity' ".
			"ORDER BY note.id "
		) or die($db->ErrorMsg());
	}
	
	$db->Execute("DROP TABLE note");
	$db->Execute("DROP TABLE note_seq");
	unset($tables['note']);
	unset($tables['note_seq']);
	
	// ===========================================================================
	// Migrate 'message_note' to 'comment'

	if(isset($tables['message_note'])) {
		$db->Execute("INSERT INTO comment (context, context_id, created, address_id, comment) ".
			"SELECT 'cerberusweb.contexts.message', message_note.message_id, message_note.created, address.id, message_note.content ".
			"FROM message_note ".
			"INNER JOIN worker ON (worker.id=message_note.worker_id) ".
			"INNER JOIN address ON (address.email=worker.email) ".
			"ORDER BY message_note.id "
		) or die($db->ErrorMsg());
	}
	
	$db->Execute("DROP TABLE message_note");
	$db->Execute("DROP TABLE message_note_seq");
	unset($tables['message_note']);
	unset($tables['message_note_seq']);

	// ===========================================================================
	// Swap out the auto-increment index
	
	$db->Execute("ALTER TABLE comment MODIFY COLUMN id INT UNSIGNED NOT NULL DEFAULT 0");
	
	// Swap the autoincrement to a sequence
	$db->GenID('comment_seq');
	
	$max_id = $db->GetOne("SELECT MAX(id) FROM comment");
	$db->Execute(sprintf("UPDATE comment_seq SET id = %d", $max_id));
	
	$tables['comment'] = 'comment';
}

// ===========================================================================
// Simplify notifications

if(!isset($tables['worker_event']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('worker_event');

if(isset($columns['content'])) {
	$db->Execute('ALTER TABLE worker_event DROP COLUMN content');
}

if(isset($columns['title']) && !isset($columns['message'])) {
	$db->Execute('ALTER TABLE worker_event CHANGE COLUMN title message VARCHAR(255)');
	
	// Clear view customizations since fields changed significantly
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'viewhome_myevents'");
}

if(isset($columns['message']) && 0 != strcasecmp('varchar(255)',$columns['message']['type'])) {
	$db->Execute("ALTER TABLE worker_event MODIFY COLUMN message VARCHAR(255) NOT NULL DEFAULT ''");
}
return TRUE;
