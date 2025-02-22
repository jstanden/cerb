<?php
/** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// LLM chat history

if(!isset($tables['llm_agent_session'])) {
	$sql = sprintf("
		CREATE TABLE `llm_agent_session` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`uuid` varchar(64) NOT NULL DEFAULT '',
		`provider` varchar(32) NOT NULL DEFAULT '',
		`created_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (uuid(6)),
		INDEX (created_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['llm_agent_session'] = 'llm_agent_session';
}

if(!isset($tables['llm_agent_message'])) {
	$sql = sprintf("
		CREATE TABLE `llm_agent_message` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`session_uuid` varchar(64) DEFAULT '' NOT NULL,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`data_json` MEDIUMTEXT,
		PRIMARY KEY (id),
		INDEX (created_at),
		INDEX (session_uuid(6))
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['llm_agent_message'] = 'llm_agent_message';
}

// ===========================================================================
// Finish up

return TRUE;
