<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `profile_tab`

if(!isset($tables['profile_tab'])) {
	$sql = sprintf("
	CREATE TABLE `profile_tab` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		context VARCHAR(255) NOT NULL DEFAULT '',
		extension_id VARCHAR(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index (context),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_tab'] = 'profile_tab';
}

// ===========================================================================
// Add `profile_widget`

if(!isset($tables['profile_widget'])) {
	$sql = sprintf("
	CREATE TABLE `profile_widget` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		profile_tab_id int(10) unsigned NOT NULL DEFAULT 0,
		extension_id varchar(255) NOT NULL DEFAULT '',
		extension_params_json TEXT,
		zone varchar(255) NOT NULL DEFAULT '',
		pos tinyint unsigned NOT NULL DEFAULT 0,
		width_units tinyint unsigned NOT NULL DEFAULT 1,
		updated_at int(10) unsigned NOT NULL DEFAULT 0,
		primary key (id),
		index (profile_tab_id),
		index (extension_id),
		index (updated_at)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['profile_widget'] = 'profile_widget';
}

// ===========================================================================
// Insert default search buttons

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.profile.tab'),
	$db->qstr('[{"context":"cerberusweb.contexts.profile.widget","label_singular":"Widget","label_plural":"Widgets","query":"tab.id:{{id}}"}]')
));

// ===========================================================================
// Drop `view_filters_preset` (this is now `context_saved_search`)

if(isset($tables['view_filters_preset'])) {
	$db->ExecuteMaster('DROP TABLE view_filters_preset');
	unset($tables['view_filters_preset']);
}

// ===========================================================================
// Add `params_query` and remove `params_hidden_json` from `worker_view_model`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['params_query'])) {
	$sql = 'ALTER TABLE worker_view_model ADD COLUMN params_query TEXT AFTER columns_hidden_json';
	$db->ExecuteMaster($sql);
}

if(isset($columns['params_hidden_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN params_hidden_json';
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Remove old worker prefs

$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mail_display_inline_log'");
$db->ExecuteMaster("DELETE FROM worker_pref WHERE setting = 'mail_always_show_all'");

// ===========================================================================
// Add `context_to_custom_fieldset`

if(!isset($tables['context_to_custom_fieldset'])) {
	$sql = sprintf("
	CREATE TABLE `context_to_custom_fieldset` (
		context VARCHAR(255) DEFAULT '',
		context_id INT UNSIGNED NOT NULL,
		custom_fieldset_id INT UNSIGNED NOT NULL,
		primary key (context,custom_fieldset_id, context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['context_to_custom_fieldset'] = 'context_to_custom_fieldset';
	
	// Populate `context_to_custom_fieldset`
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_stringvalue.context, context_id, custom_field.custom_fieldset_id from custom_field_stringvalue inner join custom_field on (custom_field_stringvalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_numbervalue.context, context_id, custom_field.custom_fieldset_id from custom_field_numbervalue inner join custom_field on (custom_field_numbervalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
	$db->ExecuteMaster("INSERT IGNORE into context_to_custom_fieldset (context, context_id, custom_fieldset_id) SELECT custom_field_clobvalue.context, context_id, custom_field.custom_fieldset_id from custom_field_clobvalue inner join custom_field on (custom_field_clobvalue.field_id=custom_field.id and custom_field.custom_fieldset_id != 0)");
}

// ===========================================================================
// Remove `context_link` records for custom fieldsets

$db->ExecuteMaster("DELETE FROM context_link WHERE from_context = 'cerberusweb.contexts.custom_fieldset'");
$db->ExecuteMaster("DELETE FROM context_link WHERE to_context = 'cerberusweb.contexts.custom_fieldset'");

// ===========================================================================
// Clean up custom fieldset link logs

$db->ExecuteMaster("DELETE FROM context_activity_log WHERE target_context = 'cerberusweb.contexts.custom_fieldset'");

// ===========================================================================
// Finish up

return TRUE;
