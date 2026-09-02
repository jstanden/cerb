<?php
namespace Cerb\Agent;

use DAO_AgentFile;
use DAO_AgentFilesystem;
use DAO_AutomationResource;
use DAO_Queue;
use DevblocksPlatform;
use DevblocksRegistryEntry;
use DirectoryIterator;
use Generator;
use Model_AgentFilesystem;
use Storage_AutomationResource;
use ZipArchive;

/**
 * Ships agent filesystems with Cerb (`assets/agent_filesystems/<name>/`) and imports them on `/update`.
 *
 * The volumes live in the repo as ORDINARY DIRECTORIES OF FILES, not archives, so a documentation change
 * is a readable diff in a pull request. Each carries a `.manifest` built by `composer build-agent-filesystems`:
 * one line per file, a 40-character SHA-1, a space, then the path.
 *
 * Only ONE thing reads that manifest at runtime -- its own hash. That single value answers "did anything in
 * this volume change since the last version?", which is the only question an update has to ask. It is the
 * same idea as an app version or a patch revision, and it is why an update where nothing changed costs one
 * registry read and does no work at all: no walk, no hashing, no archive.
 *
 * When the hash HAS moved, this hands off instead of reimplementing an importer. The directory is zipped
 * into an `automation_resource` and run through `FilesystemImporter::createJob()` exactly like an archive an
 * admin uploaded -- inheriting its batching, retry, prune, and the Queue Job Monitor UI. Per-file hashes are
 * never consulted at runtime: an `/update` runs once per version, so shaving reads off a few unchanged files
 * would buy nothing and cost a second import path to keep correct.
 *
 * The job is then drained inline rather than left to the cron, because a bundled volume is BOUNDED -- ~1K
 * files today and a few times that in a decade. That keeps the guarantee the other bundled assets have: when
 * `/update` finishes, the content is there. Whatever doesn't fit the drain budget the cron finishes.
 */
class FilesystemAssets {
	const string ASSETS_PATH = '/features/cerberusweb.core/assets/agent_filesystems/';

	/** The manifest format belongs to the importer; a bundled volume is just one source that ships one. */
	const string MANIFEST_FILENAME = FilesystemImporter::MANIFEST_FILENAME;

	/** Marks the volume as managed by Cerb: local edits are reverted and local files pruned on `/update`. */
	const string TYPE_BUNDLED = 'bundled';

	// The bundled volume of reference skills agents read on demand. Named here rather than at each
	// reader because the agent-pane roles point at it by name, and a rename would otherwise leave a
	// dangling `@cerb-agents/...` in every system prompt with nothing to fail on.
	const string VOLUME_SKILLS = 'cerb-agents';

	// The bundled volume of Cerb documentation. Same reasoning as VOLUME_SKILLS: the agent-pane prompts
	// point at paths inside it, so the name belongs in one place.
	const string VOLUME_DOCS = 'cerb-docs';

	/** How long an update will spend draining a volume's import before leaving the rest to the cron. */
	const int DRAIN_SECONDS = 120;

	static function getRegistryKey(string $name) : string {
		return sprintf('agent_filesystem.%s.manifest_sha1', $name);
	}

	static function getAssetsPath() : string {
		return APP_PATH . self::ASSETS_PATH;
	}

	/**
	 * Import every bundled volume whose manifest hash has moved.
	 *
	 * @return array Per-volume stats keyed by volume name
	 */
	static function syncAll(bool $force=false) : array {
		$logger = DevblocksPlatform::services()->log();
		$results = [];

		if(!is_dir(self::getAssetsPath()))
			return $results;

		foreach(new DirectoryIterator(self::getAssetsPath()) as $dir) {
			if($dir->isDot() || !$dir->isDir())
				continue;

			$error = null;

			if(!($stats = self::sync($dir->getPathname(), $force, $error))) {
				$logger->error(sprintf('[Agent Filesystems] Skipped `%s`: %s', $dir->getFilename(), $error));
				continue;
			}

			$results[$stats['name']] = $stats;
		}

		// `/update` is unattended, so finish the indexing here rather than leaving a fresh volume to cron.
		// Each volume's job already drained on its own budget; this tops up whatever is left across all of
		// them. A no-op when there's nothing behind the cursor.
		if($results)
			DAO_AgentFile::drainIndex(30);

		return $results;
	}

