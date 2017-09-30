<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Remove plugins from ./storage/plugins/ that moved to ./features/

$recursive_delete = function($dir) use (&$recursive_delete) {
	$dir = rtrim($dir,"/\\") . '/';
	
	if(!file_exists($dir) || !is_dir($dir))
		return false;
	
	// Ignore development directories
	if(file_exists($dir . '.git/'))
		return false;
	
	$storage_path = APP_STORAGE_PATH . '/plugins/';
	
	// Make sure the file is in the ./storage/plugins path
	if(0 != substr_compare($storage_path, $dir, 0, strlen($storage_path)))
		return false;
	
	$files = glob($dir . '*', GLOB_MARK);
	foreach($files as $file) {
		if(is_dir($file)) {
			$recursive_delete($file);
		} else {
			unlink($file);
		}
	}
	
	if(file_exists($dir) && is_dir($dir))
		rmdir($dir);
	
	return true;
};

$dirs = [
	'cerb.bots.portal.widget',
	'cerb.project_boards',
	'cerb.webhooks',
];

$plugin_dir = APP_STORAGE_PATH . '/plugins';

foreach($dirs as $dir) {
	$recursive_delete($plugin_dir . '/' . $dir);
}

// ===========================================================================
// Create `email_signature` table

if(!isset($tables['email_signature'])) {
	$sql = sprintf("
	CREATE TABLE `email_signature` (
		id int unsigned auto_increment,
		name varchar(255) default '',
		signature text,
		is_default tinyint(3) unsigned not null default 0,
		updated_at int unsigned not null default 0,
		primary key (id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['email_signature'] = 'email_signature';
}

// ===========================================================================
// Add `reply_signature_id` to the `bucket` table

if(!isset($tables['bucket'])) {
	$logger->error("The 'bucket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(!isset($columns['reply_signature_id'])) {
	$sql = 'ALTER TABLE bucket ADD COLUMN reply_signature_id int(10) unsigned NOT NULL DEFAULT 0 AFTER reply_signature';
	$db->ExecuteMaster($sql);
}

if(isset($columns['reply_signature'])) {
	$sql = "SELECT b.id AS bucket_id, b.name AS bucket_name, b.group_id, g.name AS group_name, b.reply_signature FROM bucket b INNER JOIN worker_group g ON (g.id=b.group_id) WHERE b.reply_signature != ''";
	$results = $db->GetArrayMaster($sql);
	
	if(is_array($results))
	foreach($results as $result) {
		$sql = sprintf("INSERT INTO email_signature (name, signature, updated_at) VALUES (%s, %s, %d)",
			$db->qstr(sprintf('%s: %s', $result['group_name'], $result['bucket_name'])),
			$db->qstr($result['reply_signature']),
			time()
		);
		$db->ExecuteMaster($sql);
		$sig_id = $db->LastInsertId();
		
		$db->ExecuteMaster(sprintf("UPDATE bucket SET reply_signature_id = %d WHERE id = %d",
			$sig_id,
			$result['bucket_id']
		));
	}
	
	$sql = 'ALTER TABLE bucket DROP COLUMN reply_signature';
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Add `reply_signature_id` to the `address_outgoing` table

if(isset($tables['address_outgoing'])) {
	list($columns, $indexes) = $db->metaTable('address_outgoing');
	
	if(!isset($columns['reply_signature_id'])) {
		$sql = 'ALTER TABLE address_outgoing ADD COLUMN reply_signature_id int(10) unsigned NOT NULL DEFAULT 0 AFTER reply_signature';
		$db->ExecuteMaster($sql);
	}
	
	if(isset($columns['reply_signature'])) {
		$sql = "SELECT ao.address_id, a.email, ao.is_default, ao.reply_signature FROM address_outgoing ao INNER JOIN address a ON (a.id=ao.address_id) WHERE ao.reply_signature != ''";
		$results = $db->GetArrayMaster($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			$sql = sprintf("INSERT INTO email_signature (name, signature, is_default, updated_at) VALUES (%s, %s, %d, %d)",
				$db->qstr($result['email']),
				$db->qstr($result['reply_signature']),
				$result['is_default'] ? 1 : 0,
				time()
			);
			$db->ExecuteMaster($sql);
			$sig_id = $db->LastInsertId();
			
			$db->ExecuteMaster(sprintf("UPDATE address_outgoing SET reply_signature_id = %d WHERE address_id = %d",
				$sig_id,
				$result['address_id']
			));
		}
	}
}

// ===========================================================================
// Finish up

return TRUE;
