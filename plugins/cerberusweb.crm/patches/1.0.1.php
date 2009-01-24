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

// ===========================================================================
// Migrate the campaigns to a custom field
if(isset($tables['crm_opportunity'])) {
	$columns = $datadict->MetaColumns('crm_opportunity');
	$indexes = $datadict->MetaIndexes('crm_opportunity',false);

	if(isset($columns['CAMPAIGN_ID'])) {
		// Load the campaign hash
		$campaigns = array();
		$sql = "SELECT id, name FROM crm_campaign ORDER BY name";
		$rs = $db->Execute($sql);
		while(!$rs->EOF) {
			$campaigns[$rs->fields['id']] = $rs->fields['name'];
			$rs->MoveNext();
		}
	
		if(!empty($campaigns)) { // Move to a custom field before dropping
			// Create the new custom field
			$field_id = $db->GenID('custom_field_seq');
			$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
				"VALUES (%d,'Campaign','D',0,0,%s,%s)",
				$field_id,
				$db->qstr(implode("\n",$campaigns)),
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->Execute($sql);
			
			// Populate the custom field from opp records
			$sql = sprintf("INSERT INTO custom_field_value (field_id, source_id, field_value, source_extension) ".
				"SELECT %d, o.id, c.name, %s FROM crm_opportunity o INNER JOIN crm_campaign c ON (o.campaign_id=c.id)",
				$field_id,
				$db->qstr('crm.fields.source.opportunity')
			);
			$db->Execute($sql);
		}
	}
}

// ===========================================================================
// Migrate the opportunity lead source to a custom field
if(isset($tables['crm_opportunity'])) {
	$columns = $datadict->MetaColumns('crm_opportunity');
	$indexes = $datadict->MetaIndexes('crm_opportunity',false);

	$count = $db->Execute("SELECT count(id) FROM crm_opportunity WHERE source != ''");
	
	if(isset($columns['SOURCE']) && $count) {
		// Create the new custom field
		$field_id = $db->GenID('custom_field_seq');
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Lead Source','S',0,0,'',%s)",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_value (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.source, %s FROM crm_opportunity o WHERE o.source != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Migrate the opportunity.next_action to a custom field
if(isset($tables['crm_opportunity'])) {
	$columns = $datadict->MetaColumns('crm_opportunity');
	$indexes = $datadict->MetaIndexes('crm_opportunity',false);

	$count = $db->Execute("SELECT count(id) FROM crm_opportunity WHERE next_action != ''");
	
	if(isset($columns['NEXT_ACTION']) && $count) {
		// Create the new custom field
		$field_id = $db->GenID('custom_field_seq');
		$sql = sprintf("INSERT INTO custom_field (id,name,type,group_id,pos,options,source_extension) ".
			"VALUES (%d,'Next Action','S',0,0,'',%s)",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
		
		// Populate the custom field from opp records
		$sql = sprintf("INSERT INTO custom_field_value (field_id, source_id, field_value, source_extension) ".
			"SELECT %d, o.id, o.next_action, %s FROM crm_opportunity o WHERE o.next_action != ''",
			$field_id,
			$db->qstr('crm.fields.source.opportunity')
		);
		$db->Execute($sql);
	}
}

// ===========================================================================
// Migrate the opportunity comments to platform 'notes' service
if(isset($tables['crm_opp_comment'])) {
	$sql = "SELECT id, opportunity_id, created_date, worker_id, content FROM crm_opp_comment";
	$rs = $db->Execute($sql);
	while(!$rs->EOF) {
		$note_id = $db->GenID('note_seq');
		$sql = sprintf("INSERT INTO note (id, source_extension_id, source_id, created, worker_id, content) ".
			"VALUES (%d,'%s',%d,%d,%d,%s)",
			$note_id,
			'crm.notes.source.opportunity',
			$rs->fields['opportunity_id'],
			$rs->fields['created_date'],
			$rs->fields['worker_id'],
			$db->qstr($rs->fields['content'])
		);
		$db->Execute($sql); // insert
		$rs->MoveNext();
	}
}

// ===========================================================================
// Drop the opp fields we no longer want (optimized out by custom fields)
if(isset($tables['crm_opportunity'])) {
	$columns = $datadict->MetaColumns('crm_opportunity');
	$indexes = $datadict->MetaIndexes('crm_opportunity',false);
	
	if(isset($columns['CAMPAIGN_ID'])) {
		$sql = $datadict->DropColumnSQL('crm_opportunity','campaign_id');
		$datadict->ExecuteSQLArray($sql);
	}

	if(isset($columns['CAMPAIGN_BUCKET_ID'])) {
		$sql = $datadict->DropColumnSQL('crm_opportunity','campaign_bucket_id');
		$datadict->ExecuteSQLArray($sql);
	}

	if(isset($columns['SOURCE'])) {
		$sql = $datadict->DropColumnSQL('crm_opportunity','source');
		$datadict->ExecuteSQLArray($sql);
	}
	
	if(isset($columns['NEXT_ACTION'])) {
		$sql = $datadict->DropColumnSQL('crm_opportunity','next_action');
		$datadict->ExecuteSQLArray($sql);
	}
	
}

// ===========================================================================
// Drop the campaign table (optimized out by custom fields)
if(isset($tables['crm_campaign'])) {
	$sql = $datadict->DropTableSQL('crm_campaign');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Drop the campaign buckets (optimized out by custom fields)
if(isset($tables['crm_campaign_bucket'])) {
	$sql = $datadict->DropTableSQL('crm_campaign_bucket');
	$datadict->ExecuteSQLArray($sql);
}

// ===========================================================================
// Drop the old CRM comments table in favor of the new notes functionality
if(isset($tables['crm_opp_comment'])) {
	$sql = $datadict->DropTableSQL('crm_opp_comment');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;