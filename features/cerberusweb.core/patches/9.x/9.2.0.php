<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `package_library`

if(!isset($tables['package_library'])) {
	$sql = sprintf("
		CREATE TABLE `package_library` (
		`id` INT unsigned NOT NULL AUTO_INCREMENT,
		`uri` VARCHAR(255) NOT NULL DEFAULT '',
		`name` VARCHAR(255) NOT NULL DEFAULT '',
		`description` VARCHAR(255) NOT NULL DEFAULT '',
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
				break;
				
			case 'groups':
				// Use group names for readability
				$who_ids = array_flip($who_list);
				$in_groups = array_intersect_key($group_names, $who_ids);
				
				if(!empty($in_groups))
					$member_query = sprintf('group:(name:[%s])', implode(',', array_map(function($name) {
						return '"' . str_replace('"','', $name) . '"';
					}, $in_groups)));
				break;
		}
		
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
// Finish up

return TRUE;
