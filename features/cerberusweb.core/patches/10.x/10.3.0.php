<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Move more behaviors to automation event bindings

if($revision < 1432) { // 10.3.0
	$automations_kata = $db->GetOneMaster("SELECT automations_kata FROM automation_event WHERE name = 'record.changed'");
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.mail.closed.group' ORDER BY priority DESC, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata = sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('ticket') or record_status != 'closed' or was_record_status == record_status}}"
			) . $automations_kata;
		}
	}
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.mail.moved.group' ORDER BY priority DESC, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata = sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('ticket') or (was_record_group_id == record_group_id and was_record_bucket_id == record_bucket_id)}}"
			) . $automations_kata;
		}
	}
	
	$behaviors = $db->GetArrayMaster("SELECT id, title, is_disabled, event_params_json, uri FROM trigger_event WHERE event_point = 'event.mail.assigned.group' ORDER BY priority DESC, id");
	
	if(is_iterable($behaviors)) {
		foreach($behaviors as $behavior) {
			$behavior_params = json_decode($behavior['event_params_json'], true);
			
			$automations_kata = sprintf("# %s\n# [TODO] Migrate to automations\nbehavior/%s:\n  uri: cerb:behavior:%s\n  disabled@bool: %s%s\n\n",
				$behavior['title'],
				uniqid(),
				$behavior['uri'] ?: $behavior['id'],
				$behavior['is_disabled'] ? "yes\n    #" : "\n    ",
				"{{record__type is not record type ('ticket') or was_record_owner_id == record_owner_id}}"
			) . $automations_kata;
		}
	}
	
	$db->ExecuteMaster(sprintf("UPDATE automation_event SET automations_kata = %s WHERE name = 'record.changed'",
		$db->qstr($automations_kata)
	));
}

// ===========================================================================
// Index attachment storage profiles for cron.storage

if(!isset($tables['attachment']))
	return FALSE;

list(,$indexes) = $db->metaTable('attachment');

if(!array_key_exists('profile_updated', $indexes)) {
	$db->ExecuteMaster('ALTER TABLE attachment ADD INDEX `profile_updated` (`storage_extension`,`storage_profile_id`,`updated`)');
}

if(array_key_exists('storage_profile_id', $indexes)) {
	$db->ExecuteMaster('ALTER TABLE attachment DROP INDEX storage_profile_id');
}

// ===========================================================================
// Add `available_at` to `queue_message`

if(!isset($tables['queue_message']))
	return FALSE;

list($columns,) = $db->metaTable('queue_message');

if(!array_key_exists('available_at', $columns)) {
	$db->ExecuteMaster("ALTER TABLE queue_message ADD COLUMN available_at int unsigned not null default 0");
}

// ===========================================================================
// Add `name` to `automation_resource`

if(!isset($tables['automation_resource']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('automation_resource');

if(!array_key_exists('name', $columns)) {
	$db->ExecuteMaster("ALTER TABLE automation_resource ADD COLUMN name varchar(255) not null default '', add index name_prefix (name(8))");
}

if(array_key_exists('token', $indexes) && 6 == $indexes['token']['columns']['token']['subpart']) {
	$db->ExecuteWriter("ALTER TABLE automation_resource DROP INDEX token, ADD UNIQUE (token)");
}

// ===========================================================================
// Add `record_changeset` table

if(!isset($tables['record_changeset'])) {
	$sql = sprintf("
		CREATE TABLE `record_changeset` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`record_type` varchar(64) NOT NULL DEFAULT '',
		`record_id` int(10) unsigned NOT NULL DEFAULT 0,
		`record_key` varchar(64) NOT NULL DEFAULT '',
		`worker_id` int(10) unsigned NOT NULL DEFAULT 0,
		`storage_sha1hash` varchar(40) NOT NULL DEFAULT '',
		`storage_size` int(10) unsigned NOT NULL DEFAULT '0',
		`storage_key` varchar(64) NOT NULL DEFAULT '',
		`storage_extension` varchar(128) NOT NULL DEFAULT '',
		`storage_profile_id` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		INDEX record (record_type, record_id, record_key),
		INDEX (storage_extension, storage_profile_id, created_at),
		INDEX (created_at),
		INDEX (worker_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['record_changeset'] = 'record_changeset';
}

// ===========================================================================
// Update `metric` schema

if(!isset($tables['metric']))
	return FALSE;

list($columns,) = $db->metaTable('metric');

$changes = [];

if('varchar(255)' != ($columns['description']['type']))
	$changes[] = "MODIFY COLUMN description VARCHAR(255) NOT NULL DEFAULT ''";

if($changes) {
	$db->ExecuteMaster("ALTER TABLE metric " . implode(', ', $changes));
}

// ===========================================================================
// Add `exit_state` to `cerb.automation.invocations` metric

if($revision < 1433) {
	$db->ExecuteWriter(sprintf("UPDATE metric SET dimensions_kata = %s WHERE name = 'cerb.automation.invocations'",
		$db->qstr("record/automation_id:\n  record_type: automation\nextension/trigger:\ntext/exit_state:")
	));
}

// ===========================================================================
// Update package library

$packages = [
	'cerb_project_board_kanban.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Finish up

return TRUE;