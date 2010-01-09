<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// `feedback_entry` ========================
if(!isset($tables['feedback_entry'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		log_date I4 DEFAULT 0 NOTNULL,
		list_id I4 DEFAULT 0 NOTNULL, 
		worker_id I4 DEFAULT 0 NOTNULL, 
		quote_text XL,
		quote_mood I1 DEFAULT 0 NOTNULL, 
		quote_address_id I4 DEFAULT 0 NOTNULL 
	";
	
	$sql = $datadict->CreateTableSQL('feedback_entry', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('feedback_entry');
$indexes = $datadict->MetaIndexes('feedback_entry',false);

if(!isset($columns['SOURCE_URL'])) {
	$sql = $datadict->AddColumnSQL('feedback_entry', "source_url C(255) DEFAULT '' NOTNULL");
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['log_date'])) {
	$sql = $datadict->CreateIndexSQL('log_date','feedback_entry','log_date');
	$datadict->ExecuteSQLArray($sql);
}
if(!isset($indexes['list_id'])) {
	$sql = $datadict->CreateIndexSQL('list_id','feedback_entry','list_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
	$sql = $datadict->CreateIndexSQL('worker_id','feedback_entry','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['quote_address_id'])) {
	$sql = $datadict->CreateIndexSQL('quote_address_id','feedback_entry','quote_address_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['quote_mood'])) {
	$sql = $datadict->CreateIndexSQL('quote_mood','feedback_entry','quote_mood');
	$datadict->ExecuteSQLArray($sql);
}

// `feedback_list` ========================
if(!isset($tables['feedback_list'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL
	";
	$sql = $datadict->CreateTableSQL('feedback_list', $flds);
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Ophaned feedback_entry custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN feedback_entry ON (feedback_entry.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'feedback.fields.source.feedback_entry' AND feedback_entry.id IS NULL");

// ===========================================================================
// Migrate the Feedback.List to a custom field
if(isset($tables['feedback_entry'])) {
	$columns = $datadict->MetaColumns('feedback_entry');
	$indexes = $datadict->MetaIndexes('feedback_entry',false);

	if(isset($tables['feedback_list']) && isset($columns['LIST_ID'])) {
		// Load the campaign hash
		$lists = array();
		$sql = "SELECT id, name FROM feedback_list ORDER BY name";
		$rs = $db->Execute($sql);
		while(!$rs->EOF) {
			$lists[$rs->fields['id']] = $rs->fields['name'];
			$rs->MoveNext();
		}
	
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
		
		$sql = $datadict->DropColumnSQL('feedback_entry','list_id');
	    $datadict->ExecuteSQLArray($sql);
	}
}

// Drop the feedback_list table
if(isset($tables['feedback_list'])) {
	$sql = $datadict->DropTableSQL('feedback_list');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
?>