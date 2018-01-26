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

return TRUE;