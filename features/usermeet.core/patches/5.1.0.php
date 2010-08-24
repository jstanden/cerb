<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT UNIQUE
$tables_autoinc = array(
	'community_tool',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->Execute(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT", $table));
	}
}

// ===========================================================================
// Clear unused property keys 

$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.header_html'");
$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.footer_html'");
$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.style_css'");
$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'home.html'");

// ===========================================================================
// Fix BLOBS

list($columns, $indexes) = $db->metaTable('community_session');

if(isset($columns['properties'])
	&& 0 != strcasecmp('mediumtext',$columns['properties']['type'])) {
		$db->Execute('ALTER TABLE community_session MODIFY COLUMN properties MEDIUMTEXT');
}

return TRUE;