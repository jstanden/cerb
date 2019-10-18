<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
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
	
	$id = DAO_ConnectedAccount::create([
		DAO_ConnectedAccount::NAME => 'JIRA',
		DAO_ConnectedAccount::EXTENSION_ID => 'wgm.jira.service.provider',
		DAO_ConnectedAccount::OWNER_CONTEXT => 'cerberusweb.contexts.app',
		DAO_ConnectedAccount::OWNER_CONTEXT_ID => 0,
	]);
	
	DAO_ConnectedAccount::setAndEncryptParams($id, $params);
	
	$settings->set('wgm.jira', 'sync_account_id', $id);
}

return TRUE;