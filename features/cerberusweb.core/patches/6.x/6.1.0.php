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
}

// ===========================================================================
// Add message.response_time

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

if(!isset($columns['response_time'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN response_time INT UNSIGNED NOT NULL DEFAULT 0");
	
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
	
	// Clear impossible feats
	//$db->Execute("DELETE FROM _tmp_response_times WHERE response_time < 5;");
	
	// Copy values to message table
	$db->Execute("UPDATE message INNER JOIN _tmp_response_times ON (_tmp_response_times.id=message.id) SET message.response_time = _tmp_response_times.response_time;");

	// Clean up
	$db->Execute("DROP TABLE IF EXISTS _tmp_message_pairs");
	$db->Execute("DROP TABLE IF EXISTS _tmp_response_times");
}

return TRUE;