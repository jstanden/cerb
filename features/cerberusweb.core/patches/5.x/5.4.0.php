<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Drop contact_list

if(isset($tables['trigger_event'])) {
	$db->Execute("DROP TABLE contact_list");
	unset($tables['trigger_event']);
}

// ===========================================================================
// trigger_event 

if(!isset($tables['trigger_event'])) {
	$sql = sprintf("
	CREATE TABLE IF NOT EXISTS `trigger_event` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL DEFAULT '',
		is_disabled TINYINT NOT NULL DEFAULT 0,
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		event_point VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		INDEX event_point (event_point)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['trigger_event'] = 'trigger_event';
}

// ===========================================================================
// decision_node

if(!isset($tables['decision_node'])) {
	$sql = sprintf("
	CREATE TABLE IF NOT EXISTS decision_node (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		parent_id INT UNSIGNED NOT NULL DEFAULT 0,
		trigger_id INT UNSIGNED NOT NULL DEFAULT 0,
		node_type ENUM('switch','outcome','action'),
		title VARCHAR(255) NOT NULL DEFAULT '',
		pos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		params_json LONGTEXT,
		PRIMARY KEY (id),
		INDEX parent_id (parent_id),
		INDEX trigger_id (trigger_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['decision_node'] = 'decision_node';
}

// ===========================================================================
// Rename 'worker_event' to 'notification'

if(isset($tables['worker_event']) && !isset($tables['notification'])) {
	$db->Execute('ALTER TABLE worker_event RENAME notification');
	$db->Execute("DELETE FROM worker_view_model WHERE view_id = 'home_myevents'");
}

// ===========================================================================
// Introduce a context_merge_history table

if(!isset($tables['context_merge_history'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS context_merge_history (
			context VARCHAR(128) DEFAULT '' NOT NULL,
			from_context_id INT UNSIGNED DEFAULT 0 NOT NULL,
			to_context_id INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (context, from_context_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

// ===========================================================================
// Import the 'community' tables from usermeet.core

// create community_tool
if(!isset($tables['community_tool'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS community_tool (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			code VARCHAR(8) DEFAULT '' NOT NULL,
			extension_id VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
	
} else { // update community_tool
	if($tables['community_tool']) {
		list($columns, $indexes) = $db->metaTable('community_tool');
		
		if(isset($columns['id']) 
			&& ('int(10) unsigned' != $columns['id']['type'] 
			|| 'auto_increment' != $columns['id']['extra']))	
				$db->Execute("ALTER TABLE community_tool MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT");	
		
		if(isset($columns['community_id']))
			$db->Execute("ALTER TABLE community_tool DROP COLUMN community_id");
			
		if(!isset($columns['name'])) {
		    $db->Execute("ALTER TABLE community_tool ADD COLUMN name VARCHAR(128) DEFAULT '' NOT NULL");
			$db->Execute("UPDATE community_tool SET name = 'Support Center' WHERE name = '' AND extension_id = 'sc.tool'");
		}
	}
}

// create community_tool_property
if(!isset($tables['community_tool_property'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS community_tool_property (
			tool_code VARCHAR(8) DEFAULT '' NOT NULL,
			property_key VARCHAR(64) DEFAULT '' NOT NULL,
			property_value TEXT,
			PRIMARY KEY (tool_code, property_key)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
	
} else { // update community_tool_property
	list($columns, $indexes) = $db->metaTable('community_tool_property');
	
	if(isset($columns['property_value'])
		&& 0 != strcasecmp('text',$columns['property_value']['type'])) {
			$db->Execute("ALTER TABLE community_tool_property MODIFY COLUMN property_value TEXT");
	}
}

// create community_session
if(!isset($tables['community_session'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS community_session (
			session_id VARCHAR(32) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			properties MEDIUMTEXT,
			PRIMARY KEY (session_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
	
} else { // update community_session
	list($columns, $indexes) = $db->metaTable('community_session');

	if(isset($columns['properties'])
		&& 0 != strcasecmp('mediumtext',$columns['properties']['type'])) {
			$db->Execute('ALTER TABLE community_session MODIFY COLUMN properties MEDIUMTEXT');
	}
}

// community
if(isset($tables['community']))
	$db->Execute("DROP TABLE community");

// ===========================================================================
// Add reply_address_id/reply_personal/reply_signature fields to groups

list($columns, $indexes) = $db->metaTable('team');

if(!isset($columns['reply_address_id']))
	$db->Execute("ALTER TABLE team ADD COLUMN reply_address_id INT UNSIGNED DEFAULT 0 NOT NULL");
if(!isset($columns['reply_personal']))
	$db->Execute("ALTER TABLE team ADD COLUMN reply_personal VARCHAR(128) DEFAULT '' NOT NULL");
if(isset($columns['signature']) && !isset($columns['reply_signature']))
	$db->Execute("ALTER TABLE team CHANGE COLUMN signature reply_signature TEXT");
	
if(!isset($column['reply_address_id'])) {
	// Migrate from group settings (and in code)
	$results = $db->GetArray(sprintf("SELECT group_id, setting, value FROM group_setting WHERE setting IN ('reply_from','reply_personal')"));
	foreach($results as $row) {
		if(empty($row['value']))
			continue;
		
		switch($row['setting']) {
			case 'reply_from':
				if(null == ($address = DAO_Address::lookupAddress($row['value'], true)))
					continue;
				
				$db->Execute(sprintf("UPDATE team SET reply_address_id = %d WHERE id = %d",
					$address->id,
					$row['group_id']
				));
				break;
				
			case 'reply_personal':
				$db->Execute(sprintf("UPDATE team SET reply_personal = %s WHERE id = %d",
					$db->qstr($row['value']),
					$row['group_id']
				));
				break;
		}
	}
	
	// Remove redundant settings from DB
	$db->Execute("DELETE FROM group_setting WHERE setting IN ('reply_from','reply_personal')");;
}

$db->Execute("DELETE FROM group_setting WHERE setting IN ('reply_personal_with_worker')");

// ===========================================================================
// Add reply_address_id/reply_personal/reply_signature fields to buckets

list($columns, $indexes) = $db->metaTable('category');

if(!isset($columns['reply_address_id']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_address_id INT UNSIGNED DEFAULT 0 NOT NULL");
if(!isset($columns['reply_personal']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_personal VARCHAR(128) DEFAULT '' NOT NULL");
if(!isset($columns['reply_signature']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_signature TEXT");

// ===========================================================================
// Add address_outgoing

if(!isset($tables['address_outgoing'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS address_outgoing (
			address_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_default TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			reply_personal VARCHAR(128) DEFAULT '' NOT NULL,
			reply_signature TEXT,
			PRIMARY KEY (address_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
	
	$tables['address_outgoing'] = 'address_outgoing';
	
	// Migrate the default sender address
	
	$default_reply_from = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_reply_from' AND plugin_id='cerberusweb.core'");
	$default_reply_personal = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_reply_personal' AND plugin_id='cerberusweb.core'");
	$default_reply_signature = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_signature' AND plugin_id='cerberusweb.core'");
	
	if(!empty($default_reply_from) && null != ($address = DAO_Address::lookupAddress($default_reply_from, true))) {
		$db->Execute("UPDATE address_outgoing SET is_default = 0");
		$db->Execute(sprintf("INSERT IGNORE INTO address_outgoing (address_id, is_default, reply_personal, reply_signature) ".
			"VALUES (%d, %d, %s, %s)",
			$address->id,
			1,
			$db->qstr($default_reply_personal),
			$db->qstr($default_reply_signature)
		));
	}
	
	$db->Execute("DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting IN ('default_reply_from','default_reply_personal','default_signature','default_signature_pos')");
	
	// Import from group addresses
	$db->Execute("INSERT IGNORE INTO address_outgoing (address_id,is_default) SELECT DISTINCT reply_address_id, 0 FROM team WHERE reply_address_id != 0");
	
}	

// ===========================================================================
// Migrate group settings to Virtual Attendants

$todo = $db->GetOne("SELECT count(*) FROM group_setting WHERE setting IN ('auto_reply_enabled', 'auto_reply', 'close_reply_enabled', 'close_reply', 'group_spam_threshold', 'group_spam_action', 'group_spam_action_param')");

if(!empty($todo)) {
	$group_ids = $db->GetArray("SELECT id FROM team");
	
	foreach($group_ids as $row) {
		$group_id = $row['id'];
		$settings = array();
		
		$rows = $db->GetArray(sprintf("SELECT setting, value FROM group_setting WHERE group_id = %d", $group_id));
		foreach($rows as $row)
			$settings[$row['setting']] = $row['value'];
		
		// Migrate open auto-reply
		if(isset($settings['auto_reply_enabled']) 
			&& !empty($settings['auto_reply_enabled'])) {
				@$content = $settings['auto_reply'];
				
				if(empty($content))
					continue;
					
				// Convert tokens
				$content = str_replace(
					array(
						'{{initial_message_',
						'{{latest_message_',
						'{{bucket_',
						'{{custom_',
						'{{created',
						'{{subject}}',
						'{{mask}}',
						'{{id}}',
						'{{url}}',
						'{{updated',
					),
					array(
						'{{',
						'{{',
						'{{ticket_bucket_',
						'{{ticket_custom_',
						'{{ticket_created',
						'{{ticket_subject}}',
						'{{ticket_mask}}',
						'{{ticket_id}}',
						'{{ticket_url}}',
						'{{ticket_updated',
					),
					$content
				);
	
				// Insert trigger_event
				$db->Execute(sprintf("INSERT INTO trigger_event (owner_context, owner_context_id, event_point, title) ".
					"VALUES (%s, %d, %s, %s)",
					$db->qstr('cerberusweb.contexts.group'),
					$group_id,
					$db->qstr('event.mail.received.group'),
					$db->qstr('Send New Ticket Auto-Reply')
				));
				$trigger_id = $db->LastInsertId();
				
				// Decision: Is New Ticket?
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					0,
					$trigger_id,
					$db->qstr('Is it a new ticket?'),
					$db->qstr(''),
					$db->qstr('switch'),
					0
				));
				$parent_id = $db->LastInsertId();
				
				// Outcome: Yes
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					$parent_id,
					$trigger_id,
					$db->qstr('Yes'),
					$db->qstr('{"groups":[{"any":0,"conditions":[{"condition":"is_first","bool":"1"},{"condition":"is_outgoing","bool":"0"}]}]}'),
					$db->qstr('outcome'),
					1
				));
				$parent_id = $db->LastInsertId();
				
				// Action: Send auto-reply
				
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					$parent_id,
					$trigger_id,
					$db->qstr('Send new ticket auto-reply'),
					$db->qstr('{"actions":[{"action":"send_email_recipients","content":' . json_encode($content) . ',"is_autoreply":"1"}]}'),
					$db->qstr('action'),
					2
				));
				$parent_id = $db->LastInsertId();
			}
			
		// Migrate close auto-reply
		if(isset($settings['close_reply_enabled']) 
			&& !empty($settings['close_reply_enabled'])) {
				@$content = $settings['close_reply'];
				
				if(empty($content))
					continue;
					
				// Convert tokens
				$content = str_replace(
					array(
						'{{initial_message_',
						'{{latest_message_',
						'{{bucket_',
						'{{custom_',
						'{{created',
						'{{subject}}',
						'{{mask}}',
						'{{id}}',
						'{{url}}',
						'{{updated',
					),
					array(
						'{{',
						'{{',
						'{{ticket_bucket_',
						'{{ticket_custom_',
						'{{ticket_created',
						'{{ticket_subject}}',
						'{{ticket_mask}}',
						'{{ticket_id}}',
						'{{ticket_url}}',
						'{{ticket_updated',
					),
					$content
				);
	
				// Insert trigger_event
				$db->Execute(sprintf("INSERT INTO trigger_event (owner_context, owner_context_id, event_point, title) ".
					"VALUES (%s, %d, %s, %s)",
					$db->qstr('cerberusweb.contexts.group'),
					$group_id,
					$db->qstr('event.mail.closed.group'),
					$db->qstr('Send Closed Ticket Auto-Reply')
				));
				$trigger_id = $db->LastInsertId();
				
				// Action: Send auto-reply
				
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					0,
					$trigger_id,
					$db->qstr('Send closed ticket auto-reply'),
					$db->qstr('{"actions":[{"action":"send_email_recipients","content":' . json_encode($content) . ',"is_autoreply":"1"}]}'),
					$db->qstr('action'),
					0
				));
				$parent_id = $db->LastInsertId();
			}
		
		// Migrate spam quarantine
		if(isset($settings['group_spam_threshold']) && isset($settings['group_spam_action']) && isset($settings['group_spam_action_param'])) {
			@$spam_threshold = intval($settings['group_spam_threshold']);
			@$spam_action = intval($settings['group_spam_action']);
			@$spam_action_param = intval($settings['group_spam_action_param']);
			
			if(!empty($spam_threshold) && !empty($spam_action)) {
			
				// Insert trigger_event
				$db->Execute(sprintf("INSERT INTO trigger_event (owner_context, owner_context_id, event_point, title) ".
					"VALUES (%s, %d, %s, %s)",
					$db->qstr('cerberusweb.contexts.group'),
					$group_id,
					$db->qstr('event.mail.received.group'),
					$db->qstr('Quarantine Spam')
				));
				$trigger_id = $db->LastInsertId();
				
				// Decision: Is it a new ticket with a high spam probability?
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					0,
					$trigger_id,
					$db->qstr('Is it a new ticket with a high spam probability?'),
					$db->qstr(''),
					$db->qstr('switch'),
					0
				));
				$parent_id = $db->LastInsertId();
				
				// Outcome: Yes
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					$parent_id,
					$trigger_id,
					$db->qstr('Yes'),
					$db->qstr('{"groups":[{"any":0,"conditions":[{"condition":"is_first","bool":"1"},{"condition":"is_outgoing","bool":"0"},{"condition":"ticket_status","oper":"in","values":["open"]},{"condition":"ticket_spam_training","oper":"!in","values":["N"]},{"condition":"ticket_spam_score","oper":"gt","value":'.json_encode($spam_threshold).'}]}]}'),
					$db->qstr('outcome'),
					1
				));
				$parent_id = $db->LastInsertId();
				
				// Action: Quarantine
				
				switch($spam_action) {
					// Delete
					case 1:
						$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
							"VALUES (%d, %d, %s, %s, %s, %d)",
							$parent_id,
							$trigger_id,
							$db->qstr('Delete ticket'),
							$db->qstr('{"actions":[{"action":"set_status","status":"deleted"}]}'),
							$db->qstr('action'),
							2
						));
						$parent_id = $db->LastInsertId();				
						break;
						
					// Move
					case 2:
						$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
							"VALUES (%d, %d, %s, %s, %s, %d)",
							$parent_id,
							$trigger_id,
							$db->qstr('Quarantine'),
							$db->qstr('{"actions":[{"action":"move_to_bucket","bucket_id":'.json_encode($spam_action_param).'}]} '),
							$db->qstr('action'),
							2
						));
						$parent_id = $db->LastInsertId();				
						break;
				}
			}
		}
	}
	
	// Delete
	$db->Execute("DELETE FROM group_setting WHERE setting IN ('auto_reply_enabled', 'auto_reply', 'close_reply_enabled', 'close_reply', 'group_spam_threshold', 'group_spam_action', 'group_spam_action_param')");
}

// ===========================================================================
// Add render_filters and drop render_subtotals_clickable

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['render_filters'])) {
	$db->Execute("ALTER TABLE worker_view_model ADD COLUMN render_filters TINYINT(1) NOT NULL DEFAULT 0");
	$db->Execute("UPDATE worker_view_model SET render_filters = 1 WHERE view_id = 'mail_search'");
}

if(isset($columns['render_subtotals_clickable'])) {
	$db->Execute("ALTER TABLE worker_view_model DROP COLUMN render_subtotals_clickable");
}

// ===========================================================================
// Clean up custom field values

// Find checkboxes with dupe values set (nuke dupe NO's first)
$results = $db->GetArray("select field_id, count(context_id) as hits, context_id from custom_field_numbervalue where field_id IN (select id from custom_field where type = 'C') group by context_id having hits > 1 order by hits desc");
if(is_array($results))
foreach($results as $row) {
	$db->Execute(sprintf("DELETE FROM custom_field_numbervalue WHERE field_id=%d AND context_id=%d AND field_value=0 LIMIT %d",
		$row['field_id'],
		$row['context_id'],
		abs(1-intval($row['hits']))
	));
}

// Find checkboxes with dupe values set (nuke any dupes)
$results = $db->GetArray("select field_id, count(context_id) as hits, context_id from custom_field_numbervalue where field_id IN (select id from custom_field where type = 'C') and field_value=0 group by context_id having hits > 1 order by hits desc");
if(is_array($results))
foreach($results as $row) {
	$db->Execute(sprintf("DELETE FROM custom_field_numbervalue WHERE field_id=%d AND context_id=%d LIMIT %d",
		$row['field_id'],
		$row['context_id'],
		abs(1-intval($row['hits']))
	));
}

// ===========================================================================
// Convert multi-picklist to multi-checkbox

$db->Execute("UPDATE custom_field SET type = 'X' where type = 'M'");

// ===========================================================================
// Migrate group inbox filters to Virtual Attendants

if(isset($tables['group_inbox_filter'])) {

	// Look up group labels
	$group_labels = array();
	$sql = "SELECT id, name FROM team";
	$results = $db->GetArray($sql);
	
	if(!empty($results))
	foreach($results as $result)
		$group_labels[$result['id']] = $result['name'];

	// Look up bucket labels
	$bucket_labels = array();
	$sql = "SELECT id, name FROM category";
	$results = $db->GetArray($sql);
	
	if(!empty($results))
	foreach($results as $result)
		$bucket_labels[$result['id']] = $result['name'];
	
	// Look up custom fields for types
	$sql = "SELECT id, name, context, type FROM custom_field";
	$results = $db->GetArray($sql);
	$custom_fields = array();
	
	if(!empty($results))
	foreach($results as $result) {
		$custom_fields[$result['id']] = array(
			'label' => $result['name'],
			'context' => $result['context'],
			'type' => $result['type'],
		);
	}

	// Find groups with filters
	$sql = "SELECT DISTINCT group_id FROM group_inbox_filter";
	$results = $db->GetArray($sql);
	
	if(!empty($results))
	foreach($results as $result) {
		$group_id = $result['group_id'];
		
		// Insert trigger_event
		$db->Execute(sprintf("INSERT INTO trigger_event (owner_context, owner_context_id, event_point, title) ".
			"VALUES (%s, %d, %s, %s)",
			$db->qstr('cerberusweb.contexts.group'),
			$group_id,
			$db->qstr('event.mail.moved.group'),
			$db->qstr('Inbox Routing')
		));
		$trigger_id = $db->LastInsertId();
		
		// Decision: Delivered to inbox?
		$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
			"VALUES (%d, %d, %s, %s, %s, %d)",
			0,
			$trigger_id,
			$db->qstr('Delivered to inbox?'),
			$db->qstr(''),
			$db->qstr('switch'),
			0
		));
		$parent_id = $db->LastInsertId();
		
		// Outcome: Yes
		$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
			"VALUES (%d, %d, %s, %s, %s, %d)",
			$parent_id,
			$trigger_id,
			$db->qstr('Yes'),
			$db->qstr('{"groups":[{"any":0,"conditions":[{"condition":"ticket_bucket_name","oper":"in","bucket_ids":["0"]}]}]}'),
			$db->qstr('outcome'),
			0
		));
		$parent_id = $db->LastInsertId();

		// Decision: Delivered to inbox?
		$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
			"VALUES (%d, %d, %s, %s, %s, %d)",
			$parent_id,
			$trigger_id,
			$db->qstr('First match:'),
			$db->qstr(''),
			$db->qstr('switch'),
			0
		));
		$parent_id = $db->LastInsertId();
		
		$group_filters_node_id = $parent_id;
	
		// Rules
			
		$sql = sprintf("SELECT group_id, name, criteria_ser, actions_ser ".
			"FROM group_inbox_filter ".
			"WHERE group_id = %d ".
			"ORDER BY is_sticky DESC, sticky_order ASC, pos DESC ",
			$group_id
		);
		$results = $db->GetArray($sql);

		$pos = 0;
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['group_id'];
			$conditions = array();

			if(false === (@$criterion = unserialize($result['criteria_ser'])))
				continue;
				
			if(!is_array($criterion) || empty($criterion))
				continue;
			
			// Loop through and add outcomes
			
			if(is_array($criterion))
			foreach($criterion as $key => $data) {
				
				switch($key) {
					case 'dayofweek':
						$map = array(
							'sun' => '0',
							'mon' => '1',
							'tue' => '2',
							'wed' => '3',
							'thu' => '4',
							'fri' => '5',
							'sat' => '6',
						);
						$days = array();
						
						if(is_array($data))
						foreach($data as $day => $null) {
							if(isset($map[$day]))
								$days[] = $map[$day];
						}
						
						$condition = array(
							'condition' => '_day_of_week',
							'oper' => 'is',
							'day' => $days,
						);
						
						$conditions[] = $condition;
						break;
						
					case 'timeofday':
						$from = isset($data['from']) ? $data['from'] : null;
						$to = isset($data['to']) ? $data['to'] : null;

						if(is_null($from) || is_null($to))
							break;
						
						if(false === ($from = strtotime($from))
							|| false === ($to = strtotime($to))) {
								break;
							}
							
						$condition = array(
							'condition' => '_time_of_day',
							'oper' => 'between',
							'from' => date('h:ia',$from),
							'to' => date('h:ia', $to),
						);
						
						$conditions[] = $condition;
						break;
						
					case 'subject':
						@$val = $data['value'];
						
						if(empty($val))
							break;
						
						$condition = array(
							'condition' => 'ticket_subject',
							'oper' => 'like',
							'value' => $val,
						);
							
						$conditions[] = $condition;
						break;
						
					case 'from':
						@$val = $data['value'];
						
						if(empty($val))
							break;
						
						$condition = array(
							'condition' => 'ticket_latest_message_sender_address',
							'oper' => 'like',
							'value' => $val,
						);
							
						$conditions[] = $condition;
						break;
						
					case 'body':
						@$val = $data['value'];
						
						if(empty($val))
							break;
						
						$condition = array(
							'condition' => 'ticket_latest_message_content',
							'oper' => 'regexp',
							'value' => $val,
						);
							
						$conditions[] = $condition;
						break;
						
					default:
						// Headers
						if('header' == substr($key,0,6)) {
							@$header = $data['header'];
							@$val = $data['value'];
							
							if(empty($val))
								break;
							
							$condition = array(
								'condition' => 'ticket_latest_message_header',
								'header' => $header,
								'oper' => 'like',
								'value' => $val,
							);
								
							$conditions[] = $condition;
							break;
						}
						
						// Custom fields
						if('cf_' != substr($key,0,3))
							break;

						$cfield_id = substr($key,3);
						
						if(!isset($custom_fields[$cfield_id]))
							break;
							
						$cfield = $custom_fields[$cfield_id];
						$cfield_prefix = '';
						$condition = null;
							
						switch($cfield['context']) {
							case 'cerberusweb.contexts.address':
								$cfield_prefix = 'ticket_latest_message_sender_custom_';
								break;
							case 'cerberusweb.contexts.org':
								$cfield_prefix = 'ticket_latest_message_sender_org_custom_';
								break;
							case 'cerberusweb.contexts.ticket':
								$cfield_prefix = 'ticket_custom_';
								break;
						}
						
						$condition_key = $cfield_prefix.$cfield_id;
						
						switch($cfield['type']) {
							case 'C': // Checkbox
								$condition = array(
									'condition' => $condition_key,
									'bool' => !empty($data['value']) ? 1 : 0,
								);
								break;
							case 'S': // Single text
							case 'T': // Multi text
							case 'U': // URL
								$oper = ('!=' == @$data['oper']) ? '!like' : 'like';
								$condition = array(
									'condition' => $condition_key,
									'oper' => $oper,
									'value' => $data['value'],
								);
								break;
							case 'D': // Dropdown
							case 'X': // Multi-Check
								$values = is_array($data['value']) ? array_values($data['value']) : array();
								$condition = array(
									'condition' => $condition_key,
									'oper' => 'in',
									'values' => $values,
								);
								break;
							case 'N': // Number
								$oper = null;
								switch(@$data['oper']) {
									case '=':
										$oper = 'is';
										break;
									case '!=':
										$oper = '!is';
										break;
									case '<':
										$oper = 'lt';
										break;
									case '>':
										$oper = 'gt';
										break;
								}
								
								$condition = array(
									'condition' => $condition_key,
									'oper' => $oper,
									'value' => $data['value'],
								);
								break;
							case 'E': // Date
								@$from = $data['from'];
								@$to = $data['to'];
								
								$condition = array(
									'condition' => $condition_key,
									'oper' => 'is',
									'from' => $from,
									'to' => $to,
								);
								break;
							case 'W': // Worker
								$values = is_array($data['value']) ? array_values($data['value']) : array();
								$condition = array(
									'condition' => $condition_key,
									'oper' => 'in',
									'worker_id' => $values,
								);
								break;
							default:
								break;
						}

						if(!empty($condition))
							$conditions[] = $condition;
						
						break;
				}
				
			} // end criterion
			
			if(!empty($conditions)) {
				$parent_id = $group_filters_node_id;
				
				$extra_group = null;
				
				// Nest decision if multiple addresses
				if(isset($criterion['tocc'])) {
					$data = $criterion['tocc'];
					@$val = $data['value'];
					
					if(!empty($val)) {
						$vals = DevblocksPlatform::parseCsvString($val);
						$conds = array();
						
						foreach($vals as $email) {
							$email = trim($email, '*'); // strip leading or trailing wild
							
							$conds[] = array(
								'condition' => 'ticket_latest_message_header',
								'header' => 'to',
								'oper' => 'contains',
								'value' => $email,
							);
						}
						
						if(!empty($conds)) {
							$extra_group = array(
								'any' => 1,
								'conditions' => $conds,
							);
						}
					}
				} // end tocc nest check					
				
				$groups = array();
				
				if(!empty($extra_group))
					$groups[] = $extra_group;
				
				$groups[] = array(
					'any' => 0,
					'conditions' => $conditions,
				);
				
				// Outcome: Rule
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					$parent_id,
					$trigger_id,
					$db->qstr($result['name']),
					$db->qstr(json_encode(array(
						'groups' => $groups
					))),
					$db->qstr('outcome'),
					$pos++
				));
				$parent_id = $db->LastInsertId();
				
			} // finish condition nodes
			
			$do = array();
			$pos = 0;
			
			if(false !== ($actions = unserialize($result['actions_ser']))) {
				$action_labels = array();
				
				if(is_array($actions))
				foreach($actions as $key => $data) {
					$action = null;
					
					switch($key) {
						case 'move':
							@$move_group_id = $data['group_id'];
							@$move_bucket_id = $data['bucket_id'];
							
							// Intra-group move
							if($move_group_id == $group_id) {
								// Don't re-deliver to inbox
								if(empty($move_bucket_id))
									break;
									
								if(!isset($bucket_labels[$move_bucket_id]))
									break;

								$action_labels[] = 'move to ' . $bucket_labels[$move_bucket_id] . ' bucket';
									
								$action = array(
									'action' => 'move_to_bucket',
									'bucket_id' => $move_bucket_id,
								);
								
							// If other group_id, only drop to their inbox
							} else {
								if(!isset($group_labels[$move_group_id]))
									break;

								$action_labels[] = 'move to ' . $group_labels[$move_group_id] . ' group';
								
								$action = array(
									'action' => 'move_to_group',
									'group_id' => $move_group_id,
								);
								
							}
							
							if(!empty($action))
								$do[] = $action;
							break;
							
						case 'status':
							@$is_waiting = $data['is_waiting'];
							@$is_closed = $data['is_closed'];
							@$is_deleted = $data['is_deleted'];
							
							$status = 'open';
							
							if($is_deleted) {
								$status = 'deleted';
								$action_labels[] = 'delete';
							} elseif($is_closed) {
								$status = 'closed';
								$action_labels[] = 'close';
							} elseif($is_waiting) {
								$status = 'waiting';
								$action_labels[] = 'waiting';
							} else {
								$action_labels[] = 'open';
							}
							
							$action = array(
								'action' => 'set_status',
								'status' => $status,
							);
							
							$do[] = $action;
							break;
							
						case 'spam':
							@$is_spam = $data['is_spam'];
							
							$training = 'N';
							
							if(!empty($is_spam)) {
								$training = 'S';
								$action_labels[] = 'spam';
								
							} else {
								$action_labels[] = 'not spam';
							}
							
							$action = array(
								'action' => 'set_spam_training',
								'value' => $training,
							);
							
							$do[] = $action;
							break;
							
						case 'owner':
							@$add_workers = $data['add'];
							
							if(empty($add_workers) || !is_array($data['add']))
								break;
								
							$action = array(
								'action' => 'add_watchers',
								'worker_id' => $add_workers,
							);
							
							$do[] = $action;
							
							$action_labels[] = 'assign watchers';
							break;
							
						default:
							// Custom fields							
							if('cf_' != substr($key,0,3))
								break;
							
							$cfield_id = substr($key,3);

							if(!isset($custom_fields[$cfield_id]))
								break;
								
							$cfield = $custom_fields[$cfield_id];
							$action = array();
							
							switch($cfield['type']) {
								case 'C':
								case 'D':
								case 'E':
								case 'N':
								case 'S':
								case 'T':
								case 'U':
									@$value = $data['value'];
									if(empty($value))
										break;
									
									$action = array(
										'action' => 'set_cf_'.$cfield_id,
										'value' => $value, 
									);
									break;
								case 'W':
									@$value = $data['value'];
									if(empty($value))
										break;
									
									$action = array(
										'action' => 'set_cf_'.$cfield_id,
										'worker_id' => $value, 
									);
									break;
								case 'X':
									@$value = $data['value'];
									
									if(empty($value) || !is_array($value))
										break;
									
									foreach($value as $k => $v)
										$value[$k] = ltrim($v, '+-');
										
									$action = array(
										'action' => 'set_cf_'.$cfield_id,
										'values' => $value, 
									);
									break;
							}
								
							if(!empty($action)) {
								$do[] = $action;
								$action_labels[] = 'set ticket:' . $cfield['label'];
							}
							break;
					}
				}
				
			} // finish action nodes

			if(!empty($do)) {
				$label = 'Perform actions';
				
				if(!empty($action_labels))
					$label = ucfirst(implode(', ', $action_labels));
				
				// Actions: Perform these actions
				$db->Execute(sprintf("INSERT INTO decision_node (parent_id, trigger_id, title, params_json, node_type, pos) ".
					"VALUES (%d, %d, %s, %s, %s, %d)",
					$parent_id,
					$trigger_id,
					$db->qstr($label),
					$db->qstr(json_encode(
						array(
							'actions' => $do,
						)
					)),
					$db->qstr('action'),
					$pos++
				));
				$db->LastInsertId();
				
			}
			
		} // end outcome nodes per group
	}
}

// ===========================================================================
// Drop group inbox filters (replaced by Virtual Attendants)

if(isset($tables['group_inbox_filter'])) {
	$db->Execute('DROP TABLE IF EXISTS group_inbox_filter');
}

// ===========================================================================
// context_activity_log 

if(!isset($tables['context_activity_log'])) {
	$sql = sprintf("
	CREATE TABLE IF NOT EXISTS `context_activity_log` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		activity_point VARCHAR(128) NOT NULL DEFAULT '',
		actor_context VARCHAR(255) NOT NULL DEFAULT '',
		actor_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		target_context VARCHAR(255) NOT NULL DEFAULT '',
		target_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		created INT UNSIGNED NOT NULL DEFAULT 0,
		entry_json TEXT,
		PRIMARY KEY (id),
		INDEX activity_point (activity_point),
		INDEX actor (actor_context, actor_context_id),
		INDEX target (target_context, target_context_id),
		INDEX created (created)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['context_activity_log'] = 'context_activity_log';
}

return TRUE;
