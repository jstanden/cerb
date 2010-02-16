<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Migrate the campaigns to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');

	if(isset($columns['campaign_id'])) {
		// Load the campaign hash
		$campaigns = array();
		$sql = "SELECT id, name FROM crm_campaign ORDER BY name";
		$rs = $db->Execute($sql);
		while($row = mysql_fetch_assoc($rs)) {
			$campaigns[$row['id']] = $row['name'];
		}
		
		mysql_free_result($rs);
	
		if(!empty($campaigns)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'Campaign','D',0,0,%s,%s)",
				$field_id,
				$db->qstr(implode("\n",$campaigns)),
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, o.id, c.name, %s FROM crm_opportunity o INNER JOIN crm_campaign c ON (o.campaign_id=c.id)",
				$field_id,
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->Execute($sql);
		}
	}
}

// ===========================================================================
// Migrate the opportunity lead source to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');
	
	$count = $db->Execute("SELECT count(id) FROM crm_opportunity WHERE source != ''");
	
	if(isset($columns['source']) && $count) {
		// Create the new custom field
		$field_id = $db->GenID('custom_field_seq');
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Lead Source','S',0,0,'',%s)",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.source, %s FROM crm_opportunity o WHERE o.source != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Migrate the opportunity.next_action to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');

	$count = $db->Execute("SELECT count(id) FROM crm_opportunity WHERE next_action != ''");
	
	if(isset($columns['next_action']) && $count) {
		// Create the new custom field
		$field_id = $db->GenID('custom_field_seq');
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Next Action','S',0,0,'',%s)",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.next_action, %s FROM crm_opportunity o WHERE o.next_action != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Migrate the opportunity comments to platform 'notes' service
if(isset($tables['crm_opp_comment'])) {
	$sql = "SELECT id, opportunity_id, created_date, worker_id, content FROM crm_opp_comment";
	$rs = $db->Execute($sql);
	while($row = mysql_fetch_assoc($rs)) {
		$note_id = $db->GenID('note_seq');
		$sql = sprintf("INSERT INTO note (id, source_extension_id, source_id, created, worker_id, content) ".
			"VALUES (%d,'%s',%d,%d,%d,%s)",
			$note_id,
			'crm.notes.source.opportunity',
			$row['opportunity_id'],
			$row['created_date'],
			$row['worker_id'],
			$db->qstr($row['content'])
		);
		$db->Execute($sql); // insert
	}
	
	mysql_free_result($rs);
}

// ===========================================================================
// Drop the opp fields we no longer want (optimized out by custom fields)
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');
	
	if(isset($columns['campaign_id'])) {
		$db->Execute('ALTER TABLE crm_opportunity DROP COLUMN campaign_id');
	}

	if(isset($columns['campaign_bucket_id'])) {
		$db->Execute('ALTER TABLE crm_opportunity DROP COLUMN campaign_bucket_id');
	}

	if(isset($columns['source'])) {
		$db->Execute('ALTER TABLE crm_opportunity DROP COLUMN source');
	}
	
	if(isset($columns['next_action'])) {
		$db->Execute('ALTER TABLE crm_opportunity DROP COLUMN next_action');
	}
	
}

// ===========================================================================
// Drop the campaign table (optimized out by custom fields)
if(isset($tables['crm_campaign'])) {
	$db->Execute('DROP TABLE crm_campaign');
}

// ===========================================================================
// Drop the campaign buckets (optimized out by custom fields)
if(isset($tables['crm_campaign_bucket'])) {
	$db->Execute('DROP TABLE crm_campaign_bucket');
}

// ===========================================================================
// Drop the old CRM comments table in favor of the new notes functionality
if(isset($tables['crm_opp_comment'])) {
	$db->Execute('DROP TABLE crm_opp_comment');
}

// ===========================================================================
// Add 'amount' column to opportunities (makes reports easier if it's not cfield)
list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(!isset($columns['amount'])) {
	$db->Execute('ALTER TABLE crm_opportunity ADD COLUMN amount DECIMAL(8,2) DEFAULT 0 NOT NULL');
}

if(!isset($indexes['amount'])) {
	$db->Execute('ALTER TABLE crm_opportunity ADD INDEX amount (amount)');
}

// ===========================================================================
// Ophaned opportunity notes
$db->Execute("DELETE QUICK note FROM note LEFT JOIN crm_opportunity ON (crm_opportunity.id=note.source_id) WHERE note.source_extension_id = 'crm.notes.source.opportunity' AND crm_opportunity.id IS NULL");

// ===========================================================================
// Ophaned opportunity custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN crm_opportunity ON (crm_opportunity.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'crm.fields.source.opportunity' AND crm_opportunity.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN crm_opportunity ON (crm_opportunity.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'crm.fields.source.opportunity' AND crm_opportunity.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN crm_opportunity ON (crm_opportunity.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'crm.fields.source.opportunity' AND crm_opportunity.id IS NULL");

return TRUE;