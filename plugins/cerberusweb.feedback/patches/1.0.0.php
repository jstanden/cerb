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

return TRUE;
?>