	/**
	 * Import one bundled volume if its manifest hash has moved.
	 *
	 * @return array|null Stats, or null when the volume can't be imported
	 */
	static function sync(string $volume_path, bool $force=false, &$error=null) : ?array {
		$logger = DevblocksPlatform::services()->log();

		if(!($meta = self::readManifestMeta($volume_path, $error)))
			return null;

		$stats = [
			'name' => $meta['name'],
			'skipped' => false,
			'num_files' => 0,
			'job_id' => 0,
			'drained' => false,
		];

		// Resolved before the gate so a volume an admin deleted comes back on the next update rather than
		// staying gone until the manifest happens to change.
		$was_created = false;

		if(!($filesystem = self::_resolveFilesystem($meta, $was_created, $error)))
			return null;

		$registry_key = self::getRegistryKey($meta['name']);
		$stored_sha1 = DevblocksPlatform::getRegistryKey($registry_key, DevblocksRegistryEntry::TYPE_STRING, '');

		// The whole point of the manifest: one comparison and we are done
		if(!$force && !$was_created && $stored_sha1 === $meta['sha1']) {
			$stats['skipped'] = true;
			return $stats;
		}

		// An empty volume has nothing to archive, but the hash still moved -- record it so this doesn't
		// re-examine the directory on every future update.
		if(!$meta['num_files']) {
			self::_rememberManifest($registry_key, $meta['sha1']);
			return $stats;
		}

		if(!($import_token = self::_archiveVolume($volume_path, $meta['name'], $error)))
			return null;

		$queue_job = FilesystemImporter::createJob($filesystem->id, $import_token, [
			'prune_missing' => true, // A bundled volume mirrors the repo; it isn't a starting point
			'worker_id' => 0,        // Unattended: no session to read, and no one to notify
			'manifest_registry_key' => $registry_key,
			'manifest_sha1' => $meta['sha1'],
		], $error);

		if(!$queue_job)
			return null;

		$stats['num_files'] = $queue_job->count_total;
		$stats['job_id'] = $queue_job->id;
		$stats['drained'] = self::_drainJob($queue_job);

		$logger->info(sprintf('[Agent Filesystems] Imported `%s`: %d file(s), job %d%s.',
			$meta['name'],
			$queue_job->count_total,
			$queue_job->id,
			$stats['drained'] ? '' : ' (cron will finish it)'
		));

		return $stats;
	}

	/**
	 * Run the import to completion in this request.
	 *
	 * `publish()` is what flushes the buffered message statuses and fires the exactly-once completion hook,
	 * which is where the manifest hash is finally recorded -- so a drain that runs out of budget simply
	 * leaves a normal RUNNING job for the cron, and nothing is marked done early.
	 *
	 * @return bool Whether the job finished here
	 */
	private static function _drainJob($queue_job) : bool {
		$queue_service = DevblocksPlatform::services()->queue();

		if(!($queue = DAO_Queue::get($queue_job->queue_id)))
			return false;

		// A 120s foreground drain PER bundled volume is a heavy tenant of a small pool. Being
		// throttled is the same outcome as running out of budget, which the contract above already
		// covers: the job stays RUNNING and the cron finishes it within a minute. (On the install
		// wizard there is nothing else running, so the slot is always free there.)
		if(null === ($slot = $queue_service->getAvailableConcurrencySlot()))
			return false;

		$stop_time = time() + self::DRAIN_SECONDS;

		try {
			while(time() < $stop_time) {
				if(!FilesystemImporter::processQueue($queue, $stop_time, 100, $queue_job))
					break;
			}

			$queue_service->publish();
		} finally {
			$queue_service->releaseConcurrencySlot($slot);
		}

		return (bool) $queue_service->finalizeJobsIfReady([$queue_job->id])
			|| in_array(\DAO_QueueJob::get($queue_job->id)?->status_id, [\QueueJobStatus::DONE->value], true);
	}

	private static function _rememberManifest(string $registry_key, string $sha1) : void {
		DevblocksPlatform::setRegistryKey($registry_key, $sha1, persist: true);
		DevblocksPlatform::services()->registry()->save();
	}

