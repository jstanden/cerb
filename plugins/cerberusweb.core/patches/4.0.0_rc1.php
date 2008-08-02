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
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// `attachment` =============================
$columns = $datadict->MetaColumns('attachment');
$indexes = $datadict->MetaIndexes('attachment',false);

if(!isset($indexes['message_id'])) {
	$sql = $datadict->CreateIndexSQL('message_id','attachment','message_id');
	$datadict->ExecuteSQLArray($sql);
}

// `kb_category` =============================
if(!isset($tables['kb_category'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		parent_id I4 DEFAULT 0 NOTNULL,
		name C(64) DEFAULT '' NOTNULL
	";
    $sql = $datadict->CreateTableSQL('kb_category',$flds);
    $datadict->ExecuteSQLArray($sql);
    
	if(!isset($indexes['parent_id'])) {
	    $sql = $datadict->CreateIndexSQL('parent_id','kb_category','parent_id');
	    $datadict->ExecuteSQLArray($sql);
	}
}

// `kb_article_to_category` =============================
if(!isset($tables['kb_article_to_category'])) {
    $flds = "
		kb_article_id I4 DEFAULT 0 NOTNULL PRIMARY,
		kb_category_id I4 DEFAULT 0 NOTNULL PRIMARY,
		kb_top_category_id I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('kb_article_to_category',$flds);
    $datadict->ExecuteSQLArray($sql);
    
	if(!isset($indexes['kb_article_id'])) {
	    $sql = $datadict->CreateIndexSQL('kb_article_id','kb_article_to_category','kb_article_id');
	    $datadict->ExecuteSQLArray($sql);
	}
	
	if(!isset($indexes['kb_category_id'])) {
	    $sql = $datadict->CreateIndexSQL('kb_category_id','kb_article_to_category','kb_category_id');
	    $datadict->ExecuteSQLArray($sql);
	}
	
	if(!isset($indexes['kb_top_category_id'])) {
	    $sql = $datadict->CreateIndexSQL('kb_top_category_id','kb_article_to_category','kb_top_category_id');
	    $datadict->ExecuteSQLArray($sql);
	}
}

// `kb_article` ========================
if(!isset($tables['kb_article'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		title C(128) DEFAULT '' NOTNULL,
		updated I4 DEFAULT 0 NOTNULL,
		views I4 DEFAULT 0 NOTNULL,
		content XL
	";
	$sql = $datadict->CreateTableSQL('kb_article', $flds);
	$datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('kb_article');
$indexes = $datadict->MetaIndexes('kb_article',false);

if(!isset($columns['UPDATED'])) {
	$sql = $datadict->AddColumnSQL('kb_article', "updated I4 DEFAULT 0 NOTNULL");
	$datadict->ExecuteSQLArray($sql);
	
	$sql = array(sprintf("UPDATE kb_article SET updated = %d",time()));
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['VIEWS'])) {
	$sql = $datadict->AddColumnSQL('kb_article', "views I4 DEFAULT 0 NOTNULL");
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['updated'])) {
	$sql = $datadict->CreateIndexSQL('updated','kb_article','updated');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['FORMAT'])) {
    $sql = $datadict->AddColumnSQL('kb_article', "format I1 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
    
    $db->Execute("UPDATE kb_article SET format=1");
}

if(!isset($columns['CONTENT_RAW'])) {
    $sql = $datadict->AddColumnSQL('kb_article', "content_raw XL");
    $datadict->ExecuteSQLArray($sql);
    
    $db->Execute("UPDATE kb_article SET content_raw=content");
}

if(!isset($indexes['title'])) {
	$sql = $datadict->CreateIndexSQL('title','kb_article','title',array('FULLTEXT'));
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['content'])) {
	$sql = $datadict->CreateIndexSQL('content','kb_article','content',array('FULLTEXT'));
	$datadict->ExecuteSQLArray($sql);
}

if(isset($columns['CODE'])) {
    // [TODO] Look up the KB page_title

	// First translate any existing codes to new KB topics
	$sql = "SELECT DISTINCT code FROM kb_article";
	$rs = $db->Execute($sql);
	
	$num = 1;
	
    while(!$rs->EOF) {
    	$cat_id = $db->GenID('generic_seq');
    	$code = $rs->fields['code'];

    	if(empty($cat_id) || empty($code)) {
    		$rs->MoveNext();
    		continue;
    	}
    	
    	$cat_name = "Imported KB #".$num++;
    	
    	$db->Execute(sprintf("INSERT INTO kb_category (id,parent_id,name) VALUES (%d,0,%s)",
    		$cat_id,
    		$db->qstr($cat_name)
    	));
    	
    	$rs2 = $db->Execute(sprintf("SELECT id FROM kb_article WHERE code = %s",
    		$db->qstr($code)
    	));
    	
    	while(!$rs2->EOF) {
    		$article_id = intval($rs2->fields['id']);
    		$db->Replace(
    			'kb_article_to_category',
    			array('kb_article_id'=>$article_id,'kb_category_id'=>$cat_id,'kb_top_category_id'=>$cat_id),
    			array('kb_article_id','kb_category_id'),
    			false
    		);
    		$rs2->MoveNext();
    	}
    	
    	$rs->MoveNext();
    }
    
    unset($num);
    unset($rs);
    unset($rs2);
	
    $sql = $datadict->DropColumnSQL('kb_article', 'code');
    $datadict->ExecuteSQLArray($sql);
}

// `message_content` ========================
$columns = $datadict->MetaColumns('message_content');
$indexes = $datadict->MetaIndexes('message_content',false);

if(isset($columns['CONTENT'])) {
	if(0==strcasecmp('longblob',$columns['CONTENT']->type)) {
		$sql = sprintf("ALTER TABLE message_content CHANGE COLUMN content content TEXT");
		$db->Execute($sql);
	}
	
	if(!isset($indexes['content'])) {
		$sql = $datadict->CreateIndexSQL('content','message_content','content',array('FULLTEXT'));
		$datadict->ExecuteSQLArray($sql);
	}
}

// `message_header` ========================
$columns = $datadict->MetaColumns('message_header');
$indexes = $datadict->MetaIndexes('message_header',false);

// Drop compound primary key
if(isset($columns['MESSAGE_ID']) && isset($columns['HEADER_NAME'])
	&& $columns['MESSAGE_ID']->primary_key && $columns['MESSAGE_ID']->primary_key) {
		$sql = array("ALTER TABLE message_header DROP PRIMARY KEY");
		$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['message_id'])) {
	$sql = $datadict->CreateIndexSQL('message_id','message_header','message_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['header_value'])) {
	$sql = $datadict->CreateIndexSQL('header_value','message_header','header_value(10)');
	$datadict->ExecuteSQLArray($sql);
}

// `message_note` ========================
$columns = $datadict->MetaColumns('message_note');
$indexes = $datadict->MetaIndexes('message_note',false);

if(!isset($indexes['message_id'])) {
	$sql = $datadict->CreateIndexSQL('message_id','message_note','message_id');
	$datadict->ExecuteSQLArray($sql);
}

// `preparse_rule` =============================
if(!isset($tables['preparse_rule'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(64) DEFAULT '' NOTNULL,
		criteria_ser XL,
		actions_ser XL,
		pos I4 DEFAULT 0 NOTNULL
	";
    $sql = $datadict->CreateTableSQL('preparse_rule',$flds);
    $datadict->ExecuteSQLArray($sql);
    
    $sql = $datadict->CreateIndexSQL('pos','preparse_rule','pos');
    $datadict->ExecuteSQLArray($sql);
}

// `team_routing_rule` ========================
$columns = $datadict->MetaColumns('team_routing_rule');
$indexes = $datadict->MetaIndexes('team_routing_rule',false);

if(!isset($columns['NAME'])) {
    $sql = $datadict->AddColumnSQL('team_routing_rule', "name C(64) DEFAULT '' NOTNULL");
    $datadict->ExecuteSQLArray($sql);
    
    $sql = "UPDATE team_routing_rule SET name='Rule' WHERE name=''";
    $db->Execute($sql);
}

if(!isset($columns['CRITERIA_SER'])) {
    $sql = $datadict->AddColumnSQL('team_routing_rule', "criteria_ser XL");
    $datadict->ExecuteSQLArray($sql);
}

//if(!isset($columns['ACTIONS_SER'])) {
//    $sql = $datadict->AddColumnSQL('team_routing_rule', "actions_ser XL");
//    $datadict->ExecuteSQLArray($sql);
//}

// Convert old header+patterns to criteria
if(isset($columns['HEADER']) && isset($columns['PATTERN'])) {
	$sql = "SELECT id,header,pattern FROM team_routing_rule";
	$rs = $db->Execute($sql);
	
	
	while(!$rs->EOF) {
		@$id = intval($rs->fields['id']);
		@$header = strtolower($rs->fields['header']);
		@$pattern = $rs->fields['pattern'];
		$criterion = array();
		
		if(empty($header) || empty($pattern)) {
			$rs->MoveNext();
			continue;
		}
		
		if($header != 'from' && $header != 'subject') {
			$rs->MoveNext();
			continue;
		}
			
		$criterion[$header] = array(
			'value' => $pattern
		);

		// Fill criteria_ser
		$sql = sprintf("UPDATE team_routing_rule SET criteria_ser = %s WHERE id = %d",
			$db->qstr(serialize($criterion)),
			$id
		);
		$db->Execute($sql);
		
		$rs->MoveNext();
	}

	// Drop columns
	$sql = $datadict->DropColumnSQL('team_routing_rule','header');
	$datadict->ExecuteSQLArray($sql);
	
	$sql = $datadict->DropColumnSQL('team_routing_rule','pattern');
	$datadict->ExecuteSQLArray($sql);
}

// `ticket` ========================
$columns = $datadict->MetaColumns('ticket');
$indexes = $datadict->MetaIndexes('ticket',false);

if(!isset($columns['UNLOCK_DATE'])) {
	$sql = $datadict->AddColumnSQL('ticket','unlock_date I4 DEFAULT 0 NOTNULL');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['unlock_date'])) {
	$sql = $datadict->CreateIndexSQL('unlock_date','ticket','unlock_date');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['due_date'])) {
	$sql = $datadict->CreateIndexSQL('due_date','ticket','due_date');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_deleted'])) {
	$sql = $datadict->CreateIndexSQL('is_deleted','ticket','is_deleted');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['last_action_code'])) {
	$sql = $datadict->CreateIndexSQL('last_action_code','ticket','last_action_code');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['spam_score'])) {
	$sql = $datadict->CreateIndexSQL('spam_score','ticket','spam_score');
	$datadict->ExecuteSQLArray($sql);
}


