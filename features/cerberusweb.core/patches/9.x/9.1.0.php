<?php
$db = DevblocksPlatform::services()->database();
$encrypt = DevblocksPlatform::services()->encryption();

$tables = $db->metaTables();

// ===========================================================================
// Alter `custom_field`

if(!isset($tables['custom_field']))
	return FALSE;

list($columns,) = $db->metaTable('custom_field');

if($columns['type'] && 0 == strcasecmp('varchar(1)', $columns['type']['type'])) {
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
					@$service_params['context_search'] = $prefs['context_search'];
					@$service_params['field_email'] = $prefs['field_email'];
					@$service_params['field_firstname'] = $prefs['field_firstname'];
					@$service_params['field_lastname'] = $prefs['field_lastname'];
					
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
						@$service_params['context_search'] = $prefs['context_search'];
						@$service_params['field_email'] = $prefs['field_email'];
						@$service_params['field_firstname'] = $prefs['field_firstname'];
						@$service_params['field_lastname'] = $prefs['field_lastname'];
						
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
// Migrate `login.openid` authenticators to `login.password`

if(array_key_exists('openid_to_worker', $tables)) {
	$sql = sprintf("UPDATE worker SET auth_extension_id=%s WHERE auth_extension_id=%s",
		$db->qstr('login.password'),
		$db->qstr('login.openid')
	);
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("DROP TABLE openid_to_worker");
	unset($tables['openid_to_worker']);
if(!isset($tables['worker']))
	return FALSE;

list($columns,) = $db->metaTable('worker');

if(!isset($columns['is_mfa_required'])) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN is_mfa_required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Migrate `login.password.google_auth` authenticators to `login.password`
if(!isset($columns['is_password_disabled'])) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN is_password_disabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
}

$sql = sprintf("UPDATE worker SET auth_extension_id=%s WHERE auth_extension_id=%s",
	$db->qstr('login.password'),
	$db->qstr('login.password.google_auth')
);
$db->ExecuteMaster($sql);
	
$sql = sprintf("UPDATE worker_pref SET setting = %s WHERE setting = %s",
	$db->qstr('mfa.totp.seed'),
	$db->qstr('login.password.google_auth.seed')
);
$db->ExecuteMaster($sql);

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
// Finish up

return TRUE;
