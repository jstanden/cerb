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
// Add 'updated_date' column to the 'task' table

$columns = $datadict->MetaColumns('task');
$indexes = $datadict->MetaIndexes('task',false);

if(!isset($columns['UPDATED_DATE'])) {
	$sql = $datadict->AddColumnSQL('task',"updated_date I4 DEFAULT 0 NOTNULL");
	$datadict->ExecuteSQLArray($sql);
	
	$sql = $datadict->CreateIndexSQL('updated_date','task','updated_date');
	$datadict->ExecuteSQLArray($sql);

	// Populate the created date with the due date if we know it
	$db->Execute("UPDATE task SET updated_date = due_date WHERE due_date > 0");
	
	// Otherwise, populate all blank created dates with right now
	$db->Execute("UPDATE task SET updated_date = UNIX_TIMESTAMP() WHERE due_date = 0");
}

// ===========================================================================
// Migrate 'content' from 'task' into 'note'

if(isset($columns['CONTENT'])) {
	$rs = $db->Execute("SELECT id, updated_date, content FROM task WHERE content != '' ORDER BY id");
	
	// Move 'content' blocks to anonymous notes on each task record
	while(!$rs->EOF) {
		$note_id = $db->GenID('note_seq');
		$sql = sprintf('INSERT INTO note (id, source_extension_id, source_id, created, worker_id, content) '.
			'VALUES (%d, %s, %d, %d, %d, %s)',
			$note_id,
			$db->qstr('cerberusweb.notes.source.task'),
			$rs->fields['id'],
			$rs->fields['updated_date'],
			0,
			$db->qstr($rs->fields['content'])
		);
		$db->Execute($sql);
		$rs->MoveNext();
	}
	
	// Drop the deprecated 'content' column from 'task'
	$sql = $datadict->DropColumnSQL('task','content');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
