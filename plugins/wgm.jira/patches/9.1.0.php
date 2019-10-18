<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Denormalize JIRA issue status, type, and version

if(!isset($tables['jira_project'])) {
	$logger->error("The 'jira_project' table does not exist.");
	return FALSE;
}

if(!isset($tables['jira_issue'])) {
	$logger->error("The 'jira_issue' table does not exist.");
	return FALSE;
}

list($project_columns,) = $db->metaTable('jira_project');
list($issue_columns,) = $db->metaTable('jira_issue');

if(isset($project_columns['issuetypes_json'])) {
	if(!isset($issue_columns['type'])) {
		$db->ExecuteMaster("ALTER TABLE jira_issue ADD COLUMN type VARCHAR(255) DEFAULT ''");
		
		$jira_projects = $db->GetArrayMaster("SELECT id, issuetypes_json FROM jira_project");
		
		foreach($jira_projects as $jira_project) {
			if(false == ($issuetypes = json_decode($jira_project['issuetypes_json'], true)))
				continue;
			
			foreach($issuetypes as $issuetype) {
				$db->ExecuteMaster(sprintf("UPDATE jira_issue SET type = %s WHERE project_id = %d AND jira_type_id = %s",
					$db->qstr($issuetype['name']),
					$jira_project['id'],
					$db->qstr($issuetype['id'])
				));
			}
		}
		
		$db->ExecuteMaster("ALTER TABLE jira_issue DROP COLUMN jira_type_id");
	}
	
	$db->ExecuteMaster("ALTER TABLE jira_project DROP COLUMN issuetypes_json");
	
	$db->ExecuteMaster("UPDATE worker_view_model set columns_json=replace(columns_json,'j_jira_type_id','type')");
}

if(isset($project_columns['statuses_json'])) {
	if(!isset($issue_columns['status'])) {
		$db->ExecuteMaster("ALTER TABLE jira_issue ADD COLUMN status VARCHAR(255) DEFAULT ''");
		
		$jira_projects = $db->GetArrayMaster("SELECT id, statuses_json FROM jira_project");
		
		foreach($jira_projects as $jira_project) {
			if(false == ($statuses = json_decode($jira_project['statuses_json'], true)))
				continue;
			
			foreach($statuses as $status) {
				$db->ExecuteMaster(sprintf("UPDATE jira_issue SET status = %s WHERE project_id = %d AND jira_status_id = %s",
					$db->qstr($status['name']),
					$jira_project['id'],
					$db->qstr($status['id'])
				));
			}
		}
		
		$db->ExecuteMaster("ALTER TABLE jira_issue DROP COLUMN jira_status_id");
	}
	
	$db->ExecuteMaster("ALTER TABLE jira_project DROP COLUMN statuses_json");
	
	$db->ExecuteMaster("UPDATE worker_view_model set columns_json=replace(columns_json,'j_jira_status_id','status')");
}

if(isset($project_columns['versions_json'])) {
	$db->ExecuteMaster("ALTER TABLE jira_project DROP COLUMN versions_json");
}

// ===========================================================================
// Finish

return TRUE;