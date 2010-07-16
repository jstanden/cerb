<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `ticket_audit_log` ========================
if(!isset($tables['ticket_audit_log'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS ticket_audit_log (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			ticket_id INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			change_date INT UNSIGNED DEFAULT 0 NOT NULL,
			change_field VARCHAR(64) DEFAULT '' NOT NULL,
			change_value VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('ticket_audit_log');

if(!isset($indexes['ticket_id'])) {
	$db->Execute('ALTER TABLE ticket_audit_log ADD INDEX ticket_id (ticket_id)');
}

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE ticket_audit_log ADD INDEX worker_id (worker_id)');
}

// Cleanup (these fields made no sense to audit, as they will automatically change often)
$db->Execute("DELETE FROM ticket_audit_log WHERE change_field IN ('interesting_words','updated_date','last_worker_id','first_wrote_address_id','last_wrote_address_id','first_message_id','mask');");

return TRUE;
