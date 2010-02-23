<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `address` ========================
list($columns, $indexes) = $db->metaTable('address');

if(isset($columns['contact_id'])) {
	$db->Execute("ALTER TABLE address DROP COLUMN contact_id");
}

if(isset($columns['personal'])) {
	$db->Execute("ALTER TABLE address DROP COLUMN personal");
}

if(!isset($columns['first_name'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN first_name VARCHAR(32) DEFAULT '' NOT NULL");
}

if(!isset($columns['last_name'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN last_name VARCHAR(32) DEFAULT '' NOT NULL");
}

if(!isset($columns['phone'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN phone VARCHAR(32) DEFAULT '' NOT NULL");
}

if(!isset($columns['contact_org_id'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN contact_org_id INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($columns['num_spam'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN num_spam INT UNSIGNED DEFAULT 0 NOT NULL");
    
    // Update totals
	$sql = "SELECT count(id) as hits,first_wrote_address_id FROM ticket WHERE spam_training = 'S' GROUP BY first_wrote_address_id,spam_training";
	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	
	while($row = mysql_fetch_assoc($rs)) {
		$hits = intval($row['hits']);
		$address_id = intval($row['first_wrote_address_id']);
		$db->Execute(sprintf("UPDATE address SET num_spam = %d WHERE id = %d", $hits, $address_id));
	}
	
	mysql_free_result($rs);
}

if(!isset($columns['num_nonspam'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN num_nonspam INT UNSIGNED DEFAULT 0 NOT NULL");
    
    // Update totals
	$sql = "SELECT count(id) as hits,first_wrote_address_id FROM ticket WHERE spam_training = 'N' GROUP BY first_wrote_address_id,spam_training";
	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	
	while($row = mysql_fetch_assoc($rs)) {
		$hits = intval($row['hits']);
		$address_id = intval($row['first_wrote_address_id']);
		$db->Execute(sprintf("UPDATE address SET num_nonspam = %d WHERE id = %d", $hits, $address_id));
	}
	
	mysql_free_result($rs);
}

if(!isset($columns['is_banned'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN is_banned TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($columns['sla_id'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN sla_id INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($columns['sla_expires'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN sla_expires INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($columns['last_autoreply'])) {
    $db->Execute("ALTER TABLE address ADD COLUMN last_autoreply INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($indexes['email'])) {
    $db->Execute("ALTER TABLE address ADD UNIQUE email (email)");
}

if(!isset($indexes['contact_org_id'])) {
    $db->Execute("ALTER TABLE address ADD INDEX contact_org_id (contact_org_id)");
}

if(!isset($indexes['sla_id'])) {
    $db->Execute("ALTER TABLE address ADD INDEX sla_id (sla_id)");
}

if(!isset($indexes['num_spam'])) {
    $db->Execute("ALTER TABLE address ADD INDEX num_spam (num_spam)");
}

if(!isset($indexes['num_nonspam'])) {
    $db->Execute("ALTER TABLE address ADD INDEX num_nonspam (num_nonspam)");
}

if(!isset($indexes['is_banned'])) {
    $db->Execute("ALTER TABLE address ADD INDEX is_banned (is_banned)");
}

if(!isset($indexes['last_autoreply'])) {
    $db->Execute("ALTER TABLE address ADD INDEX last_autoreply (last_autoreply)");
}

// `address_auth` =============================
if(!isset($tables['address_auth'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS address_auth (
			address_id INT UNSIGNED DEFAULT 0 NOT NULL,
			confirm VARCHAR(16) DEFAULT '' NOT NULL,
			pass VARCHAR(32) DEFAULT '' NOT NULL,
			PRIMARY KEY (address_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `address_to_worker` =============================
if(!isset($tables['address_to_worker'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS address_to_worker (
			address VARCHAR(128) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_confirmed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			code VARCHAR(32) DEFAULT '' NOT NULL,
			code_expire INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (address)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
	
    // Migrate any existing workers
	$rs = $db->Execute("SELECT id, email FROM worker");
	
	while($row = mysql_fetch_assoc($rs)) {
		$db->Execute(sprintf("INSERT INTO address_to_worker (address, worker_id, is_confirmed, code_expire) ".
			"VALUES (%s,%d,1,0)",
			$db->qstr($row['email']),
			intval($row['id'])
		));
	}
	
	mysql_free_result($rs);
}

// `contact_org` =============================
if(!isset($tables['contact_org'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS contact_org (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			account_number VARCHAR(32) DEFAULT '' NOT NULL,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			street VARCHAR(128) DEFAULT '' NOT NULL,
			city VARCHAR(64) DEFAULT '' NOT NULL,
			province VARCHAR(64) DEFAULT '' NOT NULL,
			postal VARCHAR(20) DEFAULT '' NOT NULL,
			country VARCHAR(64) DEFAULT '' NOT NULL,
			phone VARCHAR(32) DEFAULT '' NOT NULL,
			fax VARCHAR(32) DEFAULT '' NOT NULL,
			website VARCHAR(128) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			sla_id INT UNSIGNED DEFAULT 0 NOT NULL,
			sla_expires INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id),
			INDEX name (name),
			INDEX account_number (account_number),
			INDEX sla_id (sla_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `contact_person` =============================
if(isset($tables['contact_person'])) {
	$db->Execute("DROP TABLE contact_person");
}

if(isset($tables['contact_person_seq'])) {
	$db->Execute("DROP TABLE contact_person_seq");
}

// `fnr_external_resource` =======================
if(!isset($tables['fnr_external_resource'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS fnr_external_resource (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			topic_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `fnr_topic` =======================
if(!isset($tables['fnr_topic'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS fnr_topic (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `fnr_query` =======================
if(!isset($tables['fnr_query'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS fnr_query (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			query VARCHAR(255) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			source VARCHAR(32) DEFAULT '' NOT NULL,
			no_match TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `category` ========================
list($columns, $indexes) = $db->metaTable('category');

if(!isset($columns['response_hrs'])) {
	$db->Execute("ALTER TABLE category ADD COLUMN response_hrs SMALLINT UNSIGNED DEFAULT 0 NOT NULL");
}

// `group_setting` =======================
if(!isset($tables['group_setting'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS group_setting (
			group_id INT UNSIGNED DEFAULT 0 NOT NULL,
			setting VARCHAR(64) DEFAULT '' NOT NULL,
			value BLOB,
			PRIMARY KEY (group_id, setting)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `mail_template` =======================
if(!isset($tables['mail_template'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS mail_template (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			title VARCHAR(64) DEFAULT '' NOT NULL,
			description VARCHAR(255) DEFAULT '' NOT NULL,
			folder VARCHAR(64) DEFAULT '' NOT NULL,
			template_type TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			owner_id INT UNSIGNED DEFAULT 0 NOT NULL,
			content MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `mail_template_reply` =======================
if(isset($tables['mail_template_reply'])) {
	$rs = $db->Execute("SELECT id,title,description,folder,owner_id,content FROM mail_template_reply");
	
	while($row = mysql_fetch_assoc($rs)) {
		$db->Execute(sprintf("INSERT INTO mail_template (id,title,description,folder,template_type,owner_id,content) ".
			"VALUES (%d,%s,%s,%s,%d,%d,%s)",
			$row['id'],
			$db->qstr($row['title']),
			$db->qstr($row['description']),
			$db->qstr($row['folder']),
			2, // reply
			$row['owner_id'],
			$db->qstr($row['content'])
		));
	}
	
	mysql_free_result($rs);
	
	$db->Execute("DROP TABLE mail_template_reply");
}

// `message_content` =====================
if(!isset($tables['message_content'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS message_content (
			message_id INT UNSIGNED DEFAULT 0 NOT NULL,
			content MEDIUMBLOB,
			PRIMARY KEY (message_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `message_header` =====================
if(!isset($tables['message_header'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS message_header (
			message_id INT UNSIGNED DEFAULT 0 NOT NULL,
			header_name VARCHAR(64) DEFAULT '' NOT NULL,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			header_value BLOB,
			PRIMARY KEY (message_id, header_name)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('message_header');

if(!isset($indexes['header_name'])) {
	$db->Execute("ALTER TABLE message_header ADD INDEX header_name (header_name)");
}

if(!isset($indexes['ticket_id'])) {
	$db->Execute("ALTER TABLE message_header ADD INDEX header_value (header_value(10))");
}

// `message_note` ==================
if(!isset($tables['message_note'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS message_note (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			message_id INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			content BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('message_note');

if(!isset($columns['type'])) {
    $db->Execute("ALTER TABLE message_note ADD COLUMN type TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($indexes['type'])) {
	$db->Execute("ALTER TABLE message_note ADD INDEX type (type)");
}

// `message` ========================
list($columns, $indexes) = $db->metaTable('message');

if(isset($columns['headers'])) {
	$db->Execute("ALTER TABLE message DROP COLUMN headers");
}

if(isset($columns['message_id'])) {
	$db->Execute("ALTER TABLE message DROP COLUMN message_id");
}

if(isset($columns['is_admin'])) {
	$db->Execute("ALTER TABLE message DROP COLUMN is_admin");
}

if(!isset($columns['is_outgoing'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN is_outgoing TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL");
    
    // Gather Helpdesk/Group addresses
	try {
		$froms = array();
		
		$sql = "SELECT value FROM setting WHERE setting = 'default_reply_from'";
		
		if(null != ($default_from = $db->GetOne($sql))) {
			$froms[$default_from] = 1;
		}
		
		$rs = $db->Execute("SELECT group_id, setting, value FROM group_setting ORDER BY group_id, setting");
		
		while($row = mysql_fetch_assoc($rs)) {
			if('reply_from' != $row['setting'])
				continue;
			if(empty($row['value']))
				continue;
			$froms[$row['value']] = 1;
		}
		
		if(is_array($froms) && !empty($froms)) {
			$froms = array_keys($froms);
			$sql = sprintf("SELECT id FROM address WHERE email IN ('%s')",
				implode("','", $froms)
			);
			$rs = $db->Execute($sql);
			
			while($row = mysql_fetch_assoc($rs)) {
   				$address_id = intval($row['id']);
				$db->Execute(sprintf("UPDATE message SET is_outgoing = 1 WHERE address_id = %d",
		    		$address_id
		    	));
			}
			
			mysql_free_result($rs);
		}
		
	} catch(Exception $e) {}
}

if(!isset($columns['worker_id'])) {
	$db->Execute('ALTER TABLE message ADD COLUMN worker_id INT UNSIGNED DEFAULT 0 NOT NULL');
    
    // Link direct replies from worker addresses as outgoing messages (Cerb 1,2,3.x)
    $sql = "SELECT a.id as address_id,w.id as worker_id FROM address a INNER JOIN worker w ON (a.email=w.email)";
    $rs = $db->Execute($sql);
    
    while($row = mysql_fetch_assoc($rs)) {
    	$address_id = intval($row['address_id']);
    	$worker_id = intval($row['worker_id']);
    	$db->Execute(sprintf("UPDATE message SET is_outgoing = 1 AND worker_id = %d WHERE address_id = %d",
    		$worker_id,
    		$address_id
    	));
    }
    
    mysql_free_result($rs);
}

if(isset($columns['message_type'])) {
	$db->Execute('ALTER TABLE message DROP COLUMN message_type');
}

if(isset($columns['content'])) {
	$db->Execute('ALTER TABLE message DROP COLUMN content');
}

if(!isset($indexes['created_date'])) {
	$db->Execute('ALTER TABLE message ADD INDEX created_date (created_date)');
}

if(!isset($indexes['ticket_id'])) {
	$db->Execute('ALTER TABLE message ADD INDEX ticket_id (ticket_id)');
}

if(!isset($indexes['is_outgoing'])) {
	$db->Execute('ALTER TABLE message ADD INDEX is_outgoing (is_outgoing)');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE message ADD INDEX worker_id (worker_id)');
}

// `requester` ========================
list($columns, $indexes) = $db->metaTable('requester');

if(!isset($indexes['address_id'])) {
	$db->Execute('ALTER TABLE requester ADD INDEX address_id (address_id)');
}

if(!isset($indexes['ticket_id'])) {
	$db->Execute('ALTER TABLE requester ADD INDEX ticket_id (ticket_id)');
}

// `setting` ==================================
list($columns, $indexes) = $db->metaTable('setting');

if(0 != strcasecmp($columns['value']['type'],'blob')) {
	$db->Execute("ALTER TABLE setting CHANGE COLUMN value value_old VARCHAR(255) DEFAULT '' NOT NULL");
	$db->Execute("ALTER TABLE setting ADD COLUMN value BLOB");
	
	$sql = "SELECT setting, value_old FROM setting ";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$db->Execute(sprintf("UPDATE setting SET value=%s WHERE setting=%s",
			$db->qstr($row['value_old']),
			$db->qstr($row['setting'])
		));
	}

	$db->Execute("ALTER TABLE setting DROP COLUMN value_old");
	
	mysql_free_result($rs);
}

// `sla` ========================
if(!isset($tables['sla'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS sla (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			priority TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `task` =============================
if(!isset($tables['task'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS task (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			title VARCHAR(255) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			priority TINYINT(1) UNSIGNED DEFAULT 4 NOT NULL,
			due_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_completed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			completed_date INT UNSIGNED DEFAULT 0 NOT NULL,
			source_extension VARCHAR(255) DEFAULT '' NOT NULL,
			source_id INT UNSIGNED DEFAULT 0 NOT NULL,
			content TEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('task');

if(!isset($indexes['is_completed'])) {
	$db->Execute('ALTER TABLE task ADD INDEX is_completed (is_completed)');
}

if(!isset($indexes['completed_date'])) {
	$db->Execute('ALTER TABLE task ADD INDEX completed_date (completed_date)');
}

if(!isset($indexes['priority'])) {
	$db->Execute('ALTER TABLE task ADD INDEX priority (priority)');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE task ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['source_extension'])) {
	$db->Execute('ALTER TABLE task ADD INDEX source_extension (source_extension)');
}

if(!isset($indexes['source_id'])) {
	$db->Execute('ALTER TABLE task ADD INDEX source_id (source_id)');
}

// `team_routing_rule` ========================
list($columns, $indexes) = $db->metaTable('team_routing_rule');

if(!isset($columns['do_assign'])) {
    $db->Execute("ALTER TABLE team_routing_rule ADD COLUMN do_assign BIGINT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($indexes['team_id'])) {
	$db->Execute('ALTER TABLE team_routing_rule ADD INDEX team_id (team_id)');
}

if(!isset($indexes['pos'])) {
	$db->Execute('ALTER TABLE team_routing_rule ADD INDEX pos (pos)');
}

// `ticket` ========================
list($columns, $indexes) = $db->metaTable('ticket');

if(isset($columns['owner_id'])) {
    $db->Execute("ALTER TABLE ticket DROP COLUMN owner_id");
}

if(isset($columns['priority'])) {
    $db->Execute("ALTER TABLE ticket DROP COLUMN priority");
}

if(!isset($columns['is_waiting'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN is_waiting TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(isset($columns['import_pile'])) {
	$db->Execute("ALTER TABLE ticket DROP COLUMN import_pile");
}

if(!isset($columns['last_worker_id'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN last_worker_id INT UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['last_action_code'])) {
    $db->Execute("ALTER TABLE ticket ADD COLUMN last_action_code VARCHAR(1) DEFAULT 'O' NOT NULL");
}

if(!isset($columns['next_worker_id'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN next_worker_id INT UNSIGNED DEFAULT 0 NOT NULL');
    $db->Execute("UPDATE ticket SET next_worker_id = last_worker_id");
}

if(!isset($columns['sla_id'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN sla_id INT UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['sla_priority'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN sla_priority TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['first_message_id'])) {
    $db->Execute('ALTER TABLE ticket ADD COLUMN first_message_id INT UNSIGNED DEFAULT 0 NOT NULL');
}

// [JAS]: Populate our new foreign key
$sql = "SELECT m.ticket_id, min(m.id) as first_message_id ".
	"FROM message m ".
	"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	"WHERE t.first_message_id = 0 ".
	"GROUP BY ticket_id";
$rs = $db->Execute($sql);
 
while($row = mysql_fetch_assoc($rs)) {
	if(empty($row['first_message_id'])) {
		continue;
	}
	 
	$sql = sprintf("UPDATE ticket SET first_message_id = %d WHERE id = %d",
		intval($row['first_message_id']),
		intval($row['ticket_id'])
	);
	$db->Execute($sql);
}
 
mysql_free_result($rs);

if(!isset($indexes['first_message_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX first_message_id (first_message_id)');
}

if(!isset($indexes['mask'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX mask (mask)');
}

if(!isset($indexes['is_waiting'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX is_waiting (is_waiting)');
}

if(!isset($indexes['sla_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX sla_id (sla_id)');
}

if(!isset($indexes['sla_priority'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX sla_priority (sla_priority)');
}

if(!isset($indexes['team_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX team_id (team_id)');
}

if(!isset($indexes['created_date'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX created_date (created_date)');
}

if(!isset($indexes['updated_date'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX updated_date (updated_date)');
}

if(!isset($indexes['first_wrote_address_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX first_wrote_address_id (first_wrote_address_id)');
}

if(!isset($indexes['last_wrote_address_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX last_wrote_address_id (last_wrote_address_id)');
}

if(!isset($indexes['is_closed'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX is_closed (is_closed)');
}

if(!isset($indexes['category_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX category_id (category_id)');
}

if(!isset($indexes['last_worker_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX last_worker_id (last_worker_id)');
}

if(!isset($indexes['next_worker_id'])) {
	$db->Execute('ALTER TABLE ticket ADD INDEX next_worker_id (next_worker_id)');
}

// `ticket_comment` =============================
if(!isset($tables['ticket_comment'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_comment (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			comment MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `ticket_field` ==================
if(!isset($tables['ticket_field'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_field (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(32) DEFAULT '' NOT NULL,
			type VARCHAR(1) DEFAULT 'S' NOT NULL,
			group_id INT UNSIGNED DEFAULT 0 NOT NULL,
			pos SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			options TEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('ticket_field');

if(!isset($indexes['group_id'])) {
	$db->Execute('ALTER TABLE ticket_field ADD INDEX group_id (group_id)');
}

// `ticket_field_value` ==================
if(!isset($tables['ticket_field_value'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_field_value (
			field_id INT UNSIGNED DEFAULT 0 NOT NULL,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			field_value MEDIUMTEXT,
			PRIMARY KEY (field_id, ticket_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('ticket_field_value');

if(!isset($indexes['ticket_id'])) {
	$db->Execute('ALTER TABLE ticket_field_value ADD INDEX ticket_id (ticket_id)');
}

// `ticket_rss` ========================
if(!isset($tables['ticket_rss'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_rss (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			hash VARCHAR(32) DEFAULT '' NOT NULL,
			title VARCHAR(128) DEFAULT '' NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			params BLOB,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `worker`
list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['can_delete'])) {
    $db->Execute('ALTER TABLE worker ADD COLUMN can_delete TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

// `worker_to_team`
list($columns, $indexes) = $db->metaTable('worker_to_team');

if(!isset($columns['is_manager'])) {
    $db->Execute('ALTER TABLE worker_to_team ADD COLUMN is_manager TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

// `worker_workspace_list` =============================
if(!isset($tables['worker_workspace_list'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_workspace_list (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			workspace VARCHAR(32) DEFAULT '' NOT NULL,
			list_view TEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('worker_workspace_list');

if(!isset($columns['list_pos'])) {
	$db->Execute('ALTER TABLE worker_workspace_list ADD COLUMN list_pos SMALLINT UNSIGNED DEFAULT 0');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE worker_workspace_list ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['workspace'])) {
	$db->Execute('ALTER TABLE worker_workspace_list ADD INDEX workspace (workspace)');
}

// ***** CloudGlue

if(!isset($tables['tag_to_content'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS tag_to_content (
			index_id SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			tag_id INT UNSIGNED DEFAULT 0 NOT NULL,
			content_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (index_id, tag_id, content_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

if(!isset($tables['tag_index'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS tag_index (
			id SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL, 
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

if(!isset($tables['tag'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS tag (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(32) DEFAULT '' NOT NULL, 
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// Remove any worker addresses from deleted workers
$sql = "SELECT DISTINCT atw.worker_id ".
	"FROM address_to_worker atw ".
	"LEFT JOIN worker w ON (w.id=atw.worker_id) ".
	"WHERE w.id IS NULL";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$sql = sprintf("DELETE FROM address_to_worker WHERE worker_id = %d",
		$row['worker_id']
	);
	$db->Execute($sql);
}

mysql_free_result($rs);

// Remove any group settings from deleted groups
$sql = "SELECT DISTINCT gs.group_id ".
	"FROM group_setting gs ".
	"LEFT JOIN team t ON (t.id=gs.group_id) ".
	"WHERE t.id IS NULL";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$sql = sprintf("DELETE FROM group_setting WHERE group_id = %d",
		$row['group_id']
	);
	$db->Execute($sql);
}

mysql_free_result($rs);

// Recover any tickets assigned to a NULL bucket
$sql = "SELECT DISTINCT t.category_id as id ".
	"FROM ticket t ".
	"LEFT JOIN category c ON (t.category_id=c.id) ".
	"WHERE c.id IS NULL AND t.category_id > 0";
$rs = $db->Execute($sql);

while($row = mysql_fetch_assoc($rs)) {
	$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id = %d",
		$row['id']
	);
	$db->Execute($sql);
}

mysql_free_result($rs);

// Merge any addresses that managed to get into the DB mixed case
$rs = $db->Execute("SELECT count(id) AS hits, lower(email) AS email FROM address GROUP BY lower(email) HAVING count(id) > 1"); 

while($row = mysql_fetch_assoc($rs)) {
	$rs2 = $db->Execute(sprintf("SELECT id,email,lower(email) as orig_email FROM address WHERE lower(email) = %s",
		$db->qstr($row['email'])
	));

	$ids = array();
	$best_id = 0;
	$ids_not_best = array();
	
	while($row2 = mysql_fetch_assoc($rs2)) {
		$ids[] = intval($row2['id']);
		if(0==strcmp($row2['orig_email'], $row2['email'])) {
			$best_id = intval($row2['id']);
		} else {
			$ids_not_best[] = intval($row2['id']);
		}
	}
	
	mysql_free_result($rs2);
	
	if(empty($ids_not_best))
		$best_id = array_shift($ids_not_best);

	if(!empty($best_id) && !empty($ids_not_best)) {
		// Address Auth (remove dupes)
		$db->Execute(sprintf("DELETE FROM address_auth WHERE address_id IN (%s)",
			implode(',', $ids_not_best)
		));

		// Messages (merge dupe senders)
		$db->Execute(sprintf("UPDATE message SET address_id = %d WHERE address_id IN (%s)",
			$best_id,
			implode(',', $ids_not_best)
		));
		
		// Requester (merge dupe reqs)
		$db->Execute(sprintf("UPDATE requester SET address_id = %d WHERE address_id IN (%s)",
			$best_id,
			implode(',', $ids_not_best)
		));
		$db->Execute(sprintf("DELETE FROM requester WHERE address_id IN (%s)",
			implode(',', $ids_not_best)
		));
		
		// Ticket: First Wrote (merge dupe reqs)
		$db->Execute(sprintf("UPDATE ticket SET first_wrote_address_id = %d WHERE first_wrote_address_id IN (%s)",
			$best_id,
			implode(',', $ids_not_best)
		));
		$db->Execute(sprintf("DELETE FROM ticket WHERE first_wrote_address_id IN (%s)",
			implode(',', $ids_not_best)
		));

		// Ticket: Last Wrote (merge dupe reqs)
		$db->Execute(sprintf("UPDATE ticket SET last_wrote_address_id = %d WHERE last_wrote_address_id IN (%s)",
			$best_id,
			implode(',', $ids_not_best)
		));
		$db->Execute(sprintf("DELETE FROM ticket WHERE last_wrote_address_id IN (%s)",
			implode(',', $ids_not_best)
		));

		// Addresses
		$db->Execute(sprintf("DELETE FROM address WHERE id IN (%s)",
			implode(',', $ids_not_best)
		));
	}
}
	
mysql_free_result($rs);

// Fix blank ticket.first_message_id links (compose)
$rs = $db->Execute('select t.id,max(m.id) as max_id,min(m.id) as min_id from ticket t inner join message m on (m.ticket_id=t.id) where t.first_message_id = 0 group by t.id;');
while($row = mysql_fetch_assoc($rs)) {
	$db->Execute(sprintf("UPDATE ticket SET first_message_id = %d WHERE id = %d",
		$row['max_id'],
		$row['id']
	));
}

mysql_free_result($rs);

// [TODO] This should probably be checked (though MySQL needs special BINARY syntax)
$db->Execute("UPDATE address SET email = LOWER(email)");

// Enable heartbeat cron
if(null != ($cron_mf = DevblocksPlatform::getExtension('cron.heartbeat'))) {
	if(null != ($cron = $cron_mf->createInstance())) {
		$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
		$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '5');
		$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
		$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
	}
}

return TRUE;
