<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

$um_db = DevblocksPlatform::getDatabaseService();

$datadict = NewDataDictionary($um_db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

// ***** Platform

$tables = DevblocksPlatform::getDatabaseSchema();

// ***** Application

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
	first_wrote C(128) DEFAULT '' NOTNULL,
	first_wrote_address_id I4 NOTNULL DEFAULT 0,
	last_wrote_address_id I4 NOTNULL DEFAULT 0
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
	display_name C(32) DEFAULT '',
	close_autoresponse B,
	new_autoresponse B
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
	mailbox_id I4 DEFAULT 0 NOTNULL
";

$tables['requester'] = "
	address_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['tag'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL
";

$tables['tag_term'] = "
	tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
	term C(128) DEFAULT '' NOTNULL PRIMARY
";

$tables['tag_to_ticket'] ="
	tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['assign_to_ticket'] = "
	agent_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY,
	is_flag I1 DEFAULT 0 NOTNULL
";

$tables['mailbox_to_team'] = "
	mailbox_id I4 DEFAULT 0 NOTNULL PRIMARY,
	team_id I4 DEFAULT 0 NOTNULL PRIMARY,
	is_routed I1 DEFAULT 0 NOTNULL
";

$tables['worker_to_team'] = "
	agent_id I4 DEFAULT 0 NOTNULL PRIMARY,
	team_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['favorite_tag_to_worker'] = "
	tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
	agent_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['favorite_worker_to_worker'] = "
	worker_id I4 DEFAULT 0 NOTNULL PRIMARY,
	agent_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['pop3_account'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	nickname C(128) DEFAULT '' NOTNULL,
	host C(128) DEFAULT '' NOTNULL,
	username C(128) DEFAULT '' NOTNULL,
	password C(128) DEFAULT '' NOTNULL
";

$tables['kb_category'] = "
	id I2 DEFAULT 0 NOTNULL PRIMARY,
	name C(128) DEFAULT '' NOTNULL,
	parent_id I2 DEFAULT 0 NOTNULL
";

$tables['kb_to_category'] = "
	kb_id I4 DEFAULT 0 NOTNULL PRIMARY,
	category_id I2 DEFAULT 0 NOTNULL PRIMARY
";

$tables['kb'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	title C(128) DEFAULT '' NOTNULL,
	type C(1) DEFAULT 'A' NOTNULL
";

$tables['kb_content'] = "
	kb_id I4 DEFAULT 0 NOTNULL PRIMARY,
	content B DEFAULT '' NOTNULL
";

$tables['tag_to_kb'] ="
	tag_id I4 DEFAULT 0 NOTNULL PRIMARY,
	kb_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['worker'] ="
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	first_name C(32) DEFAULT '',
	last_name C(64) DEFAULT '',
	title C(64) DEFAULT '',
	login C(64) DEFAULT '',
	pass C(32) DEFAULT '',
	last_activity_date I4
";

foreach($tables as $table => $flds) {
	$sql = $datadict->ChangeTableSQL($table,$flds);
	print_r($sql);
	$datadict->ExecuteSQLArray($sql,false);
	echo "<HR>";
}

$plugins = DevblocksPlatform::readPlugins();
?>