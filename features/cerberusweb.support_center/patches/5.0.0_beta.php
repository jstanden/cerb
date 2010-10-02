<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

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
		$sql = sprintf("INSERT INTO devblocks_template (plugin_id, path, tag, last_updated, content) ".
			"VALUES ('usermeet.core', 'support_center/header.tpl', 'portal_%s', %d, %s)",
			$portal_code,
			time(),
			$db->qstr('<div id="header">'.$props['common.header_html'].'</div>')
		);
		$db->Execute($sql);
	}
	
	// Footer
	if(isset($props['common.footer_html']) && !empty($props['common.footer_html'])) {
		$sql = sprintf("INSERT INTO devblocks_template (plugin_id, path, tag, last_updated, content) ".
			"VALUES ('usermeet.core', 'support_center/footer.tpl', 'portal_%s', %d, %s)",
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
		
		$sql = sprintf("INSERT INTO devblocks_template (plugin_id, path, tag, last_updated, content) ".
			"VALUES ('usermeet.core', 'support_center/style.css.tpl', 'portal_%s', %d, %s)",
			$portal_code,
			time(),
			$db->qstr($css)
		);
		$db->Execute($sql);
	}
	
	// Welcome
	if(isset($props['home.html']) && !empty($props['home.html'])) {
		$sql = sprintf("INSERT INTO devblocks_template (plugin_id, path, tag, last_updated, content) ".
			"VALUES ('usermeet.core', 'support_center/home/index.tpl', 'portal_%s', %d, %s)",
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

return TRUE;