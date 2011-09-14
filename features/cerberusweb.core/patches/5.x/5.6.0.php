<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add context owners to snippets

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['owner_context']) && !isset($columns['owner_context_id'])) {
	$db->Execute(sprintf(
		"ALTER TABLE snippet ".
		"ADD COLUMN owner_context VARCHAR(128) NOT NULL DEFAULT '', ".
		"ADD COLUMN owner_context_id INT NOT NULL DEFAULT 0, ".
		"ADD INDEX owner_compound (owner_context, owner_context_id), ".
		"ADD INDEX owner_context (owner_context) "
	));
}

if(isset($columns['created_by'])) {
	$db->Execute(sprintf("UPDATE snippet SET owner_context='cerberusweb.contexts.worker', owner_context_id=created_by WHERE created_by > 0"));
	$db->Execute("ALTER TABLE snippet DROP COLUMN created_by");
}

if(isset($columns['last_updated'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN last_updated");
}

if(isset($columns['last_updated_by'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN last_updated_by");
}
 
if(isset($columns['is_private'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN is_private");
} 

// ===========================================================================
// Worker roles refactor

if(!isset($tables['worker_role'])) {
 	$logger->error("The 'worker_role' table does not exist.");
 	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_role');

if(!isset($columns['params_json'])) {
	$db->Execute("ALTER TABLE worker_role ADD COLUMN params_json TEXT");
	
	// Map workers to roles
	$role_to_workers = array();
	$results = $db->GetArray("SELECT worker_id, role_id FROM worker_to_role");
	foreach($results as $row) {
		$role_id = $row['role_id'];
		$worker_id = $row['worker_id'];
		
		if(!isset($role_to_workers))
			$role_to_workers[$role_id] = array();
		
		$role_to_workers[$role_id][] = intval($worker_id);
	}
	
	$results = $db->GetArray("SELECT id FROM worker_role");

	foreach($results as $row) {
		$role_id = $row['id'];
		
		$who_list = isset($role_to_workers[$role_id]) ? $role_to_workers[$role_id] : array();
		
		$db->Execute(sprintf("UPDATE worker_role SET params_json = %s WHERE id = %d",
			$db->qstr(json_encode(array(
				'who' => 'workers',
				'who_list' => $who_list,
				'what' => 'itemized',
			))),
			$role_id
		));
	}
	
	unset($results);
	unset($role_to_workers);
	
	$db->Execute("DROP TABLE worker_to_role");
}

$acl_enabled = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'acl_enabled'");

if(!is_null($acl_enabled)) {
	// If ACL was disabled, add a default role for everyone that can do everything
	if(!$acl_enabled) {
		$db->Execute(sprintf("INSERT INTO worker_role (name,params_json) ".
			"VALUES ('Default',%s)",
			$db->qstr(json_encode(array(
				'who' => 'all',
				'what' => 'all',
			)))
		));
	}
	
	$db->Execute("DELETE FROM devblocks_setting WHERE setting = 'acl_enabled'");
}

// ===========================================================================
// Worker ACL refactor

if(!isset($tables['worker_role_acl'])) {
 	$logger->error("The 'worker_role_acl' table does not exist.");
 	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_role_acl');

if(isset($columns['has_priv'])) {
	$db->Execute("ALTER TABLE worker_role_acl DROP COLUMN has_priv");
}

// ===========================================================================
// Workspace ownership refactor

if(!isset($tables['workspace'])) {
 	$logger->error("The 'workspace' table does not exist.");
 	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace');

if(!isset($columns['owner_context'])) {
	$db->Execute("ALTER TABLE workspace ADD COLUMN owner_context VARCHAR(255) DEFAULT '' NOT NULL, ADD INDEX owner_context (owner_context)");
}

if(!isset($columns['owner_context_id'])) {
	$db->Execute("ALTER TABLE workspace ADD COLUMN owner_context_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX owner_context_id (owner_context_id)");
}

if(isset($columns['worker_id'])) {
	$db->Execute("UPDATE workspace SET owner_context = 'cerberusweb.contexts.worker', owner_context_id = worker_id WHERE owner_context = '' AND owner_context_id = 0");
	$db->Execute("ALTER TABLE workspace DROP COLUMN worker_id");
}

// ===========================================================================
// Workspace endpoint refactor

if(!isset($tables['workspace_to_endpoint'])) {
 	$logger->error("The 'workspace_to_endpoint' table does not exist.");
 	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_to_endpoint');

if(!isset($columns['worker_id'])) {
	$db->Execute("ALTER TABLE workspace_to_endpoint ADD COLUMN worker_id INT UNSIGNED DEFAULT 0 NOT NULL, ADD INDEX worker_id (worker_id)");
	$db->Execute("UPDATE workspace_to_endpoint INNER JOIN workspace ON (workspace_to_endpoint.workspace_id=workspace.id) SET workspace_to_endpoint.worker_id=workspace.owner_context_id WHERE workspace.owner_context = 'cerberusweb.contexts.worker' AND workspace_to_endpoint.worker_id=0");
	$db->Execute("DELETE FROM workspace_to_endpoint WHERE worker_id = 0");
}

// ===========================================================================
// Workspace list refactor

if(!isset($tables['workspace_list'])) {
 	$logger->error("The 'workspace_list' table does not exist.");
 	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_list');

if(isset($columns['worker_id'])) {
	$db->Execute("ALTER TABLE workspace_list DROP COLUMN worker_id");
}

return TRUE;