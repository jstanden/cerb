<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// attachment_link 

if(!isset($tables['attachment_link'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS attachment_link (
			guid VARCHAR(64) NOT NULL DEFAULT '',
			attachment_id INT UNSIGNED NOT NULL,
			context VARCHAR(128) DEFAULT '' NOT NULL,
			context_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (attachment_id, context, context_id),
			INDEX guid (guid),
			INDEX attachment_id (attachment_id),
			INDEX context (context),
			INDEX context_id (context_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['attachment_link'] = 'attachment_link';
}

// ===========================================================================
// attachment

if(!isset($tables['attachment']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('attachment');

// Add updated timestamp
if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX updated (updated)");
	$db->Execute("UPDATE attachment LEFT JOIN message ON (attachment.message_id=message.id) SET attachment.updated=message.created_date WHERE attachment.updated = 0");
}

// Migrate attachment -> attachment_link (message) 
if(isset($columns['message_id'])) { // ~2.56s
	$sql = "INSERT IGNORE INTO attachment_link (attachment_id, context, context_id, guid) ".
		"SELECT id AS attachment_id, 'cerberusweb.contexts.message' AS context, message_id AS context_id, UUID() as guid ".
		"FROM attachment ";
	$db->Execute($sql);
	
	$db->Execute("ALTER TABLE attachment DROP COLUMN message_id");
}

// ===========================================================================
// view_filters_preset

if(!isset($tables['view_filters_preset']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('view_filters_preset');

if(!isset($columns['sort_json'])) {
	$db->Execute("ALTER TABLE view_filters_preset ADD COLUMN sort_json TEXT");
}

// ===========================================================================
// workspaces

// Add the workspace table
if(!isset($tables['workspace'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS workspace (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX worker_id (worker_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['workspace'] = 'workspace';
}

// Add the workspace_to_endpoint table
if(!isset($tables['workspace_to_endpoint'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS workspace_to_endpoint (
			workspace_id INT UNSIGNED NOT NULL DEFAULT 0,
			endpoint VARCHAR(128) NOT NULL DEFAULT '',
			pos TINYINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (workspace_id, endpoint),
			INDEX workspace_id (workspace_id),
			INDEX endpoint (endpoint)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['workspace_to_endpoint'] = 'workspace_to_endpoint';
}

// Rename worker_workspace_list -> workspace_list
if(!isset($tables['workspace_list']) && isset($tables['worker_workspace_list'])) {
	$db->Execute("ALTER TABLE worker_workspace_list RENAME workspace_list");
	unset($tables['worker_workspace_list']);
	$tables['workspace_list'] = 'workspace_list';
	
	$db->Execute("UPDATE workspace_list SET list_view = REPLACE(list_view, \"O:29:\\\"Model_WorkerWorkspaceListView\\\"\", \"O:23:\\\"Model_WorkspaceListView\\\"\")");
}

list($columns, $indexes) = $db->metaTable('workspace_list');

// Migrate workspace -> workspace_id
if(!isset($columns['workspace_id']) && isset($columns['workspace'])) {
	$db->Execute("ALTER TABLE workspace_list ADD COLUMN workspace_id INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX workspace_id (workspace_id)");
	
	// Create workspaces and migrate worklist links
	$sql = "SELECT workspace, worker_id FROM workspace_list GROUP BY workspace, worker_id";
	$rows = $db->GetArray($sql);
	
	foreach($rows as $row) {
		$db->Execute(sprintf("INSERT INTO workspace (name, worker_id) VALUES (%s, %d)", 
			$db->qstr($row['workspace']),
			$row['worker_id']
		));
		$id = $db->LastInsertId();
		
		$db->Execute(sprintf("UPDATE workspace_list SET workspace_id = %d WHERE workspace = %s AND worker_id = %d",
			$id,
			$db->qstr($row['workspace']),
			$row['worker_id']
		));
	}
	unset($rows);
	
	$db->Execute("ALTER TABLE workspace_list DROP COLUMN workspace");
}

// Migrate source_extension -> context
if(!isset($columns['context']) && isset($columns['source_extension'])) {
	$db->Execute("ALTER TABLE workspace_list ADD COLUMN context VARCHAR(255) NOT NULL DEFAULT '', ADD INDEX context (context)");

	$map = array(
		'core.workspace.source.address' => 'cerberusweb.contexts.address',
		'core.workspace.source.notifications' => 'cerberusweb.contexts.notification',
		'core.workspace.source.org' => 'cerberusweb.contexts.org',
		'core.workspace.source.task' => 'cerberusweb.contexts.task',
		'core.workspace.source.ticket' => 'cerberusweb.contexts.ticket',
		'core.workspace.source.worker' => 'cerberusweb.contexts.worker',
		'calls.workspace.source.call' => 'cerberusweb.contexts.call',
		'crm.workspace.source.opportunity' => 'cerberusweb.contexts.opportunity',
		'feedback.workspace.source.feedback_entry' => 'cerberusweb.contexts.feedback',
		'timetracking.workspace.source.time_entry' => 'cerberusweb.contexts.timetracking',
	);
	
	foreach($map as $source_ext => $context) {
		$db->Execute(sprintf("UPDATE workspace_list SET context = %s WHERE source_extension = %s",
			$db->qstr($context),
			$db->qstr($source_ext)
		));
	}
	
	$db->Execute("DELETE FROM workspace_list WHERE context = ''");
	$db->Execute("ALTER TABLE workspace_list DROP COLUMN source_extension");
}

// ===========================================================================
// Maintenance

$db->Execute("DELETE FROM worker_pref WHERE setting LIKE 'team_move_counts%'");

return TRUE;
