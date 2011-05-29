<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add ticket.owner_id

list($columns, $indexes) = $db->metaTable('ticket');

if(!isset($columns['owner_id'])) {
	$db->Execute("ALTER TABLE ticket ADD COLUMN owner_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX owner_id (owner_id)");

	// Add the owner column to every worker Workflow worklist
	$rows = $db->GetArray("SELECT worker_id, columns_json FROM worker_view_model WHERE view_id = 'mail_workflow'");
	if(is_array($rows))
	foreach($rows as $row) {
		if(false !== ($columns = json_decode($row['columns_json'], true))) {
			if(!in_array('t_owner_id', $columns)) {
				$columns[] = 't_owner_id';
				$db->Execute(sprintf("UPDATE worker_view_model SET columns_json = %s WHERE worker_id = %d AND view_id = 'mail_workflow'",
					$db->qstr(json_encode($columns)),
					$row['worker_id']
				));
			}
		}
	}
}
	
return TRUE;