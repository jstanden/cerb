<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2009, WebGroup Media LLC
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

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Remove the message_header.ticket_id foreign key (inefficient)

$columns = $datadict->MetaColumns('message_header');
$indexes = $datadict->MetaIndexes('message_header',false);

if(isset($columns['TICKET_ID'])) {
	$sql = $datadict->DropColumnSQL('message_header','ticket_id');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Add 'is_registered' and 'pass' fields to the 'address' table

$columns = $datadict->MetaColumns('address');
$indexes = $datadict->MetaIndexes('address',false);

if(!isset($columns['IS_REGISTERED'])) {
	$sql = $datadict->AddColumnSQL('address',"is_registered I1 DEFAULT 0 NOTNULL");
	$datadict->ExecuteSQLArray($sql);
	
	$sql = $datadict->CreateIndexSQL('is_registered','address','is_registered');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['PASS'])) {
	$sql = $datadict->AddColumnSQL('address',"pass C(32) DEFAULT '' NOTNULL");
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Merge 'address_auth' into 'address'

if(isset($tables['address_auth'])) {
	$sql = "SELECT address_id, pass FROM address_auth WHERE pass != ''";
	$rs = $db->Execute($sql);
	
	// Loop through 'address_auth' records and inject them into 'address'
	while(!$rs->EOF) {
		$address_id = $rs->fields['address_id'];
		$pass = $rs->fields['pass'];
		
		$db->Execute(sprintf("UPDATE address SET is_registered=1, pass=%s WHERE id = %d",
			$db->qstr($pass),
			$address_id
		));
		
		$rs->MoveNext();
	}
	
	// Drop 'address_auth'
	$sql = $datadict->DropTableSQL('address_auth');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
