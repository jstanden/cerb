<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add context owners to snippets

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['owner_context']) && !isset($columns['owner_context_id'])) {
	$db->Execute(sprintf(
		"ALTER TABLE snippet ".
		"ADD COLUMN owner_context VARCHAR(128) NOT NULL DEFAULT '', ".
		"ADD COLUMN owner_context_id INT NOT NULL DEFAULT 0, ".
		"ADD INDEX owner_compound (owner_context, owner_context_id), ".
		"ADD INDEX owner_context (owner_context) "
	));
}

if(isset($columns['created_by'])) {
	$db->Execute(sprintf("UPDATE snippet SET owner_context='cerberusweb.contexts.worker', owner_context_id=created_by WHERE created_by > 0"));
	$db->Execute("ALTER TABLE snippet DROP COLUMN created_by");
}

if(isset($columns['last_updated'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN last_updated");
}

if(isset($columns['last_updated_by'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN last_updated_by");
}
 
if(isset($columns['is_private'])) {
	$db->Execute("ALTER TABLE snippet DROP COLUMN is_private");
} 

return TRUE;