	/**
	 * Read the manifest's header and hash without parsing its entries.
	 *
	 * The volume's NAME is its directory name, not a manifest field -- the directory is what an admin sees
	 * and what `mounts:` resolves, so a manifest that could disagree with it is a bug waiting to happen.
	 *
	 * `sha1` is of the manifest FILE, hashed as a stream. Every path and content hash in the volume feeds
	 * into it, so it moves if anything at all moved.
	 */
	static function readManifestMeta(string $volume_path, &$error=null) : ?array {
		$volume_path = rtrim($volume_path, '/');
		$name = basename($volume_path);
		$manifest_path = $volume_path . '/' . self::MANIFEST_FILENAME;

		// Same rule the DAO enforces; a directory that can't name a volume can't create one either
		if(!preg_match('/^[A-Za-z][A-Za-z0-9-]*$/', $name)) {
			$error = sprintf('`%s` is not a valid volume name.', $name);
			return null;
		}

		// This is our own dist format, so the manifest is guaranteed. Its absence means the volume was
		// packaged wrong, and importing anyway would rebuild an archive on every update forever.
		if(!is_readable($manifest_path)) {
			$error = sprintf('No `%s`. Run `composer build-agent-filesystems`.', self::MANIFEST_FILENAME);
			return null;
		}

		if(false === ($sha1 = hash_file('sha1', $manifest_path))) {
			$error = 'The manifest could not be read.';
			return null;
		}

		$num_files = 0;

		return [
			'name' => $name,
			'description' => self::_readManifestHeader($manifest_path, $num_files),
			'num_files' => $num_files,
			'sha1' => $sha1,
			'path' => $manifest_path,
		];
	}

	/**
	 * Header lines are `# <key>: <value>`. Entry lines are only COUNTED, never parsed -- nothing at runtime
	 * needs a per-file hash.
	 */
	private static function _readManifestHeader(string $manifest_path, ?int &$num_files=null) : string {
		$num_files = 0;
		$description = '';

		if(false === ($fp = @fopen($manifest_path, 'r')))
			return '';

		try {
			while(false !== ($line = fgets($fp))) {
				if('' === ($line = rtrim($line, "\r\n")))
					continue;

				if('#' !== $line[0]) {
					$num_files++;
					continue;
				}

				if(preg_match('/^#\s*description\s*:\s*(.*)$/i', $line, $matches))
					$description = trim($matches[1]);
			}

		} finally {
			fclose($fp);
		}

		return $description;
	}

	/**
	 * Zip the volume into an `automation_resource` and return its token.
	 *
	 * Built at the volume root with no wrapper directory, so the importer needs no strip prefix. The
	 * `.manifest` rides along and is ignored on the way out -- `normalizePath()` rejects dotfiles, so it can
	 * never import as one of the files it describes.
	 */
	private static function _archiveVolume(string $volume_path, string $name, &$error=null) : ?string {
		if(!extension_loaded('zip')) {
			$error = 'The `zip` PHP extension is not loaded.';
			return null;
		}

		$fp = DevblocksPlatform::getTempFile();
		$temp_file = DevblocksPlatform::getTempFileInfo($fp);

		$zip = new ZipArchive();

		if(true !== $zip->open($temp_file, ZipArchive::OVERWRITE)) {
			$error = 'The import archive could not be created.';
			return null;
		}

		$count = 0;

		foreach(self::_walkVolume($volume_path) as $path => $file) {
			if($zip->addFile($file->getPathname(), $path))
				$count++;
		}

		$zip->close();

		if(!$count) {
			$error = 'The volume has no files to import.';
			return null;
		}

		$resource_token = DevblocksPlatform::services()->string()->uuid();

		$resource_id = DAO_AutomationResource::create([
			DAO_AutomationResource::NAME => sprintf('%s.zip', $name),
			DAO_AutomationResource::MIME_TYPE => 'application/zip',
			DAO_AutomationResource::TOKEN => $resource_token,
			// Long enough for the cron to finish an undrained job, short enough to clean itself up after
			DAO_AutomationResource::EXPIRES_AT => time() + 86400,
		]);

		if(!$resource_id) {
			$error = 'The import archive could not be stored.';
			return null;
		}

		$archive_fp = fopen($temp_file, 'rb');
		Storage_AutomationResource::put($resource_id, $archive_fp);
		fclose($archive_fp);
		fclose($fp);

		return $resource_token;
	}

