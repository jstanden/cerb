<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add custom_placeholders to `snippet`

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['custom_placeholders_json'])) {
	$db->Execute("ALTER TABLE snippet ADD COLUMN custom_placeholders_json MEDIUMTEXT");
	
	// Migrate old style snippet placeholders to JSON (single, w/default, multiple)
	$rs = $db->Execute("SELECT id, content FROM snippet");
	
	while($row = mysqli_fetch_assoc($rs)) {
		$content = $row['content'];
		
		// Extract unique placeholders and formalize
		
		// Multiple line
		$matches = array();
		if(preg_match_all("#\(\(\_\_(.*?)\_\_\)\)#", $content, $matches)) {
			$changes = array();
			$placeholders = array();
			
			foreach($matches[1] as $match_id => $match) {
				if(isset($changes[$matches[0][$match_id]])) {
					continue;
				}
				
				$label = trim($match,'_');
				$placeholder = 'prompt_' . DevblocksPlatform::strAlphaNum($label,'_','_');
				
				// Multiple line
				if(substr($match,0,2) == '__') {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_MULTI_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => '',
					);
					
				// Single line w/ default
				} else if(substr($match,0,1) == '_') {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => $label,
					);
					
				// Single line
				} else {
					$placeholders[$placeholder] = array(
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
						'key' => $placeholder,
						'label' => $label,
						'default' => '',
					);
					
				}
				
				$content = str_replace($matches[0][$match_id], '{{' . $placeholder . '}}', $content);
				
				$changes[$matches[0][$match_id]] = $placeholder;
			}
			
			if(!empty($placeholders)) {
				$db->Execute(sprintf("UPDATE snippet SET content = %s, custom_placeholders_json = %s WHERE id = %d",
					$db->qstr($content),
					$db->qstr(json_encode($placeholders)),
					$row['id']
				));
			}
		}
	}
}

// ===========================================================================
// Set a default for 'max_results' in search indexes using the MySQL Fulltext engine

$db->Execute(sprintf("UPDATE cerb_property_store SET value = %s WHERE property = 'engine_params_json' AND value = %s",
	$db->qstr('{"engine_extension_id":"devblocks.search.engine.mysql_fulltext","config":{"max_results":"500"}}'),
	$db->qstr('{"engine_extension_id":"devblocks.search.engine.mysql_fulltext","config":[]}')
));

// ===========================================================================
// Reset snippet searches

$db->Execute(sprintf("DELETE FROM worker_view_model WHERE view_id = 'search_cerberusweb_contexts_snippet'"));

// ===========================================================================
// Fix worker_view_model records for ticket searches forcing a required group_id

$db->Execute("DELETE FROM worker_view_model WHERE view_id IN ('search_cerberusweb_contexts_ticket','search_cerberusweb_contexts_message') AND params_required_json LIKE '%t_group_id%'");
$db->Execute("DELETE FROM worker_view_model WHERE view_id LIKE 'api_search_%' AND params_required_json LIKE '%t_group_id%'");

// ===========================================================================
// Add `timeout_secs` to `pop3_account`

if(!isset($tables['pop3_account'])) {
	$logger->error("The 'pop3_account' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('pop3_account');

if(!isset($columns['timeout_secs'])) {
	$db->Execute("ALTER TABLE pop3_account ADD COLUMN timeout_secs MEDIUMINT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE pop3_account SET timeout_secs = 30");
}

// ===========================================================================
// Set up defaults for mobile mail preferences

$db->Execute("INSERT IGNORE INTO worker_pref (worker_id, setting, value) SELECT id, 'mobile_mail_signature_pos', '2' FROM worker");

// ===========================================================================
// Add @mention nicknames to worker records

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['at_mention_name'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN at_mention_name VARCHAR(64)");
	$db->Execute("UPDATE worker SET at_mention_name = CONCAT(first_name,last_name)");
}

// ===========================================================================
// Finish up

return TRUE;
