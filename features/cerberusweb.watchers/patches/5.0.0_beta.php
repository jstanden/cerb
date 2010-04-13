<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

/**
 * Fix the serialized View_*
 */

$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:21:\"View_WorkerMailFilter\"', 's:22:\"View_WatcherMailFilter\"') WHERE setting LIKE 'view%'");

return TRUE;