<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
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
	$db->ExecuteMaster($sql);

	$tables['virtual_attendant'] = 'virtual_attendant';

	// Fieldsets can be owned by VAs, so we need to purge old search worklists
	$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id = 'search_cerberusweb_contexts_custom_fieldset'");
	
	// Migrate itemized schedule/unschedule behavior events to globals
	$db->ExecuteMaster("UPDATE decision_node SET params_json = REPLACE(params_json,'\"action\":\"schedule_behavior\"','\"action\":\"_schedule_behavior\"') WHERE params_json LIKE '%\"action\":\"schedule_behavior\"%'");
	$db->ExecuteMaster("UPDATE decision_node SET params_json = REPLACE(params_json,'\"action\":\"unschedule_behavior\"','\"action\":\"_unschedule_behavior\"') WHERE params_json LIKE '%\"action\":\"unschedule_behavior\"%'");
}

// ===========================================================================
// Add `virtual_attendant.is_disabled`

if(!isset($tables['virtual_attendant'])) {
	$logger->error("The 'virtual_attendant' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('virtual_attendant');

if(!isset($columns['is_disabled'])) {
	$db->ExecuteMaster("ALTER TABLE virtual_attendant ADD COLUMN is_disabled TINYINT UNSIGNED DEFAULT 0 NOT NULL AFTER owner_context_id");
}

// ===========================================================================
// Migrate `trigger_event` records from owners to `virtual_attendant`

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(!isset($columns['virtual_attendant_id'])) {
	$db->ExecuteMaster("ALTER TABLE trigger_event ADD COLUMN virtual_attendant_id INT UNSIGNED DEFAULT 0 NOT NULL AFTER event_point, ADD INDEX virtual_attendant_id (virtual_attendant_id)");
}

if(!isset($columns['is_private'])) {
	$db->ExecuteMaster("ALTER TABLE trigger_event ADD COLUMN is_private TINYINT UNSIGNED DEFAULT 0 NOT NULL AFTER is_disabled, ADD INDEX is_private (is_private)");
}

if(isset($columns['owner_context'])) {
	// Sanitize
	$db->ExecuteMaster("DELETE FROM trigger_event WHERE owner_context = 'cerberusweb.contexts.group' AND owner_context_id NOT IN (SELECT id FROM worker_group)");
	$db->ExecuteMaster("DELETE FROM trigger_event WHERE owner_context = 'cerberusweb.contexts.role' AND owner_context_id NOT IN (SELECT id FROM worker_role)");
	$db->ExecuteMaster("DELETE FROM trigger_event WHERE owner_context = 'cerberusweb.contexts.worker' AND owner_context_id NOT IN (SELECT id FROM worker)");
	$db->ExecuteMaster("DELETE FROM decision_node WHERE trigger_id NOT IN (SELECT id FROM trigger_event)");
	
	// Look up behavior owners and link them to formal Virtual Attendant records
	$sql = "SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, 'Cerb' AS owner_label FROM trigger_event WHERE trigger_event.owner_context = 'cerberusweb.contexts.app' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, worker_role.name AS owner_label FROM trigger_event INNER JOIN worker_role ON (trigger_event.owner_context_id=worker_role.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.role' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, worker_group.name AS owner_label FROM trigger_event INNER JOIN worker_group ON (trigger_event.owner_context_id=worker_group.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.group' ".
		"UNION ".
		"SELECT DISTINCT trigger_event.owner_context, trigger_event.owner_context_id, TRIM(CONCAT(worker.first_name,' ',worker.last_name)) AS owner_label FROM trigger_event INNER JOIN worker ON (trigger_event.owner_context_id=worker.id) WHERE trigger_event.owner_context = 'cerberusweb.contexts.worker' ".
		'';
	$owner_contexts = $db->GetArrayMaster($sql);
	
	if(is_array($owner_contexts))
	foreach($owner_contexts as $row) {
		$sql = sprintf("INSERT INTO virtual_attendant (name, owner_context, owner_context_id, params_json, created_at, updated_at) ".
			"VALUES (%s, %s, %d, %s, %d, %d)",
			$db->qstr(sprintf("%s's Bot", $row['owner_label'])),
			$db->qstr($row['owner_context']),
			$row['owner_context_id'],
			$db->qstr('{"events":{"mode":"all","items":[]},"actions":{"mode":"all","items":[]}}'),
			time(),
			time()
		);
		$db->ExecuteMaster($sql);
		$va_id = $db->LastInsertId();
		
		// If successfully added a new VA record, reassign the trigger_event rows
		if($va_id) {
			$sql = sprintf("UPDATE trigger_event SET virtual_attendant_id = %d WHERE owner_context = %s AND owner_context_id = %d",
				$va_id,
				$db->qstr($row['owner_context']),
				$row['owner_context_id']
			);
			$db->ExecuteMaster($sql);
			
		} else {
			$logger->error("Failed to create new `virtual_attendant` record.");
			return FALSE;
		}
	}
	
	// Verify that all rows transitioned
	$sql = "SELECT COUNT(*) FROM trigger_event WHERE virtual_attendant_id = 0";
	
	$count = $db->GetOneMaster($sql);
	if(!empty($count)) {
		$logger->error("Failed to transition all `trigger_event` records to `virtual_attendant` ownership.");
		return FALSE;
	}
	
	$db->ExecuteMaster("ALTER TABLE trigger_event DROP COLUMN owner_context, DROP COLUMN owner_context_id");
}

// ===========================================================================
// Convert workspace_list keys for t_team_id and t_category_id

$db->ExecuteMaster("UPDATE workspace_list SET list_view=REPLACE(list_view,';s:9:\"t_team_id\"',';s:10:\"t_group_id\"') WHERE context = 'cerberusweb.contexts.ticket'");
$db->ExecuteMaster("UPDATE workspace_list SET list_view=REPLACE(list_view,';s:13:\"t_category_id\"',';s:11:\"t_bucket_id\"') WHERE context = 'cerberusweb.contexts.ticket'");

// ===========================================================================
// Convert worker_view_model keys for t_team_id and t_category_id

$db->ExecuteMaster("UPDATE worker_view_model SET columns_json=REPLACE(columns_json,'\"t_team_id\"','\"t_group_id\"'), columns_hidden_json=REPLACE(columns_hidden_json,'\"t_team_id\"','\"t_group_id\"'), params_editable_json=REPLACE(params_editable_json,'\"t_team_id\"','\"t_group_id\"'), params_default_json=REPLACE(params_default_json,'\"t_team_id\"','\"t_group_id\"'), params_required_json=REPLACE(params_required_json,'\"t_team_id\"','\"t_group_id\"'), params_hidden_json=REPLACE(params_hidden_json,'\"t_team_id\"','\"t_group_id\"') WHERE class_name IN ('View_Ticket','View_Message')");
$db->ExecuteMaster("UPDATE worker_view_model SET columns_json=REPLACE(columns_json,'\"t_category_id\"','\"t_bucket_id\"'), columns_hidden_json=REPLACE(columns_hidden_json,'\"t_category_id\"','\"t_bucket_id\"'), params_editable_json=REPLACE(params_editable_json,'\"t_category_id\"','\"t_bucket_id\"'), params_default_json=REPLACE(params_default_json,'\"t_category_id\"','\"t_bucket_id\"'), params_required_json=REPLACE(params_required_json,'\"t_category_id\"','\"t_bucket_id\"'), params_hidden_json=REPLACE(params_hidden_json,'\"t_category_id\"','\"t_bucket_id\"') WHERE class_name IN ('View_Ticket','View_Message')");

// ===========================================================================
// Clean up missing scheduled behaviors

$db->ExecuteMaster("DELETE context_scheduled_behavior FROM context_scheduled_behavior LEFT JOIN trigger_event ON (trigger_event.id=context_scheduled_behavior.behavior_id) WHERE trigger_event.id IS NULL");

// ===========================================================================
// Finish up

return TRUE;
