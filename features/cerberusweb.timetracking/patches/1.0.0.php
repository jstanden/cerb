<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2008, WebGroup Media LLC
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

// `timetracking_entry` ========================
if(!isset($tables['timetracking_entry'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		time_actual_mins I2 DEFAULT 0 NOTNULL,
		log_date I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		activity_id I4 DEFAULT 0 NOTNULL, 
		debit_org_id I4 DEFAULT 0 NOTNULL,
		notes C(255) DEFAULT '' NOTNULL,
		source_extension_id C(255) DEFAULT '' NOTNULL,
		source_id I4 DEFAULT 0 NOTNULL
	";
	
	$sql = $datadict->CreateTableSQL('timetracking_entry', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('timetracking_entry');
$indexes = $datadict->MetaIndexes('timetracking_entry',false);

if(isset($columns['IS_CLOSED'])) {
    $sql = $datadict->DropColumnSQL('timetracking_entry', 'is_closed');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['activity_id'])) {
	$sql = $datadict->CreateIndexSQL('activity_id','timetracking_entry','activity_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_extension_id'])) {
	$sql = $datadict->CreateIndexSQL('source_extension_id','timetracking_entry','source_extension_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','timetracking_entry','source_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
	$sql = $datadict->CreateIndexSQL('worker_id','timetracking_entry','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['log_date'])) {
	$sql = $datadict->CreateIndexSQL('log_date','timetracking_entry','log_date');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['contact_org_id'])) {
	$sql = $datadict->CreateIndexSQL('contact_org_id','timetracking_entry','contact_org_id');
	$datadict->ExecuteSQLArray($sql);
}

// `timetracking_activity` ========================
if(!isset($tables['timetracking_activity'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL,
		rate F DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('timetracking_activity', $flds);
	$datadict->ExecuteSQLArray($sql);
}

//if(!isset($indexes['worker_id'])) {
//	$sql = $datadict->CreateIndexSQL('worker_id','timetracking_activity','worker_id');
//	$datadict->ExecuteSQLArray($sql);
//}

// ===========================================================================
// Ophaned timetracking_entry custom fields
$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");
$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN timetracking_entry ON (timetracking_entry.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'timetracking.fields.source.time_entry' AND timetracking_entry.id IS NULL");

return TRUE;
?>