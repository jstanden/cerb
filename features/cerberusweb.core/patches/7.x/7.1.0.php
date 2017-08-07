<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add the `context_avatar` table

if(!isset($tables['context_avatar'])) {
	$sql = sprintf("
		CREATE TABLE context_avatar (
		  id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  context varchar(255) DEFAULT '',
		  context_id int unsigned DEFAULT 0,
		  content_type varchar(255) NOT NULL DEFAULT '',
		  is_approved tinyint(1) unsigned NOT NULL DEFAULT '0',
		  updated_at int(10) unsigned NOT NULL DEFAULT '0',
		  storage_extension varchar(255) NOT NULL DEFAULT '',
		  storage_key varchar(255) NOT NULL DEFAULT '',
		  storage_size int(10) unsigned NOT NULL DEFAULT '0',
		  storage_profile_id int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (id),
		  UNIQUE context_and_id (context, context_id),
		  KEY storage_extension (storage_extension)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['context_avatar'] = 'context_avatar';
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `worker` table

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['first_name']) && 0 != strcasecmp('varchar(128)', $columns['first_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN first_name varchar(128) not null default ''");
}

if(isset($columns['last_name']) && 0 != strcasecmp('varchar(128)', $columns['last_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN last_name varchar(128) not null default ''");
}

if(isset($columns['title']) && 0 != strcasecmp('varchar(255)', $columns['title']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN title varchar(255) not null default ''");
}

// ===========================================================================
// Migrate worker.email to worker.email_id

if(!isset($columns['email_id']) && isset($columns['email'])) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN email_id int unsigned not null default 0");
	
	$sql = "UPDATE worker INNER JOIN address ON (worker.email=address.email) SET worker.email_id = address.id";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN email");
}

// ===========================================================================
// Add new social worker fields

$changes = array();

if(!isset($columns['gender'])) {
	$changes[] = "ADD COLUMN gender char(1) not null default ''";
}

if(!isset($columns['dob'])) {
	$changes[] = "ADD COLUMN dob date default NULL";
}

if(!isset($columns['location'])) {
	$changes[] = "ADD COLUMN location varchar(255) not null default ''";
}

if(!isset($columns['phone'])) {
	$changes[] = "ADD COLUMN phone varchar(64) not null default ''";
}

if(!isset($columns['mobile'])) {
	$changes[] = "ADD COLUMN mobile varchar(64) not null default ''";
}

if(!empty($changes)) {
	$db->ExecuteMaster(sprintf("ALTER TABLE worker %s", implode(', ', $changes)));
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `worker_group` table

if(!isset($tables['worker_group'])) {
	$logger->error("The 'worker_group' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_group');

if(isset($columns['name']) && 0 != strcasecmp('varchar(255)', $columns['name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker_group MODIFY COLUMN name varchar(255) not null default ''");
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `bucket` table

if(!isset($tables['bucket'])) {
	$logger->error("The 'bucket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(isset($columns['name']) && 0 != strcasecmp('varchar(255)', $columns['name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE bucket MODIFY COLUMN name varchar(255) not null default ''");
}

// ===========================================================================
// Fix the length restrictions on the `worker_view_model` table

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(isset($columns['placeholder_labels_json']) && 0 == strcasecmp('text', $columns['placeholder_labels_json']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model MODIFY COLUMN placeholder_labels_json mediumtext");
}

if(isset($columns['placeholder_values_json']) && 0 == strcasecmp('text', $columns['placeholder_values_json']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model MODIFY COLUMN placeholder_values_json mediumtext");
}

// ===========================================================================
// Fix the length restrictions on the `workspace_list` table

if(!isset($tables['workspace_list'])) {
	$logger->error("The 'workspace_list' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_list');

if(isset($columns['list_view']) && 0 == strcasecmp('text', $columns['list_view']['type'])) {
	$db->ExecuteMaster("ALTER TABLE workspace_list MODIFY COLUMN list_view mediumtext");
}

// ===========================================================================
// Add contact_id to `address` records

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['contact_id'])) {
	$db->ExecuteMaster("ALTER TABLE address ADD COLUMN contact_id int unsigned not null default 0, ADD INDEX (contact_id)");
}

// ===========================================================================
// Add the `contact` table

if(!isset($tables['contact'])) {
	$sql = sprintf("
		CREATE TABLE contact (
		  id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  first_name varchar(128) NOT NULL DEFAULT '',
		  last_name varchar(128) NOT NULL DEFAULT '',
		  title varchar(255) NOT NULL DEFAULT '',
		  org_id int unsigned NOT NULL DEFAULT 0,
			username varchar(64) NOT NULL DEFAULT '',
			gender char(1) NOT NULL DEFAULT '',
			dob date DEFAULT NULL,
			location varchar(255) NOT NULL DEFAULT '',
		  primary_email_id int unsigned NOT NULL DEFAULT 0,
		  phone varchar(64) NOT NULL DEFAULT '',
		  mobile varchar(64) NOT NULL DEFAULT '',
		  auth_salt varchar(64) NOT NULL DEFAULT '',
		  auth_password varchar(64) NOT NULL DEFAULT '',
		  created_at int unsigned NOT NULL DEFAULT 0,
		  updated_at int unsigned NOT NULL DEFAULT 0,
		  last_login_at int(10) unsigned NOT NULL DEFAULT 0,
		  PRIMARY KEY (id),
			INDEX username(username(8)),
			INDEX (primary_email_id),
			INDEX (org_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['contact'] = 'contact';
	
	// Auto-create contacts from contact_person records
	$sql = "INSERT INTO contact(first_name,last_name,primary_email_id,org_id,created_at,updated_at,last_login_at,auth_salt,auth_password) ".
		"SELECT address.first_name, address.last_name, address.id, address.contact_org_id, contact_person.created, contact_person.updated, contact_person.last_login, contact_person.auth_salt, contact_person.auth_password FROM contact_person INNER JOIN address ON (address.id=contact_person.email_id)";
	$db->ExecuteMaster($sql);
	
	// Auto-create contacts from address records
	$sql = "INSERT INTO contact(first_name,last_name,primary_email_id,org_id,created_at,updated_at,last_login_at,auth_salt,auth_password) ".
		"SELECT first_name,last_name,id,contact_org_id,updated,updated,0,'','' FROM address WHERE contact_person_id = 0 AND (first_name != '' OR last_name != '')";
	$db->ExecuteMaster($sql);
	
	// Link addresses to contact records
	$sql = "UPDATE address INNER JOIN contact ON (address.id=contact.primary_email_id) SET address.contact_id=contact.id";
	$db->ExecuteMaster($sql);
	
	// Truncate fulltext_contact
	if(isset($tables['fulltext_contact'])) {
		$sql = "TRUNCATE fulltext_contact";
		$db->ExecuteMaster($sql);
		
		// Reset index cerb.search.schema.contact (cron.search needs to reindex the new 'contact' records)
		$sql = "DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.contact'";
		$db->ExecuteMaster($sql);
	}
	
} else {
	list($columns, $indexes) = $db->metaTable('contact');
	
	if(isset($columns['dob']) && 0 == strcasecmp('int(10) unsigned', $columns['dob']['type'])) {
		$db->ExecuteMaster("ALTER TABLE contact DROP COLUMN dob ");
		$db->ExecuteMaster("ALTER TABLE contact ADD COLUMN dob date default null");
	}
}

// ===========================================================================
// Alter `address` (drop first_name, last_name, contact_person)

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

$changes = array();

if(isset($columns['first_name']))
	$changes[] = "DROP COLUMN first_name";
if(isset($columns['last_name']))
	$changes[] = "DROP COLUMN last_name";
if(isset($columns['contact_person_id']))
	$changes[] = "DROP COLUMN contact_person_id";

if(!empty($changes)) {
	$sql = "ALTER TABLE address " . implode(', ', $changes);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Drop 'contact_person'

if(isset($tables['contact_person'])) {
	$sql = "DROP TABLE contact_person";
	$db->ExecuteMaster($sql);
	
	unset($tables['contact_person']);
}

// ===========================================================================
// Drop 'openid_to_contact_person'

if(isset($tables['openid_to_contact_person'])) {
	$sql = "DROP TABLE openid_to_contact_person";
	$db->ExecuteMaster($sql);
	
	unset($tables['openid_to_contact_person']);
}

// ===========================================================================
// Clear view models that have changed

$sql = "DELETE FROM worker_view_model WHERE view_id IN ('org_contacts')";
$db->ExecuteMaster($sql);

// ===========================================================================
// Drop 'fnr_topic'

if(isset($tables['fnr_topic'])) {
	$sql = "DROP TABLE fnr_topic";
	$db->ExecuteMaster($sql);
	
	unset($tables['fnr_topic']);
}

// ===========================================================================
// Drop 'fnr_external_resource'

if(isset($tables['fnr_external_resource'])) {
	$sql = "DROP TABLE fnr_external_resource";
	$db->ExecuteMaster($sql);
	
	unset($tables['fnr_external_resource']);
}

// ===========================================================================
// Clear invalid groups from worker_to_group

if(isset($tables['worker_to_group'])) {
	$sql = "DELETE FROM worker_to_group WHERE group_id NOT IN (SELECT id FROM worker_group)";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Fix 'NOT NULL' inconsistencies with MySQL strict mode

if(!isset($tables['context_avatar'])) {
	$logger->error("The 'context_avatar' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('context_avatar');

if(0 == strcasecmp($columns['context']['null'], 'NO')) {
	$db->ExecuteMaster("ALTER TABLE context_avatar MODIFY COLUMN context VARCHAR(255) DEFAULT ''");
}

if(0 == strcasecmp($columns['context_id']['null'], 'NO')) {
	$db->ExecuteMaster("ALTER TABLE context_avatar MODIFY COLUMN context_id INT UNSIGNED DEFAULT 0");
}

// ===========================================================================
// Fix address_to_worker from using varchar for address

if(!isset($tables['address_to_worker'])) {
	$logger->error("The 'address_to_worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address_to_worker');

if(isset($columns['address'])) {
	$db->ExecuteMaster("ALTER TABLE address_to_worker ADD COLUMN address_id INT UNSIGNED NOT NULL");
	$db->ExecuteMaster("UPDATE address_to_worker INNER JOIN address ON (address_to_worker.address=address.email) SET address_id=address.id");
	$db->ExecuteMaster("ALTER TABLE address_to_worker DROP COLUMN address, ADD PRIMARY KEY (address_id)");
}

// ===========================================================================
// Migrate 'create_task' actions to new format

$sql = "SELECT id, params_json FROM decision_node WHERE node_type = 'action' AND params_json LIKE '%create_task%'";
$results = $db->GetArrayMaster($sql);

if(is_array($results))
foreach($results as $result) {
	$params = json_decode($result['params_json'], true);
	
	if(isset($params['actions']) && is_array($params['actions']))
	foreach($params['actions'] as &$action) {
		if(!isset($action['action']) || $action['action'] != 'create_task')
			continue;
		
		if(isset($action['on'])) {
			$action['link_to'] = array($action['on']);
			unset($action['on']);
			
			$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($params)),
				$result['id']
			));
		}
	}
}

// ===========================================================================
// Migrate 'create_call' actions to new format

$sql = "SELECT id, params_json FROM decision_node WHERE node_type = 'action' AND params_json LIKE '%calls.event.action.post%'";
$results = $db->GetArrayMaster($sql);

if(is_array($results))
foreach($results as $result) {
	$params = json_decode($result['params_json'], true);
	
	if(isset($params['actions']) && is_array($params['actions']))
	foreach($params['actions'] as &$action) {
		if(!isset($action['action']) || $action['action'] != 'calls.event.action.post')
			continue;
		
		if(isset($action['on'])) {
			$action['link_to'] = array($action['on']);
			unset($action['on']);
			
			$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($params)),
				$result['id']
			));
		}
	}
}

// ===========================================================================
// Finish up

return TRUE;
