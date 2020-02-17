<?php
class Cron_Packages extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$packages = CerberusApplication::packages();
		
		$logger->info("[Packages Importer] Started");
		
		$stop_time = time() + 20; // [TODO] Make configurable
		
		$runtime = microtime(true);
		
		$path_import = APP_STORAGE_PATH . '/import/packages/new/';
		$path_import_fail = APP_STORAGE_PATH . '/import/packages/fail/';
		
		if(!file_exists($path_import) && !is_dir($path_import)) {
			if(false == mkdir($path_import, 0755, true)) {
				$logger->error("[Packages Importer] Failed to create the ./storage/import/packages/new/ directory.");
				return;
			}
		}
		
		if(!file_exists($path_import_fail) && !is_dir($path_import_fail)) {
			if(false == mkdir($path_import_fail, 0755, true)) {
				$logger->error("[Packages Importer] Failed to create the ./storage/import/packages/fail/ directory.");
				return;
			}
		}
		
		$dirs = glob($path_import . '*', GLOB_ONLYDIR);
		
		if($dirs)
		foreach($dirs as $dir) {
			$dir = basename($dir);
			
			$logger->info('[Packages Importer] Scanning ' . $dir);
			
			$path_dir = $path_import . $dir . '/';
			$path_fail = $path_import_fail . $dir . '/';
			
			$files = glob($path_dir . '*.json');
			
			// If a directory is empty, remove it
			if(empty($files)) {
				$logger->info('[Packages Importer] Removing empty dir ' . $dir);
				rmdir($path_dir);
				continue;
			}
			
			if(!file_exists($path_fail))
				if(false == mkdir($path_fail, 0755, true))
					continue;
			
			foreach($files as $file) {
				$records_created = [];
				
				$import_filename = basename($file);
				$import_path = $path_fail . $import_filename;
				
				if(false == rename($file, $import_path)) {
					$logger->info('[Packages Importer] Failed to move ' . $dir . '/' . $import_filename . ' to ' . $path_fail);
					continue;
				}
				
				try {
					$logger->info('[Packages Importer] Importing ' . $import_filename);
					$packages->import(file_get_contents($import_path), [], $records_created);
					
					unlink($import_path);
					
				} catch(Exception $e) {
					$logger->error(sprintf('[Packages Importer] %s: %s', $import_filename, $e->getMessage()));
				}
				
				// If we're past our time limit, yield
				if(time() > $stop_time)
					break 2;
			}
			
			// If the fail dir is still empty, remove it
			$files = glob($path_fail . '*.json');
			
			if(empty($files)) {
				rmdir($path_fail);
			}
		}
		
		$logger->info("[Packages Importer] Finished (".number_format((microtime(true)-$runtime)*1000,2)." ms)");
	}

	function configure($instance) {
	}

	function saveConfiguration() {
	}
};