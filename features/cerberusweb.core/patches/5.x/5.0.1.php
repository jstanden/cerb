<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Fix CRLF and tabs in ticket subjects

$db->Execute("update ticket set subject = replace(replace(replace(subject,\"\\n\",' '),\"\\r\",' '),\"\\t\",' ') where subject regexp \"(\\r|\\n|\\t)\"");

return TRUE;
