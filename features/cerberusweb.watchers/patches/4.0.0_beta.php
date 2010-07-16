<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `worker_mail_forward` ========================
if(!isset($tables['worker_mail_forward'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS worker_mail_forward (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			group_id INT UNSIGNED DEFAULT 0 NOT NULL,
			bucket_id INT UNSIGNED DEFAULT -1 NOT NULL,
			email VARCHAR(128) DEFAULT '' NOT NULL,
			event VARCHAR(3) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('worker_mail_forward');

if(!isset($indexes['worker_id'])) {
	$db->Execute('ALTER TABLE worker_mail_forward ADD INDEX worker_id (worker_id)');
}

if(!isset($indexes['group_id'])) {
	$db->Execute('ALTER TABLE worker_mail_forward ADD INDEX group_id (group_id)');
}

if(!isset($indexes['bucket_id'])) {
	$db->Execute('ALTER TABLE worker_mail_forward ADD INDEX bucket_id (bucket_id)');
}

/*
 * [JAS]: We need to clean up any orphaned data in our notifications from 
 * workers that were already deleted.
 */
$sql = "SELECT DISTINCT wmf.worker_id 
	FROM worker_mail_forward wmf 
	LEFT JOIN worker w ON (w.id=wmf.worker_id) 
	WHERE w.id IS NULL";

if(false !== ($rs = $db->Execute($sql))) {
	while($row = mysql_fetch_assoc($rs)) {
		$sql = sprintf("DELETE FROM worker_mail_forward WHERE worker_id = %d",
			$row['worker_id']
		);
		$db->Execute($sql);
	}
	
	mysql_free_result($rs);
}

// ===========================================================================
// Clean up mail forwards where the group or buckets were removed

$sql = "DELETE worker_mail_forward FROM worker_mail_forward LEFT JOIN team ON (team.id=worker_mail_forward.group_id) WHERE team.id IS NULL";
$db->Execute($sql);

$sql = "DELETE worker_mail_forward FROM worker_mail_forward LEFT JOIN category ON (category.id=worker_mail_forward.bucket_id) WHERE worker_mail_forward.bucket_id > 0 AND category.id IS NULL";
$db->Execute($sql);

// ===========================================================================
// Add a table for new worker notification filters

if(!isset($tables['watcher_mail_filter'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS watcher_mail_filter (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			pos SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			criteria_ser MEDIUMTEXT,
			actions_ser MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('watcher_mail_filter');

if(isset($columns['id']) 
	&& ('int(10) unsigned' != $columns['id']['type'] 
	|| 'auto_increment' != $columns['id']['extra'])
) {
	$db->Execute("ALTER TABLE watcher_mail_filter MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE");
}

// Migrate and then drop the old forward table
if(isset($tables['worker_mail_forward'])) {
	$sql = "SELECT id, worker_id, group_id, bucket_id, email, event FROM worker_mail_forward";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		@$old_id = intval($row['id']);
		@$worker_id = intval($row['worker_id']);
		@$group_id = intval($row['group_id']);
		@$bucket_id = intval($row['bucket_id']);
		@$email = $row['email'];
		@$event = $row['event'];
		
		$name = 'Notification';
		$criteria = array();
		$actions = array();
	
		// Group+Bucket
		if(!empty($group_id)) {	
			$group = array($group_id=>null);
			
			if(-1 == $bucket_id) // "All"
				$group = array($group_id=>array());
			else // specific buckets
				$group = array($group_id=>array($bucket_id));
				
			$criteria['groups'] = array('groups' => $group);
		}
		
		$criteria['event'] = array();
		
		switch($event) {
			case 'i': // incoming
				$name = "Incoming messages";
				$criteria['event']['mail_incoming'] = true;
				break;
			case 'o': // outgoing
				$name = "Outgoing messages";
				$criteria['event']['mail_outgoing'] = true;
				break;
			case 'io': // in+out
				$name = "All messages";
				$criteria['event']['mail_incoming'] = true;
				$criteria['event']['mail_outgoing'] = true;
				break;
			case 'r': // reply
				$name = "Replies to me";
				$criteria['event']['mail_incoming'] = true;
				$criteria['next_worker_id'] = array('value' => $worker_id);
				break;
		}
		
		// Actions
		if(!empty($email)) {
			$actions['email'] = array('to' => array($email));
		}
		
		// Create new filter
		$sql = sprintf("INSERT INTO watcher_mail_filter (pos,name,created,worker_id,criteria_ser,actions_ser) ".
			"VALUES (%d,%s,%d,%d,%s,%s)",
			0,
			$db->qstr($name),
			time(),
			$worker_id,
			$db->qstr(serialize($criteria)),
			$db->qstr(serialize($actions))
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		// Delete source row (for partial success)
		$db->Execute(sprintf("DELETE FROM worker_mail_forward WHERE worker_id=%d AND id=%d", $worker_id, $old_id));
	}
	
	mysql_free_result($rs);
	
	// Drop old table
	$db->Execute('DROP TABLE worker_mail_forward');
}

// ===========================================================================
// Clear the old worker preference for assignment

if(isset($tables['worker_pref'])) {
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'watchers_assign_email'");
}

return TRUE;
