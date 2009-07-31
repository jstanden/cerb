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
// Clean up 'bayes_words'

if(isset($tables['bayes_words'])) {

	// Delete empty words
	$db->Execute("DELETE FROM bayes_words WHERE word = '';");

	// Nuke all the non-alphanums
	$db->Execute("DELETE FROM bayes_words WHERE word REGEXP '[^a-z0-9_\\']'");

	// Unused words
	$db->Execute("DELETE FROM bayes_words WHERE spam=0 AND nonspam=0");

	// Flatten duplicates
	$rs = $db->Execute("SELECT count(id) AS hits, SUM(spam) AS spam, SUM(nonspam) AS nonspam, LOWER(word) AS word FROM bayes_words GROUP BY LOWER(word) HAVING hits > 1");
	
	while(!$rs->EOF) {
		$word = $rs->fields['word'];
		$spam = intval($rs->fields['spam']);
		$nonspam = intval($rs->fields['nonspam']);
		
		// Nuke all the dupes
		$db->Execute(sprintf("DELETE FROM bayes_words WHERE word = %s", $db->qstr($word)));

		$id = $db->GenID('bayes_words_seq');
		
		// Insert a single new row with aggregate totals
		$db->Execute(sprintf("INSERT IGNORE INTO bayes_words (id,word,spam,nonspam) ".
			"VALUES (%d,%s,%d,%d)",
			$id,
			$db->qstr($word),
			$spam,
			$nonspam
		));
		
		$rs->MoveNext();
	}

}

return TRUE;