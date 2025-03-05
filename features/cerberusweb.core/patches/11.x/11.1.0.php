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
// Finish up

return TRUE;
