<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
$encrypt = DevblocksPlatform::services()->encryption();
$tables = $db->metaTables();

// ===========================================================================
// Encrypt oauth credentials

$consumer_key = $settings->get('wgm.twitter', 'consumer_key', null);
$consumer_secret = $settings->get('wgm.twitter', 'consumer_secret', null);

if(!is_null($consumer_key) || !is_null($consumer_secret)) {
	$credentials = [
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
	];
	
	$settings->set('wgm.twitter', 'credentials', $credentials, true, true);
	$settings->delete('wgm.twitter', ['consumer_key','consumer_secret','users']);
}

// ===========================================================================
// Modify `twitter_message` to add 'connected_account_id'

if(!isset($tables['twitter_message'])) {
	$logger->error("The 'twitter_message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('twitter_message');

if(!isset($columns['connected_account_id'])) {
	$db->ExecuteMaster("ALTER TABLE twitter_message ADD COLUMN connected_account_id int unsigned not null default 0 AFTER id, ADD INDEX (connected_account_id)");
}

// ===========================================================================
// Convert `twitter_account` rows to connected accounts

if(isset($tables['twitter_account'])) {
	$rows = $db->GetArrayMaster("SELECT id, twitter_id, screen_name, oauth_token, oauth_token_secret, last_synced_at, last_synced_msgid FROM twitter_account");
	
	if(is_array($rows))
	foreach($rows as $row) {
		$params = [
			'oauth_token' => $row['oauth_token'],
			'oauth_token_secret' => $row['oauth_token_secret'],
			'user_id' => $row['twitter_id'],
			'screen_name' => $row['screen_name'],
			'x_auth_expires' => 0,
		];
		
		$db->ExecuteMaster(sprintf("INSERT INTO connected_account (name, extension_id, owner_context, owner_context_id, params_json) ".
			"VALUES (%s, %s, %s, %d, %s)",
			$db->qstr('Twitter @' . $row['screen_name']),
			$db->qstr('wgm.twitter.service.provider'),
			$db->qstr('cerberusweb.contexts.app'),
			0,
			$db->qstr($encrypt->encrypt(json_encode($params))),
		));
		
		$id = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("UPDATE twitter_message SET connected_account_id = %d WHERE account_id = %d",
			$id,
			$row['id']
		));
	}
	
	$db->ExecuteMaster("DROP TABLE twitter_account");
	$db->ExecuteMaster("ALTER TABLE twitter_message DROP COLUMN account_id");
	unset($tables['twitter_account']);
}

return TRUE;