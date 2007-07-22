<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();

// `address` ========================
$columns = $datadict->MetaColumns('address');
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

// `message_note` ==================
if(!isset($tables['message_note'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		message_id I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		content B DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('message_note',$flds);
    $datadict->ExecuteSQLArray($sql);
}

// `message` ========================
$columns = $datadict->MetaColumns('message');
$indexes = $datadict->MetaIndexes('message',false);

if(!isset($indexes['created_date'])) {
    $sql = $datadict->CreateIndexSQL('created_date','message','created_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['ticket_id'])) {
    $sql = $datadict->CreateIndexSQL('ticket_id','message','ticket_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['HEADERS'])) {
    $sql = $datadict->DropColumnSQL('message','headers');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['MESSAGE_ID'])) {
    $sql = $datadict->DropColumnSQL('message','message_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['CONTENT'])) {
    // insert into message_content (message_id, content) select id,content FROM message
    $sql = $datadict->DropColumnSQL('message','content');
    $datadict->ExecuteSQLArray($sql);
}

// `setting` ==================================
$columns = $datadict->MetaColumns('setting');
//$indexes = $datadict->MetaIndexes('setting',false);

if(255 == $columns['VALUE']->max_length) {
	$datadict->ExecuteSQLArray($datadict->RenameColumnSQL('setting', 'value', 'value_old',"value_old C(255) DEFAULT '' NOTNULL"));
	$datadict->ExecuteSQLArray($datadict->AddColumnSQL('setting', "value B DEFAULT '' NOTNULL"));
	
	$sql = "SELECT setting, value_old FROM setting ";
	$rs = $db->Execute($sql);
	
	if($rs)
	while(!$rs->EOF) {
		@$db->UpdateBlob(
			'setting',
			'value',
			$rs->fields['value_old'],
			sprintf("setting = %s",
				$db->qstr($rs->fields['setting'])
			)
		);
		$rs->MoveNext();
	}
	
	if($rs)
		$datadict->ExecuteSQLArray($datadict->DropColumnSQL('setting', 'value_old'));
}

// `team_routing_rule` ========================
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
$columns = $datadict->MetaColumns('ticket');
$indexes = $datadict->MetaIndexes('ticket',false);

if(isset($columns['OWNER_ID'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'owner_id');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['PRIORITY'])) {
    $sql = $datadict->DropColumnSQL('ticket', 'priority');
    $datadict->ExecuteSQLArray($sql);
}

if(isset($columns['IMPORT_PILE'])) {
	$sql = $datadict->DropColumnSQL('ticket', 'import_pile');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_WORKER_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket', 'last_worker_id I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['LAST_ACTION_CODE'])) {
    $sql = $datadict->AddColumnSQL('ticket', "last_action_code C(1) DEFAULT 'O' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['FIRST_MESSAGE_ID'])) {
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

if(!isset($indexes['first_wrote_address_id'])) {
    $sql = $datadict->CreateIndexSQL('first_wrote_address_id','ticket','first_wrote_address_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['last_wrote_address_id'])) {
    $sql = $datadict->CreateIndexSQL('last_wrote_address_id','ticket','last_wrote_address_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_closed'])) {
    $sql = $datadict->CreateIndexSQL('is_closed','ticket','is_closed');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['category_id'])) {
    $sql = $datadict->CreateIndexSQL('category_id','ticket','category_id');
    $datadict->ExecuteSQLArray($sql);
}

// `worker`
$columns = $datadict->MetaColumns('worker');
$indexes = $datadict->MetaIndexes('worker',false);

if(!isset($columns['CAN_DELETE'])) {
    $sql = $datadict->AddColumnSQL('worker', 'can_delete I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// `worker_to_team`
$columns = $datadict->MetaColumns('worker_to_team');
$indexes = $datadict->MetaIndexes('worker_to_team',false);

if(!isset($columns['IS_MANAGER'])) {
    $sql = $datadict->AddColumnSQL('worker_to_team', 'is_manager I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

return TRUE;
?>