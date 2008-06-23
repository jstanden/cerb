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

@chmod(APP_PATH . '/storage/indexes/', 0774);

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();

// `community` ========================
$columns = $datadict->MetaColumns('community');
$indexes = $datadict->MetaIndexes('community',false);

if(isset($columns['URL'])) {
	$sql = $datadict->DropColumnSQL('community', 'url');
	$datadict->ExecuteSQLArray($sql);
}

// `community_session` ========================
if(!isset($tables['community_session'])) {
	$flds ="
		session_id C(32) DEFAULT '' NOTNULL PRIMARY,
		created I4 DEFAULT 0 NOTNULL,
		updated I4 DEFAULT 0 NOTNULL,
		properties B
	";
	$sql = $datadict->CreateTableSQL('community_session', $flds);
	$datadict->ExecuteSQLArray($sql);
}

// `community_tool` =============================
$columns = $datadict->MetaColumns('community_tool');
$indexes = $datadict->MetaIndexes('community_tool',false);

if(!isset($indexes['community_id'])) {
	$sql = $datadict->CreateIndexSQL('community_id','community_tool','community_id');
	$datadict->ExecuteSQLArray($sql);
}

// `community_tool_property` ========================
if(!isset($tables['community_tool_property'])) {
	$flds ="
		tool_code C(8) DEFAULT '' NOTNULL PRIMARY,
		property_key C(64) DEFAULT '' NOTNULL PRIMARY,
		property_value B
	";
	$sql = $datadict->CreateTableSQL('community_tool_property', $flds);
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
?>