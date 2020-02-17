<?php
class StorageCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		
		$runtime = microtime(true);
		
		$logger->info("[Storage] Starting...");

		$max_runtime = time() + 30; // [TODO] Make configurable
		
		// Run any pending batch DELETEs
		$pending_profiles = DAO_DevblocksStorageQueue::getPendingProfiles();
		
		if(is_array($pending_profiles))
		foreach($pending_profiles as $pending_profile) {
			if($max_runtime < time())
				continue;
			
			// Use a profile or a base extension
			$engine =
				!empty($pending_profile['storage_profile_id'])
				? $pending_profile['storage_profile_id']
				: $pending_profile['storage_extension']
				;
			
			if(false == ($storage = DevblocksPlatform::getStorageService($engine)))
				continue;
			
			// Get one page of 500 pending delete keys for this profile
			$keys = DAO_DevblocksStorageQueue::getKeys($pending_profile['storage_namespace'], $pending_profile['storage_extension'], $pending_profile['storage_profile_id'], 500);
			
			$logger->info(sprintf("[Storage] Batch deleting %d %s object(s) for %s:%d",
				count($keys),
				$pending_profile['storage_namespace'],
				$pending_profile['storage_extension'],
				$pending_profile['storage_profile_id']
			));
			
			// Pass the keys to the storage engine
			if(false !== ($keys = $storage->batchDelete($pending_profile['storage_namespace'], $keys))) {

				// Remove the entries on success
				if(is_array($keys) && !empty($keys))
					DAO_DevblocksStorageQueue::purgeKeys($keys, $pending_profile['storage_namespace'], $pending_profile['storage_extension'], $pending_profile['storage_profile_id']);
			}
		}
		
		// Synchronize storage schemas (active+archive)
		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);
		
		if(is_array($storage_schemas))
		foreach($storage_schemas as $schema) { /* @var $schema Extension_DevblocksStorageSchema */
			if($max_runtime > time())
				$schema->unarchive($max_runtime);
			if($max_runtime > time())
				$schema->archive($max_runtime);
		}
		
		$logger->info("[Storage] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}

	function saveConfiguration() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};