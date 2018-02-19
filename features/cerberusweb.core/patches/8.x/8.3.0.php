<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Replace org.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.org.merge', 'activities.record.merge') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.org\",\"context_label\":\"organization\",') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'org.merge'");

// ===========================================================================
// Replace ticket.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.ticket.merge', 'activities.record.merge') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.ticket\",\"context_label\":\"ticket\",') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'ticket.merge'");

// ===========================================================================
// Clear empty email addresses

$db->ExecuteMaster("DELETE FROM address WHERE email = ''");

// ===========================================================================
// Add `updated_at` field to the `custom_fieldset` table

if(!isset($tables['custom_fieldset'])) {
	$logger->error("The 'custom_fieldset' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_fieldset');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE custom_fieldset ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_page` table

if(!isset($tables['workspace_page'])) {
	$logger->error("The 'workspace_page' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_page');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_page ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_page SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_tab` table

if(!isset($tables['workspace_tab'])) {
	$logger->error("The 'workspace_tab' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_tab');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_tab ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_tab SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_widget` table

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_widget');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_widget ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_widget SET updated_at = %d", time()));
}

// ===========================================================================
// Add `currency`

if(!isset($tables['currency'])) {
	$sql = sprintf("
	CREATE TABLE `currency` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		name_plural VARCHAR(255) DEFAULT '',
		code VARCHAR(4) DEFAULT '',
		symbol VARCHAR(16) DEFAULT '',
		decimal_at TINYINT UNSIGNED NOT NULL DEFAULT 0,
		is_default TINYINT UNSIGNED NOT NULL DEFAULT 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['currency'] = 'currency';
	
	// USD
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('US Dollar'),
		$db->qstr('US Dollars'),
		$db->qstr('USD'),
		$db->qstr('$'),
		2,
		1,
		time()
	));
	
	// EUR
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Euro'),
		$db->qstr('Euros'),
		$db->qstr('EUR'),
		$db->qstr('€'),
		2,
		0,
		time()
	));
	
	// GBP
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('British Pound'),
		$db->qstr('British Pounds'),
		$db->qstr('GBP'),
		$db->qstr('£'),
		2,
		0,
		time()
	));
	
	// BTC
	$db->ExecuteMaster(sprintf("INSERT INTO currency (name, name_plural, code, symbol, decimal_at, is_default, updated_at) "
		. "VALUES(%s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Bitcoin'),
		$db->qstr('Bitcoins'),
		$db->qstr('BTC'),
		$db->qstr('Ƀ'),
		8,
		0,
		time()
	));
}

// ===========================================================================
// Increase the size of `custom_field_numbervalue` from int4 to int8

if(!isset($tables['custom_field_numbervalue'])) {
	$logger->error("The 'custom_field_numbervalue' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_field_numbervalue');

if(@$columns['field_value'] && 0 == strcasecmp($columns['field_value']['type'], 'int(10) unsigned')) {
	$db->ExecuteMaster("ALTER TABLE custom_field_numbervalue MODIFY COLUMN field_value BIGINT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add `updated_at` and `uri` field to the `community_tool` table

if(!isset($tables['community_tool'])) {
	$logger->error("The 'community_tool' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('community_tool');

if(!isset($columns['uri'])) {
	$sql = "ALTER TABLE community_tool ADD COLUMN uri varchar(32) NOT NULL DEFAULT ''";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("UPDATE community_tool SET uri = code");
}

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE community_tool ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE community_tool SET updated_at = %d", time()));
}

// ===========================================================================
// Finish up

return TRUE;
