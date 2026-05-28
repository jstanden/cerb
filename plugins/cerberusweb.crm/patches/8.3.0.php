<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Migrate the `amount` column to currency records

if(!isset($tables['crm_opportunity']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(isset($columns['amount'])) {
	if(!isset($columns['currency_id'])) {
		$sql = 'ALTER TABLE crm_opportunity ADD COLUMN currency_id int(10) unsigned NOT NULL DEFAULT 0';
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster("UPDATE crm_opportunity SET currency_id = 1 WHERE currency_id = 0");
	}
	
	if(!isset($columns['currency_amount'])) {
		$sql = 'ALTER TABLE crm_opportunity ADD COLUMN currency_amount bigint unsigned NOT NULL DEFAULT 0';
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster("UPDATE crm_opportunity SET currency_amount = REPLACE(amount,'.','')");
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN amount');
	}
}

// ===========================================================================
// Move the `primary_email_id` field to record links

if(!isset($tables['crm_opportunity']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(isset($columns['primary_email_id'])) {
	$sql = "INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) SELECT 'cerberusweb.contexts.opportunity', id, 'cerberusweb.contexts.address', primary_email_id from crm_opportunity where primary_email_id > 0";
	$db->ExecuteMaster($sql);
	
	$sql = "INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) SELECT 'cerberusweb.contexts.address', primary_email_id, 'cerberusweb.contexts.opportunity', id from crm_opportunity where primary_email_id > 0";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN primary_email_id');
}

// ===========================================================================
// Consolidate `is_closed` and `is_won` to `status_id`

if(!isset($tables['crm_opportunity']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('crm_opportunity');

if(!isset($columns['status_id'])) {
	$sql = 'ALTER TABLE crm_opportunity ADD COLUMN status_id tinyint unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	if(isset($columns['is_closed']) && isset($columns['is_won'])) {
		$db->ExecuteMaster('UPDATE crm_opportunity SET status_id = 1 WHERE is_closed = 1 AND is_won = 1');
		$db->ExecuteMaster('UPDATE crm_opportunity SET status_id = 2 WHERE is_closed = 1 AND is_won = 0');
		$db->ExecuteMaster('ALTER TABLE crm_opportunity DROP COLUMN is_closed, DROP COLUMN is_won');
	}
}

return TRUE;