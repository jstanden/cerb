<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Add `package_library`

if(!isset($tables['package_library'])) {
	$sql = sprintf("
		CREATE TABLE `package_library` (
		`id` INT unsigned NOT NULL AUTO_INCREMENT,
		`uri` VARCHAR(255) NOT NULL DEFAULT '',
		`name` VARCHAR(255) NOT NULL DEFAULT '',
		`description` VARCHAR(255) NOT NULL DEFAULT '',
		`instructions` TEXT,
		`point` VARCHAR(255) NOT NULL DEFAULT '',
		`updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
		`package_json` MEDIUMTEXT,
		PRIMARY KEY (id),
		UNIQUE `uri` (`uri`),
		KEY `point` (`point`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['package_library'] = 'package_library';
	
	// ===========================================================================
	// Load packages into library
	
	$packages = [
		'cerb_bot_behavior_action__execute_http_request.json',
		'cerb_bot_behavior_action__exit.json',
		'cerb_bot_behavior_action__records_search.json',
		'cerb_bot_behavior_action__set_placeholder.json',
		'cerb_bot_behavior_action_interaction_close_chat.json',
		'cerb_bot_behavior_action_interaction_prompt.json',
		'cerb_bot_behavior_action_interaction_respond.json',
		'cerb_bot_behavior_action_interaction_start_convo.json',
		'cerb_bot_behavior_action_ui_execute_jquery_script.json',
		'cerb_bot_behavior_action_webhook_set_http_body.json',
		'cerb_bot_behavior_action_webhook_set_http_header.json',
		'cerb_bot_behavior_action_webhook_set_http_status.json',
		'cerb_bot_behavior_auto_reply.json',
		'cerb_bot_behavior_interaction_worker.json',
		'cerb_bot_behavior_loop__break.json',
		'cerb_bot_behavior_loop__records.json',
		'cerb_bot_behavior_switch__cases.json',
		'cerb_bot_behavior_switch__yes_no.json',
		'cerb_calendar_us_holidays.json',
		'cerb_calendar_work_schedule.json',
		'cerb_connected_service_aws.json',
		'cerb_connected_service_dropbox.json',
		'cerb_connected_service_facebook.json',
		'cerb_connected_service_freshbooks_classic.json',
		'cerb_connected_service_github.json',
		'cerb_connected_service_gitlab.json',
		'cerb_connected_service_google.json',
		'cerb_connected_service_linkedin.json',
		'cerb_connected_service_nest.json',
		'cerb_connected_service_salesforce.json',
		'cerb_connected_service_slack.json',
		'cerb_connected_service_smartsheet.json',
		'cerb_connected_service_stripe.json',
		'cerb_connected_service_twilio.json',
		'cerb_connected_service_twitter.json',
		'cerb_profile_tab__log.json',
		'cerb_profile_tab_package_overview.json',
		'cerb_profile_tab_ticket_overview.json',
		'cerb_profile_widget_ticket_owner.json',
		'cerb_profile_widget_ticket_participants.json',
		'cerb_project_board_kanban.json',
		'cerb_task_for_me.json',
		'cerb_workspace_page_empty.json',
		'cerb_workspace_page_home.json',
		'cerb_workspace_page_mail.json',
		'cerb_workspace_page_reports.json',
		'cerb_workspace_tab_dashboard.json',
		'cerb_workspace_tab_dashboard_with_filters.json',
		'cerb_workspace_tab_world_clocks.json',
		'cerb_workspace_widget_chart_categories.json',
		'cerb_workspace_widget_chart_table.json',
		'cerb_workspace_widget_chart_time_series.json',
		'cerb_workspace_widget_clock.json',
		'cerb_workspace_widget_counter.json',
		'cerb_workspace_widget_donut.json',
		'cerb_workspace_widget_map_usa.json',
		'cerb_workspace_widget_map_world.json',
		'cerb_workspace_widget_worklist.json',
	];
	
	CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');
}

// ===========================================================================
// Update package library

if($revision >= 1331 && $revision < 1332) {
	$packages = [
		'cerb_bot_behavior_action__exit.json',
		'cerb_bot_behavior_action_ui_execute_jquery_script.json',
		'cerb_bot_behavior_action_webhook_set_http_body.json',
		'cerb_bot_behavior_action_webhook_set_http_header.json',
		'cerb_bot_behavior_action_webhook_set_http_status.json',
		'cerb_connected_service_smartsheet.json',
		'cerb_workspace_page_empty.json',
		'cerb_workspace_page_home.json',
		'cerb_workspace_page_mail.json',
		'cerb_workspace_page_reports.json',
	];
	
	CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');
}

// ===========================================================================
// Add `worker_to_role`

if(!isset($tables['worker_to_role'])) {
	$sql = sprintf("
		CREATE TABLE `worker_to_role` (
		`worker_id` INT unsigned NOT NULL DEFAULT 0,
		`role_id` INT unsigned NOT NULL DEFAULT 0,
		`is_member` TINYINT(1) NOT NULL DEFAULT 0,
		`is_editable` TINYINT(1) NOT NULL DEFAULT 0,
		`is_readable` TINYINT(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (worker_id, role_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['worker_to_role'] = 'worker_to_role';
}

// ===========================================================================
// Add queries to roles

if(!isset($tables['worker_role']))
	return FALSE;

list($columns,) = $db->metaTable('worker_role');

if(!array_key_exists('privs_mode', $columns)) {
	$db->ExecuteMaster("ALTER TABLE worker_role ADD COLUMN privs_mode VARCHAR(16) DEFAULT ''");
}

if(!array_key_exists('member_query_worker', $columns)) {
	$db->ExecuteMaster("ALTER TABLE worker_role ADD COLUMN member_query_worker TEXT");
}

if(!array_key_exists('editor_query_worker', $columns)) {
	$db->ExecuteMaster("ALTER TABLE worker_role ADD COLUMN editor_query_worker TEXT");
	$db->ExecuteMaster(sprintf("UPDATE worker_role SET editor_query_worker = %s", $db->qstr('isAdmin:y isDisabled:n')));
}

if(!array_key_exists('reader_query_worker', $columns)) {
	$db->ExecuteMaster("ALTER TABLE worker_role ADD COLUMN reader_query_worker TEXT");
}

if(array_key_exists('params_json', $columns)) {
	$group_names = array_column($db->GetArrayMaster("SELECT id, name FROM worker_group"), 'name', 'id');
	$worker_names = array_column($db->GetArrayMaster("SELECT id, at_mention_name FROM worker WHERE at_mention_name != ''"), 'at_mention_name', 'id');
	
	$roles = $db->GetArrayMaster("SELECT id, params_json FROM worker_role");
	
	if(is_array($roles))
	foreach($roles as $role) {
		if(false == ($params = json_decode($role['params_json'], true)))
			continue;
		
		// What
		
		$privs_mode = '';
		
		if(array_key_exists('what', $params) && in_array($params['what'], ['all','itemized']))
			$privs_mode = $params['what'];
		
		// Who
		
		$member_query = '';
		
		@$who_list = $params['who_list'];
		
		if(!is_array($who_list))
			$who_list = [];
		
		switch(@$params['who']) {
			case 'all':
				$member_query = 'isDisabled:n';
				
				$db->ExecuteMaster(sprintf("INSERT IGNORE INTO worker_to_role (worker_id, role_id, is_member, is_editable, is_readable) ".
					"SELECT id, %d, 1, 0, 1 FROM worker WHERE is_disabled = 0",
					$role['id']
				));
				break;
				
			case 'workers':
				// Use @mention names when available?
				$who_ids = array_flip($who_list);
				$worker_mentions = array_intersect_key($worker_names, $who_ids);
				$worker_ids = array_keys(array_diff_key($who_ids, $worker_mentions));
				
				if(!empty($worker_mentions))
					$member_query = sprintf('mention:[%s]', implode(',', array_map(function($name) {
						return str_replace('"','', $name);
					}, $worker_mentions)));
					
				if(!empty($worker_ids)) {
					if($member_query)
						$member_query .= ' OR ';
					
					$member_query .= 'id:' . json_encode($worker_ids);
				}
				
				$db->ExecuteMaster(sprintf("INSERT IGNORE INTO worker_to_role (worker_id, role_id, is_member, is_editable, is_readable) ".
					"SELECT id, %d, 1, 0, 1 FROM worker WHERE id IN (%s)",
					$role['id'],
					implode(',', $who_list)
				));
				break;
				
			case 'groups':
				// Use group names for readability
				$who_ids = array_flip($who_list);
				$in_groups = array_intersect_key($group_names, $who_ids);
				
				if(!empty($in_groups))
					$member_query = sprintf('group:(name:[%s])', implode(',', array_map(function($name) {
						return '"' . str_replace('"','', $name) . '"';
					}, $in_groups)));
				
				// Member/read by group members
				$db->ExecuteMaster(sprintf("INSERT IGNORE INTO worker_to_role (worker_id, role_id, is_member, is_editable, is_readable) ".
					"SELECT id, %d, 1, 0, 1 FROM worker WHERE id IN (SELECT DISTINCT wtg.worker_id FROM worker_to_group wtg WHERE wtg.group_id IN (%s))",
					$role['id'],
					implode(',', $who_list)
				));
				break;
		}
		
		// Editable by all admins
		$db->ExecuteMaster("UPDATE worker_to_role SET is_editable = 1 WHERE worker_id IN (SELECT id FROM worker WHERE is_superuser = 1 AND is_disabled = 0)");
		
		$db->ExecuteMaster(sprintf("UPDATE worker_role SET privs_mode = %s, member_query_worker = %s, reader_query_worker = %s WHERE id = %d",
			$db->qstr($privs_mode),
			$db->qstr($member_query),
			$db->qstr($member_query),
			$role['id']
		));
	}
	
	// Update role cards
	$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
		$db->qstr('cerberusweb.core'),
		$db->qstr('card:cerberusweb.contexts.role'),
		$db->qstr(json_encode(['privs_mode','updated_at']))
	));
	
	// Replace default search buttons on role cards
	$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
		$db->qstr('cerberusweb.core'),
		$db->qstr('card:search:cerberusweb.contexts.role'),
		$db->qstr('[{"context":"cerberusweb.contexts.worker","label_singular":"Member","label_plural":"Members","query":"{{member_query_worker}}"},{"context":"cerberusweb.contexts.worker","label_singular":"Editor","label_plural":"Editors","query":"{{editor_query_worker}}"}]')
	));

	// Drop old column
	$db->ExecuteMaster('ALTER TABLE worker_role DROP COLUMN params_json');
}

// ===========================================================================
// Convert `comment.comment` to utf8mb4

if(!isset($tables['comment']))
	return FALSE;

list($columns,) = $db->metaTable('comment');

if(!array_key_exists('comment', $columns))
	return FALSE;

if('utf8_general_ci' == $columns['comment']['collation']) {
	$db->ExecuteMaster("ALTER TABLE comment MODIFY COLUMN comment MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE comment");
	$db->ExecuteMaster("OPTIMIZE TABLE comment");
}

// ===========================================================================
// Convert `ticket.subject` to utf8mb4

if(!isset($tables['ticket']))
	return FALSE;

list($columns,) = $db->metaTable('ticket');

if('utf8_general_ci' == $columns['subject']['collation']) {
	$db->ExecuteMaster("ALTER TABLE ticket MODIFY COLUMN subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE ticket");
	$db->ExecuteMaster("OPTIMIZE TABLE ticket");
}

// ===========================================================================
// Convert `worker` table `location` and `title` to utf8mb4

if(!isset($tables['worker']))
	return FALSE;

list($columns,) = $db->metaTable('worker');

$changes = [];

if('utf8_general_ci' == $columns['location']['collation']) {
	$changes[] = "ALTER TABLE worker MODIFY COLUMN location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
}

if('utf8_general_ci' == $columns['title']['collation']) {
	$changes[] = "ALTER TABLE worker MODIFY COLUMN title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
}

if($changes) {
	foreach($changes as $sql) {
		$db->ExecuteMaster($sql);
	}
	
	$db->ExecuteMaster("REPAIR TABLE worker");
	$db->ExecuteMaster("OPTIMIZE TABLE worker");
}

// ===========================================================================
// Add `address.created_at`

if(!isset($tables['address']))
	return FALSE;

list($columns,) = $db->metaTable('address');

$changes = [];

if(!array_key_exists('created_at', $columns)) {
	$changes[] = "ALTER TABLE address ADD COLUMN created_at INT UNSIGNED NOT NULL DEFAULT 0";
	$changes[] = "UPDATE address SET created_at = updated WHERE created_at = 0";
}

if($changes) {
	foreach($changes as $sql) {
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `uri` to trigger_event

if(!isset($tables['trigger_event']))
	return FALSE;

list($columns,) = $db->metaTable('trigger_event');

if(!array_key_exists('uri', $columns)) {
	$db->ExecuteMaster("ALTER TABLE trigger_event ADD COLUMN uri VARCHAR(255) DEFAULT ''");
}

// ===========================================================================
// Finish up

return TRUE;