	/**
	 * Find the bundled volume by name (case-insensitively, matching how `mounts:` resolves one) or create it.
	 */
	private static function _resolveFilesystem(array $meta, bool &$was_created=false, &$error=null) : ?Model_AgentFilesystem {
		$was_created = false;

		foreach(DAO_AgentFilesystem::getAll() as $filesystem) {
			if(0 != strcasecmp($filesystem->name, $meta['name']))
				continue;

			$changes = [];

			if($filesystem->type !== self::TYPE_BUNDLED)
				$changes[DAO_AgentFilesystem::TYPE] = self::TYPE_BUNDLED;

			if('' !== $meta['description'] && $filesystem->description !== $meta['description'])
				$changes[DAO_AgentFilesystem::DESCRIPTION] = $meta['description'];

			if($changes)
				DAO_AgentFilesystem::update($filesystem->id, $changes);

			return DAO_AgentFilesystem::get($filesystem->id);
		}

		$id = DAO_AgentFilesystem::create([
			DAO_AgentFilesystem::NAME => $meta['name'],
			DAO_AgentFilesystem::DESCRIPTION => $meta['description'],
			DAO_AgentFilesystem::TYPE => self::TYPE_BUNDLED,
		]);

		if(!$id || !($filesystem = DAO_AgentFilesystem::get($id))) {
			$error = sprintf('Failed to create the `%s` filesystem.', $meta['name']);
			return null;
		}

		$was_created = true;

		return $filesystem;
	}

	/**
	 * Every file under a volume directory, as path => SplFileInfo. Unfiltered on purpose -- the caller
	 * decides what is importable and why something was rejected.
	 *
	 * @return Generator<string, \SplFileInfo>
	 */
	private static function _walkVolume(string $volume_path) : Generator {
		$volume_path = rtrim($volume_path, '/');

		if(!is_dir($volume_path))
			return;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($volume_path, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach($iterator as $file) {
			if(!$file->isFile())
				continue;

			yield str_replace('\\', '/', substr($file->getPathname(), strlen($volume_path) + 1)) => $file;
		}
	}

	/**
	 * Build a volume's manifest text. Run from `composer build-agent-filesystems`, never at runtime.
	 *
	 * A file the manifest can't list is a file that never reaches the database, so `$skipped` collects
	 * [path => reason] for everything dropped other than the dotfiles we exclude on purpose.
	 */
	static function buildManifest(string $volume_path, string $description='', array &$skipped=[]) : string {
		$files = [];
		$skipped = [];

		foreach(self::_walkVolume($volume_path) as $path => $file) {
			if($path !== FilesystemImporter::normalizePath($path)) {
				// Dotfiles and __MACOSX are excluded by design; anything else was rejected for a reason
				// worth hearing about (an over-long path, or a segment that escapes the root).
				if(!preg_match('#(^|/)\.#', $path) && !str_starts_with($path, '__MACOSX/'))
					$skipped[$path] = strlen($path) > FilesystemImporter::MAX_PATH_LENGTH
						? sprintf('path is longer than %d characters', FilesystemImporter::MAX_PATH_LENGTH)
						: 'path is not a valid volume path';

				continue;
			}

			if($file->getSize() > FilesystemImporter::MAX_FILE_BYTES) {
				$skipped[$path] = sprintf('larger than %d bytes', FilesystemImporter::MAX_FILE_BYTES);
				continue;
			}

			$content = file_get_contents($file->getPathname());

			if(!mb_check_encoding($content, 'UTF-8')) {
				$skipped[$path] = 'not valid UTF-8';
				continue;
			}

			// A newline in a path would split one entry into two unparseable lines
			if(str_contains($path, "\n") || str_contains($path, "\r")) {
				$skipped[$path] = 'path contains a line break';
				continue;
			}

			$files[$path] = sha1($content);
		}

		// `strcmp` order so a rebuild of an unchanged volume is byte-identical and diffs stay readable
		ksort($files, SORT_STRING);

		$out = '';

		if('' !== $description)
			$out .= sprintf("# description: %s\n", str_replace(["\r", "\n"], ' ', $description));

		foreach($files as $path => $sha1)
			$out .= $sha1 . ' ' . $path . "\n";

		return $out;
	}
}
