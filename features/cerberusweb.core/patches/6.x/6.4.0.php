<?php
if(!class_exists('C4_AbstractViewModel')) {
class C4_AbstractViewModel {};
}

$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Convert worklist-based workspace widgets to a simplified JSON format

$rs = $db->Execute("SELECT id, extension_id, params_json FROM workspace_widget");

while($row = mysql_fetch_assoc($rs)) {
	$changed = false;
	
	$widget_id = $row['id'];
	$extension_id = $row['extension_id'];
	$params_json = $row['params_json'];
	
	if(false == ($json = json_decode($params_json, true)))
		continue;
	
	switch($extension_id) {
		case 'core.workspace.widget.counter':
		case 'core.workspace.widget.gauge':
		case 'core.workspace.widget.subtotals':
		case 'core.workspace.widget.worklist':
			$pass = true;

			switch($extension_id) {
				case 'core.workspace.widget.counter':
				case 'core.workspace.widget.gauge':
					if(!isset($json['datasource'])
						|| $json['datasource'] != 'core.workspace.widget.datasource.worklist')
							$pass = false;
					break;
			}
			
			if(!$pass)
				break;
			
			if(!isset($json['view_model']))
				break;
			
			if(!isset($json['view_context']))
				break;
			
			$view_context = $json['view_context'];
			
			if(false == ($old_model = unserialize(base64_decode($json['view_model']))))
				break;
			
			$json['worklist_model'] = array(
				'context' => $view_context,
				'columns' => $old_model->view_columns,
				'params' => json_decode(json_encode($old_model->paramsEditable), true),
				'limit' => $old_model->renderLimit,
				'sort_by' => $old_model->renderSortBy,
				'sort_asc' => !empty($old_model->renderSortAsc),
				'subtotals' => $old_model->renderSubtotals,
			);
		
			switch($extension_id) {
				case 'core.workspace.widget.subtotals':
				case 'core.workspace.widget.worklist':
					unset($json['datasource']);
					break;
			}
			
			unset($json['view_context']);
			unset($json['view_model']);
			unset($json['view_id']);
			
			$changed = true;
			break;
			
		case 'core.workspace.widget.chart':
		case 'core.workspace.widget.scatterplot':
			
			if(!isset($json['series']) || !is_array($json['series']))
				break;
			
			foreach($json['series'] as $idx => $series) {
				if(!isset($series['datasource']) || $series['datasource'] != 'core.workspace.widget.datasource.worklist')
					continue;
				
				if(!isset($series['view_model']))
					continue;
				
				if(!isset($series['view_context']))
					continue;
				
				$view_context = $series['view_context'];
				
				if(false == ($old_model = unserialize(base64_decode($series['view_model']))))
					break;
				
				$series['worklist_model'] = array(
					'context' => $view_context,
					'columns' => $old_model->view_columns,
					'params' => json_decode(json_encode($old_model->paramsEditable), true),
					'limit' => $old_model->renderLimit,
					'sort_by' => $old_model->renderSortBy,
					'sort_asc' => !empty($old_model->renderSortAsc),
					'subtotals' => $old_model->renderSubtotals,
				);
				
				unset($series['view_context']);
				unset($series['view_model']);
				unset($series['view_id']);
				
				$json['series'][$idx] = $series;
				
				$changed = true;
			}
			
			break;
	}
	
	if($changed) {
		$sql = sprintf("UPDATE workspace_widget SET params_json=%s WHERE id=%d",
			$db->qstr(json_encode($json)),
			$widget_id
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Convert worklist-based calendar tabs to a simplified JSON format

$rs = $db->Execute("SELECT id, params_json FROM workspace_tab WHERE extension_id = 'core.workspace.tab.calendar'");

while($row = mysql_fetch_assoc($rs)) {
	$tab_id = $row['id'];
	$params_json = $row['params_json'];
	
	if(false == ($json = json_decode($params_json, true)))
		continue;

	if(!isset($json['context_extid']))
		continue;
	
	$view_context = $json['context_extid'];
	
	if(!isset($json['view_model']))
		continue;
	
	if(false == ($old_model = unserialize(base64_decode($json['view_model']))))
		continue;
	
	$json['worklist_model'] = array(
		'context' => $view_context,
		'columns' => $old_model->view_columns,
		'params' => json_decode(json_encode($old_model->paramsEditable), true),
		'limit' => $old_model->renderLimit,
		'sort_by' => $old_model->renderSortBy,
		'sort_asc' => !empty($old_model->renderSortAsc),
		'subtotals' => $old_model->renderSubtotals,
	);
	
	unset($json['context_extid']);
	unset($json['view_id']);
	unset($json['view_model']);
	
	$sql = sprintf("UPDATE workspace_tab SET params_json=%s WHERE id=%d",
		$db->qstr(json_encode($json)),
		$tab_id
	);
	$db->Execute($sql);
}

// ===========================================================================
// Convert worklist-based VA actions to a simplified JSON format

$rs = $db->Execute("SELECT decision_node.id, decision_node.params_json, trigger_event.variables_json FROM decision_node INNER JOIN trigger_event ON (trigger_event.id = decision_node.trigger_id) WHERE decision_node.node_type = 'action'");

while($row = mysql_fetch_assoc($rs)) {
	$changed = false;
	
	$node_id = $row['id'];
	$params_json = $row['params_json'];
	$variables_json = $row['variables_json'];
	
	if(false == ($variables = json_decode($variables_json, true)))
		continue;
	
	if(false == ($json = json_decode($params_json, true)))
		continue;
	
	if(!isset($json['actions']) || !is_array($json['actions']))
		continue;
	
	foreach($json['actions'] as $idx => $action) {
		if(!isset($action['action']))
			continue;
		
		if('var_' == substr($action['action'], 0, 4)) {
			if(!isset($variables[$action['action']]))
				continue;
			
			if(!isset($action['view_model']))
				continue;
			
			if(false == ($old_model = unserialize(base64_decode($action['view_model']))))
				continue;
			
			$action['worklist_model'] = array(
				'context' => substr($variables[$action['action']]['type'], 4),
				'columns' => $old_model->view_columns,
				'params' => json_decode(json_encode($old_model->paramsEditable), true),
				'limit' => $old_model->renderLimit,
				'sort_by' => $old_model->renderSortBy,
				'sort_asc' => !empty($old_model->renderSortAsc),
				'subtotals' => $old_model->renderSubtotals,
			);
			
			unset($action['view_model']);
			
			$json['actions'][$idx] = $action;
			$changed = true;
		}
	}
	
	if($changed) {
		$sql = sprintf("UPDATE decision_node SET params_json=%s WHERE id=%d",
			$db->qstr(json_encode($json)),
			$node_id
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Convert `pop3_account` records to not auto-disable on failure, but delay

if(!isset($tables['pop3_account'])) {
	$logger->error("The 'pop3_account' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('pop3_account');

if(!isset($columns['delay_until'])) {
	$db->Execute("ALTER TABLE pop3_account ADD COLUMN delay_until INT UNSIGNED DEFAULT 0 NOT NULL");
}

// ===========================================================================
// Add the `custom_fieldset` database table

if(!isset($tables['custom_fieldset'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS custom_fieldset (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			context VARCHAR(255) DEFAULT '',
			owner_context VARCHAR(255) DEFAULT '',
			owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX owner (owner_context, owner_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['custom_fieldset'] = 'custom_fieldset';
}

// ===========================================================================
// Add the `custom_field.custom_fieldset_id` field

if(!isset($tables['custom_field'])) {
	$logger->error("The 'custom_field' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_field');

// If the foreign key column for groups doesn't exist, add it.
if(!isset($columns['custom_fieldset_id'])) {
	$db->Execute("ALTER TABLE custom_field ADD COLUMN custom_fieldset_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX (custom_fieldset_id)");
}

// If the old style custom_field.group_id field exists, migrate it to custom_fieldset rows.
if(isset($columns['group_id'])) {
	$rs = $db->Execute("SELECT DISTINCT custom_field.group_id, worker_group.name AS group_name, custom_field.context FROM custom_field INNER JOIN worker_group ON (custom_field.group_id=worker_group.id) WHERE custom_field.group_id > 0");
	
	// Migrate group-based custom fields to custom fieldset records
	while($row = mysql_fetch_assoc($rs)) {
		$group_id = $row['group_id'];
		$group_name = $row['group_name'];
		$context = $row['context'];
		
		$sql = sprintf("INSERT INTO custom_fieldset (name, context, owner_context, owner_context_id) VALUES ('%s', '%s', '%s', %d)",
			mysql_real_escape_string($group_name),
			mysql_real_escape_string($context),
			mysql_real_escape_string('cerberusweb.contexts.group'),
			$group_id
		);
		$db->Execute($sql);
		
		$custom_fieldset_id = $db->LastInsertId();
		
		$db->Execute(sprintf("UPDATE custom_field SET custom_fieldset_id = %d WHERE group_id = %d",
			$custom_fieldset_id,
			$group_id
		));
	}
	
	// Set up context_link records between custom_fieldset records and tickets
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) SELECT 'cerberusweb.contexts.ticket', cfv.context_id, 'cerberusweb.contexts.custom_fieldset', cf.custom_fieldset_id from custom_field_stringvalue AS cfv INNER JOIN custom_field AS cf ON (cf.id=cfv.field_id) WHERE cf.custom_fieldset_id > 0");
	$db->Execute("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) SELECT 'cerberusweb.contexts.custom_fieldset', cf.custom_fieldset_id, 'cerberusweb.contexts.ticket', cfv.context_id from custom_field_stringvalue AS cfv INNER JOIN custom_field AS cf ON (cf.id=cfv.field_id) WHERE cf.custom_fieldset_id > 0");

	// Drop the old column
	$db->Execute("ALTER TABLE custom_field DROP COLUMN group_id");
}

// ===========================================================================
// Clean up some `worker_view_model` rows

$db->Execute("DELETE FROM worker_view_model WHERE view_id IN ('_snippets', 'snippets', 'mail_drafts', 'mail_snippets','cerberuswebaddresstab','cerberuswebcrmopportunitytab','cerberusweborgtab','cerberuswebprofilesaddress','cerberuswebprofilesopportunity','cerberuswebprofilesorg')");

// ===========================================================================
// Add the `calendar` database table

if(!isset($tables['calendar'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS calendar (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			owner_context VARCHAR(255) DEFAULT '',
			owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX owner (owner_context, owner_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['calendar'] = 'calendar';
}

// ===========================================================================
// Finish up

return TRUE;