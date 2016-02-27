<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Remove the worker last activity fields

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['last_activity'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity");
}

if(isset($columns['last_activity_date'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity_date");
}

if(isset($columns['last_activity_ip'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity_ip");
}

// ===========================================================================
// Remove the old _version file if it exists

if(file_exists(APP_STORAGE_PATH . '/_version'))
	@unlink(APP_STORAGE_PATH . '/_version');

// ===========================================================================
// Convert `message_header` to `message_headers`

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

if(!isset($tables['message_headers'])) {
	$sql = sprintf("
		CREATE TABLE `message_headers` (
			`message_id` int unsigned not null default 0,
			`headers` text,
			primary key (message_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['message_headers'] = 'message_headers';
}

if(isset($tables['message_header'])) {
	$id_max = $db->GetOneMaster('SELECT max(id) from message');

	if(false === $id_max)
		die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$id_from = 0;
	$id_to = 0;
	
	$db->GetOneMaster('SET group_concat_max_len = 1024000');
	
	while($id_from < $id_max) {
		$id_to = $id_from + 9999;
		
	  // Move all the headers for a single message_id into a single blob
	  $sql = sprintf("INSERT IGNORE INTO message_headers (message_id, headers) SELECT message_id, GROUP_CONCAT(header_name, ': ', REPLACE(header_value, '\r\n', '\r\n\t') separator '\r\n') AS headers FROM message_header WHERE message_id BETWEEN %d and %d GROUP BY message_id", $id_from, $id_to);
		if(false === ($db->ExecuteMaster($sql))) {
	  	die("[MySQL Error] " . $db->ErrorMsgMaster());
		}
		
		$id_from = $id_to + 1;
	}
	
	if(!isset($tables['message'])) {
		$logger->error("The 'message' table does not exist.");
		return FALSE;
	}
	
	list($columns, $indexes) = $db->metaTable('message');
	
	if(isset($tables['message_header'])) {
		$db->ExecuteMaster("DROP TABLE message_header") or die("[MySQL Error] " . $db->ErrorMsgMaster());
		unset($tables['message_header']);
	}
}

// Finish up

return TRUE;
