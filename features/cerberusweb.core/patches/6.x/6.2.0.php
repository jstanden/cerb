<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Refactor series datasources in workspace widgets

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

$sql = "SELECT id, extension_id, params_json FROM workspace_widget";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$changes = 0;
	
	$params = json_decode($row['params_json'], true);
	$params_orig = $params;
	
	switch($row['extension_id']) {
		
		// On charts, we want to default the datasource for each series
		//  to 'worklist' if there is data
		case 'core.workspace.widget.chart':
			if(isset($params['chart_type']) && $params['chart_type'] == 'scatterplot') {
				// Move scatterplots out of charts and into their own extension
				$db->Execute(sprintf("UPDATE workspace_widget SET extension_id = %s WHERE id = %d",
					$db->qstr('core.workspace.widget.scatterplot'),
					$row['id']
				));
				$row['extension_id'] = 'core.workspace.widget.scatterplot';
			}
			
			if(isset($params['datasource'])) {
				unset($params['datasource']);
				$changes++;
			}
			
			// Loop through each series
			if(isset($params['series']))
			foreach($params['series'] as $series_idx => $series) {
				// Only if the widget hasn't been converted yet
				if(!isset($series['datasource'])) {
					
					// If this wasn't a worklist (empty series) blank it out
					if(!isset($series['view_context']) || empty($series['view_context'])) {
						//unset($params['series'][$series_idx]);
						
						$params['series'][$series_idx]['datasource'] = '';
						unset($params['series'][$series_idx]['view_context']);
						unset($params['series'][$series_idx]['view_id']);
						unset($params['series'][$series_idx]['view_model']);
						unset($params['series'][$series_idx]['xaxis_field']);
						unset($params['series'][$series_idx]['xaxis_tick']);
						unset($params['series'][$series_idx]['yaxis_func']);
						
					// Otherwise set it to a worklist
					} else if (isset($series['view_context']) && !empty($series['view_context'])) {
						$params['series'][$series_idx]['datasource'] = 'core.workspace.widget.datasource.worklist';
					}
					
					$changes++;
				}
			}
			break;
			
		// On counters, we want to migrate the built-in datasources to extensions
		case 'core.workspace.widget.counter':
			switch(@$params['datasource']) {
				// Remove pointless params
				case '':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'sensor':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'worklist':
					if(isset($params['counter_func']))
						$params['metric_func'] = $params['counter_func'];
					
					if(isset($params['counter_field']))
						$params['metric_field'] = $params['counter_field'];
					
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					$changes++;
					break;
					
				case 'url':
					unset($params['counter_func']);
					unset($params['counter_field']);
					unset($params['metric_value']);
					unset($params['sensor_id']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
				
			}
			
			switch(@$params['datasource']) {
				case '':
					$params['datasource'] = 'core.workspace.widget.datasource.manual';
					$changes++;
					break;
					
				case 'worklist':
					$params['datasource'] = 'core.workspace.widget.datasource.worklist';
					$changes++;
					break;
					
				case 'sensor':
					$params['datasource'] = 'cerberusweb.datacenter.sensor.widget.datasource';
					$changes++;
					break;
					
				case 'url':
					$params['datasource'] = 'core.workspace.widget.datasource.url';
					$changes++;
					break;
			}
			break;
			
		// On gauges, we want to migrate the built-in datasources to extensions
		case 'core.workspace.widget.gauge':
			switch(@$params['datasource']) {
				// Remove pointless params
				case '':
					unset($params['counter_func']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'sensor':
					unset($params['counter_func']);
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
					
				case 'worklist':
					if(isset($params['needle_format']))
						$params['metric_type'] = $params['needle_format'];
					
					if(isset($params['needle_func']))
						$params['metric_func'] = $params['needle_func'];
					
					if(isset($params['needle_field']))
						$params['metric_field'] = $params['needle_field'];
					
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['url']);
					unset($params['url_cache_mins']);
					$changes++;
					break;
					
				case 'url':
					unset($params['counter_func']);
					unset($params['metric_value']);
					unset($params['needle_format']);
					unset($params['needle_func']);
					unset($params['needle_field']);
					unset($params['sensor_id']);
					unset($params['view_context']);
					unset($params['view_model']);
					$changes++;
					break;
				
			}
			
			switch(@$params['datasource']) {
				case '':
					$params['datasource'] = 'core.workspace.widget.datasource.manual';
					$changes++;
					break;
					
				case 'worklist':
					$params['datasource'] = 'core.workspace.widget.datasource.worklist';
					$changes++;
					break;
					
				case 'sensor':
					$params['datasource'] = 'cerberusweb.datacenter.sensor.widget.datasource';
					$changes++;
					break;
					
				case 'url':
					$params['datasource'] = 'core.workspace.widget.datasource.url';
					$changes++;
					break;
			}
			break;
	}
	
	if($changes) {
		$db->Execute(sprintf("UPDATE workspace_widget SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode($params)),
			$row['id']
		));
	}
}

