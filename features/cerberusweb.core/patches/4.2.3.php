<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

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
	
	while($row = mysql_fetch_assoc($rs)) {
		$word = $row['word'];
		$spam = intval($row['spam']);
		$nonspam = intval($row['nonspam']);
		
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
	}
	
	mysql_free_result($rs);
}

// ===========================================================================
// Fix orphaned tickets

$default_group_id = $db->GetOne("SELECT id FROM team WHERE is_default = 1");

if(!empty($default_group_id)) {
	$db->Execute(sprintf("UPDATE ticket SET team_id=%d WHERE team_id=0", $default_group_id));
	
}

return TRUE;