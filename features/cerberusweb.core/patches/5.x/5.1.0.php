<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Context Links

if(!isset($tables['context_link'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS context_link (
			from_context VARCHAR(128) DEFAULT '',
			from_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			to_context VARCHAR(128) DEFAULT '',
			to_context_id INT UNSIGNED NOT NULL DEFAULT 0,
			INDEX from_context (from_context, from_context_id),
			INDEX to_context (to_context, to_context_id),
			UNIQUE from_and_to (from_context, from_context_id, to_context, to_context_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['context_link'] = 'context_link';
}

// ===========================================================================
// Task Sources->Context Links

// ===========================================================================
// Convert task sources to URLs

// Orgs
//$url->write(sprintf('c=contacts&a=orgs&display=display&id=%d',$object_id)

// Tickets
//$url->write(sprintf('c=display&mask=%s&tab=tasks',$ticket->mask), true)

// Opps
//$url->write(sprintf('c=crm&a=opps&id=%d',$opp->id)),

//if(!isset($tables['contact_org']))
//	return FALSE;
//
//list($columns, $indexes) = $db->metaTable('contact_org');
//
//if(!isset($columns['parent_org_id'])) {
//	$db->Execute("ALTER TABLE contact_org ADD COLUMN parent_org_id BIGINT UNSIGNED NOT NULL DEFAULT 0");
//	$db->Execute("ALTER TABLE contact_org ADD INDEX parent_org_id (parent_org_id)");
//}

return TRUE;