// `ticket_comment` ========================
$columns = $datadict->MetaColumns('ticket_comment');
$indexes = $datadict->MetaIndexes('ticket_comment',false);

if(!isset($indexes['ticket_id'])) {
	$sql = $datadict->CreateIndexSQL('ticket_id','ticket_comment','ticket_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($columns['ADDRESS_ID'])) {
    $sql = $datadict->AddColumnSQL('ticket_comment', "address_id I4 DEFAULT 0 NOTNULL");
    $datadict->ExecuteSQLArray($sql);
	
	$sql = $datadict->CreateIndexSQL('address_id','ticket_comment','address_id');
	$datadict->ExecuteSQLArray($sql);
}

if(isset($columns['WORKER_ID'])) {
	// Convert worker_id to address_id
	$sql = "SELECT w.id, a.id AS address_id FROM worker w INNER JOIN address a ON (w.email=a.email)";
	$rs = $db->Execute($sql);
	
	while(!$rs->EOF) {
		$worker_id = intval($rs->fields['id']);
		$address_id = intval($rs->fields['address_id']);
		
		$db->Execute(sprintf("UPDATE ticket_comment SET address_id = %d WHERE worker_id = %d AND address_id = 0",
			$address_id,
			$worker_id
		));
		
		$rs->MoveNext();
	}
	
	$sql = $datadict->DropColumnSQL('ticket_comment','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

// `ticket_field` ========================
$columns = $datadict->MetaColumns('ticket_field');
$indexes = $datadict->MetaIndexes('ticket_field',false);

if(!isset($indexes['pos'])) {
	$sql = $datadict->CreateIndexSQL('pos','ticket_field','pos');
	$datadict->ExecuteSQLArray($sql);
}

if(32 == $columns['NAME']->max_length) {
	$sql = "ALTER TABLE ticket_field CHANGE COLUMN name name varchar(128) DEFAULT '' NOT NULL";
	$db->Execute($sql);
}

// `worker` ========================
$columns = $datadict->MetaColumns('worker');
$indexes = $datadict->MetaIndexes('worker',false);

if(!isset($indexes['pos'])) {
	$sql = $datadict->CreateIndexSQL('last_activity_date','worker','last_activity_date');
	$datadict->ExecuteSQLArray($sql);
}

// Configure import cron
if(null != ($cron_mf = DevblocksPlatform::getExtension('cron.import'))) {
	if(null != ($cron = $cron_mf->createInstance())) {
		$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, false);
		$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '0');
		$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
		$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday'));
	}
}

return TRUE;
?>