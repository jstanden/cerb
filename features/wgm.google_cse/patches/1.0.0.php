<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// `wgm_google_cse` ========================
if(!isset($tables['wgm_google_cse'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL,
		url C(255) DEFAULT '' NOTNULL,
		token C(255) DEFAULT '' NOTNULL
	";
	$sql = $datadict->CreateTableSQL('wgm_google_cse', $flds);
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;