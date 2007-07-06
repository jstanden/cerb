<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ 

$tables = array();

// ***** Application

$tables['faq'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	question C(255) DEFAULT '' NOTNULL,
	is_answered I1 DEFAULT 0,
	answer B DEFAULT '',
	answered_by I4 DEFAULT 0,
	created I4 DEFAULT 0
";

// [TODO] Faq comments (worker + visitor contributed)
// [TODO] Faq votes (worker + visitor contributed)

$currentTables = $db->MetaTables('TABLE', false);

if(is_array($tables))
foreach($tables as $table => $flds) {
	if(false === array_search($table,$currentTables)) {
		$sql = $datadict->CreateTableSQL($table,$flds);
	//			print_r($sql);
		// [TODO] Buffer up success and fail messages?  Patcher!
		if(!$datadict->ExecuteSQLArray($sql,false)) {
			return FALSE;
		}
	}
//			echo "<HR>";
}				
?>