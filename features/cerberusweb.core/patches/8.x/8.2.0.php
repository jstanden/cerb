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
// Finish up

return TRUE;
