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
		$field_id = $db->GenID('custom_field_seq');
		
		// Make a custom field for the portal
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Community','D',0,0,%s,%s)",
			$field_id,
			$db->qstr(implode("\n", $communities)),
			$db->qstr('usermeet.fields.source.community_portal')
		);
		$db->Execute($sql);

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
// Migrate community_tool properties to user-editable templates

$sql = "SELECT ctp.tool_code, ctp.property_key, ctp.property_value FROM community_tool_property ctp INNER JOIN community_tool ct ON (ct.code=ctp.tool_code) WHERE ct.extension_id='sc.tool'";
$rs = $db->Execute($sql);

$portals = array();

while($row = mysql_fetch_assoc($rs)) {
	$code = $row['tool_code'];
	$key = $row['property_key'];
	$value = $row['property_value'];
	
	if(!isset($portals[$code]))
		$portals[$code] = array();
	
	switch($key) {
		case 'common.header_html':
		case 'common.footer_html':
		case 'common.style_css':
		case 'home.html':
			$portals[$code][$key] = $value;
			break;
		default:
			break;
	}
}

mysql_free_result($rs);

if(!empty($portals))
foreach($portals as $portal_code => $props) {
	// Header
	if(isset($props['common.header_html']) && !empty($props['common.header_html'])) {
		$id = $db->GenID('generic_seq');
		$sql = sprintf("INSERT INTO devblocks_template (id, plugin_id, path, tag, last_updated, content) ".
			"VALUES (%d, 'usermeet.core', 'support_center/header.tpl', 'portal_%s', %d, %s)",
			$id,
			$portal_code,
			time(),
			$db->qstr('<div id="header">'.$props['common.header_html'].'</div>')
		);
		$db->Execute($sql);
	}
	
	// Footer
	if(isset($props['common.footer_html']) && !empty($props['common.footer_html'])) {
		$id = $db->GenID('generic_seq');
		$sql = sprintf("INSERT INTO devblocks_template (id, plugin_id, path, tag, last_updated, content) ".
			"VALUES (%d, 'usermeet.core', 'support_center/footer.tpl', 'portal_%s', %d, %s)",
			$id,
			$portal_code,
			time(),
			$db->qstr('<div id="footer">'.$props['common.footer_html'].'</div>')
		);
		$db->Execute($sql);
	}
	
	// Style
	if(isset($props['common.style_css']) && !empty($props['common.style_css'])) {
		$css = '';
		$css_path = dirname(dirname(__FILE__)) . '/templates/support_center/style.css.tpl';
		if(file_exists($css_path)) {
			$css = file_get_contents($css_path);
			$css .= "\n\n/* User-defined styles */\n\n";
		}
		$css .= $props['common.style_css'];
		
		$id = $db->GenID('generic_seq');
		$sql = sprintf("INSERT INTO devblocks_template (id, plugin_id, path, tag, last_updated, content) ".
			"VALUES (%d, 'usermeet.core', 'support_center/style.css.tpl', 'portal_%s', %d, %s)",
			$id,
			$portal_code,
			time(),
			$db->qstr($css)
		);
		$db->Execute($sql);
	}
	
	// Welcome
	if(isset($props['home.html']) && !empty($props['home.html'])) {
		$id = $db->GenID('generic_seq');
		$sql = sprintf("INSERT INTO devblocks_template (id, plugin_id, path, tag, last_updated, content) ".
			"VALUES (%d, 'usermeet.core', 'support_center/home/index.tpl', 'portal_%s', %d, %s)",
			$id,
			$portal_code,
			time(),
			$db->qstr('<div id="home">'.$props['home.html'].'</div>')
		);
		$db->Execute($sql);
	}
}

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