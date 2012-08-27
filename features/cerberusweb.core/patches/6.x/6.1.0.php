<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add address.is_defunct

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN is_defunct TINYINT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($indexes['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD INDEX is_defunct (is_defunct)");
}

// ===========================================================================
// workspace_widget

if(!isset($tables['workspace_widget'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS workspace_widget (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			extension_id VARCHAR(255) DEFAULT '' NOT NULL,
			workspace_tab_id INT UNSIGNED DEFAULT 0 NOT NULL,
			label VARCHAR(255) DEFAULT '' NOT NULL,
			updated_at INT UNSIGNED DEFAULT 0 NOT NULL,
			params_json TEXT,
			pos CHAR(4) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['workspace_widget'] = 'workspace_widget';
	
} else {
	$db->Execute("UPDATE workspace_widget SET extension_id = 'core.workspace.widget.worklist' WHERE extension_id = 'core.workspace.widget.calendar.worklist'");
	
}

// ===========================================================================
// Add message.response_time

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

$add_columns = array(
	'response_time' => 'ADD COLUMN response_time INT UNSIGNED NOT NULL DEFAULT 0',
	'is_broadcast' => 'ADD COLUMN is_broadcast TINYINT UNSIGNED NOT NULL DEFAULT 0',
);

foreach($add_columns as $column => $alter_sql) {
	if(isset($columns[$column]))
		unset($add_columns[$column]);
}

// Alter the table
if(!empty($add_columns))
	$db->Execute("ALTER TABLE message " . implode(', ', $add_columns));

if(isset($add_columns['response_time'])) {
	// initialize variables
	$db->Execute("DROP TABLE IF EXISTS _tmp_message_pairs");
	$db->Execute("DROP TABLE IF EXISTS _tmp_response_times");
	$db->Execute("SET @ticket_id=NULL, @is_outgoing=NULL, @created_date=NULL;");

	// create a list of all incoming/outgoing messages (0.50s)
	$db->Execute("CREATE TEMPORARY TABLE _tmp_message_pairs SELECT ticket_id, id, is_outgoing, created_date, worker_id FROM message ORDER BY ticket_id, created_date;");
	
	// remove tickets with a single message (3.18s)
	$db->Execute("DELETE FROM _tmp_message_pairs WHERE ticket_id IN (SELECT id FROM ticket WHERE num_messages = 1);");
	
	// create a table of avg response times
	$db->Execute("CREATE TEMPORARY TABLE _tmp_response_times SELECT id, IF(@ticket_id=ticket_id AND is_outgoing=1 AND worker_id != 0 AND @is_outgoing=0,(created_date-@created_date),0) AS response_time, @created_date := IF(@ticket_id=ticket_id AND is_outgoing=1,@created_date,created_date) AS created_date, @ticket_id := ticket_id AS ticket_id, @is_outgoing := is_outgoing AS is_outgoing, worker_id FROM _tmp_message_pairs ORDER BY ticket_id, created_date;");
	
	// Clear rows with no response time (e.g. dupes and non-pairs)
	$db->Execute("DELETE FROM _tmp_response_times WHERE response_time = 0;");
	
	// Copy values to message table
	$db->Execute("UPDATE message INNER JOIN _tmp_response_times ON (_tmp_response_times.id=message.id) SET message.response_time = _tmp_response_times.response_time;");

	// Clean up
	$db->Execute("DROP TABLE IF EXISTS _tmp_message_pairs");
	$db->Execute("DROP TABLE IF EXISTS _tmp_response_times");
}

if(isset($add_columns['is_broadcast'])) {
	$db->Execute("UPDATE message SET is_broadcast=1 WHERE is_outgoing=1 AND worker_id=0");
}

// ===========================================================================
// Ticket

if(!isset($tables['ticket'])) {
	$logger->error("The 'ticket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('ticket');

// ===========================================================================
// Rename ticket.due_date -> ticket.reopen_at

if(isset($columns['due_date'])) {
	$db->Execute("ALTER TABLE ticket CHANGE COLUMN due_date reopen_at INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add new ticket columns

// (8.3s)
$add_columns = array(
	'closed_at' => 'ADD COLUMN closed_at INT UNSIGNED NOT NULL DEFAULT 0',
	'first_outgoing_message_id' => 'ADD COLUMN first_outgoing_message_id INT UNSIGNED NOT NULL DEFAULT 0',
	'elapsed_response_first' => 'ADD COLUMN elapsed_response_first INT UNSIGNED NOT NULL DEFAULT 0',
	'elapsed_resolution_first' => 'ADD COLUMN elapsed_resolution_first INT UNSIGNED NOT NULL DEFAULT 0',
);

foreach($add_columns as $column => $alter_sql) {
	if(isset($columns[$column]))
		unset($add_columns[$column]);
}

// Alter the table
if(!empty($add_columns))
	$db->Execute("ALTER TABLE ticket " . implode(', ', $add_columns));


// ===========================================================================
// Populate ticket.closed_at

if(isset($add_columns['closed_at'])) {
	// Fix any wonky updated dates 
	$db->Execute("UPDATE ticket SET updated_date = created_date WHERE updated_date < created_date");
	
	// If we know of any close events from the activity log, use them if the ticket is still closed (1.88s)
	$db->Execute("UPDATE ticket INNER JOIN context_activity_log ON (context_activity_log.target_context_id=ticket.id) SET ticket.closed_at=context_activity_log.created WHERE context_activity_log.activity_point = 'ticket.status.closed' AND context_activity_log.target_context = 'cerberusweb.contexts.ticket' AND ticket.is_closed=1;");

	// Default all closed_date to their last_updated date (1.84s)
	$db->Execute("UPDATE ticket SET closed_at=updated_date WHERE is_closed=1 AND closed_at = 0");
}

// ===========================================================================
// Populate ticket.first_outgoing_message_id

if(isset($add_columns['first_outgoing_message_id'])) {
	// Find the initial responses for each ticket (1.11s)
	$db->Execute("CREATE TEMPORARY TABLE _tmp_initial_responses SELECT ticket_id, MIN(id) AS message_id FROM message WHERE is_outgoing = 1 GROUP BY ticket_id;");
	
	// Set the initial responses on tickets (2.74s)
	$db->Execute("UPDATE ticket INNER JOIN _tmp_initial_responses ON (_tmp_initial_responses.ticket_id=ticket.id) SET ticket.first_outgoing_message_id=_tmp_initial_responses.message_id WHERE ticket.first_outgoing_message_id = 0;");
	
	// Cleanup
	$db->Execute("DROP TABLE _tmp_initial_responses");
}

// ===========================================================================
// Populate ticket.elapsed_response_first

if(isset($add_columns['elapsed_response_first'])) {
	// (3.46s)
	$db->Execute("UPDATE ticket INNER JOIN message ON (ticket.first_outgoing_message_id=message.id) SET ticket.elapsed_response_first=message.response_time WHERE ticket.elapsed_response_first = 0;");
}

// ===========================================================================
// Populate ticket.elapsed_resolution_first

if(isset($add_columns['elapsed_resolution_first'])) {
	$db->Execute("UPDATE ticket SET elapsed_resolution_first=GREATEST(closed_at-created_date,0) WHERE is_closed = 1 AND closed_at != 0");
}

return TRUE;