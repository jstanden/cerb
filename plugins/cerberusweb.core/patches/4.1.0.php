<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// Drop the Service Level fields on address
$columns = $datadict->MetaColumns('address');
$indexes = $datadict->MetaIndexes('address',false);

if(isset($columns['SLA_ID'])) {
	$sql = $datadict->DropColumnSQL('address','sla_id');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($columns['SLA_EXPIRES'])) {
	$sql = $datadict->DropColumnSQL('address','sla_expires');
	$datadict->ExecuteSQLArray($sql);
}

// Drop the Service Level expires field on contact_org (later sla_id is migrated and dropped)
$columns = $datadict->MetaColumns('contact_org');
$indexes = $datadict->MetaIndexes('contact_org',false);

if(isset($columns['SLA_EXPIRES'])) {
	$sql = $datadict->DropColumnSQL('contact_org','sla_expires');
	$datadict->ExecuteSQLArray($sql);
}

// Drop the Service Level fields on tickets
$columns = $datadict->MetaColumns('ticket');
$indexes = $datadict->MetaIndexes('ticket',false);

if(isset($columns['SLA_ID'])) {
	$sql = $datadict->DropColumnSQL('ticket','sla_id');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($columns['SLA_PRIORITY'])) {
	$sql = $datadict->DropColumnSQL('ticket','sla_priority');
	$datadict->ExecuteSQLArray($sql);
}

// Migrate contact_org.sla_id to a custom field dropdown
$columns = $datadict->MetaColumns('contact_org');
$indexes = $datadict->MetaIndexes('contact_org',false);

if(isset($columns['SLA_ID'])) {
	$sql = "SELECT count(id) FROM contact_org WHERE sla_id != ''";
	$count = $db->GetOne($sql);
	
	// Load the SLA hash
	$slas = array();
	if(isset($tables['sla'])) {
		$sql = "SELECT id, name FROM sla ORDER BY name";
		$rs = $db->Execute($sql);
		
		while(!$rs->EOF) {
			$slas[$rs->fields['id']] = $rs->fields['name'];
			$rs->MoveNext();
		}
	}
	
	if(!empty($count) && !empty($slas)) { // Move to a custom field before dropping
		// Create the new custom field
		$field_id = $db->GenID('custom_field_seq');
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Service Level','D',0,0,%s,%s)",
			$field_id,
			$db->qstr(implode("\n",$slas)),
			$db->qstr('cerberusweb.fields.source.org')
		);
		$db->Execute($sql);
		
		// Populate the custom field from org records
		$sql = sprintf("INSERT INTO custom_field_value (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, s.name, %s FROM contact_org o INNER JOIN sla s ON (o.sla_id=s.id)",
			$field_id,
			$db->qstr('cerberusweb.fields.source.org')
		);
		$db->Execute($sql);
	}
	
	// Drop the account number hardcoded column
	$sql = $datadict->DropColumnSQL('contact_org','sla_id');
	$datadict->ExecuteSQLArray($sql);
}

// Drop the SLA table
if(isset($tables['sla'])) {
	$sql = $datadict->DropTableSQL('sla');
	$datadict->ExecuteSQLArray($sql);
}

// Migrate custom field values to data-type dependent tables
if(isset($tables['custom_field_value'])) {
	
	// Custom field number values: (C) Checkbox, (E) Date
	if(!isset($tables['custom_field_numbervalue'])) {
	    $flds = "
			field_id I4 DEFAULT 0 NOTNULL PRIMARY,
			source_id I4 DEFAULT 0 NOTNULL PRIMARY,
			field_value I4 DEFAULT 0 NOTNULL,
			source_extension C(255) DEFAULT '' NOTNULL
		";
	    $sql = $datadict->CreateTableSQL('custom_field_numbervalue',$flds);
	    $res = $datadict->ExecuteSQLArray($sql);
		
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
	    $flds = "
			field_id I4 DEFAULT 0 NOTNULL PRIMARY,
			source_id I4 DEFAULT 0 NOTNULL PRIMARY,
			field_value C(255) DEFAULT '' NOTNULL,
			source_extension C(255) DEFAULT '' NOTNULL
		";
	    $sql = $datadict->CreateTableSQL('custom_field_stringvalue',$flds);
	    $res = $datadict->ExecuteSQLArray($sql);
		
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
	    $flds = "
			field_id I4 DEFAULT 0 NOTNULL PRIMARY,
			source_id I4 DEFAULT 0 NOTNULL PRIMARY,
			field_value XL,
			source_extension C(255) DEFAULT '' NOTNULL
		";
	    $sql = $datadict->CreateTableSQL('custom_field_clobvalue',$flds);
	    $res = $datadict->ExecuteSQLArray($sql);
		
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
		$sql = $datadict->DropTableSQL('custom_field_value');
		$datadict->ExecuteSQLArray($sql);
	}
}

