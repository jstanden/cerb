<?php
namespace Cerb\Agent;

use Cerb_ORMHelper;
use CerberusApplication;
use DAO_AgentFile;
use DAO_AgentFilesystem;
use DAO_AutomationResource;
use DAO_Queue;
use DAO_QueueJob;
use DevblocksPlatform;
use Model_Queue;
use Model_QueueJob;
use QueueJobStatus;
use Throwable;
use ZipArchive;

/**
 * Imports a ZIP archive into an `agent_filesystem` as `agent_file` records.
 *
 * Rides the existing `cerb.records.import` queue rather than defining its own: the job carries
 * `metadata['format'] = 'zip'`, and `_DevblocksRecordsService::processImportQueue()` hands off here.
 * That inherits the queue-job fan-out, concurrency, retry, and the Queue Job Monitor UI for free while
 * skipping `Cerb\Records\FileImporter` entirely (it wants a column mapping and an `IDevblocksContextImport`
 * context, neither of which applies to a directory of files).
 *
 * The unit of work is a ZIP entry INDEX. A central directory is randomly addressable via statIndex()/
 * getFromIndex(), so each queue_message carries a batch of indexes the same way the CSV/JSONL importer
 * carries byte offsets — no path strings are duplicated into the queue.
 */
class FilesystemImporter {
	// Bodies land in a MEDIUMTEXT column; this is about content an agent can actually read, not storage.
	const MAX_FILE_BYTES = 1_048_576;

	// The unique key is `(filesystem_id, name(700))`, so anything longer can collide silently. One rule for
	// every writer -- the VFS enforces the same ceiling on `write` and `copy`.
	const MAX_PATH_LENGTH = Filesystem::MAX_PATH_LENGTH;

	const BATCH_SIZE = 25;

	/**
	 * An import source MAY carry one of these at its root: one line per file, a 40-character SHA-1, a space,
	 * then the path. When present it lets a batch be reconciled without reading the bodies it isn't going to
	 * write. Leading dot so `normalizePath()` rejects it -- the manifest never imports as one of the files it
	 * describes.
	 */
	const MANIFEST_FILENAME = '.manifest';

	/**
	 * Extensions taken as text without reading the body. Anything else (including extensionless files
	 * like LICENSE or Makefile) falls through to a UTF-8 check, which is what actually rejects binaries.
	 */
	const TEXT_EXTENSIONS = [
		'conf', 'css', 'csv', 'htm', 'html', 'ini', 'js', 'json', 'log', 'markdown', 'md', 'php',
		'py', 'sh', 'sql', 'toml', 'ts', 'tsv', 'txt', 'xml', 'yaml', 'yml',
	];

	/**
	 * Open the archive behind an automation resource token into a temp file.
	 * Returns [ZipArchive, $fp] so the caller can hold the handle open, or null.
	 */
	private static function _openArchive(string $import_token, &$error=null) : ?array {
		if(!extension_loaded('zip')) {
			$error = 'The `zip` PHP extension is not loaded.';
			return null;
		}

		if(!($resource = DAO_AutomationResource::getByToken($import_token))) {
			$error = 'The import file no longer exists.';
			return null;
		}

		$fp = DevblocksPlatform::getTempFile();
		$fp_info = DevblocksPlatform::getTempFileInfo($fp);

		if(false === $resource->getFileContents($fp)) {
			$error = 'The import file could not be read.';
			return null;
		}

		$zip = new ZipArchive();

		if(true !== $zip->open($fp_info)) {
			$error = 'The file is not a valid ZIP archive.';
			return null;
		}

		return [$zip, $fp];
	}

	/**
	 * Normalize a ZIP entry name into a virtual filesystem path, or '' if it isn't importable.
	 * Rejects anything that escapes the archive root or hides from a `ls` (dotfiles, __MACOSX).
	 */
	public static function normalizePath(string $name, string $strip_prefix='') : string {
		$name = str_replace('\\', '/', $name);

		if($strip_prefix !== '' && str_starts_with($name, $strip_prefix))
			$name = substr($name, strlen($strip_prefix));

		$name = ltrim(preg_replace('#/+#', '/', $name), '/');

		if('' === $name || str_ends_with($name, '/'))
			return '';

		foreach(explode('/', $name) as $segment) {
			// '..' escapes the root; a leading dot is editor/VCS noise (.git, .DS_Store, .gitignore)
			if('' === $segment || '.' === $segment || '..' === $segment || str_starts_with($segment, '.'))
				return '';
		}

		if(str_starts_with($name, '__MACOSX/'))
			return '';

		if(strlen($name) > self::MAX_PATH_LENGTH)
			return '';

		return $name;
	}

