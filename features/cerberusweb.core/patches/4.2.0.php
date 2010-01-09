<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2009, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Add a new 'mail_to_group_routing' for more complex routing rules

if(!isset($tables['mail_to_group_rule'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		pos I2 DEFAULT 0 NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		name C(128) DEFAULT '' NOTNULL,
		criteria_ser XL,
		actions_ser XL,
		is_sticky I1 DEFAULT 0 NOTNULL,
		sticky_order I1 DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('mail_to_group_rule', $flds);
	$datadict->ExecuteSQLArray($sql);
}

if(isset($tables['mail_routing'])) {
	$sql = "SELECT id,pattern,team_id,pos FROM mail_routing";
	$rs = $db->Execute($sql);
	
	// Migrate data out of the table and drop it
	while(!$rs->EOF) {
		// Turn 'pattern' into a criteria on 'TO/CC'
		$criteria = array(
			'tocc' => array('value' => $rs->fields['pattern']),
		);

		// Turn 'team_id' into an action
		$actions = array(
			'move' => array('group_id' => intval($rs->fields['team_id']),'bucket_id' => 0),
		);
		
		$sql = sprintf("INSERT INTO mail_to_group_rule (id,pos,created,name,criteria_ser,actions_ser,is_sticky,sticky_order) ".
			"VALUES(%d,%d,%d,%s,%s,%s,0,0)",
			$db->GenID('generic_seq'),
			intval($rs->fields['pos']),
			time(),
			$db->qstr($rs->fields['pattern']),
			$db->qstr(serialize($criteria)),
			$db->qstr(serialize($actions))
		);
		$db->Execute($sql);
		
		$rs->MoveNext();
	}
	
	// Drop it
	$sql = $datadict->DropTableSQL('mail_routing');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Add the sticky/stable concepts to pre-parser rules

$columns = $datadict->MetaColumns('preparse_rule');
$indexes = $datadict->MetaIndexes('preparse_rule',false);

if(!isset($columns['CREATED'])) {
    $sql = $datadict->AddColumnSQL('preparse_rule', 'created I4 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['IS_STICKY'])) {
    $sql = $datadict->AddColumnSQL('preparse_rule', 'is_sticky I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['STICKY_ORDER'])) {
    $sql = $datadict->AddColumnSQL('preparse_rule', 'sticky_order I1 DEFAULT 0 NOTNULL');
    $datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Fix pre-parser criteria that used 'To Group' and convert them to 'To/Cc'

$columns = $datadict->MetaColumns('preparse_rule');

if(isset($columns['CRITERIA_SER'])) {
	$sql = "SELECT id, criteria_ser FROM preparse_rule";
	$rs = $db->Execute($sql);
	
	while(!$rs->EOF) {
		$criteria_ser = $rs->fields['criteria_ser'];
		if(!empty($criteria_ser) && false !== (@$criteria = unserialize($criteria_ser))) {
			if(isset($criteria['to'])) {
				unset($criteria['to']);
				$db->Execute(sprintf("UPDATE preparse_rule SET criteria_ser= %s WHERE id = %d",
					$db->qstr(serialize($criteria)),
					$rs->fields['id']
				));
			}
		}
		$rs->MoveNext();
	}
}

// ===========================================================================
// Increase the size of 'pos' past 2 bytes (32K filter hits)

// Mail Routing
$columns = $datadict->MetaColumns('mail_to_group_rule');

if(isset($columns['POS']) && 0!=strcasecmp('mediumint',$columns['POS']->type)) {
	$sql = sprintf("ALTER TABLE mail_to_group_rule CHANGE COLUMN pos pos mediumint DEFAULT 0 NOT NULL");
	$db->Execute($sql);
}

// Pre-Parser
$columns = $datadict->MetaColumns('preparse_rule');

if(isset($columns['POS']) && 0!=strcasecmp('mediumint',$columns['POS']->type)) {
	$sql = sprintf("ALTER TABLE preparse_rule CHANGE COLUMN pos pos mediumint DEFAULT 0 NOT NULL");
	$db->Execute($sql);
}

// Group Inbox Filters
$columns = $datadict->MetaColumns('group_inbox_filter');

if(isset($columns['POS']) && 0!=strcasecmp('mediumint',$columns['POS']->type)) {
	$sql = sprintf("ALTER TABLE group_inbox_filter CHANGE COLUMN pos pos mediumint DEFAULT 0 NOT NULL");
	$db->Execute($sql);
}

// ===========================================================================
// Enable the new KB plugin by default

if(isset($tables['cerb_plugin'])) {
	$sql = sprintf("UPDATE cerb_plugin SET enabled=1 WHERE id = %s",$db->qstr('cerberusweb.kb'));
	$db->Execute($sql);
}

return TRUE;
