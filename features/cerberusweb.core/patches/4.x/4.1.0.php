<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// Drop the Service Level fields on address
list($columns, $indexes) = $db->metaTable('address');

if(isset($columns['sla_id'])) {
	$db->Execute("ALTER TABLE address DROP COLUMN sla_id");
}

if(isset($columns['sla_expires'])) {
	$db->Execute("ALTER TABLE address DROP COLUMN sla_expires");
}

// Drop the Service Level expires field on contact_org (later sla_id is migrated and dropped)
list($columns, $indexes) = $db->metaTable('contact_org');

if(isset($columns['sla_expires'])) {
	$db->Execute("ALTER TABLE contact_org DROP COLUMN sla_expires");
}

// Drop the Service Level fields on tickets
list($columns, $indexes) = $db->metaTable('ticket');

if(isset($columns['sla_id'])) {
	$db->Execute("ALTER TABLE ticket DROP COLUMN sla_id");
}

if(isset($columns['sla_priority'])) {
	$db->Execute("ALTER TABLE ticket DROP COLUMN sla_priority");
}

// Migrate contact_org.sla_id to a custom field dropdown
list($columns, $indexes) = $db->metaTable('contact_org');

if(isset($columns['sla_id'])) {
	$sql = "SELECT count(id) FROM contact_org WHERE sla_id != ''";
	$count = $db->GetOne($sql);
	
	// Load the SLA hash
	$slas = array();
	if(isset($tables['sla'])) {
		$sql = "SELECT id, name FROM sla ORDER BY name";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$slas[$row['id']] = $row['name'];
		}
		
		mysql_free_result($rs);
	}
	
	if(!empty($count) && !empty($slas)) { // Move to a custom field before dropping
		// Create the new custom field
		$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
			"VALUES ('Service Level','D',0,0,%s,%s)",
			$db->qstr(implode("\n",$slas)),
			$db->qstr('cerberusweb.fields.source.org')
		);
		$db->Execute($sql);
		$field_id = $db->LastInsertId();
		
		// Populate the custom field from org records
		$sql = sprintf("INSERT INTO custom_field_value (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, s.name, %s FROM contact_org o INNER JOIN sla s ON (o.sla_id=s.id)",
			$field_id,
			$db->qstr('cerberusweb.fields.source.org')
		);
		$db->Execute($sql);
	}
	
	// Drop the account number hardcoded column
	$db->Execute("ALTER TABLE contact_org DROP COLUMN sla_id");
}

// Drop the SLA table
if(isset($tables['sla'])) {
	$db->Execute('DROP TABLE sla');
}