	/**
	 * Enumerate the archive and return the entry indexes worth importing.
	 *
	 * Extension-allowlisted entries are accepted without a read (the fast path for a docs/skill tree);
	 * everything else is read once, bounded by the size cap, and kept only if it's valid UTF-8.
	 */
	private static function _collectEntries(ZipArchive $zip, string $strip_prefix, int &$skipped=0) : array {
		$indexes = [];
		$skipped = 0;

		for($i = 0; $i < $zip->numFiles; $i++) {
			if(false === ($fstat = $zip->statIndex($i)))
				continue;

			$raw_name = $fstat['name'] ?? '';

			// Directory entries aren't records; the tree is derived from file paths.
			if(str_ends_with($raw_name, '/'))
				continue;

			if('' === self::normalizePath($raw_name, $strip_prefix)) {
				$skipped++;
				continue;
			}

			if(($fstat['size'] ?? 0) > self::MAX_FILE_BYTES) {
				$skipped++;
				continue;
			}

			$extension = strtolower(pathinfo($raw_name, PATHINFO_EXTENSION));

			if(!in_array($extension, self::TEXT_EXTENSIONS, true)) {
				$bytes = $zip->getFromIndex($i, self::MAX_FILE_BYTES);

				if(false === $bytes || !mb_check_encoding($bytes, 'UTF-8')) {
					$skipped++;
					continue;
				}
			}

			$indexes[] = $i;
		}

		return $indexes;
	}

	/**
	 * The single leading directory every entry shares, e.g. 'cerb-dev/', or '' when there isn't one.
	 * A ZIP of a skill directory almost always has one, and it's noise inside the volume.
	 */
	public static function detectCommonPrefix(ZipArchive $zip) : string {
		$prefix = null;

		for($i = 0; $i < $zip->numFiles; $i++) {
			if(false === ($fstat = $zip->statIndex($i)))
				continue;

			$name = str_replace('\\', '/', $fstat['name'] ?? '');

			if('' === $name || str_starts_with($name, '__MACOSX/'))
				continue;

			if(false === ($slash_at = strpos($name, '/')))
				return ''; // A file at the root means there's no single wrapper directory

			$segment = substr($name, 0, $slash_at + 1);

			if(is_null($prefix))
				$prefix = $segment;
			elseif($prefix !== $segment)
				return '';
		}

		return $prefix ?? '';
	}

	/**
	 * Inspect an uploaded archive for the import options step (file count + wrapper directory).
	 */
	public static function inspect(string $import_token, &$error=null) : ?array {
		if(!($opened = self::_openArchive($import_token, $error)))
			return null;

		list($zip, ) = $opened;

		$common_prefix = self::detectCommonPrefix($zip);
		$skipped = 0;
		$indexes = self::_collectEntries($zip, $common_prefix, $skipped);

		$zip->close();

		return [
			'num_files' => count($indexes),
			'num_skipped' => $skipped,
			'common_prefix' => $common_prefix,
		];
	}

	public static function getSingletonKey(int $filesystem_id) : string {
		return sprintf('agent_filesystem:%d:import', $filesystem_id);
	}

