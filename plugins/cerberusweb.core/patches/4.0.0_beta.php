<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();

// `address` ========================
$columns = $datadict->MetaColumns('address', false, false);
$indexes = $datadict->MetaIndexes('address',false);

if(!isset($indexes['email'])) {
    $sql = $datadict->CreateIndexSQL('email','address','email',array('UNIQUE'));
    $datadict->ExecuteSQLArray($sql);
}

// `group_setting` =======================
if(!isset($tables['group_setting'])) {
    $flds = "
		group_id I4 DEFAULT 0 NOTNULL PRIMARY,
		setting C(64) DEFAULT '' NOTNULL PRIMARY,
		value B DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('group_setting',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `message_content` =====================
//$columns = $datadict->MetaColumns('message_content', false, false);
if(!isset($tables['message_content'])) {
    $flds = "
		message_id I4 DEFAULT 0 NOTNULL PRIMARY,
		content B DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('message_content',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `message_header` =====================
//$columns = $datadict->MetaColumns('message_header', false, false);
$indexes = $datadict->MetaIndexes('message_header',false);
if(!isset($tables['message_header'])) {
    $flds = "
		message_id I4 DEFAULT 0 NOTNULL PRIMARY,
		header_name C(64) DEFAULT '' NOTNULL PRIMARY,
		ticket_id I4 DEFAULT 0 NOTNULL,
		header_value B DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('message_header',$flds);
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['header_name'])) {
    $sql = $datadict->CreateIndexSQL('header_name','message_header','header_name');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','message_header','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

// `message` ========================
$columns = $datadict->MetaColumns('message', false, false);
$indexes = $datadict->MetaIndexes('message',false);

if(!isset($indexes['created_date'])) {
    $sql = $datadict->CreateIndexSQL('created_date','message','created_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','message','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['headers'])) {
    $sql = $datadict->DropColumnSQL('message','headers');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['message_id'])) {
    $sql = $datadict->DropColumnSQL('message','message_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['content'])) {
    // insert into message_content (message_id, content) select id,content FROM message
    $sql = $datadict->DropColumnSQL('message','content');
    $datadict->ExecuteSQLArray($sql);
}

// `team_routing_rule` ========================
$columns = $datadict->MetaColumns('team_routing_rule', false, false);
$indexes = $datadict->MetaIndexes('team_routing_rule',false);

if(!isset($indexes['team_id'])) {
    $sql = $datadict->CreateIndexSQL('team_id','team_routing_rule','team_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['pos'])) {
    $sql = $datadict->CreateIndexSQL('pos','team_routing_rule','pos');
    $datadict->ExecuteSQLArray($sql);
}

// `ticket` ========================
$columns = $datadict->MetaColumns('ticket', false, false);
$indexes = $datadict->MetaIndexes('ticket',false);

if(isset($columns['owner_id'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'owner_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['priority'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'priority');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['import_pile'])) {
	$sql = $datadict->DropColumnSQL('ticket', 'import_pile');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['first_message_id'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'first_message_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

   // [JAS]: Populate our new foreign key
   $sql = "SELECT m.ticket_id, min(m.id) as first_message_id ".
       "FROM message m ".
       "INNER JOIN ticket t ON (t.id=m.ticket_id) ".
 	   "WHERE t.first_message_id = 0 ".
 	   "GROUP BY ticket_id";
   $rs = $db->Execute($sql); /* @var $rs ADORecordSet */
   
   while(!$rs->EOF) {
       if(empty($rs->fields['first_message_id'])) {
           continue;
       }
       
       $sql = sprintf("UPDATE ticket SET first_message_id = %d WHERE id = %d", 
			intval($rs->fields['first_message_id']),
			intval($rs->fields['ticket_id'])
			);
       $db->Execute($sql);
       
       $rs->MoveNext();
   }

if(!isset($indexes['first_message_id'])) {
    $sql = $datadict->CreateIndexSQL('first_message_id','ticket','first_message_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['mask'])) {
    $sql = $datadict->CreateIndexSQL('mask','ticket','mask');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['updated_date'])) {
    $sql = $datadict->CreateIndexSQL('updated_date','ticket','updated_date');
    $datadict->ExecuteSQLArray($sql);
}

return TRUE;
?>