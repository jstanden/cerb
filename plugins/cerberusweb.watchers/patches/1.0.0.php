<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// `worker_mail_forward` ========================
if(!isset($tables['worker_mail_forward'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		worker_id I4 DEFAULT 0 NOTNULL,
		group_id I4 DEFAULT 0 NOTNULL,
		bucket_id I4 DEFAULT -1 NOTNULL,
		email C(128) DEFAULT '' NOTNULL,
		event C(3) DEFAULT '' NOTNULL
	";
	$sql = $datadict->CreateTableSQL('worker_mail_forward', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker_mail_forward');
$indexes = $datadict->MetaIndexes('worker_mail_forward',false);

if(!isset($indexes['worker_id'])) {
	$sql = $datadict->CreateIndexSQL('worker_id','worker_mail_forward','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['group_id'])) {
	$sql = $datadict->CreateIndexSQL('group_id','worker_mail_forward','group_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['bucket_id'])) {
	$sql = $datadict->CreateIndexSQL('bucket_id','worker_mail_forward','bucket_id');
	$datadict->ExecuteSQLArray($sql);
}

/*
 * [JAS]: We need to clean up any orphaned data in our notifications from 
 * workers that were already deleted.
 */
$sql = "SELECT DISTINCT wmf.worker_id 
	FROM worker_mail_forward wmf 
	LEFT JOIN worker w ON (w.id=wmf.worker_id) 
	WHERE w.id IS NULL";
$rs = $db->Execute($sql);

while(!$rs->EOF) {
	$sql = sprintf("DELETE FROM worker_mail_forward WHERE worker_id = %d",
		$rs->fields['worker_id']
	);
	$db->Execute($sql);
	$rs->MoveNext();
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
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		pos I2 DEFAULT 0 NOTNULL,
		name C(128) DEFAULT '' NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		criteria_ser XL,
		actions_ser XL
	";
	$sql = $datadict->CreateTableSQL('watcher_mail_filter', $flds);
	$datadict->ExecuteSQLArray($sql);
}

// Migrate and then drop the old forward table
if(isset($tables['worker_mail_forward'])) {
	$sql = "SELECT id, worker_id, group_id, bucket_id, email, event FROM worker_mail_forward";
	$rs = $db->Execute($sql);
	
	while(!$rs->EOF) {
		@$old_id = intval($rs->fields['id']);
		@$worker_id = intval($rs->fields['worker_id']);
		@$group_id = intval($rs->fields['group_id']);
		@$bucket_id = intval($rs->fields['bucket_id']);
		@$email = $rs->fields['email'];
		@$event = $rs->fields['event'];
		
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
		
		switch($event) {
			case 'i': // incoming
				$name = "Incoming messages";
				$criteria['is_outgoing'] = array('value' => 0);
				break;
			case 'o': // outgoing
				$name = "Outgoing messages";
				$criteria['is_outgoing'] = array('value' => 1);
				break;
			case 'io': // in+out
				$name = "All messages";
				// Ignoring this is the same as in||out
				break;
			case 'r': // reply
				$name = "Replies to me";
				$criteria['is_outgoing'] = array('value' => 0);
				$criteria['next_worker_id'] = array('value' => $worker_id);
				break;
		}
		
		// Actions
		if(!empty($email)) {
			$actions['email'] = array('to' => array($email));
		}
		
		// Create new filter
		$id = $db->GenID('generic_seq');
		$sql = sprintf("INSERT INTO watcher_mail_filter (id,pos,name,created,worker_id,criteria_ser,actions_ser) ".
			"VALUES (%d,%d,%s,%d,%d,%s,%s)",
			$id,
			0,
			$db->qstr($name),
			time(),
			$worker_id,
			$db->qstr(serialize($criteria)),
			$db->qstr(serialize($actions))
		);
		$db->Execute($sql);
		
		// Delete source row (for partial success)
		$db->Execute(sprintf("DELETE FROM worker_mail_forward WHERE worker_id=%d AND id=%d", $worker_id, $old_id));
		
		$rs->MoveNext();
	}
	
	// Drop old table
	$sql = $datadict->DropTableSQL('worker_mail_forward');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
