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

// ===========================================================================
// Drop kb_article.content_raw

if(!isset($tables['kb_article']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('kb_article');

if(!isset($columns['content_raw'])) {
	$db->Execute("ALTER TABLE kb_article DROP COLUMN content_raw");
}

// ===========================================================================
// Plaintext->Markdown

if(!isset($tables['kb_article']))
	return FALSE;
	
$db->Execute("UPDATE kb_article SET content=REPLACE(content,\"\\r\\n\",\"\\n\") WHERE format=0");
$db->Execute("UPDATE kb_article SET format=2, content=REPLACE(content,\"\\n\",\"  \\n\") WHERE format=0");

return TRUE;