	/**
	 * Producer. Enumerates the archive, creates the queue job, and fans the entry indexes out as
	 * queue messages. Runs inside the upload request, so the enumerate pass is the only synchronous cost.
	 *
	 * @param array $opts strip_prefix (string), prune_missing (bool), manifest_registry_key + manifest_sha1
	 *                    (string, for a bundled volume that records its version on completion)
	 */
	public static function createJob(int $filesystem_id, string $import_token, array $opts=[], &$error=null) : ?Model_QueueJob {
		$queue_service = DevblocksPlatform::services()->queue();

		if(!($filesystem = DAO_AgentFilesystem::get($filesystem_id))) {
			$error = 'Invalid agent filesystem.';
			return null;
		}

		if(!($queue = DAO_Queue::getByName('cerb.records.import'))) {
			$error = 'The import queue is not configured.';
			return null;
		}

		if(!($opened = self::_openArchive($import_token, $error)))
			return null;

		list($zip, ) = $opened;

		$strip_prefix = strval($opts['strip_prefix'] ?? '');

		// Only honor a prefix the archive actually has, so a stale/forged value can't mangle every path
		if($strip_prefix !== '' && $strip_prefix !== self::detectCommonPrefix($zip))
			$strip_prefix = '';

		$skipped = 0;
		$indexes = self::_collectEntries($zip, $strip_prefix, $skipped);

		$zip->close();

		if(!$indexes) {
			$error = 'The archive contains no importable files.';
			return null;
		}

		$queue_job = new Model_QueueJob();
		$queue_job->queue_id = $queue->id;
		$queue_job->name = sprintf('Import agent files: %s', $filesystem->name);
		$queue_job->singleton_key = self::getSingletonKey($filesystem_id); // One import per filesystem at a time
		$queue_job->status_id = QueueJobStatus::RUNNING->value;
		$queue_job->count_total = count($indexes);
		$queue_job->worker_id = array_key_exists('worker_id', $opts)
			? intval($opts['worker_id'])
			: (CerberusApplication::getActiveWorker()->id ?? 0);
		$queue_job->metadata = [
			'format' => 'zip',
			'filesystem_id' => $filesystem_id,
			'import_token' => $import_token,
			'import_uuid' => DevblocksPlatform::services()->string()->uuid(),
			'strip_prefix' => $strip_prefix,
			'prune_missing' => !empty($opts['prune_missing']),
			'num_skipped' => $skipped,
		];

		// A bundled volume records its manifest hash when the job COMPLETES, so it rides in the metadata
		// rather than being written up front where a failed import would still mark itself done.
		if(!empty($opts['manifest_registry_key'])) {
			$queue_job->metadata['manifest_registry_key'] = strval($opts['manifest_registry_key']);
			$queue_job->metadata['manifest_sha1'] = strval($opts['manifest_sha1'] ?? '');
		}

		if(!($queue_job = DAO_QueueJob::createFromModel($queue_job))) {
			$error = 'Failed to create the import job.';
			return null;
		}

		// `cardinality` applies to every message in an enqueue() call, so the uniform batches and the
		// short remainder go out separately to keep the job's progress counts honest.
		$batches = array_chunk($indexes, self::BATCH_SIZE);
		$remainder = (count(end($batches)) < self::BATCH_SIZE) ? array_pop($batches) : null;

		$to_message = fn($batch) => ['indexes' => array_values($batch)];

		if($batches)
			$queue_service->enqueue($queue->name, array_map($to_message, $batches), job_id: $queue_job->id, cardinality: self::BATCH_SIZE);

		if($remainder)
			$queue_service->enqueue($queue->name, [$to_message($remainder)], job_id: $queue_job->id, cardinality: count($remainder));

		return $queue_job;
	}

	/**
	 * Consumer. Opens the archive once per invocation and drains messages until the time budget runs out,
	 * so the storage fetch is amortized across many batches.
	 */
	public static function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		$queue_service = DevblocksPlatform::services()->queue();

		if(!$queue_job)
			return 0;

		$filesystem_id = intval($queue_job->metadata['filesystem_id'] ?? 0);
		$import_token = strval($queue_job->metadata['import_token'] ?? '');
		$import_uuid = strval($queue_job->metadata['import_uuid'] ?? '');
		$strip_prefix = strval($queue_job->metadata['strip_prefix'] ?? '');

		$claim_id = null;
		$error = null;

