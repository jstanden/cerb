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

return TRUE;