// Add a merge table to track when older ticket masks should point to a new ticket
if(!isset($tables['ticket_mask_forward'])) {
	$flds ="
		old_mask C(32) DEFAULT '' NOTNULL PRIMARY,
		new_mask C(32) DEFAULT '' NOTNULL,
		new_ticket_id I4 DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('ticket_mask_forward', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('ticket_mask_forward');
$indexes = $datadict->MetaIndexes('ticket_mask_forward',false);

if(!isset($indexes['new_ticket_id'])) {
	$sql = $datadict->CreateIndexSQL('new_ticket_id','ticket_mask_forward','new_ticket_id');
	$datadict->ExecuteSQLArray($sql);
}

// Drop primary compound key on custom_field_stringvalue so we can have multi-select dropdowns

$columns = $datadict->MetaColumns('custom_field_stringvalue');
$indexes = $datadict->MetaIndexes('custom_field_stringvalue',false);

// Drop compound primary key
if(isset($columns['FIELD_ID']) && isset($columns['SOURCE_ID'])
	&& $columns['FIELD_ID']->primary_key && $columns['SOURCE_ID']->primary_key) {
		$sql = array("ALTER TABLE custom_field_stringvalue DROP PRIMARY KEY");
		$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_stringvalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_stringvalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}

// Drop primary compound key on custom_field_numbervalue so we can have multi-select checkboxes

$columns = $datadict->MetaColumns('custom_field_numbervalue');
$indexes = $datadict->MetaIndexes('custom_field_numbervalue',false);

// Drop compound primary key
if(isset($columns['FIELD_ID']) && isset($columns['SOURCE_ID'])
	&& $columns['FIELD_ID']->primary_key && $columns['SOURCE_ID']->primary_key) {
		$sql = array("ALTER TABLE custom_field_numbervalue DROP PRIMARY KEY");
		$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_numbervalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_numbervalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}

// Drop primary compound key on custom_field_clobvalue

$columns = $datadict->MetaColumns('custom_field_clobvalue');
$indexes = $datadict->MetaIndexes('custom_field_clobvalue',false);

// Drop compound primary key
if(isset($columns['FIELD_ID']) && isset($columns['SOURCE_ID'])
	&& $columns['FIELD_ID']->primary_key && $columns['SOURCE_ID']->primary_key) {
		$sql = array("ALTER TABLE custom_field_clobvalue DROP PRIMARY KEY");
		$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_clobvalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_clobvalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}

// Phase out bucket 'response_hrs'
$columns = $datadict->MetaColumns('category');
$indexes = $datadict->MetaIndexes('category',false);

if(isset($columns['RESPONSE_HRS'])) {
	$sql = $datadict->DropColumnSQL('category','response_hrs');
    $datadict->ExecuteSQLArray($sql);
}

// Add bucket 'is_assignable' for new group workflow
$columns = $datadict->MetaColumns('category');
$indexes = $datadict->MetaIndexes('category',false);

if(!isset($columns['IS_ASSIGNABLE'])) {
	$sql = $datadict->AddColumnSQL('category','is_assignable I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
    
    // Set default to make everything assignable (like pre 4.1).  Managers can tweak.
    $sql = "UPDATE category SET is_assignable=1";
    $db->Execute($sql);
}

// Add bucket 'pos' for ordering buckets by importance
$columns = $datadict->MetaColumns('category');
$indexes = $datadict->MetaIndexes('category',false);

if(!isset($columns['POS'])) {
	$sql = $datadict->AddColumnSQL('category','pos I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
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
// Add worker.can_export privilege (temporary, before true ACL)
$columns = $datadict->MetaColumns('worker');
$indexes = $datadict->MetaIndexes('worker',false);

if(!isset($columns['CAN_EXPORT'])) {
    $sql = $datadict->AddColumnSQL('worker', 'can_export I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Migrate team_routing_rule to group_inbox_filter

if(isset($tables['team_routing_rule']) && !isset($tables['group_inbox_filter'])) {
	$sql = "RENAME TABLE team_routing_rule TO group_inbox_filter";
	$db->Execute($sql) or die($db->ErrorMsg());
	
	unset($tables['team_routing_rule']);
	$tables['group_inbox_filter'] = true;
}

$columns = $datadict->MetaColumns('group_inbox_filter');

if(isset($columns['TEAM_ID']) && !isset($columns['GROUP_ID'])) {
	$datadict->ExecuteSQLArray($datadict->RenameColumnSQL('group_inbox_filter', 'team_id', 'group_id',"group_id I4 DEFAULT 0 NOTNULL"));
}

$columns = $datadict->MetaColumns('group_inbox_filter');

// Add a field for serializing action values, so we can include custom fields
if(!isset($columns['ACTIONS_SER'])) {
	$sql = $datadict->AddColumnSQL('group_inbox_filter','actions_ser XL');
    $datadict->ExecuteSQLArray($sql);
    
    // Move the hardcoded fields into the new format
    if(isset($columns['DO_ASSIGN'])) {
    	// Hash buckets
    	$buckets = array();
    	$sql = sprintf("SELECT id,name,team_id FROM category");
    	$rs = $db->Execute($sql);
    	while(!$rs->EOF) {
    		$buckets[intval($rs->fields['id'])] = array(
    			'name' => $rs->fields['name'],
    			'group_id' => intval($rs->fields['team_id'])
    		);
    		$rs->MoveNext();
    	}
    	
    	// Loop through the old style values
    	$sql = "SELECT id, do_assign, do_move, do_spam, do_status FROM group_inbox_filter";
    	$rs = $db->Execute($sql);
    	
    	$actions = array();
    	
    	while(!$rs->EOF) {
    		$rule_id = intval($rs->fields['id']);
    		$do_assign = intval($rs->fields['do_assign']);
    		$do_move = $rs->fields['do_move'];
    		$do_spam = $rs->fields['do_spam'];
    		$do_status = intval($rs->fields['do_status']);
    		
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
    		if(!empty($do_status)) // only 1|2 == closed|deleted
    			$actions['status'] = array('is_closed' => (0==$do_status?0:1), 'is_deleted' => (2==$do_status?1:0));
    		
    		$sql = sprintf("UPDATE group_inbox_filter SET actions_ser = %s WHERE id = %d",
    			$db->qstr(serialize($actions)),
    			$rule_id
    		);
    		$db->Execute($sql);
    			
    		$rs->MoveNext();
    	}
    	
    	unset($buckets);
    }
}

if(isset($columns['DO_ASSIGN'])) {
	$sql = $datadict->DropColumnSQL('group_inbox_filter','do_assign');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['DO_MOVE'])) {
	$sql = $datadict->DropColumnSQL('group_inbox_filter','do_move');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['DO_SPAM'])) {
	$sql = $datadict->DropColumnSQL('group_inbox_filter','do_spam');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['DO_STATUS'])) {
	$sql = $datadict->DropColumnSQL('group_inbox_filter','do_status');
    $datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Drop the unused dashboard, dashboard_view, and dashboard_view_action tables 

if(isset($tables['dashboard'])) {
	$sql = $datadict->DropTableSQL('dashboard');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($tables['dashboard_view'])) {
	$sql = $datadict->DropTableSQL('dashboard_view');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($tables['dashboard_view_action'])) {
	$sql = $datadict->DropTableSQL('dashboard_view_action');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Add sticky bit to group inbox filters 

$columns = $datadict->MetaColumns('group_inbox_filter');
$indexes = $datadict->MetaIndexes('group_inbox_filter',false);

if(!isset($columns['IS_STICKY'])) {
    $sql = $datadict->AddColumnSQL('group_inbox_filter', 'is_sticky I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['STICKY_ORDER'])) {
    $sql = $datadict->AddColumnSQL('group_inbox_filter', 'sticky_order I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['IS_STACKABLE'])) {
    $sql = $datadict->AddColumnSQL('group_inbox_filter', 'is_stackable I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Add extensions to workspaces so workers can combine worklist types 

$columns = $datadict->MetaColumns('worker_workspace_list');
$indexes = $datadict->MetaIndexes('worker_workspace_list',false);

if(!isset($columns['SOURCE_EXTENSION'])) {
    $sql = $datadict->AddColumnSQL('worker_workspace_list', "source_extension C(255) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
    
    $db->Execute("UPDATE worker_workspace_list SET source_extension='core.workspace.source.ticket' WHERE source_extension = ''");
}

// ===========================================================================
// Migrate contact_org.fax to a custom field (optional field)
if(isset($tables['contact_org'])) {
	$columns = $datadict->MetaColumns('contact_org');
	$indexes = $datadict->MetaIndexes('contact_org',false);

	if(isset($columns['FAX'])) {
		$sql = "SELECT count(id) FROM contact_org WHERE fax != ''";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'Fax','S',0,0,'',%s)",
				$field_id,
				$db->qstr('cerberusweb.fields.source.org')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, o.id, o.fax, %s FROM contact_org o WHERE o.fax != ''",
				$field_id,
				$db->qstr('cerberusweb.fields.source.org')
			);
			$db->Execute($sql);
		}
		
		$sql = $datadict->DropColumnSQL('contact_org','fax');
	    $datadict->ExecuteSQLArray($sql);
	}
}

// ===========================================================================
// Migrate ticket.next_action to a custom field (optional field)
if(isset($tables['ticket'])) {
	$columns = $datadict->MetaColumns('ticket');
	$indexes = $datadict->MetaIndexes('ticket',false);

	if(isset($columns['NEXT_ACTION'])) {
		$sql = "SELECT count(id) FROM ticket WHERE next_action != ''";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'Next Action','S',0,0,'',%s)",
				$field_id,
				$db->qstr('cerberusweb.fields.source.ticket')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, t.id, t.next_action, %s FROM ticket t WHERE t.next_action != ''",
				$field_id,
				$db->qstr('cerberusweb.fields.source.ticket')
			);
			$db->Execute($sql);
		}
		
		$sql = $datadict->DropColumnSQL('ticket','next_action');
	    $datadict->ExecuteSQLArray($sql);
	}
}

// ===========================================================================
// Migrate task.priority to a custom field (optional field)
if(isset($tables['task'])) {
	$columns = $datadict->MetaColumns('task');
	$indexes = $datadict->MetaIndexes('task',false);

	if(isset($columns['PRIORITY'])) {
		$priority_hash = array(
			'1' => 'High', 
			'2' => 'Normal', 
			'3' => 'Low', 
		);
		
		$sql = "SELECT count(id) FROM task WHERE priority IN (1,2,3)";
		$count = $db->GetOne($sql);
	
		if(!empty($count)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'Priority','D',0,0,%s,%s)",
				$field_id,
				$db->qstr(implode("\n", $priority_hash)),
				$db->qstr('cerberusweb.fields.source.task')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, t.id, IF(1=t.priority,'High',IF(2=t.priority,'Normal',IF(3=t.priority,'Low',''))), %s FROM task t WHERE t.priority IN (1,2,3)",
				$field_id,
				$db->qstr('cerberusweb.fields.source.task')
			);
			$db->Execute($sql);
		}
		
		$sql = $datadict->DropColumnSQL('task','priority');
	    $datadict->ExecuteSQLArray($sql);
	}
}

return TRUE;
?>