// Migrate custom field values to data-type dependent tables
if(isset($tables['custom_field_value'])) {
	
	// Custom field number values: (C) Checkbox, (E) Date
	if(!isset($tables['custom_field_numbervalue'])) {
		$sql = "
			CREATE TABLE IF NOT EXISTS custom_field_numbervalue (
				field_id INT UNSIGNED DEFAULT 0 NOT NULL,
				source_id INT UNSIGNED DEFAULT 0 NOT NULL,
				field_value INT UNSIGNED DEFAULT 0 NOT NULL,
				source_extension VARCHAR(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (field_id, source_id)
			) ENGINE=MyISAM;
		";
		$res = $db->Execute($sql);	
		
	    if($res) {
	    	$tables['custom_field_numbervalue'] = true;
	    	
	    	$sql = "INSERT IGNORE INTO custom_field_numbervalue (field_id, source_id, field_value, source_extension) ".
	    		"SELECT v.field_id, v.source_id, CAST(v.field_value AS SIGNED), v.source_extension ".
	    		"FROM custom_field_value v ".
	    		"INNER JOIN custom_field cf ON (cf.id=v.field_id) ".
	    		"WHERE cf.type IN ('C','E')";
	    	$db->Execute($sql);
	    	
	    } else {
	    	die($db->ErrorMsg());
	    }
	}

	// Custom field string values: (S) Single, (D) Dropdown
	if(!isset($tables['custom_field_stringvalue'])) {
		$sql = "
			CREATE TABLE IF NOT EXISTS custom_field_stringvalue (
				field_id INT UNSIGNED DEFAULT 0 NOT NULL,
				source_id INT UNSIGNED DEFAULT 0 NOT NULL,
				field_value VARCHAR(255) DEFAULT '' NOT NULL,
				source_extension VARCHAR(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (field_id, source_id)
			) ENGINE=MyISAM;
		";
		$res = $db->Execute($sql);	
		
	    if($res) {
	    	$tables['custom_field_stringvalue'] = true;
	    	
	    	$sql = "INSERT IGNORE INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
	    		"SELECT v.field_id, v.source_id, LEFT(v.field_value,255), v.source_extension ".
	    		"FROM custom_field_value v ".
	    		"INNER JOIN custom_field cf ON (cf.id=v.field_id) ".
	    		"WHERE cf.type IN ('S','D')";
	    	$db->Execute($sql);
	    	
	    } else {
	    	die($db->ErrorMsg());
	    }
	}

	// Custom field text/clob values: (T) Multi-line
	if(!isset($tables['custom_field_clobvalue'])) {
		$sql = "
			CREATE TABLE IF NOT EXISTS custom_field_clobvalue (
				field_id INT UNSIGNED DEFAULT 0 NOT NULL,
				source_id INT UNSIGNED DEFAULT 0 NOT NULL,
				field_value MEDIUMTEXT,
				source_extension VARCHAR(255) DEFAULT '' NOT NULL,
				PRIMARY KEY (field_id, source_id)
			) ENGINE=MyISAM;
		";
		$res = $db->Execute($sql);	
		
	    if($res) {
	    	$tables['custom_field_clobvalue'] = true;
	    	
	    	$sql = "INSERT IGNORE INTO custom_field_clobvalue (field_id, source_id, field_value, source_extension) ".
	    		"SELECT v.field_id, v.source_id, v.field_value, v.source_extension ".
	    		"FROM custom_field_value v ".
	    		"INNER JOIN custom_field cf ON (cf.id=v.field_id) ".
	    		"WHERE cf.type IN ('T')";
	    	$db->Execute($sql);
	    	
	    } else {
	    	die($db->ErrorMsg());
	    }
	}
	
	// If we passed all that, delete the original custom_field_value table
	if(isset($tables['custom_field_value'])) {
		$db->Execute('DROP TABLE custom_field_value');
	}
}

// Add a merge table to track when older ticket masks should point to a new ticket
if(!isset($tables['ticket_mask_forward'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_mask_forward (
			old_mask VARCHAR(32) DEFAULT '' NOT NULL,
			new_mask VARCHAR(32) DEFAULT '' NOT NULL,
			new_ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (old_mask)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('ticket_mask_forward');

if(!isset($indexes['new_ticket_id'])) {
	$db->Execute('ALTER TABLE ticket_mask_forward ADD INDEX new_ticket_id (new_ticket_id)');
}

// Drop primary compound key on custom_field_stringvalue so we can have multi-select dropdowns

list($columns, $indexes) = $db->metaTable('custom_field_stringvalue');

// Drop compound primary key
if(isset($columns['field_id']) && isset($columns['source_id'])
	&& 'PRI'==$columns['field_id']['key'] && 'PRI'==$columns['source_id']['key']) {
		$db->Execute("ALTER TABLE custom_field_stringvalue DROP PRIMARY KEY");
}

if(!isset($indexes['field_id'])) {
	$db->Execute('ALTER TABLE custom_field_stringvalue ADD INDEX field_id (field_id)');
}

if(!isset($indexes['source_id'])) {
	$db->Execute('ALTER TABLE custom_field_stringvalue ADD INDEX source_id (source_id)');
}

// Drop primary compound key on custom_field_numbervalue so we can have multi-select checkboxes

list($columns, $indexes) = $db->metaTable('custom_field_numbervalue');

// Drop compound primary key
if(isset($columns['field_id']) && isset($columns['source_id'])
	&& 'PRI'==$columns['field_id']['key'] && 'PRI'==$columns['source_id']['key']) {
		$db->Execute("ALTER TABLE custom_field_numbervalue DROP PRIMARY KEY");
}

if(!isset($indexes['field_id'])) {
	$db->Execute('ALTER TABLE custom_field_numbervalue ADD INDEX field_id (field_id)');
}

if(!isset($indexes['source_id'])) {
	$db->Execute('ALTER TABLE custom_field_numbervalue ADD INDEX source_id (source_id)');
}

// Drop primary compound key on custom_field_clobvalue

list($columns, $indexes) = $db->metaTable('custom_field_clobvalue');

// Drop compound primary key
if(isset($columns['field_id']) && isset($columns['source_id'])
	&& 'PRI'==$columns['field_id']['key'] && 'PRI'==$columns['source_id']['key']) {
		$db->Execute("ALTER TABLE custom_field_clobvalue DROP PRIMARY KEY");
}

if(!isset($indexes['field_id'])) {
	$db->Execute('ALTER TABLE custom_field_clobvalue ADD INDEX field_id (field_id)');
}

if(!isset($indexes['source_id'])) {
	$db->Execute('ALTER TABLE custom_field_clobvalue ADD INDEX source_id (source_id)');
}

// Phase out bucket 'response_hrs'
list($columns, $indexes) = $db->metaTable('category');

if(isset($columns['response_hrs'])) {
	$db->Execute("ALTER TABLE category DROP COLUMN response_hrs");
}

if(!isset($columns['is_assignable'])) {
	$db->Execute('ALTER TABLE category ADD COLUMN is_assignable TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
    
    // Set default to make everything assignable (like pre 4.1).  Managers can tweak.
    $sql = "UPDATE category SET is_assignable=1";
    $db->Execute($sql);
}

if(!isset($columns['pos'])) {
	$db->Execute('ALTER TABLE category ADD COLUMN pos TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

// Drop some deprecated worker_pref keys
if(isset($tables['worker_pref'])) {
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'overview_assign_type' ");
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'overview_assign_howmany' ");
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'worker_overview_filter' ");
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'move_counts' ");
}

// ===========================================================================
// Ophaned org notes
$db->Execute("DELETE QUICK note FROM note LEFT JOIN contact_org ON (contact_org.id=note.source_id) WHERE note.source_extension_id = 'cerberusweb.notes.source.org' AND contact_org.id IS NULL");

// ===========================================================================
// Ophaned address custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN address ON (address.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'cerberusweb.fields.source.address' AND address.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN address ON (address.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'cerberusweb.fields.source.address' AND address.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN address ON (address.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'cerberusweb.fields.source.address' AND address.id IS NULL");

// ===========================================================================
// Ophaned org custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN contact_org ON (contact_org.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'cerberusweb.fields.source.org' AND contact_org.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN contact_org ON (contact_org.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'cerberusweb.fields.source.org' AND contact_org.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN contact_org ON (contact_org.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'cerberusweb.fields.source.org' AND contact_org.id IS NULL");

// ===========================================================================
// Ophaned task custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN task ON (task.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'cerberusweb.fields.source.task' AND task.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN task ON (task.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'cerberusweb.fields.source.task' AND task.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN task ON (task.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'cerberusweb.fields.source.task' AND task.id IS NULL");

// ===========================================================================
// Ophaned ticket custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN ticket ON (ticket.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN ticket ON (ticket.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN ticket ON (ticket.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");

// ===========================================================================
// Migrate team_routing_rule to group_inbox_filter

if(isset($tables['team_routing_rule']) && !isset($tables['group_inbox_filter'])) {
	$sql = "RENAME TABLE team_routing_rule TO group_inbox_filter";
	$db->Execute($sql) or die($db->ErrorMsg());
	
	unset($tables['team_routing_rule']);
	$tables['group_inbox_filter'] = true;
}

list($columns, $indexes) = $db->metaTable('group_inbox_filter');

if(isset($columns['team_id']) && !isset($columns['group_id'])) {
	$db->Execute('ALTER TABLE group_inbox_filter CHANGE COLUMN team_id group_id INT UNSIGNED DEFAULT 0 NOT NULL');
}

// Add a field for serializing action values, so we can include custom fields
if(!isset($columns['actions_ser'])) {
	$db->Execute('ALTER TABLE group_inbox_filter ADD COLUMN actions_ser MEDIUMTEXT');
    
    // Move the hardcoded fields into the new format
    if(isset($columns['do_assign'])) {
    	// Hash buckets
    	$buckets = array();
    	$sql = sprintf("SELECT id,name,team_id FROM category");
    	$rs = $db->Execute($sql);
    	while($row = mysql_fetch_assoc($rs)) {
    		$buckets[intval($row['id'])] = array(
    			'name' => $row['name'],
    			'group_id' => intval($row['team_id'])
    		);
    	}
    	
    	mysql_free_result($rs);
    	
    	// Loop through the old style values
    	$sql = "SELECT id, do_assign, do_move, do_spam, do_status FROM group_inbox_filter";
    	$rs = $db->Execute($sql);
    	
    	while($row = mysql_fetch_assoc($rs)) {
    		$actions = array();
    		
    		$rule_id = intval($row['id']);
    		$do_assign = intval($row['do_assign']);
    		$do_move = $row['do_move'];
    		$do_spam = $row['do_spam'];
    		$do_status = intval($row['do_status']);
    		
    		if(!empty($do_assign)) // counts 0 or ''
    			$actions['assign'] = array('worker_id' => $do_assign);
    		if(0 != strlen($do_move)) {
    			$group_id = 0;
    			$bucket_id = 0;
    			if('t'==substr($do_move,0,1))
    				$group_id = intval(substr($do_move,1));
    			if('c'==substr($do_move,0,1)) {
    				$bucket_id = intval(substr($do_move,1));
    				$group_id = intval($buckets[$bucket_id]['group_id']);
    			}
    			
    			if(!empty($group_id))
    				$actions['move'] = array('group_id' => $group_id, 'bucket_id' => $bucket_id);
    		}
    		if(0 != strlen($do_spam))
    			$actions['spam'] = array('is_spam' => ('N'==$do_spam?0:1));
    		if(0 != strlen($do_status))
    			$actions['status'] = array('is_closed' => (0==$do_status?0:1), 'is_deleted' => (2==$do_status?1:0));
    		
    		$sql = sprintf("UPDATE group_inbox_filter SET actions_ser = %s WHERE id = %d",
    			$db->qstr(serialize($actions)),
    			$rule_id
    		);
    		$db->Execute($sql);
    	}
    	
    	mysql_free_result($rs);
    	
    	unset($buckets);
    }
}

if(isset($columns['do_assign'])) {
	$db->Execute("ALTER TABLE group_inbox_filter DROP COLUMN do_assign");
}

if(isset($columns['do_move'])) {
	$db->Execute("ALTER TABLE group_inbox_filter DROP COLUMN do_move");
}

if(isset($columns['do_spam'])) {
	$db->Execute("ALTER TABLE group_inbox_filter DROP COLUMN do_spam");
}

if(isset($columns['do_status'])) {
	$db->Execute("ALTER TABLE group_inbox_filter DROP COLUMN do_status");
}

// ===========================================================================
// Drop the unused dashboard, dashboard_view, and dashboard_view_action tables 

if(isset($tables['dashboard'])) {
	$db->Execute('DROP TABLE dashboard');
}

if(isset($tables['dashboard_view'])) {
	$db->Execute('DROP TABLE dashboard_view');
}

if(isset($tables['dashboard_view_action'])) {
	$db->Execute('DROP TABLE dashboard_view_action');
}

// ===========================================================================
// Add sticky bit to group inbox filters 

list($columns, $indexes) = $db->metaTable('group_inbox_filter');

if(!isset($columns['is_sticky'])) {
    $db->Execute('ALTER TABLE group_inbox_filter ADD COLUMN is_sticky TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['sticky_order'])) {
    $db->Execute('ALTER TABLE group_inbox_filter ADD COLUMN sticky_order TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['is_stackable'])) {
    $db->Execute('ALTER TABLE group_inbox_filter ADD COLUMN is_stackable TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

// ===========================================================================
// Add extensions to workspaces so workers can combine worklist types 

list($columns, $indexes) = $db->metaTable('worker_workspace_list');

if(!isset($columns['source_extension'])) {
    $db->Execute("ALTER TABLE worker_workspace_list ADD COLUMN source_extension VARCHAR(255) DEFAULT '' NOT NULL");
    $db->Execute("UPDATE worker_workspace_list SET source_extension='core.workspace.source.ticket' WHERE source_extension = ''");
}

// ===========================================================================
// Migrate contact_org.fax to a custom field (optional field)
if(isset($tables['contact_org'])) {
	list($columns, $indexes) = $db->metaTable('contact_org');

	if(isset($columns['fax'])) {
		$sql = "SELECT count(id) FROM contact_org WHERE fax != ''";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
				"VALUES ('Fax','S',0,0,'',%s)",
				$db->qstr('cerberusweb.fields.source.org')
			);
			$db->Execute($sql);
			$field_id = $db->LastInsertId();
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, o.id, o.fax, %s FROM contact_org o WHERE o.fax != ''",
				$field_id,
				$db->qstr('cerberusweb.fields.source.org')
			);
			$db->Execute($sql);
		}
		
		$db->Execute("ALTER TABLE contact_org DROP COLUMN fax");
	}
}

// ===========================================================================
// Migrate ticket.next_action to a custom field (optional field)
if(isset($tables['ticket'])) {
	list($columns, $indexes) = $db->metaTable('ticket');

	if(isset($columns['next_action'])) {
		$sql = "SELECT count(id) FROM ticket WHERE next_action != ''";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
				"VALUES ('Next Action','S',0,0,'',%s)",
				$db->qstr('cerberusweb.fields.source.ticket')
			);
			$db->Execute($sql);
			$field_id = $db->LastInsertId();
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, t.id, t.next_action, %s FROM ticket t WHERE t.next_action != ''",
				$field_id,
				$db->qstr('cerberusweb.fields.source.ticket')
			);
			$db->Execute($sql);
		}
		
		$db->Execute("ALTER TABLE ticket DROP COLUMN next_action");
	}
}

// ===========================================================================
// Migrate task.priority to a custom field (optional field)
if(isset($tables['task'])) {
	list($columns, $indexes) = $db->metaTable('task');

	if(isset($columns['priority'])) {
		$priority_hash = array(
			'1' => 'High', 
			'2' => 'Normal', 
			'3' => 'Low', 
		);
		
		$sql = "SELECT count(id) FROM task WHERE priority IN (1,2,3)";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
				"VALUES ('Priority','D',0,0,%s,%s)",
				$db->qstr(implode("\n", $priority_hash)),
				$db->qstr('cerberusweb.fields.source.task')
			);
			$db->Execute($sql);
			$field_id = $db->LastInsertId();
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, t.id, IF(1=t.priority,'High',IF(2=t.priority,'Normal',IF(3=t.priority,'Low',''))), %s FROM task t WHERE t.priority IN (1,2,3)",
				$field_id,
				$db->qstr('cerberusweb.fields.source.task')
			);
			$db->Execute($sql);
		}
		
		$db->Execute("ALTER TABLE task DROP COLUMN priority");
	}
}

// ===========================================================================
// Change 'setting' values to X (text) rather than B (blob)

if(isset($tables['setting'])) {
	list($columns, $indexes) = $db->metaTable('setting');
	
	if(isset($columns['value'])) {
		if(0 != strcasecmp('mediumtext',$columns['value']['type'])) {
			$db->Execute("ALTER TABLE setting CHANGE COLUMN `value` `value` MEDIUMTEXT");
		}
	}
}

// ===========================================================================
// Port Setting.DEFAULT_TEAM_ID to the group ('team') table as a bit, that way
// it's much harder for us to use an invalid value while parsing, etc.

if(isset($tables['team'])) {
	list($columns, $indexes) = $db->metaTable('team');
	
	if(!isset($columns['is_default'])) {
		$db->Execute('ALTER TABLE team ADD COLUMN is_default TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
    	
    	// Set the default group based on the old setting
		$sql = "SELECT value FROM setting WHERE setting = 'default_team_id'";
		$default_team_id = $db->GetOne($sql);
		
		if(!empty($default_team_id)) {
			$db->Execute(sprintf("UPDATE team SET is_default=1 WHERE id = %d", $default_team_id));
		}
		
		$db->Execute("DELETE FROM setting WHERE setting = 'default_team_id'");
	}
}

// ===========================================================================
// Add worker roles for ACL

// Roles
if(!isset($tables['worker_role'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_role (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// n:m table for linking workers to roles
if(!isset($tables['worker_to_role'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_to_role (
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			role_id INT UNSIGNED DEFAULT 0 NOT NULL
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('worker_to_role');

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE worker_to_role ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['role_id'])) {
	$db->Execute('ALTER TABLE worker_to_role ADD INDEX role_id (role_id)');
}

// n:m table for linking roles to ACL
if(!isset($tables['worker_role_acl'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_role_acl (
			role_id INT UNSIGNED DEFAULT 0 NOT NULL,
			priv_id VARCHAR(255) DEFAULT '' NOT NULL,
			has_priv INT UNSIGNED DEFAULT 0 NOT NULL
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('worker_role_acl');

if(!isset($indexes['role_id'])) {
	$db->Execute('ALTER TABLE worker_role_acl ADD INDEX role_id (role_id)');
}

if(!isset($indexes['has_priv'])) {
	$db->Execute('ALTER TABLE worker_role_acl ADD INDEX has_priv (has_priv)');
}

// ===========================================================================
// New style licenses
$obj = unserialize($db->GetOne("SELECT value FROM setting WHERE setting='license'"));
if(!empty($obj) && isset($obj['features'])) {
$l = array('name' => $obj['name'],'email' => '','serial' => '** Contact support@webgroupmedia.com for your new 4.1 serial number **');
(isset($obj['users'])&&!empty($obj['users'])?(($l['users']=$obj['users'])&&($l['a']='XXX')):($l['e']='XXX'));
$db->Execute("DELETE FROM setting WHERE setting='license'");
$db->Execute(sprintf("INSERT INTO setting (setting,value) VALUES ('license',%s)",$db->qstr(serialize($l))));
$db->Execute("DELETE FROM setting WHERE setting='company'");
$db->Execute("DELETE FROM setting WHERE setting='patch'");
}

// ===========================================================================
// Drop worker.can_delete and worker.can_export privilege (real ACL now)
list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['can_export'])) {
    $db->Execute("ALTER TABLE worker DROP COLUMN can_export");
}

if(isset($columns['can_delete'])) {
    $db->Execute("ALTER TABLE worker DROP COLUMN can_delete");
}

return TRUE;
