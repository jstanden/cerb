<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `email_signature.signature_html` field

list($columns,) = $db->metaTable('email_signature');

if(!isset($columns['signature_html'])) {
	$sql = "ALTER TABLE email_signature ADD COLUMN signature_html TEXT AFTER signature";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `comment.is_markdown` field

list($columns,) = $db->metaTable('comment');

if(!isset($columns['is_markdown'])) {
	$sql = "ALTER TABLE comment ADD COLUMN is_markdown TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove deprecated worker preferences

$sql = sprintf("DELETE FROM worker_pref WHERE setting IN (%s,%s)",
	$db->qstr('mail_reply_textbox_size_auto'),
	$db->qstr('mail_reply_textbox_size_px')
);
$db->ExecuteMaster($sql);

// ===========================================================================
// Drop plugin library tables

if(array_key_exists('plugin_library', $tables))
	$db->ExecuteMaster("DROP TABLE plugin_library");

if(array_key_exists('fulltext_plugin_library', $tables))
	$db->ExecuteMaster("DROP TABLE fulltext_plugin_library");

// ===========================================================================
// Finish up

return TRUE;
