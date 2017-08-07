<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add 'updated_date' column to the 'task' table

list($columns, $indexes) = $db->metaTable('task');

if(!isset($columns['updated_date'])) {
	$db->ExecuteMaster('ALTER TABLE task ADD COLUMN updated_date INT UNSIGNED DEFAULT 0 NOT NULL');
	$db->ExecuteMaster('ALTER TABLE task ADD INDEX updated_date (updated_date)');

	// Populate the created date with the due date if we know it
	$db->ExecuteMaster("UPDATE task SET updated_date = due_date WHERE due_date > 0");
	
	// Otherwise, populate all blank created dates with right now
	$db->ExecuteMaster("UPDATE task SET updated_date = UNIX_TIMESTAMP() WHERE due_date = 0");
}

// ===========================================================================
// Migrate 'content' from 'task' into 'note'

if(isset($columns['content'])) {
	$rs = $db->ExecuteMaster("SELECT id, updated_date, content FROM task WHERE content != '' ORDER BY id");
	
	// Move 'content' blocks to anonymous notes on each task record
	while($row = mysqli_fetch_assoc($rs)) {
		$sql = sprintf('INSERT INTO note (source_extension_id, source_id, created, worker_id, content) '.
			'VALUES (%s, %d, %d, %d, %s)',
			$db->qstr('cerberusweb.notes.source.task'),
			$row['id'],
			$row['updated_date'],
			0,
			$db->qstr($row['content'])
		);
		$db->ExecuteMaster($sql);
	}
	
	mysqli_free_result($rs);
	
	// Drop the deprecated 'content' column from 'task'
	$db->ExecuteMaster('ALTER TABLE task DROP COLUMN content');
}

return TRUE;
