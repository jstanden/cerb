<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
//require(UM_PATH . '/api/UserMeetApplication.class.php');

$um_db = UserMeetDatabase::getInstance();

$datadict = NewDataDictionary($um_db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = array();

$tables['extension'] = "
	id C(128) DEFAULT '' NOTNULL PRIMARY,
	plugin_id I DEFAULT 0 NOTNULL,
	point C(128) DEFAULT '' NOTNULL,
	name C(128) DEFAULT '' NOTNULL,
	file C(128) DEFAULT '' NOTNULL,
	class C(128) DEFAULT '' NOTNULL,
	params B DEFAULT '' NOTNULL
";

//$tables['page'] = "
//	id I PRIMARY,
//	extension_id C(128) DEFAULT '' NOTNULL,
//	display_order I1 DEFAULT 0 NOTNULL
//";

$tables['plugin'] = "
	id I PRIMARY,
	enabled I1 DEFAULT 0 NOTNULL,
	name C(128) DEFAULT '' NOTNULL,
	author C(64) DEFAULT '' NOTNULL,
	dir C(128) DEFAULT '' NOTNULL
";

$tables['property_store'] = "
	extension_id C(128) DEFAULT '' NOTNULL PRIMARY,
	instance_id I DEFAULT 0 NOTNULL PRIMARY,
	property C(128) DEFAULT '' NOTNULL PRIMARY,
	value C(255) DEFAULT '' NOTNULL
";

$tables['session'] = "
	sesskey C(64) PRIMARY,
	expiry T,
	expireref C(250),
	created T NOTNULL,
	modified T NOTNULL,
	sessdata B
";

$tables['ticket'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	mask C(16) DEFAULT '' NOTNULL, 
	subject C(128)  DEFAULT '' NOTNULL, 
	status C(16) DEFAULT '' NOTNULL, 
	last_wrote C(128) DEFAULT '' NOTNULL 
";

$tables['login'] = "
	id I PRIMARY,
	login C(32) NOTNULL,
	password C(32) NOTNULL,
	admin I1 DEFAULT 0 NOTNULL
";

foreach($tables as $table => $flds) {
	$sql = $datadict->ChangeTableSQL($table,$flds);
	print_r($sql);
	$datadict->ExecuteSQLArray($sql,false);
	echo "<HR>";
}

?>