<?php
/**
 * Rebuild the `.manifest` for each bundled agent filesystem under
 * `features/cerberusweb.core/assets/agent_filesystems/`.
 *
 * Run it after adding, editing, or removing files in one of those volumes. Nothing imports at runtime
 * unless the manifest lists it, and `/update` skips a volume entirely while its manifest hash is unchanged.
 *
 * The format is one line per file -- a 40-character SHA-1, a space, then the path -- sorted by path, with
 * optional `# key: value` header lines. Fixed-width hash first means a reader splits a line by offset and
 * streams the file instead of parsing it.
 *
 * Needs no database and no framework bootstrap, so it is safe to run in CI.
 *
 *   php install/extras/developers/agent-filesystem-manifest.php
 *   php install/extras/developers/agent-filesystem-manifest.php cerb-docs
 *   php install/extras/developers/agent-filesystem-manifest.php --check
 *
 * `--check` writes nothing and exits 1 if any manifest is stale.
 */

require(dirname(__FILE__) . '/../../../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setStateless(true);

$classloader = DevblocksPlatform::services()->classloader();
$classloader->registerPsr4Path(APP_PATH . '/features/cerberusweb.core/api/Agent/', 'Cerb\\Agent\\');

if('cli' !== php_sapi_name())
	die("This script must be run from the command line.\n");

$assets_path = APP_PATH . Cerb\Agent\FilesystemAssets::ASSETS_PATH;

$args = array_slice($argv, 1);
$check_only = in_array('--check', $args, true);
$only_volumes = array_values(array_filter($args, fn($arg) => !str_starts_with($arg, '--')));

if(!is_dir($assets_path))
	die(sprintf("No such directory: %s\n", $assets_path));

$stale = 0;
$volumes = 0;

foreach(new DirectoryIterator($assets_path) as $dir) {
	if($dir->isDot() || !$dir->isDir())
		continue;

	$name = $dir->getFilename();

	if($only_volumes && !in_array($name, $only_volumes, true))
		continue;

	$volumes++;

	$volume_path = $dir->getPathname();
	$manifest_path = $volume_path . '/' . Cerb\Agent\FilesystemAssets::MANIFEST_FILENAME;

	// Keep whatever the current manifest declares about the volume itself; only the file hashes are derived
	$meta = Cerb\Agent\FilesystemAssets::readManifestMeta($volume_path);
	$description = $meta['description'] ?? '';

	$skipped = [];
	$manifest = Cerb\Agent\FilesystemAssets::buildManifest($volume_path, $description, $skipped);

	// A skipped file is one that will never reach the database, so never let it pass silently
	foreach($skipped as $skipped_path => $reason)
		fprintf(STDERR, "%-20s SKIPPED %s (%s)\n", $name, $skipped_path, $reason);

	$num_files = substr_count($manifest, "\n") - ('' !== $description ? 1 : 0);

	$is_current = is_readable($manifest_path) && file_get_contents($manifest_path) === $manifest;

	if($check_only) {
		printf("%-20s %s (%d files)\n", $name, $is_current ? 'OK' : 'STALE', $num_files);

		if(!$is_current)
			$stale++;

		continue;
	}

	if($is_current) {
		printf("%-20s unchanged (%d files)\n", $name, $num_files);
		continue;
	}

	if(false === file_put_contents($manifest_path, $manifest))
		die(sprintf("Failed to write %s\n", $manifest_path));

	printf("%-20s wrote %d files, sha1 %s\n", $name, $num_files, sha1($manifest));
}

if(!$volumes)
	die("No agent filesystem volumes found.\n");

if($check_only && $stale) {
	fprintf(STDERR, "\n%d manifest(s) out of date. Run this script without --check.\n", $stale);
	exit(1);
}

exit(0);
