<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Clean up 'bayes_words'

if(isset($tables['bayes_words'])) {

	list($columns, $indexes) = $db->metaTable('bayes_words');
	
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->ExecuteMaster("ALTER TABLE bayes_words MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT");
	}
	
	// Delete empty words
	$db->ExecuteMaster("DELETE FROM bayes_words WHERE word = '';");

	// Nuke all the non-alphanums
	$db->ExecuteMaster("DELETE FROM bayes_words WHERE word REGEXP '[^a-z0-9_\\']'");

	// Unused words
	$db->ExecuteMaster("DELETE FROM bayes_words WHERE spam=0 AND nonspam=0");

	// Flatten duplicates
	$rs = $db->ExecuteMaster("SELECT count(id) AS hits, SUM(spam) AS spam, SUM(nonspam) AS nonspam, LOWER(word) AS word FROM bayes_words GROUP BY LOWER(word) HAVING count(id) > 1");
	
	while($row = mysqli_fetch_assoc($rs)) {
		$word = $row['word'];
		$spam = intval($row['spam']);
		$nonspam = intval($row['nonspam']);
		
		// Nuke all the dupes
		$db->ExecuteMaster(sprintf("DELETE FROM bayes_words WHERE word = %s", $db->qstr($word)));

		// Insert a single new row with aggregate totals
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO bayes_words (word,spam,nonspam) ".
			"VALUES (%s,%d,%d)",
			$db->qstr($word),
			$spam,
			$nonspam
		));
	}
	
	mysqli_free_result($rs);
}

// ===========================================================================
// Fix orphaned tickets

$default_group_id = $db->GetOneMaster("SELECT id FROM team WHERE is_default = 1");

if(!empty($default_group_id)) {
	$db->ExecuteMaster(sprintf("UPDATE ticket SET team_id=%d WHERE team_id=0", $default_group_id));
	
}

return TRUE;