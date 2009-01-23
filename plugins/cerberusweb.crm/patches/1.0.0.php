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
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ***** Application

if(!isset($tables['crm_opportunity'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		campaign_id I4 DEFAULT 0 NOTNULL,
		name C(255) DEFAULT '' NOTNULL,
		source C(255) DEFAULT '' NOTNULL,
		primary_email_id I4 DEFAULT 0 NOTNULL,
		created_date I4 DEFAULT 0 NOTNULL,
		updated_date I4 DEFAULT 0 NOTNULL,
		closed_date I4 DEFAULT 0 NOTNULL,
		is_won I1 DEFAULT 0 NOTNULL,
		is_closed I1 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('crm_opportunity', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('crm_opportunity');
$indexes = $datadict->MetaIndexes('crm_opportunity',false);

if(!isset($columns['NEXT_ACTION'])) {
    $sql = $datadict->AddColumnSQL('crm_opportunity', "next_action C(255) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['CAMPAIGN_BUCKET_ID'])) {
    $sql = $datadict->AddColumnSQL('crm_opportunity', "campaign_bucket_id I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['campaign_id'])) {
    $sql = $datadict->CreateIndexSQL('campaign_id','crm_opportunity','campaign_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['campaign_bucket_id'])) {
    $sql = $datadict->CreateIndexSQL('campaign_bucket_id','crm_opportunity','campaign_bucket_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['primary_email_id'])) {
    $sql = $datadict->CreateIndexSQL('primary_email_id','crm_opportunity','primary_email_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['updated_date'])) {
    $sql = $datadict->CreateIndexSQL('updated_date','crm_opportunity','updated_date');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
    $sql = $datadict->CreateIndexSQL('worker_id','crm_opportunity','worker_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_closed'])) {
    $sql = $datadict->CreateIndexSQL('is_closed','crm_opportunity','is_closed');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($tables['crm_opp_comment'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		opportunity_id I4 DEFAULT 0 NOTNULL,
		created_date I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		content XL
	";
	$sql = $datadict->CreateTableSQL('crm_opp_comment', $flds);
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['opportunity_id'])) {
    $sql = $datadict->CreateIndexSQL('opportunity_id','crm_opp_comment','opportunity_id');
    $datadict->ExecuteSQLArray($sql);
}

if(!isset($tables['crm_campaign'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(128) DEFAULT '' NOTNULL
	";
	$sql = $datadict->CreateTableSQL('crm_campaign', $flds);
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($tables['crm_campaign_bucket'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		campaign_id I4 DEFAULT 0 NOTNULL,
		name C(64) DEFAULT '' NOTNULL
	";
	$sql = $datadict->CreateTableSQL('crm_campaign_bucket', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('crm_campaign_bucket');
$indexes = $datadict->MetaIndexes('crm_campaign_bucket',false);

if(!isset($indexes['campaign_id'])) {
    $sql = $datadict->CreateIndexSQL('campaign_id','crm_campaign_bucket','campaign_id');
    $datadict->ExecuteSQLArray($sql);
}

return TRUE;

?>