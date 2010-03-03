<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Drop the FULLTEXT indexes on KB articles

list($columns, $indexes) = $db->metaTable('kb_article');

if(isset($indexes['title'])) {
	$db->Execute("ALTER TABLE kb_article DROP INDEX title");
}

if(isset($indexes['content'])) {
	$db->Execute("ALTER TABLE kb_article DROP INDEX content");	
}

return TRUE;
