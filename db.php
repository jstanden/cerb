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

$tables['login'] = "
	id I PRIMARY,
	login C(32) NOTNULL,
	password C(32) NOTNULL,
	admin I1 DEFAULT 0 NOTNULL
";

$tables['ticket'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	mask C(16) DEFAULT '' NOTNULL, 
	subject C(128)  DEFAULT '' NOTNULL, 
	bitflags I2 DEFAULT 0,
	created_date I4,
	updated_date I4,
	status C(1) DEFAULT '' NOTNULL, 
	priority I1 DEFAULT 0 NOTNULL, 
	mailbox_id I4 NOTNULL, 
	last_wrote C(128) DEFAULT '' NOTNULL,
	first_wrote C(128) DEFAULT '' NOTNULL
";

$tables['message'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL,
	is_admin I1 DEFAULT 0 NOTNULL,
	message_type C(1),
	created_date I4,
	address_id I4,
	message_id C(255),
	headers B DEFAULT '' NOTNULL,
	content B DEFAULT '' NOTNULL
";

$tables['attachment'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	message_id I4 DEFAULT 0 NOTNULL,
	display_name C(128) DEFAULT '' NOTNULL,
	filepath C(255) DEFAULT '' NOTNULL
";

$tables['team'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL
";

$tables['mailbox'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL,
	reply_address_id I4 DEFAULT 0 NOTNULL,
	display_name C(32) DEFAULT ''
";

$tables['dashboard'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL,
	agent_id I4 NOTNULL
";

$tables['dashboard_view'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	dashboard_id I4 DEFAULT 0 NOTNULL,
	type C(1) DEFAULT 'D',
	agent_id I4 NOTNULL,
	name C(32) DEFAULT '' NOTNULL,
	view_columns B,
	sort_by C(32) DEFAULT '' NOTNULL,
	sort_asc I1 DEFAULT 1 NOTNULL,
	num_rows I2 DEFAULT 10 NOTNULL,
	page I2 DEFAULT 0 NOTNULL,
	params B
";

$tables['address'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	email C(255) DEFAULT '' NOTNULL,
	personal C(255) DEFAULT '',
	bitflags I2 DEFAULT 0
";

$tables['mail_rule'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	criteria B DEFAULT '' NOTNULL,
	sequence C(4) DEFAULT '',
	strictness C(4) DEFAULT ''
";

$tables['address_to_mailbox'] = "
	address_id I4 DEFAULT 0 NOTNULL PRIMARY,
	mailbox_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['requester'] = "
	address_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['tag'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL
";

$tables['tag_to_ticket'] ="
	tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
";

foreach($tables as $table => $flds) {
	$sql = $datadict->ChangeTableSQL($table,$flds);
	print_r($sql);
	$datadict->ExecuteSQLArray($sql,false);
	echo "<HR>";
}

$plugins = UserMeetPlatform::readPlugins();
?>