<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `bot_interaction_proactive` table

if(!isset($tables['bot_interaction_proactive'])) {
	$sql = sprintf("
	CREATE TABLE `bot_interaction_proactive` (
		id int unsigned auto_increment,
		actor_bot_id int unsigned not null default 0,
		worker_id int unsigned not null default 0,
		behavior_id int unsigned not null default 0,
		interaction varchar(255) not null default '',
		interaction_params_json mediumtext,
		run_at int unsigned not null default 0,
		updated_at int unsigned not null default 0,
		expires_at int unsigned not null default 0,
		primary key (id),
		index (worker_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['bot_interaction_proactive'] = 'bot_interaction_proactive';
}

// ===========================================================================
// Add `worker_view_model.render_sort_json`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['render_sort_json'])) {
	$db->ExecuteMaster("ALTER TABLE worker_view_model ADD COLUMN render_sort_json varchar(255) not null default ''");

	// Migrate sorting data to JSON format
$sql = <<< EOD
UPDATE worker_view_model SET render_sort_json = concat('{"',render_sort_by,'":',IF(0=render_sort_asc,'false','true'),'}') WHERE render_sort_json = '';
EOD;
	
	$db->ExecuteMaster($sql);

	// Drop the old columns
	$db->ExecuteMaster('ALTER TABLE worker_view_model DROP COLUMN render_sort_by, DROP COLUMN render_sort_asc');
}

// ===========================================================================
// Add `gpg_public_key` table

if(!isset($tables['gpg_public_key'])) {
	$sql = sprintf("
	CREATE TABLE `gpg_public_key` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		fingerprint VARCHAR(255) DEFAULT '',
		expires_at int unsigned not null default 0,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id),
		index fingerprint (fingerprint(4))
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['gpg_public_key'] = 'gpg_public_key';
}

// ===========================================================================
// Add `message.was_encrypted` and `message.was_signed`

if(!isset($tables['message'])) {
	$logger->error("The 'message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('message');

$changes = [];

if(!isset($columns['was_encrypted'])) {
	$changes[] = 'ADD COLUMN was_encrypted tinyint(1) not null default 0';
	$changes[] = 'ADD INDEX (was_encrypted)';
}
	
if(!isset($columns['was_signed']))
	$changes[] = 'ADD COLUMN was_signed tinyint(1) not null default 0';
	
if(!empty($changes))
	$db->ExecuteMaster("ALTER TABLE message " . implode(', ', $changes));

// ===========================================================================
// Add `worker_role.updated_at` and `worker_role.privs_json`

if(!isset($tables['worker_role'])) {
	$logger->error("The 'worker_role' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_role');

$changes = [];

if(!isset($columns['updated_at'])) {
	$changes[] = 'ADD COLUMN updated_at int unsigned not null default 0';
	$changes[] = 'ADD INDEX (updated_at)';
}
	
if(!isset($columns['privs_json']))
	$changes[] = 'ADD COLUMN privs_json MEDIUMTEXT';
	
if(!empty($changes))
	$db->ExecuteMaster("ALTER TABLE worker_role " . implode(', ', $changes));

$db->ExecuteMaster('UPDATE worker_role SET updated_at = UNIX_TIMESTAMP() WHERE updated_at = 0');

// ===========================================================================
// Migrate `worker_role_acl` to `worker_role.privs_json`

if(isset($tables['worker_role_acl'])) {
	$results = $db->GetArrayMaster("SELECT DISTINCT role_id FROM worker_role_acl");
	$role_ids = array_column($results, 'role_id');
	
	$remapping = [
		'calls.actions.update_all' => 'contexts.cerberusweb.contexts.call.update',
		'context.contact.worklist.broadcast' => 'contexts.cerberusweb.contexts.contact.broadcast',
		'context.org.worklist.broadcast' => 'contexts.cerberusweb.contexts.org.broadcast',
		'context.worker.worklist.broadcast' => 'contexts.cerberusweb.contexts.worker.broadcast',
		'core.addybook.addy.actions.update' => 'contexts.cerberusweb.contexts.address.update',
		'core.addybook.addy.view.actions.broadcast' => 'contexts.cerberusweb.contexts.address.broadcast',
		'core.addybook.addy.view.actions.export' => 'contexts.cerberusweb.contexts.address.export',
		'core.addybook.contact.view.actions.export' => 'contexts.cerberusweb.contexts.contact.export',
		'core.addybook.org.actions.delete' => 'contexts.cerberusweb.contexts.org.delete',
		'core.addybook.org.actions.merge' => 'contexts.cerberusweb.contexts.org.merge',
		'core.addybook.org.actions.update' => 'contexts.cerberusweb.contexts.org.update',
		'core.addybook.org.view.actions.export' => 'contexts.cerberusweb.contexts.org.export',
		'core.addybook.person.actions.delete' => 'contexts.cerberusweb.contexts.contact.delete',
		'core.addybook.person.actions.update' => 'contexts.cerberusweb.contexts.contact.update',
		'core.comment.actions.update.own' => 'contexts.cerberusweb.contexts.comment.update',
		'core.display.actions.note' => 'contexts.cerberusweb.contexts.message.comment',
		'core.display.message.actions.delete' => 'contexts.cerberusweb.contexts.message.delete',
		'core.kb.articles.modify' => 'contexts.cerberusweb.contexts.kb_article.update',
		'core.kb.categories.modify' => 'contexts.cerberusweb.contexts.kb_category.update',
		'core.mail.draft.delete_all' => 'contexts.cerberusweb.contexts.draft.delete',
		'core.mail.send' => 'contexts.cerberusweb.contexts.ticket.create', 
		'core.snippets.actions.create' => 'contexts.cerberusweb.contexts.snippet.create',
		'core.tasks.actions.create' => 'contexts.cerberusweb.contexts.task.create',
		'core.tasks.actions.delete' => 'contexts.cerberusweb.contexts.task.delete',
		'core.tasks.actions.update_all' => 'contexts.cerberusweb.contexts.task.update.bulk',
		'core.tasks.view.actions.export' => 'contexts.cerberusweb.contexts.task.export',
		'core.ticket.actions.delete' => 'contexts.cerberusweb.contexts.delete',
		'core.ticket.view.actions.broadcast_reply' => 'contexts.cerberusweb.contexts.ticket.broadcast',
		'core.ticket.view.actions.bulk_update' => 'contexts.cerberusweb.contexts.ticket.update.bulk',
		'core.ticket.view.actions.export' => 'contexts.cerberusweb.contexts.ticket.export',
		'core.ticket.view.actions.merge' => 'contexts.cerberusweb.contexts.ticket.merge',
		'crm.opp.actions.create' => 'contexts.cerberusweb.contexts.opportunity.create',
		'crm.opp.actions.delete' => 'contexts.cerberusweb.contexts.opportunity.delete',
		'crm.opp.actions.import' => 'contexts.cerberusweb.contexts.opportunity.import',
		'crm.opp.actions.update_all' => 'contexts.cerberusweb.contexts.opportunity.update',
		'crm.opp.view.actions.broadcast' => 'contexts.cerberusweb.contexts.opportunity.broadcast',
		'crm.opp.view.actions.export' => 'contexts.cerberusweb.contexts.opportunity.export',
		'datacenter.domains.actions.delete' => 'contexts.cerberusweb.contexts.datacenter.domain.delete',
		'feedback.actions.create' => 'contexts.cerberusweb.contexts.feedback.create',
		'feedback.actions.delete_all' => 'contexts.cerberusweb.contexts.feedback.delete',
		'feedback.actions.update_all' => 'contexts.cerberusweb.contexts.feedback.update',
		'feedback.view.actions.export' => 'contexts.cerberusweb.contexts.feedback.export',
		'kb.articles.actions.update_all' => 'contexts.cerberusweb.contexts.kb_article.update.bulk',
		'timetracking.actions.create' => 'contexts.cerberusweb.contexts.timetracking.create',
		'timetracking.actions.update_all' => 'contexts.cerberusweb.contexts.timetracking.update.bulk',
		'timetracking.views.actions.export' => 'contexts.cerberusweb.contexts.timetracking.export',
	];
	
	foreach($role_ids as $role_id) {
		$sql = sprintf("SELECT priv_id FROM worker_role_acl WHERE role_id = %d ORDER BY priv_id", $role_id);
		$results = $db->GetArrayMaster($sql);
		$privs = array_column($results, 'priv_id');
		
		foreach($privs as $idx => &$priv) {
			if(DevblocksPlatform::strStartsWith($priv, 'plugin.')) {
				unset($privs[$idx]);
				continue;
			}
			
			switch($priv) {
				case 'core.kb.categories.modify':
					unset($privs[$idx]);
					$privs[] = 'contexts.cerberusweb.contexts.kb_category.create';
					$privs[] = 'contexts.cerberusweb.contexts.kb_category.update';
					$privs[] = 'contexts.cerberusweb.contexts.kb_category.delete';
					continue;
					break;
			}
			
			// Are we renaming the privilege?
			if(isset($remapping[$priv]))
				$priv = $remapping[$priv];
		}
		
		sort($privs);
		
		$privs_json = json_encode(array_values($privs));
		
		// Update role
		$db->ExecuteMaster(sprintf("UPDATE worker_role SET privs_json = %s WHERE id = %d",
			$db->qstr($privs_json),
			$role_id
		));
	}
	
	// Drop table
	$db->ExecuteMaster('DROP TABLE worker_role_acl');
	unset($tables['worker_role_acl']);
}

// ===========================================================================
// Default GPG setup

$gpg_path = APP_STORAGE_PATH . '/.gnupg';
$gpg_agent_config_path = $gpg_path . '/gpg-agent.conf';

if(!file_exists($gpg_path))
	@mkdir($gpg_path, 0770);

if(!file_exists($gpg_agent_config_path))
	file_put_contents($gpg_agent_config_path, "batch\n");

// ===========================================================================
// Add `custom_record`

if(!isset($tables['custom_record'])) {
	$sql = sprintf("
	CREATE TABLE `custom_record` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(255) DEFAULT '',
		params_json TEXT,
		updated_at INT UNSIGNED NOT NULL DEFAULT 0,
		primary key (id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['custom_record'] = 'custom_record';
}

// ===========================================================================
// Finish up

return TRUE;
