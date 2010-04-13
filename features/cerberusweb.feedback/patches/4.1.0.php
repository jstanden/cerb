<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `feedback_entry` ========================
if(!isset($tables['feedback_entry'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS feedback_entry (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			log_date INT UNSIGNED DEFAULT 0 NOT NULL,
			list_id INT UNSIGNED DEFAULT 0 NOT NULL, 
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL, 
			quote_text TEXT,
			quote_mood TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL, 
			quote_address_id INT UNSIGNED DEFAULT 0 NOT NULL, 
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);
	
	$tables['feedback_entry'] = 'feedback_entry';
}

list($columns, $indexes) = $db->metaTable('feedback_entry');

if(!isset($columns['source_url'])) {
	$db->Execute("ALTER TABLE feedback_entry ADD COLUMN source_url VARCHAR(255) DEFAULT '' NOT NULL");
}

if(!isset($indexes['log_date'])) {
	$db->Execute('ALTER TABLE feedback_entry ADD INDEX log_date (log_date)');
}
if(!isset($indexes['list_id'])) {
	$db->Execute('ALTER TABLE feedback_entry ADD INDEX list_id (list_id)');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE feedback_entry ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['quote_address_id'])) {
	$db->Execute('ALTER TABLE feedback_entry ADD INDEX quote_address_id (quote_address_id)');
}

if(!isset($indexes['quote_mood'])) {
	$db->Execute('ALTER TABLE feedback_entry ADD INDEX quote_mood (quote_mood)');
}

// `feedback_list` ========================
if(!isset($tables['feedback_list'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS feedback_list (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);
	
	$tables['feedback_list'] = 'feedback_list';
}

// ===========================================================================
// Ophaned feedback_entry custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");

// ===========================================================================
// Migrate the Feedback.List to a custom field
if(isset($tables['feedback_entry'])) {
	list($columns, $indexes) = $db->metaTable('feedback_entry');

	if(isset($tables['feedback_list']) && isset($columns['list_id'])) {
		// Load the campaign hash
		$lists = array();
		$sql = "SELECT id, name FROM feedback_list ORDER BY name";
		$rs = $db->Execute($sql);
		while($row = mysql_fetch_assoc($rs)) {
			$lists[$row['id']] = $row['name'];
		}
		
		mysql_free_result($rs);
	
		if(!empty($lists)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'List','D',0,0,%s,%s)",
				$field_id,
				$db->qstr(implode("\n",$lists)),
				$db->qstr('feedback.fields.source.feedback_entry')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, f.id, fl.name, %s FROM feedback_entry f INNER JOIN feedback_list fl ON (f.list_id=fl.id) WHERE f.list_id != 0",
				$field_id,
				$db->qstr('feedback.fields.source.feedback_entry')
			);
			$db->Execute($sql);
		}
		
		$db->Execute('ALTER TABLE feedback_entry DROP COLUMN list_id');
	}
}

// Drop the feedback_list table
if(isset($tables['feedback_list'])) {
	$db->Execute('DROP TABLE feedback_list');
}

return TRUE;
