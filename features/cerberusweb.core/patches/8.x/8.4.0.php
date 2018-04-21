<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Drop `placeholder_labels_json` and `placeholder_values_json` in `worker_view_model`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(isset($columns['placeholder_labels_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN placeholder_labels_json';
	$db->ExecuteMaster($sql);
}

if(isset($columns['placeholder_values_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN placeholder_values_json';
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Insert default search buttons

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.address'),
	$db->qstr('[{"context":"cerberusweb.contexts.group","label_singular":"","label_plural":"","query":"send.from.id:{{id}}"},{"context":"cerberusweb.contexts.bucket","label_singular":"","label_plural":"","query":"send.from.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.bot'),
	$db->qstr('[{"context":"cerberusweb.contexts.behavior","label_singular":"","label_plural":"","query":"bot.id:{{id}}"},{"context":"cerberusweb.contexts.classifier","label_singular":"","label_plural":"","query":"owner.bot:(id:{{id}})"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.calendar'),
	$db->qstr('[{"context":"cerberusweb.contexts.calendar_event","label_singular":"Event","label_plural":"Events","query":"calendar.id:{{id}}"},{"context":"cerberusweb.contexts.calendar_event.recurring","label_singular":"Recurring Event","label_plural":"Recurring Events","query":"calendar.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.classifier'),
	$db->qstr('[{"context":"cerberusweb.contexts.classifier.class","label_singular":"Classification","label_plural":"Classifications","query":"classifier.id:{{id}}"},{"context":"cerberusweb.contexts.classifier.example","label_singular":"Example","label_plural":"Examples","query":"classifier.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.classifier.class'),
	$db->qstr('[{"context":"cerberusweb.contexts.classifier.example","label_singular":"Example","label_plural":"Examples","query":"classifier.id:{{classifier_id}} class.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.contact'),
	$db->qstr('[{"context":"cerberusweb.contexts.address","label_singular":"","label_plural":"","query":"contact.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.custom_fieldset'),
	$db->qstr('[{"context":"cerberusweb.contexts.custom_field","label_singular":"Field","label_plural":"Fields","query":"fieldset.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.custom_record'),
	$db->qstr('[{"context":"cerberusweb.contexts.custom_field","label_singular":"Field","label_plural":"Fields","query":"context:\"contexts.custom_record.{{id}}\" fieldset.id:0 sort:pos,name"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.datacenter.server'),
	$db->qstr('[{"context":"cerberusweb.contexts.datacenter.domain","label_singular":"","label_plural":"","query":"server:(id:{{id}})"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.email.signature'),
	$db->qstr('[{"context":"cerberusweb.contexts.group","label_singular":"","label_plural":"","query":"signature.id:{{id}}"},{"context":"cerberusweb.contexts.bucket","label_singular":"","label_plural":"","query":"signature.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.group'),
	$db->qstr('[{"context":"cerberusweb.contexts.worker","label_singular":"Member","label_plural":"Members","query":"group:(id:{{id}})"},{"context":"cerberusweb.contexts.bucket","label_singular":"","label_plural":"","query":"group.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.kb_article'),
	$db->qstr('[{"context":"cerberusweb.contexts.kb_category","label_singular":"Category","label_plural":"Categories","query":"article.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.kb_category'),
	$db->qstr('[{"context":"cerberusweb.contexts.kb_article","label_singular":"Article","label_plural":"Articles","query":"category.id:{{id}}"},{"context":"cerberusweb.contexts.kb_category","label_singular":"Subcategory","label_plural":"Subcategories","query":"parent.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.mail.html_template'),
	$db->qstr('[{"context":"cerberusweb.contexts.group","label_singular":"","label_plural":"","query":"template.id:{{id}}"},{"context":"cerberusweb.contexts.bucket","label_singular":"","label_plural":"","query":"template.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.mail.transport'),
	$db->qstr('[{"context":"cerberusweb.contexts.address","label_singular":"","label_plural":"","query":"mailTransport.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.org'),
	$db->qstr('[{"context":"cerberusweb.contexts.contact","label_singular":"","label_plural":"","query":"org.id:{{id}}"},{"context":"cerberusweb.contexts.address","label_singular":"","label_plural":"","query":"org.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.project.board'),
	$db->qstr('[{"context":"cerberusweb.contexts.project.board.column","label_singular":"Column","label_plural":"Columns","query":"board.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.ticket'),
	$db->qstr('[{"context":"cerberusweb.contexts.address","label_singular":"Participant","label_plural":"Participants","query":"ticket.id:{{id}}"},{"context":"cerberusweb.contexts.message","label_singular":"","label_plural":"","query":"ticket.id:{{id}}"},{"context":"cerberusweb.contexts.comment","label_singular":"","label_plural":"","query":"on.ticket:(id:{{id}})"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.worker'),
	$db->qstr('[{"context":"cerberusweb.contexts.group","label_singular":"","label_plural":"","query":"member:(id:{{id}})"},{"context":"cerberusweb.contexts.address","label_singular":"","label_plural":"","query":"worker.id:{{id}}"}]')
));

$db->ExecuteMaster(sprintf("INSERT IGNORE INTO devblocks_setting (plugin_id, setting, value) VALUES (%s, %s, %s)",
	$db->qstr('cerberusweb.core'),
	$db->qstr('card:search:cerberusweb.contexts.workspace.page'),
	$db->qstr('[{"context":"cerberusweb.contexts.workspace.tab","label_singular":"Tab","label_plural":"Tabs","query":"page.id:{{id}} sort:pos"}]')
));

// ===========================================================================
// Finish up

return TRUE;