		// A bad job can't make progress, but it still has to report failure against a real message or
		// the monitor sits at 0% forever.
		if(!$filesystem_id || !($opened = self::_openArchive($import_token, $error))) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $claim_id, $queue_job->id)))
				return 0;

			$queue_service->reportFailure($queue_messages, $error ?: 'Invalid import job.');
			return count($queue_messages);
		}

		list($zip, ) = $opened;

		$processed = 0;

		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $claim_id, $queue_job->id)))
				break;

			foreach($queue_messages as $queue_message) {
				$indexes = array_map('intval', $queue_message->message['indexes'] ?? []);

				try {
					$count = self::_importEntries($zip, $indexes, $filesystem_id, $strip_prefix, $import_uuid);

					$queue_service->reportSuccess(
						[$queue_message],
						sprintf('Imported %d file%s', $count, 1 == $count ? '' : 's')
					);

				} catch(Throwable $e) {
					DevblocksPlatform::logException($e);
					$queue_service->reportFailure([$queue_message], $e->getMessage());
				}
			}

			$processed += count($queue_messages);
		}

		$zip->close();

		return $processed;
	}

	/**
	 * Decode one batch of archive entries into [path => content] and upsert them.
	 *
	 * Only THIS batch's indexes are fetched and hashed -- nothing scans the whole archive, so a 100K-entry
	 * ZIP costs the same per batch as a 100-entry one.
	 */
	private static function _importEntries(ZipArchive $zip, array $indexes, int $filesystem_id, string $strip_prefix, string $import_uuid) : int {
		$entries = [];

		foreach($indexes as $index) {
			if(false === ($fstat = $zip->statIndex($index)))
				continue;

			if('' === ($name = self::normalizePath($fstat['name'] ?? '', $strip_prefix)))
				continue;

			if(false === ($content = $zip->getFromIndex($index, self::MAX_FILE_BYTES)))
				continue;

			// A binary that slipped past the producer's extension fast path would corrupt the row
			if(!mb_check_encoding($content, 'UTF-8'))
				continue;

			$entries[$name] = $content;
		}

		$counts = self::importEntries($filesystem_id, $entries, $import_uuid);

		return $counts['created'] + $counts['updated'] + $counts['unchanged'];
	}

	/**
	 * Upsert a batch of [path => content] into a volume.
	 *
	 * THE write path for every bulk importer -- the ZIP archive importer and the bundled-asset importer
	 * (`FilesystemAssets`) both funnel through here, so a change to how a file becomes a row cannot apply to
	 * only one of them. Callers differ only in where the bytes came from.
	 *
	 * Paths are assumed to be normalized already; the caller knows whether its source needs a strip prefix.
	 *
	 * @param array $entries [path => content]
	 * @return array ['created'=>int, 'updated'=>int, 'unchanged'=>int]
	 */
	public static function importEntries(int $filesystem_id, array $entries, string $import_uuid) : array {
		$db = DevblocksPlatform::services()->database();

		$counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

		if(!$entries)
			return $counts;

		// One lookup for the whole batch; the unique key on (filesystem_id, name(700)) makes this exact.
		$existing = $db->GetArrayReader(sprintf(
			"SELECT id, name, sha1 FROM agent_file WHERE filesystem_id = %d AND name IN (%s)",
			$filesystem_id,
			implode(',', array_map(fn($name) => Cerb_ORMHelper::qstr($name), array_keys($entries)))
		));

		$existing_by_name = array_column($existing, null, 'name');

		$unchanged_ids = [];

		// One refresh per batch instead of one per file, for the volume's counters.
		DAO_AgentFilesystem::deferRecount();

		try {
			foreach($entries as $name => $content) {
				$sha1 = sha1($content);
				$row = $existing_by_name[$name] ?? null;

				// Unchanged bodies only get the import stamp — no DAO write, so nothing re-indexes.
				if($row && 0 === strcasecmp(strval($row['sha1'] ?? ''), $sha1)) {
					$unchanged_ids[] = intval($row['id']);
					$counts['unchanged']++;
					continue;
				}

				// file_extension/frontmatter_json/size are derived by the DAO from the content + name. `sha1` is
				// passed because it's already computed above for the unchanged-body check.
				$fields = [
					DAO_AgentFile::FILESYSTEM_ID => $filesystem_id,
					DAO_AgentFile::NAME => $name,
					DAO_AgentFile::CONTENT => $content,
					DAO_AgentFile::SHA1 => $sha1,
					DAO_AgentFile::IMPORT_UUID => $import_uuid,
					DAO_AgentFile::UPDATED_AT => time(),
				];

				// Through the DAO so markContextChanged() fires and the fulltext index picks these up
				if($row) {
					DAO_AgentFile::update(intval($row['id']), $fields);
					$counts['updated']++;
				} else {
					DAO_AgentFile::create($fields);
					$counts['created']++;
				}
			}

			self::stampIds($unchanged_ids, $import_uuid);

		} finally {
			DAO_AgentFilesystem::flushRecount();
		}

		return $counts;
	}

	/**
	 * Mark rows as still wanted by this run. Bookkeeping only -- no events, so nothing re-indexes.
	 */
	public static function stampIds(array $ids, string $import_uuid) : void {
		if(!$ids)
			return;

		DAO_AgentFile::updateWhere(
			[DAO_AgentFile::IMPORT_UUID => $import_uuid],
			sprintf('id IN (%s)', implode(',', array_map('intval', $ids)))
		);
	}

	/**
	 * Stamp rows this run still wants, then delete whatever is left unstamped.
	 *
	 * Read in bounded pages so a full-volume prune can't materialize a million ids.
	 */
	public static function pruneUnstamped(int $filesystem_id, string $import_uuid) : int {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf(
			"SELECT id FROM agent_file WHERE filesystem_id = %d AND import_uuid != %s LIMIT 100",
			$filesystem_id,
			Cerb_ORMHelper::qstr($import_uuid)
		);

		$deleted = 0;
		$last_first_id = null;

		// Each pass deletes the rows it just read, so the same LIMIT walks the whole set
		while($rows = $db->GetArrayMaster($sql)) {
			$ids = array_map('intval', array_column($rows, 'id'));

			// A pass that returns the same page it just deleted would spin forever
			if($ids[0] === $last_first_id) {
				DevblocksPlatform::services()->log()->error(sprintf(
					'[Agent Filesystem] Prune stalled on agent_file %d; aborting.',
					$ids[0]
				));
				break;
			}

			$last_first_id = $ids[0];

			// Through the DAO so links, comments, and custom field values are cleaned up too
			DAO_AgentFile::delete($ids);
			$deleted += count($ids);
		}

		return $deleted;
	}

	/**
	 * Bookkeeping only — cleared without events so it never triggers a reindex.
	 */
	public static function clearImportStamps(int $filesystem_id) : void {
		DAO_AgentFile::updateWhere(
			[DAO_AgentFile::IMPORT_UUID => ''],
			sprintf('filesystem_id = %d', $filesystem_id)
		);
	}

	/**
	 * @deprecated The record owns its derived columns — `DAO_AgentFile::update()` fills `frontmatter_json`
	 * from the content on every write. Kept as a delegating alias for existing callers.
	 */
	public static function parseFrontmatterJson(string $name, string $content) : ?string {
		return DAO_AgentFile::parseFrontmatterJson($name, $content);
	}

	/**
	 * Completion hook. Prunes whatever the archive no longer contains (opt-in), then refreshes the
	 * filesystem's cached counters and clears the import stamps.
	 */
	public static function onJobComplete(Model_QueueJob $queue_job) : void {
		$filesystem_id = intval($queue_job->metadata['filesystem_id'] ?? 0);
		$import_uuid = strval($queue_job->metadata['import_uuid'] ?? '');

		if(!$filesystem_id || !$import_uuid)
			return;

		if($queue_job->metadata['prune_missing'] ?? false)
			self::pruneUnstamped($filesystem_id, $import_uuid);

		// Every write path keeps these current now, so this is belt-and-braces for the prune above rather than
		// the only thing that maintains them. Same implementation either way -- two counts that can disagree
		// is worse than none.
		DAO_AgentFilesystem::recount($filesystem_id);

		self::clearImportStamps($filesystem_id);

		// Nobody is waiting on a queue consumer, so index the way the cron does -- walk the cursor, which
		// covers exactly what this import wrote and nothing twice.
		DAO_AgentFile::drainIndex(10);

		// A bundled volume records its manifest hash HERE, not when the job was created -- committing it up
		// front would let a failed import mark itself done and never retry.
		if(($registry_key = strval($queue_job->metadata['manifest_registry_key'] ?? ''))) {
			DevblocksPlatform::setRegistryKey(
				$registry_key,
				strval($queue_job->metadata['manifest_sha1'] ?? ''),
				\DevblocksRegistryEntry::TYPE_STRING,
				persist: true
			);

			DevblocksPlatform::services()->registry()->save();
		}
	}
}
