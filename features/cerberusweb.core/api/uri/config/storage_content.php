<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupStorageContent extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'storage_content');
		
		// Scope
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_engines', $storage_engines);

		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);

		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);
		$tpl->assign('storage_schemas', $storage_schemas);

		$tpl->assign('storage_migration_jobs', $this->_getOpenMigrationJobs());

		$tpl->assign('storage_engine_styles', $this->_getEngineStyles());

		// Aggregate object distribution across every schema/store (for the top distribution bar) and
		// build each schema's per-store rows for its card (sharing the one getStats() call per schema)

		$distribution = [];
		$total_count = 0;
		$total_bytes = 0;
		$schema_stores = [];

		foreach($storage_schemas as $schema_id => $schema) { /* @var $schema Extension_DevblocksStorageSchema */
			$stats = $schema->getStats();

			foreach($stats as $key => $stat) {
				if(!isset($distribution[$key]))
					$distribution[$key] = ['storage_extension' => $stat['storage_extension'], 'storage_profile_id' => intval($stat['storage_profile_id']), 'count' => 0, 'bytes' => 0];

				$distribution[$key]['count'] += intval($stat['count']);
				$distribution[$key]['bytes'] += intval($stat['bytes']);
				$total_count += intval($stat['count']);
				$total_bytes += intval($stat['bytes']);
			}

			$schema_stores[$schema_id] = $this->_getSchemaStores($schema, $stats, $storage_profiles, $storage_engines);
		}

		$tpl->assign('storage_distribution', $distribution);
		$tpl->assign('storage_total_count', $total_count);
		$tpl->assign('storage_total_bytes', $total_bytes);
		$tpl->assign('schema_stores', $schema_stores);

		// Totals
		
		$db = DevblocksPlatform::services()->database();
		
		if(!($rs = $db->ExecuteMaster("SHOW TABLE STATUS")))
			return;

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		
		if(!($rs instanceof mysqli_result))
			return;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$table_size_data = floatval($row['Data_length']);
			$table_size_indexes = floatval($row['Index_length']);
			$table_size_slack = floatval($row['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
		}
		
		mysqli_free_result($rs);
		
		$tpl->assign('total_db_size', $total_db_size);
		$tpl->assign('total_db_data', $total_db_data);
		$tpl->assign('total_db_indexes', $total_db_indexes);
		$tpl->assign('total_db_slack', $total_db_slack);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'showStorageSchema':
					return $this->_configAction_showStorageSchema();
				case 'showStorageSchemaPeek':
					return $this->_configAction_showStorageSchemaPeek();
				case 'saveStorageSchemaPeek':
					return $this->_configAction_saveStorageSchemaPeek();
				case 'showMigratePopup':
					return $this->_configAction_showMigratePopup();
				case 'startMigration':
					return $this->_configAction_startMigration();
			}
		}
		return false;
	}

	/**
	 * Map of open storage-migration jobs keyed by `[schema_id][src_extension:src_profile_id]`.
	 */
	private function _getOpenMigrationJobs() : array {
		$map = [];

		if(!($queue = DAO_Queue::getByName(\Cerb\Records\StorageMigration::QUEUE_NAME)))
			return $map;

		$jobs = DAO_QueueJob::getWhere(sprintf("queue_id = %d AND status_id IN (0,1)", $queue->id));
		
		foreach($jobs as $job) {
			// Read the source location from job metadata (the singleton key is a hash now, not parseable)
			$schema = $job->metadata['schema'] ?? '';
			$src = $job->metadata['src'] ?? null;

			if(!$schema || !is_array($src) || !isset($src['extension']))
				continue;

			// Key by source "<extension>:<profile_id>" to match the getStats() keys used in rule.tpl
			$map[$schema][$src['extension'] . ':' . intval($src['profile_id'])] = $job;
		}
		return $map;
	}

	/**
	 * Ordered store rows for a schema's card. The configured active + archive profiles come first and are
	 * shown even when empty (so the lifecycle reads at a glance); any other store that still holds objects
	 * follows. Each row carries its object count/bytes, a lifecycle role ('active'|'archive'|null) for the
	 * badge, and a color key (matching its bar fill + swatch via the shared CerbUI color scale).
	 */
	private function _getSchemaStores(Extension_DevblocksStorageSchema $schema, array $stats, array $storage_profiles, array $storage_engines) : array {
		$lifecycle = $schema->getLifecycleConfig();

		$active_key = $lifecycle['active_key'];
		// Archive is only a distinct role when archivable and pointed at a different store than active
		$archive_key = ($lifecycle['is_archivable'] && $lifecycle['archive_key'] !== $active_key) ? $lifecycle['archive_key'] : '';

		$stores = [];
		$seen = [];

		$add = function(string $key, ?string $role) use (&$stores, &$seen, $stats, $storage_profiles, $storage_engines, $lifecycle) {
			if($key === '' || isset($seen[$key]))
				return;

			[$ext, $pid] = array_pad(explode(':', $key, 2), 2, 0);
			$pid = intval($pid);

			if($pid)
				$name = isset($storage_profiles[$pid]) ? $storage_profiles[$pid]->name : $ext;
			else
				$name = isset($storage_engines[$ext]) ? $storage_engines[$ext]->name : $ext;

			$stat = $stats[$key] ?? ['count' => 0, 'bytes' => 0];

			$stores[] = [
				'extension' => $ext,
				'profile_id' => $pid,
				'key' => $key,
				'name' => $name,
				'count' => intval($stat['count']),
				'bytes' => intval($stat['bytes']),
				'role' => $role,
				'archive_after_days' => ('archive' === $role) ? intval($lifecycle['archive_after_days']) : null,
			];
			$seen[$key] = true;
		};

		$add($active_key, 'active');
		$add($archive_key, 'archive');

		foreach($stats as $key => $stat)
			$add($key, null);

		return $stores;
	}

	// Presentation map: storage engine id => [cerb-icon class] from each engine's manifest params
	// (chart colors come from the shared CerbUI color scale, keyed by store identity, not the manifest)
	private function _getEngineStyles() : array {
		$styles = [];

		foreach(DevblocksPlatform::getExtensions('devblocks.storage.engine', false) as $manifest) {
			$icon = $manifest->params['icon'] ?? 'database';

			// Icons are stored without the prefix; add it here
			$styles[$manifest->id] = ['icon' => 'cerb-icon-' . $icon];
		}

		return $styles;
	}


	private function _configAction_showStorageSchema() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'] ?? null, 'string','');
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_engines', $storage_engines);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$extension = DevblocksPlatform::getExtension($ext_id, true);
		$tpl->assign('schema', $extension);

		$tpl->assign('storage_migration_jobs', $this->_getOpenMigrationJobs());

		$tpl->assign('storage_engine_styles', $this->_getEngineStyles());

		if($extension instanceof Extension_DevblocksStorageSchema)
			$tpl->assign('schema_stores', [$extension->manifest->id => $this->_getSchemaStores($extension, $extension->getStats(), $storage_profiles, $storage_engines)]);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl');
	}

	private function _configAction_showStorageSchemaPeek() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'] ?? null, 'string','');

		$extension = DevblocksPlatform::getExtension($ext_id, true);

		if(!($extension instanceof Extension_DevblocksStorageSchema))
			DevblocksPlatform::dieWithHttpError(null, 404);

		/* @var $extension Extension_DevblocksStorageSchema */
		$tpl->assign('schema', $extension);

		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);

		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_engines', $storage_engines);
		$tpl->assign('storage_engine_styles', $this->_getEngineStyles());

		// Active content must always live on a local engine; these are the only choices for the active tile
		$tpl->assign('local_engine_ids', ['devblocks.storage.engine.disk', 'devblocks.storage.engine.database']);

		$tpl->assign('lifecycle', $extension->getLifecycleConfig());

		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/peek.tpl');
	}
	
	private function _configAction_saveStorageSchemaPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$ext_id = DevblocksPlatform::importGPC($_POST['ext_id'] ?? null, 'string','');
		
		$extension = DevblocksPlatform::getExtension($ext_id, true);
		/* @var $extension Extension_DevblocksStorageSchema */
		$extension->saveConfig();

		$this->_configAction_showStorageSchema();
	}

	private function _configAction_showMigratePopup() : void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$schema_id = DevblocksPlatform::importGPC($_REQUEST['schema_id'] ?? null, 'string', '');
		$src_extension = DevblocksPlatform::importGPC($_REQUEST['src_extension'] ?? null, 'string', '');
		$src_profile_id = DevblocksPlatform::importGPC($_REQUEST['src_profile_id'] ?? null, 'integer', 0);

		if(!(($schema = DevblocksPlatform::getExtension($schema_id, true)) instanceof Extension_DevblocksStorageSchema))
			DevblocksPlatform::dieWithHttpError(null, 404);

		/* @var $schema Extension_DevblocksStorageSchema */
		
		// The source storage location we're vacating
		$src_value = $src_profile_id ?: $src_extension;

		// Build the destination tiles (engines + profiles), excluding the source
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$styles = $this->_getEngineStyles();

		// One descriptor per destination: value (engine id or profile id), label, icon, and the
		// store-identity color key (engine:profile_id) shared with the storage page's color scale
		$destinations = [];

		$add_dest = function($value, string $engine_id, int $pid, string $label) use (&$destinations, $styles) {
			$destinations[] = [
				'value' => $value,
				'label' => $label,
				'icon' => $styles[$engine_id]['icon'] ?? 'cerb-icon-database',
				'color_key' => $engine_id . ':' . $pid,
			];
		};

		foreach(['devblocks.storage.engine.disk', 'devblocks.storage.engine.database'] as $engine_id) {
			if(isset($storage_engines[$engine_id]) && $engine_id !== $src_value)
				$add_dest($engine_id, $engine_id, 0, $storage_engines[$engine_id]->name);
		}

		foreach($storage_profiles as $profile_id => $profile) {
			if($profile_id != $src_value)
				$add_dest($profile_id, $profile->extension_id, intval($profile_id), $profile->name);
		}

		// Current object count/bytes for the source
		$stats = $schema->getStats();
		$src_stats = $stats[$src_extension . ':' . $src_profile_id] ?? ['count' => 0, 'bytes' => 0];

		$tpl->assign('schema', $schema);
		$tpl->assign('src_extension', $src_extension);
		$tpl->assign('src_profile_id', $src_profile_id);
		// Source tile descriptor (locked) — same shape as a destination
		$tpl->assign('source', [
			'name' => \Cerb\Records\StorageMigration::label($src_extension, $src_profile_id),
			'icon' => $styles[$src_extension]['icon'] ?? 'cerb-icon-database',
			'color_key' => $src_extension . ':' . intval($src_profile_id),
		]);
		$tpl->assign('src_stats', $src_stats);
		$tpl->assign('destinations', $destinations);
		$tpl->assign('default_dst', $destinations[0]['value'] ?? '');

		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_content/migrate_peek.tpl');
	}

	private function _configAction_startMigration() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE) {
			echo json_encode(['status' => 'error', 'error' => 'Storage changes are disabled on this instance.']);
			return;
		}

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$schema_id = DevblocksPlatform::importGPC($_POST['schema_id'] ?? null, 'string', '');
		$src_extension = DevblocksPlatform::importGPC($_POST['src_extension'] ?? null, 'string', '');
		$src_profile_id = DevblocksPlatform::importGPC($_POST['src_profile_id'] ?? null, 'integer', 0);
		$dst = DevblocksPlatform::importGPC($_POST['dst'] ?? null, 'string', '');

		// A numeric destination is a profile id; anything else is a bare engine extension id
		if(is_numeric($dst))
			$dst = intval($dst);

		$error = null;

		if(!($job = \Cerb\Records\StorageMigration::enqueueProfileMigration($schema_id, $src_extension, $src_profile_id, $dst, $active_worker->id, $error))) {
			echo json_encode(['status' => 'error', 'error' => $error ?: 'Failed to start the migration.']);
			return;
		}

		echo json_encode(['status' => 'ok', 'job_id' => $job->id]);
	}
}