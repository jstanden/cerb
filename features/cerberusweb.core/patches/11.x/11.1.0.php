<?php
/** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// LLM agent session

if(!isset($tables['llm_agent_session'])) {
	$sql = sprintf("
		CREATE TABLE `llm_agent_session` (
		`uuid` binary(16) NOT NULL,
		`provider` varchar(32) NOT NULL DEFAULT '',
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`automation_id` int unsigned NOT NULL DEFAULT 0,
		`automation_node` varchar(255) NOT NULL DEFAULT '',
		`user_type` varchar(64) NOT NULL DEFAULT '',
		`user_id` int unsigned NOT NULL DEFAULT 0,
		`user_ip` varchar(64) NOT NULL DEFAULT '',
		`is_read` tinyint NOT NULL DEFAULT 0,
		PRIMARY KEY (uuid),
		INDEX (created_at),
		INDEX `user` (user_type, user_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['llm_agent_session'] = 'llm_agent_session';
}

// ===========================================================================
// LLM agent message

if(!isset($tables['llm_agent_message'])) {
	$sql = sprintf("
		CREATE TABLE `llm_agent_message` (
		`uuid` binary(16) NOT NULL,
		`seq` int unsigned AUTO_INCREMENT,
		`session_uuid` binary(16) NOT NULL,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`data_json` MEDIUMTEXT,
		PRIMARY KEY (uuid),
		UNIQUE (seq),
		INDEX (session_uuid)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['llm_agent_message'] = 'llm_agent_message';
}

// ===========================================================================
// Update package library

$packages = [
	'cerb_connected_service_airtable.json',
	'cerb_connected_service_bluesky.json',
	'cerb_connected_service_buttondown.json',
	'cerb_connected_service_elevenlabs.json',
	'cerb_connected_service_exa.json',
	'cerb_connected_service_groq.json',
	'cerb_connected_service_linkup.json',
	'cerb_connected_service_notion.json',
	'cerb_connected_service_openweather.json',
	'cerb_connected_service_pinecone.json',
	'cerb_connected_service_sambanova.json',
	'cerb_connected_service_stripe.json',
	'cerb_connected_service_tavily.json',
	'cerb_connected_service_telegram.json',
	'cerb_connected_service_togetherai.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Convert saved search profile page 'Query' widgets from HTML/JS to sheet

$db->ExecuteMaster(
	sprintf("update profile_widget set extension_id = 'cerb.profile.tab.widget.sheet', extension_params_json = %s where profile_tab_id in (select id from profile_tab where context = 'cerberusweb.contexts.context.saved.search') and name = 'Query' and extension_id = 'cerb.profile.tab.widget.html'",
		$db->qstr("{\"data_query\": \"type:worklist.records\\r\\nof:saved_search\\r\\nquery:(\\r\\n  id:{{record_id}}\\r\\n)\\r\\nformat:dictionaries\",\"cache_secs\": \"\",\"placeholder_simulator_kata\": \"\",\"sheet_kata\": \"layout:\\r\\n  style: table\\r\\n  headings@bool: no\\r\\n  paging@bool: no\\r\\n  title_column: query\\r\\n\\r\\ncolumns:\\r\\n  text/query:\\r\\n    params:\\r\\n      text_size@raw: 125%\\r\\n  search_button/_query:\\r\\n    params:\\r\\n      context_key: context\\r\\n      query_key: query\",\"toolbar_kata\": \"\"}")
	)
);

// ===========================================================================
// Resize `worker_view_model`

list($columns, ) = $db->metaTable('worker_view_model');

$changes = [];

if(array_key_exists('class_name', $columns) && 'varchar(128)' != $columns['class_name']['type']) {
	$changes[] = "MODIFY COLUMN class_name varchar(128) NOT NULL DEFAULT ''";
}

if(array_key_exists('render_sort_json', $columns) && 'varchar(128)' != $columns['render_sort_json']['type']) {
	$changes[] = "MODIFY COLUMN render_sort_json varchar(128) NOT NULL DEFAULT ''";
}

if(array_key_exists('render_subtotals', $columns) && 'varchar(128)' != $columns['render_subtotals']['type']) {
	$changes[] = "MODIFY COLUMN render_subtotals varchar(128) NOT NULL DEFAULT ''";
}

if(array_key_exists('render_template', $columns) && 'varchar(64)' != $columns['render_template']['type']) {
	$changes[] = "MODIFY COLUMN render_template varchar(64) NOT NULL DEFAULT ''";
}

if(array_key_exists('params_timezone', $columns) && 'varchar(128)' != $columns['params_timezone']['type']) {
	$changes[] = "MODIFY COLUMN params_timezone varchar(128) NOT NULL DEFAULT ''";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model ".
		implode(', ', $changes)
	);
}

// ===========================================================================
// Finish up

return TRUE;
