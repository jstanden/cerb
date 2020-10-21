<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `custom_field.uri`

list($columns,) = $db->metaTable('custom_field');

if(!array_key_exists('uri', $columns)) {
	$sql = "ALTER TABLE custom_field ADD COLUMN uri VARCHAR(128) NOT NULL DEFAULT '', ADD INDEX (uri)";
	$db->ExecuteMaster($sql);
	
	// Generate aliases for existing custom fields
	
	$fields = $db->GetArrayMaster("select id, name, (select name from custom_fieldset where id = custom_field.custom_fieldset_id) as custom_fieldset from custom_field");
	
	foreach($fields as $field) {
		$field_key = sprintf("%s%s",
			$field['custom_fieldset'] ? (DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['custom_fieldset'], MB_CASE_TITLE))) . '_') : '',
			DevblocksPlatform::strAlphaNum(lcfirst(mb_convert_case($field['name'], MB_CASE_TITLE)))
		);
		
		$db->ExecuteMaster(sprintf("UPDATE custom_field SET uri = %s WHERE id = %d",
			$db->qstr($field_key),
			$field['id']
		));
	}
}

// ===========================================================================
// Add `connected_account.uri`

list($columns,) = $db->metaTable('connected_account');

if(!array_key_exists('uri', $columns)) {
	$sql = "ALTER TABLE connected_account ADD COLUMN uri VARCHAR(128) NOT NULL DEFAULT '', ADD INDEX (uri)";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `automation` table

if(!isset($tables['automation'])) {
	$sql = sprintf("
		CREATE TABLE `automation` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`extension_id` varchar(255) NOT NULL DEFAULT '',
		`extension_params_json` mediumtext,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`script` mediumtext,
		`policy_kata` text,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (extension_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation'] = 'automation';
}

// ===========================================================================
// Update automations

$automations_current = $db->GetArrayMaster("SELECT name FROM automation WHERE name like 'cerb.%' OR name like 'ai.cerb.%'");
$automations_current = array_column($automations_current, 'name', 'name');

$automations_json = file_get_contents(APP_PATH . '/features/cerberusweb.core/kata/cerb.json');

$automations = json_decode($automations_json, true);

foreach($automations as $automation) {
	if(array_key_exists($automation['name'], $automations_current)) {
		$db->ExecuteMaster(sprintf('UPDATE automation SET extension_id = %s, script = %s, description = %s, policy_kata = %s, created_at = %d, updated_at = %d WHERE name = %s',
			$db->qstr($automation['extension_id']),
			$db->qstr($automation['script']),
			$db->qstr($automation['description']),
			$db->qstr($automation['policy_kata']),
			$automation['created_at'],
			$automation['updated_at'],
			$db->qstr($automation['name'])
		));
	} else {
		$db->ExecuteMaster(sprintf('INSERT INTO automation (name, extension_id, script, description, policy_kata, created_at, updated_at)'.
			"VALUES (%s, %s, %s, %s, %s, %d, %d)",
			$db->qstr($automation['name']),
			$db->qstr($automation['extension_id']),
			$db->qstr($automation['script']),
			$db->qstr($automation['description']),
			$db->qstr($automation['policy_kata']),
			$automation['created_at'],
			$automation['updated_at']
		));
	}
}

// ===========================================================================
// Add `automation_datastore` table

if(!isset($tables['automation_datastore'])) {
	$sql = sprintf("
		CREATE TABLE `automation_datastore` (
		`data_key` varchar(255) NOT NULL DEFAULT '',
		`data_value` mediumtext,
		`expires_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (`data_key`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['automation_datastore'] = 'automation_datastore';
}

// ===========================================================================
// Add `automation_execution` table

if(!isset($tables['automation_execution'])) {
	$sql = sprintf("
	CREATE TABLE `automation_execution` (
		token varchar(64) NOT NULL DEFAULT '',
		uri varchar(255) NOT NULL DEFAULT '',
		state varchar(8) NOT NULL DEFAULT '',
		state_data mediumtext,
		expires_at int(10) unsigned NOT NULL DEFAULT 0,
		updated_at int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (token),
		INDEX (uri),
		INDEX (state),
		INDEX (expires_at),
		INDEX (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_execution'] = 'automation_execution';
}

// ===========================================================================
// Drop `email_signature.is_default`

list($columns,) = $db->metaTable('email_signature');

if(array_key_exists('is_default', $columns)) {
	$sql = "ALTER TABLE email_signature DROP COLUMN is_default";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add automations to reminders

list($columns,) = $db->metaTable('reminder');

if(!array_key_exists('automations_kata', $columns)) {
	$sql = "ALTER TABLE reminder ADD COLUMN automations_kata mediumtext";
	$db->ExecuteMaster($sql);
}

// Migrate behaviors to automations
if(array_key_exists('params_json', $columns)) {
	$sql = "select id, title FROM trigger_event WHERE event_point = 'event.macro.reminder'";
	$behaviors = $db->GetArrayMaster($sql);
	
	$behaviors = array_column($behaviors, 'title', 'id');
	
	$sql = "select id, params_json from reminder where is_closed = 0 and params_json not in ('','[]','{\"behaviors\":[]}')";
	$rs = $db->ExecuteMaster($sql);
	
	while($row = mysqli_fetch_assoc($rs)) {
		@$params = json_decode($row['params_json'], true);
		
		$automations_kata = "# [TODO] Migrate to automations\n";
		
		// Behaviors
		if(array_key_exists('behaviors', $params)) {
			foreach($params['behaviors'] as $behavior_id => $behavior_data) {
				$automations_kata .= sprintf("# %s\nbehavior/%s:\n  uri: uri:behavior:%d\n  disabled@bool: no\n",
					$behaviors[$behavior_id] ?: 'Behavior',
					uniqid(),
					$behavior_id
				);
				
				if(is_array($behavior_data) && $behavior_data) {
					$automations_kata .= "  inputs:\n";
					
					foreach($behavior_data as $k => $v) {
						if(is_array($v)) {
							$automations_kata .= sprintf("    %s@list:\n      %s\n",
								$k,
								implode('\n      ', $v)
							);
							
						} else {
							$automations_kata .= sprintf("    %s: %s\n",
								$k,
								$v
							);
						}
					}
				}
				
				$automations_kata .= "\n";
			}
		}
		
		if($automations_kata) {
			$sql = sprintf("UPDATE reminder SET automations_kata = %s WHERE id = %d",
				$db->qstr($automations_kata),
				$row['id']
			);
			
			$db->ExecuteMaster($sql);
		}
	}
	
	$db->ExecuteMaster('alter table reminder drop column params_json');
}

// ===========================================================================
// Convert form interaction `prompt_sheet.selection_key` to sheet/selection col

$sql = "select id, params_json from decision_node where params_json like '%prompt_sheet%' and params_json like '%selection_key%' and trigger_id in (select id from trigger_event where event_point = 'event.form.interaction.worker')";
$nodes = $db->GetArrayMaster($sql);

foreach($nodes as $node) {
	$actions = json_decode($node['params_json'], true);
	$is_changed = false;
	
	foreach($actions['actions'] as $action_idx => $action) {
		if($action['action'] == 'prompt_sheet') {
			@$selection_key = $action['selection_key'];
			@$selection_mode = $action['mode'] ?: 'single';
			@$sheet_kata = $action['schema'];
			
			if($selection_key && $sheet_kata) {
				$sheet_kata = preg_replace(
					'#^columns:#m',
					sprintf("columns:\n  selection/%s:\n    params:\n      mode: %s",
						$selection_key,
						$selection_mode
					),
					$sheet_kata
				);
				
				$actions['actions'][$action_idx]['schema'] = $sheet_kata;
				unset($actions['actions'][$action_idx]['selection_key']);
				unset($actions['actions'][$action_idx]['mode']);
				$is_changed = true;
			}
		}
	}
	
	if($is_changed) {
		$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($actions)),
			$node['id']
		));
	}
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
