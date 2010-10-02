<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Migrate 'community' to a custom field on portals

if(isset($tables['community'])) {
	$communities = array();
	$sql = "SELECT id,name FROM community ORDER BY name";
	$rs = $db->Execute($sql);
	
	// all communities
	while($row = mysql_fetch_assoc($rs)) {
		$communities[$row['id']] = $row['name'];
	}
	
	mysql_free_result($rs);

	// alphabetize community list
	asort($communities);
	
	if(!empty($communities)) {
		// Make a custom field for the portal
		$sql = sprintf("INSERT INTO custom_field (name,type,group_id,pos,options,source_extension) ".
			"VALUES ('Community','D',0,0,%s,%s)",
			$db->qstr(implode("\n", $communities)),
			$db->qstr('usermeet.fields.source.community_portal')
		);
		$db->Execute($sql);
		$field_id = $db->LastInsertId();

		// Loop through set community_id's on portals
		$sql = "SELECT id, community_id FROM community_tool";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$id = $row['id'];
			$community_id = $row['community_id'];
			
			if(isset($communities[$community_id])) {
				// Populate the custom field from org records
				$sql = sprintf("INSERT INTO custom_field_stringvalue (field_id, source_id, field_value, source_extension) ".
					"VALUES (%d, %d, %s, %s)",
					$field_id,
					$id, // portal id
					$db->qstr($communities[$community_id]), // community name
					$db->qstr('usermeet.fields.source.community_portal')
				);
				$db->Execute($sql);
			}
		}
		
		mysql_free_result($rs);
	}
	
	// Drop 'community' table
	$db->Execute('DROP TABLE community');
}

// ===========================================================================
// Drop 'community_id' on portals (migrated to a custom field above)

list($columns, $indexes) = $db->metaTable('community_tool');

if(isset($columns['community_id'])) {
	$db->Execute('ALTER TABLE community_tool DROP COLUMN community_id');
}

// ===========================================================================
// Fix BLOBS

list($columns, $indexes) = $db->metaTable('community_session');

if(isset($columns['properties'])
	&& 0 != strcasecmp('mediumtext',$columns['properties']['type'])) {
		$db->Execute('ALTER TABLE community_session MODIFY COLUMN properties MEDIUMTEXT');
}

return TRUE;