// ===========================================================================
// Add auth_extension_id to worker records

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['auth_extension_id'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN auth_extension_id VARCHAR(255) NOT NULL DEFAULT ''");
	$db->Execute("UPDATE worker SET auth_extension_id='login.password'");
}

// ===========================================================================
// Update VA 'set_*_links' actions

$sql = "SELECT trigger_event.event_point, decision_node.id, decision_node.params_json ".
	"FROM decision_node ".
	"INNER JOIN trigger_event ON (trigger_event.id=decision_node.trigger_id) ".
	"WHERE decision_node.node_type = 'action' ".
	"AND decision_node.params_json LIKE '%links%'"
	;
$results = $db->GetArray($sql);

foreach($results as $result) {
	$original_json = $result['params_json'];
	
	if(false === ($params = @json_decode($original_json, true)))
		continue;
	
	if(!isset($result['event_point']))
		continue;

	if(!isset($params['actions']))
		continue;
	
	switch($result['event_point']) {
		
		case 'event.macro.address':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_email_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'email_id';
						break;
					case 'set_email_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'email_org_id';
						break;
				}
			}
			break;
			
		case 'event.macro.call':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_call_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'call_id';
						break;
				}
			}
			break;
			
		case 'event.macro.crm.opportunity':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_opp_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'opp_id';
						break;
					case 'set_opp_email_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'opp_email_id';
						break;
					case 'set_opp_email_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'opp_email_org_id';
						break;
				}
			}
			break;
			
		case 'event.macro.domain':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_domain_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'domain_id';
						break;
					case 'set_domain_server_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'domain_server_id';
						break;
				}
			}
			break;
			
		case 'event.macro.feeditem':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_item_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'item_id';
						break;
				}
			}
			break;
			
		case 'event.macro.kb_article':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_article_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'article_id';
						break;
				}
			}
			break;
			
		case 'event.macro.message':
		case 'event.mail.after.sent.group':
		case 'event.mail.received.group':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_sender_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'sender_id';
						break;
					case 'set_sender_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'sender_org_id';
						break;
					case 'set_ticket_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_id';
						break;
				}
			}
			break;
			
		case 'event.macro.org':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'org_id';
						break;
				}
			}
			break;
			
		case 'event.macro.sensor':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_sensor_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'sensor_id';
						break;
				}
			}
			break;
			
		case 'event.macro.server':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_server_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'server_id';
						break;
				}
			}
			break;
			
		case 'event.macro.task':
		case 'event.task.created.worker':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_task_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'task_id';
						break;
				}
			}
			break;
			
		case 'event.macro.ticket':
		case 'event.mail.assigned.group':
		case 'event.mail.closed.group':
		case 'event.mail.moved.group':
		case 'event.ticket.viewed.worker':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_initial_sender_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_initial_message_sender_id';
						break;
					case 'set_initial_sender_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_initial_message_sender_org_id';
						break;
					case 'set_latest_sender_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_latest_message_sender_id';
						break;
					case 'set_latest_sender_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_latest_message_sender_org_id';
						break;
					case 'set_ticket_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'ticket_id';
						break;
				}
			}
			break;
		
		case 'event.macro.timetracking':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_time_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'time_id';
						break;
				}
			}
			break;
			
		case 'event.macro.worker':
			foreach($params['actions'] as $idx => $action) {
				switch($action['action']) {
					case 'set_email_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'worker_address_id';
						break;
					case 'set_email_org_links':
						$params['actions'][$idx]['action'] = 'set_links';
						$params['actions'][$idx]['on'] = 'worker_address_org_id';
						break;
				}
			}
			break;
			
	}
	
	$json = json_encode($params);
	
	if($json != $original_json) {
		$sql = sprintf("UPDATE decision_node SET params_json=%s WHERE id=%d",
			$db->qstr($json),
			$result['id']
		);
		$db->Execute($sql);
	}
}

unset($results);

// ===========================================================================
// Fix redundant '{actor} tracked 5 mins mins' strings in time tracking entries

$db->Execute("UPDATE context_activity_log set entry_json=replace(entry_json,'mins mins','mins') where (actor_context='cerberusweb.contexts.timetracking' OR target_context='cerberusweb.contexts.timetracking')");

// ===========================================================================
// Finish

return TRUE;