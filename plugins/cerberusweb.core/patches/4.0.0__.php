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

$tables = array();
$indexes = array();

// ***** Application

$tables['ticket'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	mask C(16) DEFAULT '' NOTNULL, 
	subject C(255)  DEFAULT '' NOTNULL,
	is_closed I1 DEFAULT 0 NOTNULL,
	is_deleted I1 DEFAULT 0 NOTNULL,
	team_id I4 DEFAULT 0 NOTNULL,
	category_id I4 DEFAULT 0 NOTNULL,
	first_message_id I4 DEFAULT 0 NOTNULL,
	created_date I4,
	updated_date I4,
	due_date I4,
	first_wrote_address_id I4 NOTNULL DEFAULT 0,
	last_wrote_address_id I4 NOTNULL DEFAULT 0,
	spam_score F NOTNULL DEFAULT 0,
	spam_training C(1) NOTNULL DEFAULT '',
	interesting_words C(255) NOTNULL DEFAULT '',
	next_action C(255) NOTNULL DEFAULT ''
";

$tables['message'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL,
	is_admin I1 DEFAULT 0 NOTNULL,
	message_type C(1),
	created_date I4,
	address_id I4,
	headers B,
	content B
";
// Spin headers + content blobs into their own table

$tables['attachment'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	message_id I4 DEFAULT 0 NOTNULL,
	display_name C(128) DEFAULT '' NOTNULL,
	mime_type C(255) DEFAULT '' NOTNULL,
	file_size I4 DEFAULT 0 NOTNULL,
	filepath C(255) DEFAULT '' NOTNULL
";

$tables['team'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL,
	signature B
";

$tables['category'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	team_id I4 DEFAULT 0 NOTNULL,
	name C(32) DEFAULT '' NOTNULL
";

$tables['dashboard'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(32) DEFAULT '' NOTNULL,
	agent_id I4 NOTNULL
";

$tables['dashboard_view'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	dashboard_id I4 DEFAULT 0 NOTNULL,
	type C(1) DEFAULT 'D',
	name C(32) DEFAULT '' NOTNULL,
	view_columns B,
	sort_by C(32) DEFAULT '' NOTNULL,
	sort_asc I1 DEFAULT 1 NOTNULL,
	num_rows I2 DEFAULT 10 NOTNULL,
	page I2 DEFAULT 0 NOTNULL,
	params B
";

// [TODO] Nuke?
$tables['dashboard_view_action'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	dashboard_view_id I4 DEFAULT 0 NOTNULL,
	name C(64) DEFAULT '' NOTNULL,
	worker_id I4 NOTNULL,
	params B
";

$tables['address'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	email C(255) DEFAULT '' NOTNULL,
	personal C(255) DEFAULT ''
";

$tables['mail_routing'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	pattern C(255) DEFAULT '' NOTNULL,
	team_id I4 DEFAULT 0 NOTNULL,
	pos I4 DEFAULT 0 NOT NULL
";

$tables['requester'] = "
	address_id I4 DEFAULT 0 NOTNULL PRIMARY,
	ticket_id I4 DEFAULT 0 NOTNULL PRIMARY
";

$tables['worker_to_team'] = "
	agent_id I4 DEFAULT 0 NOTNULL PRIMARY,
	team_id I4 DEFAULT 0 NOTNULL PRIMARY,
	is_manager I1 DEFAULT 0 NOTNULL
";

// [TODO] Move to POP3 plugin
$tables['pop3_account'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	enabled I1 DEFAULT 1 NOTNULL,
	nickname C(128) DEFAULT '' NOTNULL,
	protocol C(32) DEFAULT 'pop3' NOTNULL,
	host C(128) DEFAULT '' NOTNULL,
	username C(128) DEFAULT '' NOTNULL,
	password C(128) DEFAULT '' NOTNULL,
	port I2 DEFAULT 110 NOTNULL
";

$tables['worker'] ="
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	first_name C(32) DEFAULT '',
	last_name C(64) DEFAULT '',
	title C(64) DEFAULT '',
	email C(128) DEFAULT '',
	pass C(32) DEFAULT '',
	is_superuser I1 DEFAULT 0 NOTNULL,
	can_delete I1 DEFAULT 0 NOTNULL,
	last_activity_date I4,
	last_activity B
";

$tables['bayes_words'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	word C(64) DEFAULT '' NOTNULL,
	spam I4 DEFAULT 0,
	nonspam I4 DEFAULT 0
";

$indexes['bayes_words'] = array(
    'word' => 'word',
);

$tables['bayes_stats'] = "
	spam I4 DEFAULT 0,
	nonspam I4 DEFAULT 0
";

// Communities
$tables['community'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	name C(64) DEFAULT '',
	url C(128) DEFAULT ''
";

// Worker Preferences
$tables['worker_pref'] = "
	worker_id I4 DEFAULT 0 NOTNULL PRIMARY,
	setting C(32) DEFAULT '' NOTNULL PRIMARY,
	value B
";

// Team Routing
$tables['team_routing_rule'] = "
	id I4 DEFAULT 0 NOTNULL PRIMARY,
	team_id I4 DEFAULT 0 NOTNULL,
	header C(64) DEFAULT 'from',
	pattern C(255) DEFAULT '' NOTNULL,
	pos I2 DEFAULT 0 NOT NULL,
	created I4 DEFAULT 0 NOT NULL,
	do_spam C(1) DEFAULT '',
	do_status C(1) DEFAULT '',
	do_move C(16) DEFAULT ''
";

// [JAS]: [TODO] Platform table?
$tables['setting'] = "
	setting C(32) DEFAULT '' NOTNULL PRIMARY,
	value C(255) DEFAULT '' NOTNULL
";

// [TODO] This could be part of the patcher
$currentTables = $db->MetaTables('TABLE', false);

if(is_array($tables))
foreach($tables as $table => $flds) {
	if(false === array_search($table,$currentTables)) {
		$sql = $datadict->CreateTableSQL($table,$flds);
		// [TODO] Need verify step
		// [TODO] Buffer up success and fail messages?  Patcher!
		if(!$datadict->ExecuteSQLArray($sql,false)) {
			echo '[' . $table . '] ' . $db->ErrorMsg();
			exit;
			return FALSE;
		}

		// Add indexes for this table if we have them
		if(is_array($indexes) && isset($indexes[$table]))
		foreach($indexes[$table] as $idxname => $idxflds) {
			$sqlarray = $datadict->CreateIndexSQL($idxname, $table, $idxflds);
			if(!$datadict->ExecuteSQLArray($sqlarray,false)) {
				echo '[' . $table . '] ' . $db->ErrorMsg();
				exit;
				return FALSE;
			}
		}
		
	}
}

return TRUE;
?>