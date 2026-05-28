<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// `kb_category` =============================
if(!isset($tables['kb_category'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS kb_category (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			parent_id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(64) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
	
}

list($columns, $indexes) = $db->metaTable('kb_category');

if(!isset($indexes['parent_id'])) {
	$db->ExecuteMaster('ALTER TABLE kb_category ADD INDEX parent_id (parent_id)');
}

if(isset($columns['id'])
	&& ('int(10) unsigned' != $columns['id']['type']
		|| 'auto_increment' != $columns['id']['extra'])
) {
	$db->ExecuteMaster("ALTER TABLE kb_category MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT");
}

// `kb_article_to_category` =============================
if(!isset($tables['kb_article_to_category'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS kb_article_to_category (
			kb_article_id INT UNSIGNED DEFAULT 0 NOT NULL,
			kb_category_id INT UNSIGNED DEFAULT 0 NOT NULL,
			kb_top_category_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (kb_article_id, kb_category_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
	
	if(!isset($indexes['kb_article_id'])) {
		$db->ExecuteMaster('ALTER TABLE kb_article_to_category ADD INDEX kb_article_id (kb_article_id)');
	}
	
	if(!isset($indexes['kb_category_id'])) {
		$db->ExecuteMaster('ALTER TABLE kb_article_to_category ADD INDEX kb_category_id (kb_category_id)');
	}
	
	if(!isset($indexes['kb_top_category_id'])) {
		$db->ExecuteMaster('ALTER TABLE kb_article_to_category ADD INDEX kb_top_category_id (kb_top_category_id)');
	}
}

// `kb_article` ========================
if(!isset($tables['kb_article'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS kb_article (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			title VARCHAR(128) DEFAULT '' NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			views INT UNSIGNED DEFAULT 0 NOT NULL,
			content MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

list($columns, $indexes) = $db->metaTable('kb_article');

if(!isset($columns['updated'])) {
	$db->ExecuteMaster('ALTER TABLE kb_article ADD COLUMN updated INT UNSIGNED DEFAULT 0 NOT NULL');
	$db->ExecuteMaster("UPDATE kb_article SET updated = %d", time());
}

if(!isset($columns['views'])) {
	$db->ExecuteMaster('ALTER TABLE kb_article ADD COLUMN views INT UNSIGNED DEFAULT 0 NOT NULL');
}

if(!isset($indexes['updated'])) {
	$db->ExecuteMaster('ALTER TABLE kb_article ADD INDEX updated (updated)');
}

if(!isset($columns['format'])) {
	$db->ExecuteMaster('ALTER TABLE kb_article ADD COLUMN format TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL');
	$db->ExecuteMaster("UPDATE kb_article SET format=1");
}

if(!isset($columns['content_raw'])) {
	$db->ExecuteMaster('ALTER TABLE kb_article ADD COLUMN content_raw MEDIUMTEXT');
	$db->ExecuteMaster("UPDATE kb_article SET content_raw=content");
}

if(isset($columns['code'])) {
	// First translate any existing codes to new KB topics
	$sql = "SELECT DISTINCT code FROM kb_article";
	$rs = $db->ExecuteMaster($sql);
	
	$num = 1;
	
	while($row = mysqli_fetch_assoc($rs)) {
		$code = $row['code'];
		
		if(empty($code))
			continue;
		
		$cat_name = "Imported KB #".$num++;
		
		$db->ExecuteMaster(sprintf("INSERT INTO kb_category (parent_id,name) VALUES (0,%s)",
			$db->qstr($cat_name)
		));
		$cat_id = $db->LastInsertId();
		
		$rs2 = $db->ExecuteMaster(sprintf("SELECT id FROM kb_article WHERE code = %s",
			$db->qstr($code)
		));
		
		while($row2 = mysqli_fetch_assoc($rs2)) {
			$article_id = intval($row2['id']);
			$db->ExecuteMaster("REPLACE INTO kb_article_to_category (kb_article_id, kb_category_id, kb_top_category_id) ".
				"VALUES (%d, %d, %d)",
				$article_id,
				$cat_id,
				$cat_id
			);
		}
		
		mysqli_free_result($rs2);
	}
	
	mysqli_free_result($rs);
	
	unset($num);
	
	$db->ExecuteMaster("ALTER TABLE kb_article DROP COLUMN code");
}