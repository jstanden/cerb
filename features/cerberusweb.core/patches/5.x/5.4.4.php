<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Fix unindexed KB articles

$db->ExecuteMaster("UPDATE cerb_property_store SET value = '0' WHERE property = 'last_indexed_time' and extension_id = 'cerberusweb.search.schema.kb_article'");

return TRUE;