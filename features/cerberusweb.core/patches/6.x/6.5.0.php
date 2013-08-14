<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add the `virtual_attendant` database table

if(!isset($tables['virtual_attendant'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS virtual_attendant (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			owner_context VARCHAR(255) DEFAULT '',
			owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			is_disabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
			params_json MEDIUMTEXT,
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX owner (owner_context, owner_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['virtual_attendant'] = 'virtual_attendant';
}

// ===========================================================================
// Add `virtual_attendant.is_disabled`

if(!isset($tables['virtual_attendant'])) {
	$logger->error("The 'virtual_attendant' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('virtual_attendant');

if(!isset($columns['is_disabled'])) {
	$db->Execute("ALTER TABLE virtual_attendant ADD COLUMN is_disabled TINYINT UNSIGNED DEFAULT 0 NOT NULL AFTER owner_context_id");
}

// ===========================================================================
// Migrate `trigger_event` records from owners to `virtual_attendant`

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(!isset($columns['virtual_attendant_id'])) {
	$db->Execute("ALTER TABLE trigger_event ADD COLUMN virtual_attendant_id INT UNSIGNED DEFAULT 0 NOT NULL AFTER event_point, ADD INDEX virtual_attendant_id (virtual_attendant_id)");
}

if(isset($columns['owner_context'])) {
	$sql = "SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, 'Cerb' AS owner_label FROM trigger_event WHERE trigger_event.owner_context = 'cerberusweb.contexts.app' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, worker_role.name AS owner_label FROM trigger_event INNER JOIN worker_role ON (trigger_event.owner_context_id=worker_role.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.role' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, worker_group.name AS owner_label FROM trigger_event INNER JOIN worker_group ON (trigger_event.owner_context_id=worker_group.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.group' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, TRIM(CONCAT(worker.first_name,' ',worker.last_name)) AS owner_label FROM trigger_event INNER JOIN worker ON (trigger_event.owner_context_id=worker.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.worker' ".
		'';
	$owner_contexts = $db->GetArray($sql);
	
	if(is_array($owner_contexts))
	foreach($owner_contexts as $row) {
		$sql = sprintf("INSERT INTO virtual_attendant (name, owner_context, owner_context_id, params_json, created_at, updated_at) ".
			"VALUES (%s, %s, %d, %s, %d, %d)",
			$db->qstr(sprintf("%s's Virtual Attendant", $row['owner_label'])),
			$db->qstr($row['owner_context']),
			$row['owner_context_id'],
			$db->qstr(json_encode(array())),
			time(),
			time()
		);
		$db->Execute($sql);
		$va_id = $db->LastInsertId();
		
		// If successfully added a new VA record, reassign the trigger_event rows
		if($va_id) {
			$sql = sprintf("UPDATE trigger_event SET virtual_attendant_id = %d WHERE owner_context = %s AND owner_context_id = %d",
				$va_id,
				$db->qstr($row['owner_context']),
				$row['owner_context_id']
			);
			$db->Execute($sql);
			
		} else {
			$logger->error("Failed to create new `virtual_attendant` record.");
			return FALSE;
		}
	}
	
	// Verify that all rows transitioned
	$sql = "SELECT COUNT(*) FROM trigger_event WHERE virtual_attendant_id = 0";
	
	$count = $db->GetOne($sql);
	if(!empty($count)) {
		$logger->error("Failed to transition all `trigger_event` records to `virtual_attendant` ownership.");
		return FALSE;
	}
	
	$db->Execute("ALTER TABLE trigger_event DROP COLUMN owner_context, DROP COLUMN owner_context_id");
}

// ===========================================================================
// Finish up

return TRUE;
