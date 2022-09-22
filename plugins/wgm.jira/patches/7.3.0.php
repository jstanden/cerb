<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
$encrypt = DevblocksPlatform::services()->encryption();
$tables = $db->metaTables();

// ===========================================================================
// Convert JIRA credentials to connected accounts

$base_url = $settings->get('wgm.jira', 'base_url', null);
$user = $settings->get('wgm.jira', 'jira_user', null);
$password = $settings->get('wgm.jira', 'jira_password', null);

if(!is_null($base_url) || !is_null($user) || !is_null($password)) {
	$settings->delete('wgm.jira', ['base_url','jira_user', 'jira_password']);
	
	$params = [
		'base_url' => $base_url,
		'jira_user' => $user,
		'jira_password' => $password,
	];
	
	$db->ExecuteMaster(sprintf("INSERT INTO connected_account (name, extension_id, owner_context, owner_context_id, params_json) ".
		"VALUES (%s, %s, %s, %d, %s)",
		$db->qstr('JIRA'),
		$db->qstr('wgm.jira.service.provider'),
		$db->qstr('cerberusweb.contexts.app'),
		0,
		$db->qstr($encrypt->encrypt(json_encode($params))),
	));
	
	$id = $db->LastInsertId();
	
	$settings->set('wgm.jira', 'sync_account_id', $id);
}

return TRUE;