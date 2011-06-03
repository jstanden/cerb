<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Fix unindexed KB articles

$db->Execute("UPDATE cerb_property_store SET value = '0' WHERE property = 'last_indexed_time' and extension_id = 'cerberusweb.search.schema.kb_article'");

return TRUE;