<?php
$db = DevblocksPlatform::services()->database();
$encrypt = DevblocksPlatform::services()->encryption();

$tables = $db->metaTables();

// ===========================================================================
// Test SPATIAL indexes

$db->ExecuteMaster("DROP TABLE IF EXISTS _installer_test_suite");

if(false == $db->ExecuteMaster(sprintf("CREATE TABLE _installer_test_suite (id int, pos POINT NOT NULL, PRIMARY KEY(id), SPATIAL INDEX(pos)) ENGINE=%s", APP_DB_ENGINE))) {
	echo "ERROR: Your MySQL version does not support SPATIAL indexes. Cerb requires MySQL 5.6 (MariaDB 10.2.2) or newer. ";
	return false;
}

if(false == $db->ExecuteMaster("DROP TABLE IF EXISTS _installer_test_suite")) {
	echo "ERROR: Failed to delete the `_installer_test_suite` table. "; 
	return false;
}

// ===========================================================================
// Alter `custom_field`

if(!isset($tables['custom_field']))
	return FALSE;

list($columns,) = $db->metaTable('custom_field');

if($columns['type'] && in_array(strtolower($columns['type']['type']), ['varchar(1)','char(1)'])) {
	$db->ExecuteMaster("ALTER TABLE custom_field MODIFY COLUMN type VARCHAR(255)");
}

// ===========================================================================
// Add `custom_field_geovalue`

