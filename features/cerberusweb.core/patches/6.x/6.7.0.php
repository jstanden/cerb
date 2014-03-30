<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Set the new default number of columns to 3 on existing dashboard tabs

$params_json = json_encode(array('num_columns' => 3));
$db->Execute(sprintf("UPDATE workspace_tab SET params_json = %s WHERE extension_id = 'core.workspace.tab' AND params_json IS NULL",
	$db->qstr($params_json)
));

// ===========================================================================
// Add `cache_ttl` to `workspace_widget`

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_widget');

if(!isset($columns['cache_ttl'])) {
	$db->Execute("ALTER TABLE workspace_widget ADD COLUMN cache_ttl MEDIUMINT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE workspace_widget SET cache_ttl = 60");
}

// ===========================================================================
// Add `context_crc32` to `fulltext_comment_content`

if(isset($tables['fulltext_comment_content'])) {
	list($columns, $indexes) = $db->metaTable('fulltext_comment_content');

	if(!isset($columns['context_crc32'])) {
		$db->Execute("ALTER TABLE fulltext_comment_content ADD COLUMN context_crc32 INT UNSIGNED");
		$db->Execute("UPDATE fulltext_comment_content INNER JOIN comment ON (fulltext_comment_content.id=comment.id) SET fulltext_comment_content.context_crc32 = CRC32(comment.context)");
	}
}

// ===========================================================================
// Drop redundant indexes (id vs pkey)

$check_tables = array(
	'address',
	'attachment',
	'bayes_words',
	'bucket',
	'custom_field',
	'devblocks_storage_profile',
	'devblocks_template',
	'kb_category',
	'mail_to_group_rule',
	'message',
	'pop3_account',
	'snippet',
	'ticket',
	'worker',
	'worker_group',
);

foreach($check_tables as $check_table) {
	if(isset($tables[$check_table])) {
		list($columns, $indexes) = $db->metaTable($check_table);
		
		if(isset($indexes['id']))
			$db->Execute(sprintf('ALTER TABLE %s DROP INDEX id', $db->escape($check_table)));
	}
}

// ===========================================================================
// Finish up

return TRUE;
