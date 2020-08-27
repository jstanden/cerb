<?php
require(getcwd() . '/../../../framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');
DevblocksPlatform::setStateless(true);

set_time_limit(0);
ini_set('memory_limit', '4G');

// Verify CLI usage
if('cli' != php_sapi_name())
	die("This script must be executed from the command line.\n");

// Load CLI arguments
$options = getopt('d:v:', [
	'dir:',
	'verbose:',
	'help'
]);

$subdir = @$options['dir'] ?: @$options['d'] ?: false;

if(!$subdir)
	die("ERROR: The target import directory is required.\n");

$subdir = realpath($subdir);

if(!file_exists($subdir))
	die("ERROR: The given path does not exist.\n");

if(!is_writeable($subdir))
	die("ERROR: The given path is not writeable.\n");

$subdir = rtrim($subdir, DIRECTORY_SEPARATOR);

$iter = new GlobIterator($subdir . '/*.json', FilesystemIterator::KEY_AS_FILENAME);

$started_ms = microtime(true);

$index = 0;

if($iter->count()) {
	$index++;
	
	foreach($iter as $file) {
		$import_file_path = $file->getPathName();
		$records_created = [];
		
		try {
			if(false == ($import_json = file_get_contents($import_file_path))) {
				throw new Exception(sprintf("Failed to open file: %s\n", $import_file_path));
			}
			
			$before_ms = microtime(true);
			
			CerberusApplication::packages()->import($import_json, [], $records_created);
			
			// Remove file on success
			unlink($import_file_path);
			
			echo sprintf("Imported %s.... %0.2f ms\n",
				basename($import_file_path),
				(microtime(true) - $before_ms)*1000
			);
			
		} catch (Exception $e) {
			echo sprintf("ERROR on %s: %s (skipping)\n",
				basename($import_file_path),
				$e->getMessage()
			);
			
			// Rename on fail
			rename($import_file_path, str_replace('.json', '.json.failed', $import_file_path));
		}
		
		if(0 == $index % 5)
			flush();
	}
}

echo sprintf("Finished in %0.2f ms\n",
	(microtime(true) - $started_ms)*1000
);
