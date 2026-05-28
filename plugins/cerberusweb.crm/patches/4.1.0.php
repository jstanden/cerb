<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Migrate the campaigns to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');

	if(isset($columns['campaign_id'])) {
		// Load the campaign hash
		$campaigns = array();
		$sql = "SELECT id, name FROM crm_campaign ORDER BY name";
		$rs = $db->ExecuteMaster($sql);
		while($row = mysqli_fetch_assoc($rs)) {
			$campaigns[$row['id']] = $row['name'];
		}
		
		mysqli_free_result($rs);
	
		if(!empty($campaigns)) { // Move to a custom field before dropping
			// Create the new custom field
			$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
				"VALUES ('Campaign','D',0,0,%s,%s)",
				$db->qstr(implode("\n",$campaigns)),
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->ExecuteMaster($sql);
			$field_id = $db->LastInsertId();
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, o.id, c.name, %s FROM crm_opportunity o INNER JOIN crm_campaign c ON (o.campaign_id=c.id)",
				$field_id,
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->ExecuteMaster($sql);
		}
	}
}

// ===========================================================================
// Migrate the opportunity lead source to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');
	
	$count = $db->GetOneMaster("SELECT count(id) FROM crm_opportunity WHERE source != ''");
	
	if(isset($columns['source']) && $count) {
		// Create the new custom field
		$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
			"VALUES ('Lead Source','S',0,0,'',%s)",
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->ExecuteMaster($sql);
		$field_id = $db->LastInsertId();
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.source, %s FROM crm_opportunity o WHERE o.source != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Migrate the opportunity.next_action to a custom field
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');

	$count = $db->GetOneMaster("SELECT count(id) FROM crm_opportunity WHERE next_action != ''");
	
	if(isset($columns['next_action']) && $count) {
		// Create the new custom field
		$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
			"VALUES ('Next Action','S',0,0,'',%s)",
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->ExecuteMaster($sql);
		$field_id = $db->LastInsertId();
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.next_action, %s FROM crm_opportunity o WHERE o.next_action != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->ExecuteMaster($sql);
	}
}

// ===========================================================================
// Migrate the opportunity comments to platform 'notes' service
if(isset($tables['crm_opp_comment'])) {
	$sql = "SELECT id, opportunity_id, created_date, worker_id, content FROM crm_opp_comment";
	$rs = $db->ExecuteMaster($sql);
	while($row = mysqli_fetch_assoc($rs)) {
		$sql = sprintf("INSERT INTO comment (context, context_id, created, author_context, author_id, comment) ".
			"VALUES (%s,%d,%d,%s,%d,%s)",
			$db->qstr('cerberusweb.contexts.opportunity'),
			$row['opportunity_id'],
			$row['created_date'],
			$db->qstr('cerberusweb.contexts.worker'),
			$row['worker_id'],
			$db->qstr($row['content'])
		);
		$db->ExecuteMaster($sql); // insert
	}
	
	mysqli_free_result($rs);
}

// ===========================================================================
// Drop the opp fields we no longer want (optimized out by custom fields)
if(isset($tables['crm_opportunity'])) {
	list($columns, $indexes) = $db->metaTable('crm_opportunity');
	
	if(isset($columns['campaign_id'])) {
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN campaign_id');
	}

	if(isset($columns['campaign_bucket_id'])) {
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN campaign_bucket_id');
	}

	if(isset($columns['source'])) {
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN source');
	}
	
	if(isset($columns['next_action'])) {
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN next_action');
	}
	
}

// ===========================================================================
// Drop the campaign table (optimized out by custom fields)
if(isset($tables['crm_campaign'])) {
	$db->ExecuteMaster('DROP TABLE crm_campaign');
}

// ===========================================================================
// Drop the campaign buckets (optimized out by custom fields)
if(isset($tables['crm_campaign_bucket'])) {
	$db->ExecuteMaster('DROP TABLE crm_campaign_bucket');
}

// ===========================================================================
// Drop the old CRM comments table in favor of the new notes functionality
if(isset($tables['crm_opp_comment'])) {
	$db->ExecuteMaster('DROP TABLE crm_opp_comment');
}

// ===========================================================================
// Add 'amount' column to opportunities (makes reports easier if it's not cfield)
list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(!isset($columns['amount'])) {
	$db->ExecuteMaster('ALTER TABLE crm_opportunity ADD COLUMN amount DECIMAL(8,2) DEFAULT 0 NOT NULL');
}

if(!isset($indexes['amount'])) {
	$db->ExecuteMaster('ALTER TABLE crm_opportunity ADD INDEX amount (amount)');
}

return TRUE;