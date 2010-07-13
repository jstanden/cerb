<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// TimeEntry Sources->Context Links

if(!isset($tables['timetracking_entry']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('timetracking_entry');
	
if(isset($columns['source_extension_id']) && isset($columns['source_id'])) {
	$source_to_context = array(
		'timetracking.source.ticket' => 'cerberusweb.contexts.ticket',
	);
	
	if(is_array($source_to_context))
	foreach($source_to_context as $source => $context) {
		$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"SELECT 'cerberusweb.contexts.timetracking', id, %s, source_id FROM timetracking_entry WHERE source_extension_id = %s ",
			$db->qstr($context),
			$db->qstr($source)
		));
	}
	
	// Insert reciprocals
	$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
		"SELECT to_context, to_context_id, from_context, from_context_id ".
		"FROM context_link"
	));
	
	$db->Execute('ALTER TABLE timetracking_entry DROP COLUMN source_extension_id');
	$db->Execute('ALTER TABLE timetracking_entry DROP COLUMN source_id');
}

return TRUE;