if(!isset($tables['custom_field_geovalue'])) {
	$sql = sprintf("
		CREATE TABLE `custom_field_geovalue` (
		`field_id` int(10) unsigned NOT NULL DEFAULT '0',
		`context_id` int(10) unsigned NOT NULL DEFAULT '0',
		`field_value` POINT NOT NULL,
		`context` varchar(255) NOT NULL DEFAULT '',
		KEY `field_id` (`field_id`),
		KEY `context_and_id` (`context`,`context_id`),
		SPATIAL INDEX (field_value)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['custom_field_geovalue'] = 'custom_field_geovalue';
}

// ===========================================================================
// Add `oauth_app`

if(!isset($tables['oauth_app'])) {
	$sql = sprintf("
	CREATE TABLE `oauth_app` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		url varchar(255) NOT NULL DEFAULT '',
		client_id varchar(255) NOT NULL DEFAULT '',
		client_secret varchar(255) NOT NULL DEFAULT '',
		callback_url varchar(255) NOT NULL DEFAULT '',
		scopes text,
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		INDEX (client_id)
	) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['oauth_app'] = 'oauth_app';
	
} else {
	list($columns,) = $db->metaTable('oauth_app');
	
	if(!isset($columns['scopes'])) {
		$db->ExecuteMaster("ALTER TABLE oauth_app ADD COLUMN scopes TEXT");
	}
}

// ===========================================================================
// Default profile for OAuth apps

if(!$db->GetOneMaster(sprintf("SELECT COUNT(*) FROM profile_tab WHERE context = %s",
	$db->qstr('cerberusweb.contexts.oauth.app')))) {

	$sqls = <<< EOD
# OAuth app
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.oauth.app','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('OAuth App',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.oauth.app\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"url\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[],\"label_singular\":[],\"label_plural\":[],\"query\":[]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.oauth.app',CONCAT('[',@last_tab_id,']'));
EOD;

	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Add `oauth_token`

if(!isset($tables['oauth_token'])) {
	$sql = sprintf("
	CREATE TABLE `oauth_token` (
		token_type varchar(32) NOT NULL DEFAULT '',
		token varchar(255) NOT NULL DEFAULT '',
		app_id int(10) unsigned NOT NULL DEFAULT '0',
		worker_id int(10) unsigned NOT NULL DEFAULT '0',
		created_at int(10) unsigned NOT NULL DEFAULT '0',
		expires_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (token_type, token),
		INDEX (app_id)
	) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['oauth_token'] = 'oauth_token';
}

// ===========================================================================
// Add `connected_service` table

if(!isset($tables['connected_service'])) {
	$sql = sprintf("
	CREATE TABLE `connected_service` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		uri varchar(255) not null default '',
		extension_id varchar(255) not null default '',
		params_json text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (extension_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['connected_service'] = 'connected_service';
}

// ===========================================================================
// Add `service_id` to `connected_account`

list($columns,) = $db->metaTable('connected_account');

if(!isset($columns['service_id'])) {
	$sql = "ALTER TABLE connected_account ADD COLUMN service_id int unsigned not null default 0";
	$db->ExecuteMaster($sql);
}

if(array_key_exists('extension_id', $columns)) {
	function cerb_910_migrate_connected_service($service_name, $from_extension_id, $service_params, $service_extension_id='cerb.service.provider.oauth2') {
		$db = DevblocksPlatform::services()->database();
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr($service_extension_id),
			$db->qstr($encrypt->encrypt(json_encode($service_params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		$sql = sprintf("UPDATE connected_account SET extension_id = '', service_id = %d WHERE extension_id = %s",
			$service_id,
			$db->qstr($from_extension_id)
		);
		$db->ExecuteMaster($sql);
		
		return $service_id;
	}
	
	// ===========================================================================
	// Migrate AWS accounts to service provider
	
	if(false != ($accounts = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.aws.service.provider'))))) {
		$service_name = 'Amazon Web Services (AWS)';
		$extension_id = 'cerb.service.provider.aws';
		$params = [];
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr($extension_id),
			$db->qstr($encrypt->encrypt(json_encode($params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		foreach($accounts as $account) {
			$sql = sprintf("UPDATE connected_account SET extension_id = '', service_id = %d, updated_at = %d WHERE id = %d",
				$service_id,
				time(),
				$account['id']
			);
			$db->ExecuteMaster($sql);
		}
	}
	
	// ===========================================================================
	// Migrate Cerb accounts to service provider
	
	if(false != $db->GetOneMaster(sprintf("SELECT COUNT(*) FROM connected_account WHERE extension_id = %s", $db->qstr('core.service.provider.cerb')))) {
		$service_name = 'Cerb (Legacy API)';
		$extension_id = 'cerb.service.provider.cerb.api.legacy';
		$params = [];
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr($extension_id),
			$db->qstr($encrypt->encrypt(json_encode($params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		$sql = sprintf("UPDATE connected_account SET extension_id = '', service_id = %d WHERE extension_id = %s",
			$service_id,
			$db->qstr('core.service.provider.cerb')
		);
		$db->ExecuteMaster($sql);
	}
	
	// ===========================================================================
	// Migrate Clickatell plugin to bearer token
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.clickatell'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'token_name' => 'Bearer',
		];
		
		cerb_910_migrate_connected_service('Clickatell', 'wgm.clickatell', $params, 'cerb.service.provider.token.bearer');
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.clickatell'");
		
		// Migrate 'Execute API Request to JIRA' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmclickatell.event.action.send_sms%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmclickatell.event.action.send_sms' == $action['action']) {
						$placeholder_action = [
							'action' => '_set_custom_var',
							'value' => $action['content'],
							'format' => '',
							'is_simulator_only' => '0',
							'var' => '_clickatell_message',
						];
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => 'post',
							'http_url' => 'https://api.clickatell.com/rest/message',
							'http_headers' => "Content-Type: application/json\r\nX-Version: 1\r\nAccept: application/json\r\n",
							'http_body' => "{% set json = {\r\n\ttext: _clickatell_message,\r\n\tto: [\"" . $action['phone'] . "\"]\r\n}%}\r\n{{json|json_encode|json_pretty}}",
							'auth' => 'connected_account',
							'auth_connected_account_id' => 0,
							'run_in_simulator' => 0,
							'response_placeholder' => '_clickatell_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $placeholder_action, $http_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate LDAP accounts to service provider
	
	if(false != ($accounts = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.ldap.service.provider'))))) {
		$account_to_service_id = [];
		
		foreach($accounts as $account) {
			$service_name = 'LDAP';
			$extension_id = 'cerb.service.provider.ldap';
			
			$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
				"VALUES (%s, %s, %s, %d)",
				$db->qstr(sprintf("LDAP (%s)", $account['name'])),
				$db->qstr($extension_id),
				$db->qstr($account['params_json']),
				time()
			);
			
			if(false === $db->ExecuteMaster($sql))
				die("Failed to create an LDAP service for " . $account['name']);
			
			$service_id = $db->LastInsertId();
			
			$account_to_service_id[$account['id']] = $service_id;
			
			$sql = sprintf("DELETE FROM connected_account WHERE id = %d",
				$account['id']
			);
			$db->ExecuteMaster($sql);
		}
		
		// LDAP worker settings
		if(false != ($prefs_json = $db->GetOneMaster("SELECT value FROM devblocks_setting WHERE plugin_id = 'wgm.ldap' and setting = 'config_json'"))) {
			$prefs = json_decode($prefs_json, true);
			
			if(array_key_exists('connected_account_id', $prefs)) {
				$prefs['connected_service_id'] = @$account_to_service_id[$prefs['connected_account_id']] ?: 0;
				unset($prefs['connected_account_id']);
			}
			
			if(false != ($connected_service_id = $prefs['connected_service_id'])) {
				$service_params_json = $db->GetOneMaster(sprintf("SELECT params_json FROM connected_service WHERE id = %d", $connected_service_id));
				
				if(false != ($service_params = json_decode($encrypt->decrypt($service_params_json), true))) {
					$service_params['context_search'] = $prefs['context_search'] ?? null;
					$service_params['field_email'] = $prefs['field_email'] ?? null;
					$service_params['field_firstname'] = $prefs['field_firstname'] ?? null;
					$service_params['field_lastname'] = $prefs['field_lastname'] ?? null;
					
					$sql = sprintf("UPDATE connected_service SET params_json = %s WHERE id = %d",
						$db->qstr($encrypt->encrypt(json_encode($service_params))),
						$connected_service_id
					);
					$db->ExecuteMaster($sql);
				}
				
				// [TODO] Append to SSO options if any workers used that auth_extension_id
			}
			
			$sql = "DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.ldap' and setting = 'config_json'";
			$db->ExecuteMaster($sql);
		}
		
		// LDAP portal settings
		if(false != ($portals = $db->GetArrayMaster("SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'wgm.ldap.config_json'"))) {
			$portal_ldap_setup_to_id = [];
			
			foreach($portals as $portal) {
				$prefs = json_decode($portal['property_value'], true);
				
				if(array_key_exists('connected_account_id', $prefs)) {
					$prefs['connected_service_id'] = @$account_to_service_id[$prefs['connected_account_id']] ?: 0;
					unset($prefs['connected_account_id']);
				}
				
				if(false != ($connected_service_id = $prefs['connected_service_id'])) {
					$service_params_json = $db->GetOneMaster(sprintf("SELECT params_json FROM connected_service WHERE id = %d", $connected_service_id));
					
					if(false != ($service_params = json_decode($encrypt->decrypt($service_params_json), true))) {
						$service_params['context_search'] = $prefs['context_search'] ?? null;
						$service_params['field_email'] = $prefs['field_email'] ?? null;
						$service_params['field_firstname'] = $prefs['field_firstname'] ?? null;
						$service_params['field_lastname'] = $prefs['field_lastname'] ?? null;
						
						ksort($service_params);
						
						$service_hash = sha1(json_encode($service_params));
						
						if(array_key_exists($service_hash, $portal_ldap_setup_to_id)) {
							$connected_service_id = $portal_ldap_setup_to_id[$service_hash];
							
						} else {
							// Dupe it
							$sql = sprintf("INSERT INTO connected_service (name, uri, extension_id, params_json, updated_at) ".
								"SELECT name, %s, extension_id, %s, %d ".
								"FROM connected_service ".
								"WHERE id = %d",
								$db->qstr('ldap-' . $portal['tool_code']),
								$db->qstr($encrypt->encrypt(json_encode($service_params))),
								time(),
								$connected_service_id
							);
							$db->ExecuteMaster($sql);
							
							$connected_service_id = $db->LastInsertId();
							$portal_ldap_setup_to_id[$service_hash] = $connected_service_id;
						}
					}
				}
				
				$sql = sprintf("INSERT IGNORE INTO community_tool_property (tool_code, property_key, property_value) VALUES (%s, %s, %d)",
					$db->qstr($portal['tool_code']),
					$db->qstr('sso.ldap.service_id'),
					$connected_service_id
				);
				$db->ExecuteMaster($sql);
				
				$sql = sprintf("DELETE FROM community_tool_property WHERE tool_code = %s AND property_key = 'wgm.ldap.config_json'",
					$db->qstr($portal['tool_code'])
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate Dropbox plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.dropbox'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['client_id'],
			'client_secret' => $credentials['client_secret'],
			'authorization_url' => 'https://www.dropbox.com/oauth2/authorize',
			'access_token_url' => 'https://api.dropboxapi.com/oauth2/token',
			'scope' => '',
			'approval_prompt' => '',
		];
		
		cerb_910_migrate_connected_service('Dropbox', 'wgm.dropbox.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.dropbox'");
	}
	
	// ===========================================================================
	// Migrate Facebook plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.facebook'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['client_id'],
			'client_secret' => $credentials['client_secret'],
			'authorization_url' => 'https://graph.facebook.com/oauth/authorize',
			'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
			'scope' => 'public_profile,read_page_mailboxes,manage_pages,publish_pages',
			'approval_prompt' => 'auto',
		];
		
		// Facebook Accounts
		
		cerb_910_migrate_connected_service('Facebook', 'wgm.facebook.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.facebook'");
	}
	
	// ===========================================================================
	// Migrate Facebook Pages to services
	
	if(false != ($pages = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.facebook.pages.service.provider'))))) {
		$service_name = 'Facebook Pages';
		$params = [];
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr('wgm.facebook.pages.service.provider'),
			$db->qstr($encrypt->encrypt(json_encode($params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		foreach($pages as $page) {
			@$params = json_decode($encrypt->decrypt($page['params_json']), true) ?: [];
			
			$new_params = [
				'connected_account_id' => 0,
				'page' => $params,
			];
			
			$sql = sprintf("UPDATE connected_account SET params_json = %s, service_id = %d, extension_id = '' WHERE id = %d",
				$db->qstr($encrypt->encrypt(json_encode($new_params))),
				$service_id,
				$page['id']
			);
			$db->ExecuteMaster($sql);
		}
		
		// Migrate 'Post to Facebook Page' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmfacebook.event.action.post%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmfacebook.event.action.post' == $action['action']) {
						$account = $db->GetRowMaster(sprintf("SELECT id, params_json FROM connected_account WHERE id = %d", $action['connected_account_id']));
						$account_params = json_decode($encrypt->decrypt($account['params_json']), true);
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => 'post',
							'http_url' => sprintf('https://graph.facebook.com/%s/feed',
								@$account_params['page']['id'] ?: 'page-id'
							),
							'http_headers' => '',
							'http_body' => "{% set message %}\r\n" . $action['content'] . "\r\n{% endset %}\r\nmessage={{message|url_encode}}",
							'auth' => 'connected_account',
							'auth_connected_account_id' => $action['connected_account_id'],
							'run_in_simulator' => 0,
							'response_placeholder' => '_facebook_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $http_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate Freshbooks plugin to abstract OAuth1
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s and setting = 'credentials'", $db->qstr('wgm.freshbooks'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'request_token_url' => 'https://webgroupmedia.freshbooks.com/oauth/oauth_request.php',
			'authentication_url' => 'https://webgroupmedia.freshbooks.com/oauth/oauth_authorize.php',
			'access_token_url' => 'https://webgroupmedia.freshbooks.com/oauth/oauth_access.php',
			'signature_method' => 'PLAINTEXT',
		];
		
		cerb_910_migrate_connected_service('Freshbooks Classic', 'wgm.freshbooks.service.provider', $params, 'cerb.service.provider.oauth1');
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.freshbooks'");
		
		if(isset($tables['wgm_freshbooks_client'])) {
			$sql = sprintf("INSERT INTO custom_fieldset (name, context, owner_context, owner_context_id, updated_at) ".
				"VALUES (%s, %s, %s, %d, %d)",
				$db->qstr('Freshbooks'),
				$db->qstr('cerberusweb.contexts.org'),
				$db->qstr('cerberusweb.contexts.app'),
				0,
				time()
			);
			$db->ExecuteMaster($sql);
			
			$custom_fieldset_id = $db->LastInsertId();
			
			$sql = sprintf("INSERT INTO custom_field (name, context, type, pos, params_json, custom_fieldset_id, updated_at) ".
				"VALUES (%s, %s, %s, %d, %s, %d, %d)",
				$db->qstr('Client ID'),
				$db->qstr('cerberusweb.contexts.org'),
				$db->qstr('N'),
				0,
				$db->qstr('[]'),
				$custom_fieldset_id,
				time()
			);
			$db->ExecuteMaster($sql);
			
			$custom_field_id = $db->LastInsertId();
			
			// Migrate
			$sql = sprintf("INSERT INTO custom_field_numbervalue (field_id, context, context_id, field_value) ".
				"SELECT %d, %s, org_id, id FROM wgm_freshbooks_client WHERE org_id != 0",
				$custom_field_id,
				$db->qstr('cerberusweb.contexts.org')
			);
			$db->ExecuteMaster($sql);
			
			$sql = sprintf("INSERT IGNORE INTO context_to_custom_fieldset (context, context_id, custom_fieldset_id) ".
				"SELECT context, context_id, %d FROM custom_field_numbervalue WHERE field_id = %d",
				$custom_fieldset_id,
				$custom_field_id
			);
			$db->ExecuteMaster($sql);
			
			// Drop tables
			$db->ExecuteMaster('DROP TABLE wgm_freshbooks_client');
			$db->ExecuteMaster('DROP TABLE freshbooks_invoice');
			
			unset($tables['wgm_freshbooks_client']);
			unset($tables['freshbooks_invoice']);
		}
	}
	
	// ===========================================================================
	// Migrate GitHub plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.github'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://github.com/login/oauth/authorize',
			'access_token_url' => 'https://github.com/login/oauth/access_token',
			'scope' => 'user public_repo notifications',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('GitHub', 'wgm.github.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.github'");
	}
	
	// ===========================================================================
	// Migrate GitLab plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.gitlab'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$base_url = rtrim($credentials['base_url'], '/');
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => $base_url . '/oauth/authorize',
			'access_token_url' => $base_url . '/oauth/token',
			'scope' => 'api read_user',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('GitLab', 'wgm.gitlab.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.gitlab'");
	}
	
	// ===========================================================================
	// Migrate Google plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.google'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth?access_type=offline',
			'access_token_url' => 'https://www.googleapis.com/oauth2/v4/token',
			'scope' => 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('Google', 'wgm.google.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.google'");
	}
	
	// ===========================================================================
	// Migrate HipChat plugin to bearer token
	
	if(false != ($results = $db->GetArrayMaster(sprintf("SELECT setting, value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.hipchat'))))) {
		$params = [
			'token_name' => 'Bearer',
		];
		
		$service_id = cerb_910_migrate_connected_service('HipChat', 'wgm.hipchat', $params, 'cerb.service.provider.token.bearer');
		
		// Create connected account
		
		$plugin_settings = [];
		
		foreach($results as $row) {
			$plugin_settings[$row['setting']] = $row['value'];
		}
		
		$account_params = [
			'token' => $plugin_settings['api_token'],
		];
		
		$sql = sprintf("INSERT INTO connected_account (name, owner_context, owner_context_id, params_json, created_at, updated_at, service_id) ".
			"VALUES (%s, %s, %d, %s, %d, %d, %d)",
			$db->qstr('HipChat'),
			$db->qstr('cerberusweb.contexts.app'),
			0,
			$db->qstr($encrypt->encrypt(json_encode($account_params))),
			time(),
			time(),
			$service_id
		);
		$db->ExecuteMaster($sql);
		
		// Wipe old settings
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.hipchat'");
		
		// Migrate 'Post to HipChat' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmhipchat.event.action.post%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmhipchat.event.action.post' == $action['action']) {
						$placeholder_room = [
							'action' => '_set_custom_var',
							'value' => (@$action['room'] ?: $plugin_settings['api_room']),
							'format' => '',
							'is_simulator_only' => '0',
							'var' => '_hipchat_room',
						];
						
						$placeholder_from = [
							'action' => '_set_custom_var',
							'value' => @$action['from'] ?: 'Cerb',
							'format' => '',
							'is_simulator_only' => '0',
							'var' => '_hipchat_from',
						];
						
						$placeholder_message = [
							'action' => '_set_custom_var',
							'value' => @$action['content'],
							'format' => '',
							'is_simulator_only' => '0',
							'var' => '_hipchat_message',
						];
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => 'post',
							'http_url' => 'https://api.hipchat.com/v1/rooms/message?auth_token=' . $account_params['token'],
							'http_headers' => "Accept: application/json\r\n",
							'http_body' => "{% set params = {\r\n\tmessage: _hipchat_message,\r\n\tmessage_format: \"" . (@$action['is_html'] ? 'html' : 'text') . "\",\r\n\tnotify: true,\r\n\tfrom: _hipchat_from,\r\n\troom_id: _hipchat_room,\r\n\tcolor: \"" . $action['color'] . "\",\r\n}%}\r\n{{params|url_encode}}",
							'auth' => '',
							'auth_connected_account_id' => 0,
							'run_in_simulator' => @$action['run_in_simulator'] ? 1 : 0,
							'response_placeholder' => '_hipchat_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $placeholder_room, $placeholder_from, $placeholder_message, $http_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate JIRA accounts to service provider
	
	$jira_default_account_id = 0;
	
	if(false != ($accounts = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.jira.service.provider'))))) {
		$base_url_to_service_id = [];
		
		foreach($accounts as $account) {
			if(!$jira_default_account_id)
				$jira_default_account_id = $account['id'];
			
			$params = json_decode($encrypt->decrypt($account['params_json']), true);
			
			if(!array_key_exists($params['base_url'], $base_url_to_service_id)) {
				@$host = parse_url($params['base_url'], PHP_URL_HOST) ?: '';
				
				$service_name = sprintf('JIRA (%s)', $host);
				$extension_id = 'cerb.service.provider.http.basic';
				$service_params = [
					'base_url' => $params['base_url'],
				];
				
				$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
					"VALUES (%s, %s, %s, %d)",
					$db->qstr($service_name),
					$db->qstr($extension_id),
					$db->qstr($encrypt->encrypt(json_encode($service_params))),
					time()
				);
				
				if(false === $db->ExecuteMaster($sql))
					die("Failed to create a connected service for " . $service_name);
				
				$service_id = $db->LastInsertId();
				$base_url_to_service_id[$params['base_url']] = $service_id;
				
			} else {
				$service_id = $base_url_to_service_id[$params['base_url']];
			}
			
			$params['username'] = @$params['jira_user'] ?: '';
			$params['password'] = @$params['jira_password'] ?: '';
			unset($params['base_url']);
			unset($params['jira_user']);
			unset($params['jira_password']);
			
			$sql = sprintf("UPDATE connected_account SET extension_id = '', params_json = %s, service_id = %d, updated_at = %d WHERE id = %d",
				$db->qstr($encrypt->encrypt(json_encode($params))),
				$service_id,
				time(),
				$account['id']
			);
			$db->ExecuteMaster($sql);
		}
		
		// Migrate 'Execute API Request to JIRA' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmjira.event.action.api_call%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmjira.event.action.api_call' == $action['action']) {
						@$connected_account_id = $action['connected_account_id'] ?: $jira_default_account_id;
						
						$account = $db->GetRowMaster(sprintf("SELECT id, service_id, params_json FROM connected_account WHERE id = %d", $connected_account_id));
						$account_params = json_decode($encrypt->decrypt($account['params_json']), true);
						
						$service = $db->GetRowMaster(sprintf("SELECT params_json FROM connected_service WHERE id = %d", $account['service_id']));
						$service_params = json_decode($encrypt->decrypt($service['params_json']), true);
						
						$http_verb =  DevblocksPlatform::strLower($action['api_verb']);
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => $http_verb,
							'http_url' => sprintf('%s%s',
								rtrim($service_params['base_url'], '/'),
								$action['api_path']
							),
							'http_headers' => "Content-Type: application/json\r\n",
							'auth' => 'connected_account',
							'auth_connected_account_id' => $connected_account_id,
							'run_in_simulator' => @$action['run_in_simulator'] ? 1 : 0,
							'response_placeholder' => '_jira_http_response',
						];
						
						if(in_array($http_verb, ['post', 'put']))
							$http_action['http_body'] = $action['json'];
						
						$placeholder_action = [
							'action' => '_set_custom_var',
							'value' => '{{_jira_http_response.body|json_encode|json_pretty}}',
							'format' => 'json',
							'is_simulator_only' => '0',
							'var' => @$action['response_placeholder'] ?: '_jira_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $http_action, $placeholder_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate LinkedIn plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.linkedin'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://www.linkedin.com/oauth/v2/authorization',
			'access_token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
			'scope' => 'r_basicprofile r_emailaddress rw_company_admin w_share',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('LinkedIn', 'wgm.linkedin.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.linkedin'");
	}
	
	// ===========================================================================
	// Migrate Nest plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.nest'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['product_id'],
			'client_secret' => $credentials['product_secret'],
			'authorization_url' => 'https://home.nest.com/login/oauth2',
			'access_token_url' => 'https://api.home.nest.com/oauth2/access_token',
			'scope' => '',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('Nest', 'wgm.nest.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.nest'");
	}
	
	// ===========================================================================
	// Migrate Salesforce plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.salesforce'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://login.salesforce.com/services/oauth2/authorize',
			'access_token_url' => 'https://login.salesforce.com/services/oauth2/token',
			'scope' => 'api refresh_token',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('Salesforce', 'wgm.salesforce.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.salesforce'");
	}
	
	// ===========================================================================
	// Drop Shiftplanning plugin
	
	if(false != $db->GetRowMaster("SELECT COUNT(setting) FROM devblocks_setting WHERE plugin_id = 'wgm.shiftplanning'")) {
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.shiftplanning'");
	}
	
	// ===========================================================================
	// Migrate Slack plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.slack'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://slack.com/oauth/authorize',
			'access_token_url' => 'https://slack.com/api/oauth.access',
			'scope' => 'channels:read chat:write:bot chat:write:user im:read im:write users:read users.profile:read',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('Slack', 'wgm.slack.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.slack'");
	}
	
	// ===========================================================================
	// Migrate Spotify plugin to abstract OAuth2
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s", $db->qstr('wgm.spotify'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
		
		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'authorization_url' => 'https://accounts.spotify.com/authorize',
			'access_token_url' => 'https://accounts.spotify.com/api/token',
			'scope' => '',
			'approval_prompt' => 'auto',
		];
		
		cerb_910_migrate_connected_service('Spotify', 'wgm.spotify.service.provider', $params);
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.spotify'");
	}
	
	// ===========================================================================
	// Migrate Stripe accounts to service provider
	
	if(false != ($accounts = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.stripe.service.provider'))))) {
		$service_name = 'Stripe';
		$extension_id = 'cerb.service.provider.http.basic';
		$params = [
			'base_url' => 'https://api.stripe.com/',
		];
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr($extension_id),
			$db->qstr($encrypt->encrypt(json_encode($params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		foreach($accounts as $account) {
			$params = json_decode($encrypt->decrypt($account['params_json']), true);
			
			$params['username'] = @$params['secret_key'] ?: '';
			$params['password'] = '';
			unset($params['secret_key']);
			
			$sql = sprintf("UPDATE connected_account SET extension_id = '', params_json = %s, service_id = %d, updated_at = %d WHERE id = %d",
				$db->qstr($encrypt->encrypt(json_encode($params))),
				$service_id,
				time(),
				$account['id']
			);
			$db->ExecuteMaster($sql);
		}
	}
	
	// ===========================================================================
	// Migrate Twilio accounts to service provider
	
	if(false != ($accounts = $db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM connected_account WHERE extension_id = %s", $db->qstr('wgm.twilio.service.provider'))))) {
		$service_name = 'Twilio';
		$extension_id = 'cerb.service.provider.http.basic';
		$params = [
			'base_url' => 'https://api.twilio.com/',
		];
		
		$sql = sprintf("INSERT INTO connected_service (name, extension_id, params_json, updated_at) ".
			"VALUES (%s, %s, %s, %d)",
			$db->qstr($service_name),
			$db->qstr($extension_id),
			$db->qstr($encrypt->encrypt(json_encode($params))),
			time()
		);
		
		if(false === $db->ExecuteMaster($sql))
			die("Failed to create a connected service for " . $service_name);
		
		$service_id = $db->LastInsertId();
		
		foreach($accounts as $account) {
			$params = json_decode($encrypt->decrypt($account['params_json']), true);
			
			$params['username'] = @$params['api_sid'] ?: '';
			$params['password'] = @$params['api_token'] ?: '';
			unset($params['api_sid']);
			unset($params['api_token']);
			
			$sql = sprintf("UPDATE connected_account SET extension_id = '', params_json = %s, service_id = %d, updated_at = %d WHERE id = %d",
				$db->qstr($encrypt->encrypt(json_encode($params))),
				$service_id,
				time(),
				$account['id']
			);
			$db->ExecuteMaster($sql);
		}
		
		// Migrate 'Send SMS via Twilio' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmtwilio.event.action.send_sms%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmtwilio.event.action.send_sms' == $action['action']) {
						$account_params_json = $db->GetOneMaster(sprintf("SELECT params_json FROM connected_account WHERE id = %d", $action['connected_account_id']));
						$account_params = json_decode($encrypt->decrypt($account_params_json), true);
						
						$placeholder_action = [
							'action' => '_set_custom_var',
							'value' => $action['content'],
							'format' => '',
							'is_simulator_only' => '0',
							'var' => 'twilio_message',
						];
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => 'post',
							'http_url' => sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/SMS/Messages.json',
								$account_params['username']
							),
							'http_headers' => "Content-Type: application/x-www-form-urlencoded\r\n",
							'http_body' => sprintf("{%% set params = {\r\n\t\"From\": \"%s\",\r\n\t\"To\": \"%s\",\r\n\t\"Body\": twilio_message,\r\n} %%}\r\n{{params|url_encode}}",
								@$action['from'] ?: $account_params['default_caller_id'],
								$action['phone']
							),
							'auth' => 'connected_account',
							'auth_connected_account_id' => $action['connected_account_id'],
							'run_in_simulator' => '0',
							'response_placeholder' => '_twilio_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $placeholder_action, $http_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	
	// ===========================================================================
	// Migrate Twitter plugin to abstract OAuth1
	
	if(false != ($credentials_encrypted = $db->GetOneMaster(sprintf("SELECT value FROM devblocks_setting WHERE plugin_id = %s and setting = 'credentials'", $db->qstr('wgm.twitter'))))) {
		$credentials = json_decode($encrypt->decrypt($credentials_encrypted), true);
	
		$params = [
			'client_id' => $credentials['consumer_key'],
			'client_secret' => $credentials['consumer_secret'],
			'request_token_url' => 'https://api.twitter.com/oauth/request_token',
			'authentication_url' => 'https://api.twitter.com/oauth/authenticate',
			'access_token_url' => 'https://api.twitter.com/oauth/access_token',
			'signature_method' => 'HMAC-SHA1',
		];
		
		cerb_910_migrate_connected_service('Twitter', 'wgm.twitter.service.provider', $params, 'cerb.service.provider.oauth1');
		
		$db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'wgm.twitter' AND setting = 'credentials'");
		
		// Migrate 'Post to Twitter' actions
		if(false != ($nodes = $db->GetArrayMaster('SELECT id, params_json FROM decision_node WHERE params_json LIKE "%wgmtwitter.event.action.post%"'))) {
			foreach($nodes as $node) {
				$params = json_decode($node['params_json'], true);
				
				if(array_key_exists('actions', $params))
				foreach($params['actions'] as $action_idx => $action) {
					if('wgmtwitter.event.action.post' == $action['action']) {
						$placeholder_action = [
							'action' => '_set_custom_var',
							'value' => $action['content'],
							'format' => '',
							'is_simulator_only' => '0',
							'var' => 'tweet',
						];
						
						$http_action = [
							'action' => 'core.va.action.http_request',
							'http_verb' => 'post',
							'http_url' => 'https://api.twitter.com/1.1/statuses/update.json',
							'http_headers' => "",
							'http_body' => "status={{tweet|url_encode}}",
							'auth' => 'connected_account',
							'auth_connected_account_id' => @$action['connected_account_id'],
							'run_in_simulator' => '0',
							'response_placeholder' => '_twitter_response',
						];
						
						array_splice($params['actions'], $action_idx, 1, [ $placeholder_action, $http_action ]);
					}
				}
				
				$sql = sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
					$db->qstr(json_encode($params)),
					$node['id']
				);
				$db->ExecuteMaster($sql);
			}
		}
	}
	$db->ExecuteMaster("ALTER TABLE connected_account DROP COLUMN extension_id");
}

// ===========================================================================
// Insert default search buttons for connected services

if(!$db->GetOneMaster(sprintf('SELECT value FROM devblocks_setting WHERE plugin_id = %s AND setting = %s',
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.connected_service')
	))) {
	
	$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
		$db->qstr('cerberusweb.core'),
		$db->qstr('card:search:cerberusweb.contexts.connected_service'),
		$db->qstr('[{"context":"cerberusweb.contexts.connected_account","label_singular":"Account","label_plural":"Accounts","query":"service.id:{{id}}"}]')
	));
}

// ===========================================================================
// Default profile for connected services

if(!$db->GetOneMaster(sprintf("SELECT COUNT(*) FROM profile_tab WHERE context = %s",
	$db->qstr('cerberusweb.contexts.connected_service')))) {

	$sqls = <<< EOD
# Connected services
INSERT INTO profile_tab (name, context, extension_id, extension_params_json, updated_at) VALUES ('Overview','cerberusweb.contexts.connected_service','cerb.profile.tab.dashboard','{\"layout\":\"sidebar_left\"}',UNIX_TIMESTAMP());
SET @last_tab_id = LAST_INSERT_ID();
INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, zone, pos, width_units, updated_at) VALUES ('Service',@last_tab_id,'cerb.profile.tab.widget.fields','{\"context\":\"cerberusweb.contexts.connected_service\",\"context_id\":\"{{record_id}}\",\"properties\":[[\"name\",\"extension_id\",\"updated\"]],\"links\":{\"show\":\"1\"},\"search\":{\"context\":[\"cerberusweb.contexts.connected_account\"],\"label_singular\":[\"Account\"],\"label_plural\":[\"Accounts\"],\"query\":[\"service.id:{{record_id}}\"]}}','sidebar',1,4,UNIX_TIMESTAMP());
INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','profile:tabs:cerberusweb.contexts.connected_service',CONCAT('[',@last_tab_id,']'));
EOD;

	foreach(DevblocksPlatform::parseCrlfString($sqls) as $sql) {
		$sql = str_replace(['\r','\n','\t'],['\\\r','\\\n','\\\t'],$sql);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Remove 'worker.auth_extension_id'

if(!isset($tables['worker']))
	return FALSE;

list($columns,) = $db->metaTable('worker');

if(!isset($columns['is_mfa_required'])) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN is_mfa_required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($columns['is_password_disabled'])) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN is_password_disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
}

if(isset($columns['auth_extension_id'])) {
	// ===========================================================================
	// Migrate `login.openid` authenticators
	
	if(array_key_exists('openid_to_worker', $tables)) {
		$sql = sprintf("UPDATE worker SET is_password_disabled=1 WHERE auth_extension_id=%s",
			$db->qstr('login.openid')
		);
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster("DROP TABLE openid_to_worker");
		unset($tables['openid_to_worker']);
	}
	
	// ===========================================================================
	// Migrate `login.password.google_auth` authenticators
	
	$sql = sprintf("UPDATE worker_pref SET setting = %s WHERE setting = %s",
		$db->qstr('mfa.totp.seed'),
		$db->qstr('login.password.google_auth.seed')
	);
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mfa.totp.seed' AND value = ''");
	
	$sql = "UPDATE worker SET is_mfa_required=1 WHERE id IN (SELECT worker_id FROM worker_pref WHERE setting = 'mfa.totp.seed')";
	$db->ExecuteMaster($sql);
	
	// ===========================================================================
	// Migrate `login.ldap` authenticators
	
	$sql = sprintf("UPDATE worker SET is_password_disabled=1 WHERE auth_extension_id=%s",
		$db->qstr('login.ldap')
	);
	$db->ExecuteMaster($sql);
	
	// ===========================================================================
	// Drop column
	
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN auth_extension_id");
}

// ===========================================================================
// Migrate asset records to custom records

if(isset($tables['asset'])) {
	$sql = sprintf("INSERT INTO custom_record (name, name_plural, uri, params_json, updated_at) " .
		"VALUES (%s, %s, %s, %s, %d)",
		$db->qstr('Asset'),
		$db->qstr('Assets'),
		$db->qstr('asset'),
		$db->qstr('[]'),
		time()
	);
	$db->ExecuteMaster($sql);
	
	$custom_record_id = $db->LastInsertId();
	
	$sql = sprintf("
		CREATE TABLE `custom_record_%d` (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			owner_context VARCHAR(255) DEFAULT '',
			owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			created_at INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			primary key (id),
			index (created_at),
			index (updated_at),
			index owner (owner_context, owner_context_id)
		) ENGINE=%s",
		$custom_record_id,
		APP_DB_ENGINE
	);
	$db->ExecuteMaster($sql);
	
	// Move records over
	$sql = sprintf("INSERT INTO custom_record_%d (name, created_at, updated_at) SELECT name, updated_at AS created_at, updated_at FROM asset",
		$custom_record_id
	);
	$db->ExecuteMaster($sql);
	
	// Migrate contexts
	$db->ExecuteMaster(sprintf("UPDATE attachment_link SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE comment SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_activity_log SET target_context = 'contexts.custom_record.%d' WHERE target_context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_alias SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_avatar SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_bulk_update SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_link SET from_context = 'contexts.custom_record.%d' WHERE from_context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_link SET to_context = 'contexts.custom_record.%d' WHERE to_context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_merge_history SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_saved_search SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_scheduled_behavior SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_to_skill SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE context_to_custom_fieldset SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE custom_field SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE custom_field_clobvalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE custom_field_numbervalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE custom_field_stringvalue SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET setting = 'card:search:contexts.custom_record.%d' WHERE setting = 'card:search:cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET setting = 'profile:tabs:contexts.custom_record.%d' WHERE setting = 'profile:tabs:cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE notification SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE snippet SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE profile_tab SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE workspace_list SET context = 'contexts.custom_record.%d' WHERE context = 'cerberusweb.contexts.asset'", $custom_record_id));
	
	$db->ExecuteMaster(sprintf("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d')", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = REPLACE(params_json, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d')", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET setting = REPLACE(setting, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d') WHERE setting LIKE '%%cerberusweb.contexts.asset%%'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE devblocks_setting SET value = REPLACE(value, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d') WHERE value LIKE '%%cerberusweb.contexts.asset%%'", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE notification SET entry_json = REPLACE(entry_json, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d')", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE profile_widget SET extension_params_json = REPLACE(extension_params_json, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d')", $custom_record_id));
	$db->ExecuteMaster(sprintf("UPDATE workspace_widget SET params_json = REPLACE(params_json, 'cerberusweb.contexts.asset', 'contexts.custom_record.%d')", $custom_record_id));
	
	$db->ExecuteMaster("DELETE FROM worker_view_model WHERE class_name = 'View_Asset'");

	// Drop table
	$db->ExecuteMaster("DROP TABLE asset");
	
	unset($tables['asset']);
}

// ===========================================================================
// Convert workspace tab placeholder prompts from JSON to YAML

$sql = <<< EOD
SELECT id, params_json FROM workspace_tab WHERE extension_id = 'core.workspace.tab.dashboard' AND params_json LIKE '%placeholder\\\\\\%'
EOD;

if($rs = $db->ExecuteMaster($sql)) {
	while($row = mysqli_fetch_assoc($rs)) {
		if(false === ($json = json_decode($row['params_json'], true)))
			continue;
		
		if(!array_key_exists('placeholder_prompts', $json))
			continue;
		
		$placeholder_prompts_json = $json['placeholder_prompts'];
		
		// If it's still in JSON, convert to YAML
		if(DevblocksPlatform::strStartsWith($placeholder_prompts_json, '[')) {
			if(false === ($arr = json_decode($placeholder_prompts_json, true)))
				continue;
			
			$yaml = yaml_emit($arr);
			
			$yaml_clean = preg_replace(
				[
					'#^\-\-\-[\r\n]*#',
					'#\.\.\.[\r\n]*#',
					'#^\- #m',
					'#^  #m',
				],
				[
					'',
					'',
					"---\n",
					'',
				],
				$yaml
			);
			
			$json['placeholder_prompts'] = $yaml_clean;
			
			$sql = sprintf("UPDATE workspace_tab SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($json)),
				$row['id']
			);
			$db->ExecuteMaster($sql);
		}
	}
	
	mysqli_free_result($rs);
}

// ===========================================================================
// Disable the MFA requirement on any accounts without a 2FA seed

$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mfa.totp.seed' AND value = ''");
$db->ExecuteMaster("UPDATE worker SET is_mfa_required = 0 WHERE id NOT IN (SELECT worker_id FROM worker_pref WHERE setting = 'mfa.totp.seed')");

// ===========================================================================
// Finish up

return TRUE;
