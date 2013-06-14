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
			params_json TEXT,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX owner (owner_context, owner_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['calendar'] = 'calendar';
}

// ===========================================================================
// Migrate `calendar_recurring_profile.owner_*` to `calendar_recurring_profile.calendar_id`

if(!isset($tables['calendar_recurring_profile'])) {
	$logger->error("The 'calendar_recurring_profile' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('calendar_recurring_profile');

if(!isset($columns['calendar_id'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN calendar_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX calendar_id (calendar_id)");
}

// ===========================================================================
// Migrate `calendar_event.owner_*` to `calendar_event.calendar_id`

if(!isset($tables['calendar_event'])) {
	$logger->error("The 'calendar_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('calendar_event');

if(!isset($columns['calendar_id'])) {
	$db->Execute("ALTER TABLE calendar_event ADD COLUMN calendar_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX calendar_id (calendar_id)");
}

if(isset($columns['owner_context'])) {
	$rs = $db->Execute("SELECT DISTINCT calendar_event.owner_context, calendar_event.owner_context_id, CONCAT(worker.first_name,' ',worker.last_name) as worker_name FROM calendar_event INNER JOIN worker ON (worker.id=owner_context_id) WHERE calendar_event.calendar_id = 0");
	
	while($row = mysql_fetch_assoc($rs)) {
		// Create calendar
		$calendar_name = $row['worker_name'] . "'s Schedule";
		$owner_context = $row['owner_context'];
		$owner_context_id = $row['owner_context_id'];
		
		$sql = sprintf("INSERT INTO calendar(name, owner_context, owner_context_id, params_json, updated_at) ".
			"VALUES (%s, %s, %d, %s, %d)",
			$db->qstr($calendar_name),
			$db->qstr($owner_context),
			$owner_context_id,
			$db->qstr(''),
			time()
		);
		$db->Execute($sql);
	
		$new_calendar_id = $db->LastInsertId();
		
		// Set the new calendar id on event records
		
		$sql = sprintf("UPDATE calendar_event SET calendar_id = %d WHERE owner_context = %s AND owner_context_id = %d",
			$new_calendar_id,
			$db->qstr($owner_context),
			$owner_context_id
		);
		$db->Execute($sql);
		
		// Update recurring profiles
		
		$sql = sprintf("UPDATE calendar_recurring_profile SET calendar_id = %d WHERE owner_context = %s AND owner_context_id = %d",
			$new_calendar_id,
			$db->qstr($owner_context),
			$owner_context_id
		);
		$db->Execute($sql);
		
		// Elect the new calendar as the worker's availability
		
		$sql = sprintf("INSERT INTO worker_pref (worker_id, setting, value) VALUES (%d, %s, %d)",
			$owner_context_id,
			$db->qstr('availability_calendar_id'),
			$new_calendar_id
		);
		$db->Execute($sql);
	}
	
	// Drop owner_context_* columns from calendar
	$db->Execute("ALTER TABLE calendar_event DROP COLUMN owner_context, DROP COLUMN owner_context_id");
}

// ===========================================================================
// Drop `calendar_recurring_profile.owner_*`

if(!isset($tables['calendar_recurring_profile'])) {
	$logger->error("The 'calendar_recurring_profile' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('calendar_recurring_profile');

if(isset($columns['owner_context'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile DROP COLUMN owner_context, DROP COLUMN owner_context_id");
}

// ===========================================================================
// Convert calendar workspace tabs to calendar records

if(!isset($tables['workspace_tab'])) {
	$logger->error("The 'workspace_tab' table does not exist.");
	return FALSE;
}

$sql = "SELECT workspace_tab.id, workspace_tab.name, workspace_tab.params_json, workspace_page.owner_context, workspace_page.owner_context_id ".
	"FROM workspace_tab ".
	"INNER JOIN workspace_page ON (workspace_tab.workspace_page_id=workspace_page.id) ".
	"WHERE workspace_tab.extension_id = 'core.workspace.tab.calendar'"
	;
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$calendar_name = $row['name'];
	$owner_context = $row['owner_context'];
	$owner_context_id = $row['owner_context_id'];
	@$params = json_decode($row['params_json'], true);

	if(!isset($params['worklist_model']) || isset($params['calendar_id']))
		continue;
	
	$params['datasource'] = 'calendar.datasource.worklist';
	
	$params = array(
		'manual_disabled' => 1,
		'sync_enabled' => 1,
		'series' => array(
			$params,
		)
	);
	
	$sql = sprintf("INSERT INTO calendar(name, owner_context, owner_context_id, params_json, updated_at) ".
		"VALUES (%s, %s, %d, %s, %d)",
		$db->qstr($calendar_name),
		$db->qstr($owner_context),
		$owner_context_id,
		$db->qstr(json_encode($params)),
		time()
	);
	$db->Execute($sql);

	$new_calendar_id = $db->LastInsertId();
	
	$sql = sprintf("UPDATE workspace_tab SET params_json = %s WHERE id = %d",
		$db->qstr(json_encode(array('calendar_id' => $new_calendar_id))),
		$row['id']
	);
	$db->Execute($sql);
}

// ===========================================================================
// Clear cached calendar models

$sql = "DELETE FROM worker_view_model WHERE class_name = 'View_CalendarEvent'";
$db->Execute($sql);

// ===========================================================================
// Convert recurring events to a more flexible text-based format (because this isn't Windows)

if(!isset($tables['calendar_recurring_profile'])) {
	$logger->error("The 'calendar_recurring_profile' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('calendar_recurring_profile');

// Add a timezone to each recurring profile (based on the owner)

if(!isset($columns['tz'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN tz VARCHAR(128) NOT NULL DEFAULT ''");

	$db->Execute(sprintf("UPDATE calendar_recurring_profile SET tz = %s",
		$db->qstr(date_default_timezone_get())
	));
	
	$worker_timezones = $db->GetArray("SELECT worker_id, value FROM worker_pref WHERE setting = 'timezone'");
	
	foreach($worker_timezones as $wtz) {
		$sql = sprintf("UPDATE calendar_recurring_profile INNER JOIN calendar ON (calendar.id=calendar_recurring_profile.calendar_id) SET tz = %s WHERE calendar.owner_context = %s AND calendar.owner_context_id = %d",
			$db->qstr($wtz['value']),
			$db->qstr('cerberusweb.contexts.worker'),
			$wtz['worker_id']
		);
		$db->Execute($sql);
	}
	
	unset($worker_timezones);
}

if(!isset($columns['event_start'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN event_start VARCHAR(128) NOT NULL DEFAULT ''");
}

if(!isset($columns['event_end'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN event_end VARCHAR(128) NOT NULL DEFAULT ''");
}

if(!isset($columns['recur_start'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN recur_start INT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($columns['recur_end'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN recur_end INT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($columns['patterns'])) {
	$db->Execute("ALTER TABLE calendar_recurring_profile ADD COLUMN patterns TEXT");
}

if(isset($columns['params_json'])) {
	$sql = "SELECT r.id, r.date_start, r.date_end, r.tz, r.params_json, c.owner_context, c.owner_context_id FROM calendar_recurring_profile AS r INNER JOIN calendar AS c ON (c.id=r.calendar_id)";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$event_start = 0;
		$event_end = 0;
		$recur_end = 0;

		// Convert params
		
		if(false !== (@$json = json_decode($row['params_json'], true)) && isset($json['freq'])) {
			$options = $json['options'];
			
			$patterns = '';
			
			// Pull an example event
			$event = $db->GetRow(sprintf("SELECT e.date_start, e.date_end FROM calendar_event AS e WHERE e.recurring_id = %d ORDER BY e.date_start desc LIMIT 1", $row['id']));
			
			// If there wasn't an event in the system, use the recurring profile itself.
			if(empty($event)) {
				$event = array(
					'date_start' => $row['date_start'],
					'date_end' => $row['date_end'],
				);
			}
			
			// Handle ending dates
			if(isset($json['end']['term']) && $json['end']['term'] == 'date') {
				if(isset($json['end']['options']))
					if(isset($json['end']['options']['on']))
						$recur_end = $json['end']['options']['on'];
			}
			
			$days_of_week = array(
				0 => 'Sunday',
				1 => 'Monday',
				2 => 'Tuesday',
				3 => 'Wednesday',
				4 => 'Thursday',
				5 => 'Friday',
				6 => 'Saturday',
			);
			
			switch($json['freq']) {
				case 'daily':
					foreach($days_of_week as $day) {
						$patterns .= sprintf("%s\n",
							$day
						);
					}
					break;
					
				case 'weekly':
					if(isset($options['day']))
					foreach($options['day'] as $day) {
						$patterns .= sprintf("%s\n",
							$days_of_week[$day]
						);
					}
					break;
					
				case 'monthly':
					$suffixes = array(
						'0' => 'th',
						'1' => 'st',
						'2' => 'nd',
						'3' => 'rd',
						'4' => 'th',
						'5' => 'th',
						'6' => 'th',
						'7' => 'th',
						'8' => 'th',
						'9' => 'th',
					);
					
					if(isset($options['day']))
					foreach($options['day'] as $day) {
						$patterns .= sprintf("%d%s\n",
							$day,
							$suffixes[substr($day,-1)]
						);
					}
					break;
					
				case 'yearly':
					$months = array(
						1 => 'Jan',
						2 => 'Feb',
						3 => 'Mar',
						4 => 'Apr',
						5 => 'May',
						6 => 'Jun',
						7 => 'Jul',
						8 => 'Aug',
						9 => 'Sep',
						10 => 'Oct',
						11 => 'Nov',
						12 => 'Dec',
					);
					
					if(isset($options['month']))
					foreach($options['month'] as $month) {
						$patterns .= sprintf("%s %d\n",
							$months[$month],
							gmdate('d', $event['date_start'])
						);
					}
					break;
			}
			
			// Handle start and end relative to Jan 1 1970 (ignore DST)
			
			$timezone = new DateTimeZone($row['tz']);
			
			$datetime_start = new DateTime(date('r', $event['date_start']), $timezone);
			$datetime_end = new DateTime(date('r', $event['date_end']), $timezone);
			
			$event_start = $datetime_start->format('H:i');

			$event_duration = $datetime_end->getTimestamp() - $datetime_start->getTimestamp();

			if(empty($event_duration)) {
				$event_end = $event_start;
				
			} else {
				$datetime_tomorrow = clone $datetime_start;
				$datetime_tomorrow->modify("+1 day");
				
				// Later the same day
				if($datetime_start->format('Y-m-d') == $datetime_end->format('Y-m-d')) {
					$event_end = $datetime_end->format('H:i');
					
				// Tomorrow
				} elseif($datetime_tomorrow->format('Y-m-d') == $datetime_end->format('Y-m-d')) {
					$event_end = 'tomorrow ' . $datetime_end->format('H:i');
					
				// Relative date longer than two days
				} else {
					$event_end = '+' . DevblocksPlatform::strSecsToString($event_duration);
				}
			}
			
			// Update
			
			$sql = sprintf("UPDATE calendar_recurring_profile ".
				"SET patterns = %s, event_start = %s, event_end = %s, recur_end = %d ".
				"WHERE id = %d",
				$db->qstr($patterns),
				$db->qstr($event_start),
				$db->qstr($event_end),
				$recur_end,
				$row['id']
			);
			$db->Execute($sql);
		}
	}
	
	$db->Execute("ALTER TABLE calendar_recurring_profile DROP COLUMN params_json, DROP COLUMN date_start, DROP COLUMN date_end");
}

// ===========================================================================
// Delete old manual recurring event records

if(!isset($tables['calendar_event'])) {
	$logger->error("The 'calendar_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('calendar_event');

if(isset($columns['recurring_id'])) {
	$db->Execute("DELETE FROM calendar_event WHERE recurring_id != 0");
	$db->Execute("ALTER TABLE calendar_event DROP COLUMN recurring_id");
}

// ===========================================================================
// All workspace pages and tabs should have an extension

$db->Execute(sprintf("UPDATE workspace_page SET extension_id = %s WHERE extension_id = ''",
	$db->qstr('core.workspace.page.workspace')
));

$db->Execute(sprintf("UPDATE workspace_tab SET extension_id = %s WHERE extension_id = ''",
	$db->qstr('core.workspace.tab.worklists')
));

// ===========================================================================
// Finish up

return TRUE;
