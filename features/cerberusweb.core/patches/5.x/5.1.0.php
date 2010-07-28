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
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
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

// ===========================================================================
// Convert task assignments to contexts

if(!isset($tables['task']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('task');

if(isset($columns['worker_id'])) {
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.task', id, 'cerberusweb.contexts.worker', worker_id FROM task WHERE worker_id > 0"
	);
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.worker', worker_id, 'cerberusweb.contexts.task', id FROM task WHERE worker_id > 0"
	);
	
	$db->Execute('ALTER TABLE task DROP COLUMN worker_id');
}

// ===========================================================================
// Create a table for persisting worker view models

if(!isset($tables['worker_view_model'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_view_model (
			worker_id INT UNSIGNED NOT NULL DEFAULT '0',
			view_id VARCHAR(255) NOT NULL DEFAULT '',
			is_ephemeral TINYINT UNSIGNED NOT NULL DEFAULT '0',
			class_name VARCHAR(255) NOT NULL DEFAULT '',
			title VARCHAR(255) NOT NULL DEFAULT '',
			columns_json TEXT,
			columns_hidden_json TEXT,
			params_editable_json TEXT,
			params_default_json TEXT,
			params_required_json TEXT,
			params_hidden_json TEXT,
			render_page SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			render_total TINYINT UNSIGNED NOT NULL DEFAULT 0,
			render_limit SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			render_sort_by VARCHAR(255) NOT NULL DEFAULT '',
			render_sort_asc TINYINT UNSIGNED NOT NULL DEFAULT 1,
			render_template VARCHAR(255) NOT NULL DEFAULT '',
			INDEX worker_id (worker_id),
			INDEX view_id (view_id),
			UNIQUE worker_to_view_id (worker_id, view_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['worker_view_model'] = 'worker_view_model';
	
	$rs = $db->Execute("SELECT worker_id, SUBSTRING(setting,5) AS view_id, value AS model FROM worker_pref WHERE setting LIKE 'view%%'");
	
	if(false !== $rs)
	while($row = mysql_fetch_assoc($rs)) {
		$worker_id = $row['worker_id'];
		$view_id = $row['view_id'];
		
		if(false !== (@$model = unserialize($row['model']))) {
			$fields = array(
				'worker_id' => $worker_id,
				'view_id' => $db->qstr($view_id),
				'class_name' => $db->qstr($model->class_name),
				'title' => $db->qstr($model->name),
				'columns_json' => $db->qstr(json_encode($model->view_columns)),
				'columns_hidden_json' => $db->qstr(json_encode($model->columnsHidden)),
				'params_editable_json' => $db->qstr(json_encode($model->paramsEditable)),
				'params_required_json' => $db->qstr(json_encode($model->paramsRequired)),
				'params_default_json' => $db->qstr(json_encode($model->paramsDefault)),
				'params_hidden_json' => $db->qstr(json_encode($model->paramsHidden)),
				'render_page' => abs(intval($model->renderPage)),
				'render_total' => !empty($model->renderTotal) ? 1 : 0,
				'render_limit' => intval($model->renderLimit),
				'render_sort_by' => $db->qstr($model->renderSortBy),
				'render_sort_asc' => !empty($model->renderSortAsc) ? 1 : 0,
				'render_template' => $db->qstr($model->renderTemplate),
			);
			
			$db->Execute(sprintf("REPLACE INTO worker_view_model (%s)".
				"VALUES (%s)",
				implode(',', array_keys($fields)),
				implode(',', $fields)
			));			
		}
	}
	
	mysql_free_result($rs);
	
	$db->Execute("DELETE FROM worker_pref WHERE setting LIKE 'view%'");
}

// ===========================================================================
// Clean up the last bit of parent orgs

list($columns, $indexes) = $db->metaTable('contact_org');

if(isset($columns['parent_org_id']))
	$db->Execute("ALTER TABLE contact_org DROP COLUMN parent_org_id");

// ===========================================================================
// Convert ticket 'next_worker' assignments to contexts

if(!isset($tables['ticket']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('ticket');

if(isset($columns['next_worker_id'])) {
	// ~23s
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.ticket', id, 'cerberusweb.contexts.worker', next_worker_id FROM ticket WHERE next_worker_id > 0 AND is_deleted = 0"
	);
	// ~30s
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT 'cerberusweb.contexts.worker', next_worker_id, 'cerberusweb.contexts.ticket', id FROM ticket WHERE next_worker_id > 0 AND is_deleted = 0"
	);
	
	$db->Execute('ALTER TABLE ticket DROP COLUMN next_worker_id');
}

// ===========================================================================
// Convert group_inbox_filter actions from 'assign' to 'owners_set'

if(!isset($tables['group_inbox_filter']))
	return FALSE;

$sql = "SELECT id, actions_ser FROM group_inbox_filter";
$rs = $db->Execute($sql);

if(false !== $rs)
while($row = mysql_fetch_assoc($rs)) {
	$filter_id = $row['id'];
	$filter_actions_ser = $row['actions_ser'];
	
	$filter_actions = array();
	if(!empty($filter_actions_ser))
		@$filter_actions = unserialize($filter_actions_ser);
		
	if(!empty($filter_actions)) {
		if(isset($filter_actions['assign'])) {
			@$worker_id = $filter_actions['assign']['worker_id'];
			
			if(!empty($worker_id)) {
				$filter_actions['owner'] = array(
					'add' => array($worker_id),
				);
			}
				
			unset($filter_actions['assign']);
			
			$db->Execute(sprintf("UPDATE group_inbox_filter SET actions_ser = %s WHERE id = %d",
				$db->qstr(serialize($filter_actions)),
				$filter_id
			));
		}
	}
}

mysql_free_result($rs);

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'generic_seq',
	'address_seq',
	'attachment_seq',
	'bayes_words_seq',
	'comment_seq',
	'contact_org_seq',
	'custom_field_seq',
	'mail_queue_seq',
	'message_seq',
	'snippet_seq',
	'task_seq',
	'ticket_seq',
	'worker_event_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->Execute(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'address',
	'attachment',
	'bayes_words',
	'category',
	'comment',
	'custom_field',
	'fnr_external_resource',
	'fnr_topic',
	'group_inbox_filter',
	'mail_queue',
	'mail_to_group_rule',
	'message',
	'pop3_account',
	'preparse_rule',
	'snippet',
	'task',
	'team',
	'ticket',
	'view_filters_preset',
	'worker',
	'worker_event',
	'worker_role',
	'worker_workspace_list',
	'view_rss',
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
