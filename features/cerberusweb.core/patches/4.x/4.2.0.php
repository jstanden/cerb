<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Add a new 'mail_to_group_routing' for more complex routing rules

if(!isset($tables['mail_to_group_rule'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS mail_to_group_rule (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
			pos SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			criteria_ser MEDIUMTEXT,
			actions_ser MEDIUMTEXT,
			is_sticky TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			sticky_order TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('mail_to_group_rule');

if(isset($columns['id']) 
	&& ('int(10) unsigned' != $columns['id']['type'] 
	|| 'auto_increment' != $columns['id']['extra'])
) {
	$db->Execute("ALTER TABLE mail_to_group_rule MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE");
}

if(isset($tables['mail_routing'])) {
	$sql = "SELECT id,pattern,team_id,pos FROM mail_routing";
	$rs = $db->Execute($sql);
	
	// Migrate data out of the table and drop it
	while($row = mysql_fetch_assoc($rs)) {
		// Turn 'pattern' into a criteria on 'TO/CC'
		$criteria = array(
			'tocc' => array('value' => $row['pattern']),
		);

		// Turn 'team_id' into an action
		$actions = array(
			'move' => array('group_id' => intval($row['team_id']),'bucket_id' => 0),
		);
		
		$sql = sprintf("INSERT INTO mail_to_group_rule (pos,created,name,criteria_ser,actions_ser,is_sticky,sticky_order) ".
			"VALUES(%d,%d,%s,%s,%s,0,0)",
			intval($row['pos']),
			time(),
			$db->qstr($row['pattern']),
			$db->qstr(serialize($criteria)),
			$db->qstr(serialize($actions))
		);
		$db->Execute($sql);
	}
	
	mysql_free_result($rs);
	
	// Drop it
	$db->Execute('DROP TABLE mail_routing');
}

// ===========================================================================
// Add the sticky/stable concepts to pre-parser rules

list($columns, $indexes) = $db->metaTable('preparse_rule');

if(!isset($columns['created'])) {
    $db->Execute('ALTER TABLE preparse_rule ADD COLUMN created INT UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['is_sticky'])) {
    $db->Execute('ALTER TABLE preparse_rule ADD COLUMN is_sticky TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($columns['sticky_order'])) {
    $db->Execute('ALTER TABLE preparse_rule ADD COLUMN sticky_order TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
}

// ===========================================================================
// Fix pre-parser criteria that used 'To Group' and convert them to 'To/Cc'

list($columns, $indexes) = $db->metaTable('preparse_rule');

if(isset($columns['criteria_ser'])) {
	$sql = "SELECT id, criteria_ser FROM preparse_rule";
	$rs = $db->Execute($sql);
	
	while($row = mysql_fetch_assoc($rs)) {
		$criteria_ser = $row['criteria_ser'];
		if(!empty($criteria_ser) && false !== (@$criteria = unserialize($criteria_ser))) {
			if(isset($criteria['to'])) {
				unset($criteria['to']);
				$db->Execute(sprintf("UPDATE preparse_rule SET criteria_ser= %s WHERE id = %d",
					$db->qstr(serialize($criteria)),
					$row['id']
				));
			}
		}
	}
	
	mysql_free_result($rs);
}

// ===========================================================================
// Increase the size of 'pos' past 2 bytes (32K filter hits)

// Mail Routing
list($columns, $indexes) = $db->metaTable('mail_to_group_rule');

if(isset($columns['pos']) && 0 != strcasecmp('int',substr($columns['pos']['type'],0,3))) {
	$db->Execute('ALTER TABLE mail_to_group_rule CHANGE COLUMN pos pos int unsigned DEFAULT 0 NOT NULL');
}

// Pre-Parser
list($columns, $indexes) = $db->metaTable('preparse_rule');

if(isset($columns['pos']) && 0 != strcasecmp('int',substr($columns['pos']['type'],0,3))) {
	$db->Execute("ALTER TABLE preparse_rule CHANGE COLUMN pos pos int unsigned DEFAULT 0 NOT NULL");
}

// Group Inbox Filters
list($columns, $indexes) = $db->metaTable('group_inbox_filter');

if(isset($columns['pos']) && 0 != strcasecmp('int',substr($columns['pos']['type'],0,3))) {
	$db->Execute("ALTER TABLE group_inbox_filter CHANGE COLUMN pos pos int unsigned DEFAULT 0 NOT NULL");
}

return TRUE;
