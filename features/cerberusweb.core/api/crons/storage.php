<?php
class StorageCron extends CerberusCronPageExtension {
	const int ARCHIVE_BATCH_SIZE = 1000;

	function run() {
		$logger = DevblocksPlatform::services()->log();

		$runtime = microtime(true);

		$logger->info("[Storage] Starting...");

		$stop_time = time() + 20;

		// Candidates per schema per run (admin-configurable on the scheduler job; chunked into
		// BATCH_SIZE migrate messages, so e.g. 1000 -> up to ~10 messages)
		$batch_size = max(1, intval($this->getParam('archive_batch_size', self::ARCHIVE_BATCH_SIZE)));

		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);

		shuffle($storage_schemas);

		foreach($storage_schemas as $schema) { /* @var $schema Extension_DevblocksStorageSchema */
			// One keyset fetch + enqueue + cursor advance per schema; the cursor resumes next run
			$schema->archive($batch_size);

			if($stop_time < time()) break;
		}

		$logger->info("[Storage] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('archive_batch_size', $this->getParam('archive_batch_size', self::ARCHIVE_BATCH_SIZE));

		$tpl->display('devblocks:cerberusweb.core::cron/storage/config.tpl');
	}

	function saveConfiguration() {
		$archive_batch_size = DevblocksPlatform::importGPC($_POST['archive_batch_size'] ?? null, 'integer', self::ARCHIVE_BATCH_SIZE);

		$this->setParam('archive_batch_size', max(1, $archive_batch_size));

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};