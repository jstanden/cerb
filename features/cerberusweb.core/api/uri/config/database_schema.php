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

class PageSection_SetupDevelopersDatabaseSchema extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // database_schema

		$visit->set(ChConfigurationPage::ID, 'database_schema');

		if(!($schema = $this->_computeSchema()))
			return;

		$extra_tables = $schema['extra'];
		$existing_tables = $schema['existing'];
		$diff_tables = $schema['diffs'];

		$sheet_schema = [
			'layout' => [
				'style' => 'table',
				'paging' => 'false',
				'colors' => [
					'added' => ['#00FF00'],
					'error' => ['#FF0000'],
					'warning' => ['#FF9900'],
				]
			],
			'columns' => [
				'text/table' => [
					'params' => [
						'text_color' => '{% if "extra" == __mode %}added{% endif %}',
						'icon' => [
							'image_template' => '{% if "extra" == __mode %}plus{% endif %}'
						]
					]
				],
				'text/field' => [],
				'text/type' => [
					'params' => [
						'text_color' => '{% if __diff.type %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.type %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.type %}{{__diff.type.theirs|default(\'null\')}} -> {{__diff.type.ours|default(\'null\')}}{% else %}{{type}}{% endif %}',
					]
				],
				'text/collation' => [
					'label' => 'Encoding',
					'params' => [
						'text_color' => '{% if __diff.collation %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.collation %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.collation %}{{__diff.collation.theirs|default(\'null\')}} -> {{__diff.collation.ours|default(\'null\')}}{% else %}{{collation}}{% endif %}',
					]
				],
				'text/nullable' => [
					'params' => [
						'text_color' => '{% if __diff.nullable %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.nullable %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.nullable %}{{__diff.nullable.theirs|default(\'null\')}} -> {{__diff.nullable.ours|default(\'null\')}}{% else %}{{nullable}}{% endif %}',
					]
				],
				'text/key' => [
					'params' => [
						'text_color' => '{% if __diff.key %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.key %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.key %}{{__diff.key.theirs|default(\'null\')}} -> {{__diff.key.ours|default(\'null\')}}{% else %}{{key}}{% endif %}',
					]
				],
				'text/default' => [
					'params' => [
						'text_color' => '{% if __diff.default %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.default %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.default %}{{__diff.default.theirs|default(\'null\')}} -> {{__diff.default.ours|default(\'null\')}}{% else %}{{default}}{% endif %}',
					]
				],
				'text/extra' => [
					'params' => [
						'text_color' => '{% if __diff.extra %}warning{% endif %}',
						'icon' => [
							'image_template' => '{% if __diff.extra %}alert{% endif %}'
						],
						'value_template' => '{% if __diff.extra %}{{__diff.extra.theirs|default(\'null\')}} -> {{__diff.extra.ours|default(\'null\')}}{% else %}{{extra}}{% endif %}',
					]
				],
			],
		];
		
		$layout = $sheets->getLayout($sheet_schema);
		$tpl->assign('layout', $layout);
		
		$columns = $sheets->getColumns($sheet_schema);
		$tpl->assign('columns', $columns);
		
		$table_data = [];
		
		foreach(array_keys($existing_tables) as $table_name) {
			$row_data = $existing_tables[$table_name]['columns'];
			
			foreach ($row_data as $row_idx => $row) {
				$row_data[$row_idx]['table'] = $table_name;
				$row_data[$row_idx]['__diff'] = $diff_tables[$table_name]['columns'][$row_idx] ?? [];
				$table_data[$table_name . '__' . $row_idx] = $row_data[$row_idx];
			}
		}
		
		foreach(array_keys($extra_tables) as $table_name) {
			$row_data = $extra_tables[$table_name]['columns'];
			
			foreach ($row_data as $row_idx => $row) {
				$row_data[$row_idx]['table'] = $table_name;
				$row_data[$row_idx]['__mode'] = 'extra';
				$table_data[$table_name . '__' . $row_idx] = $row_data[$row_idx];
			}
		}
		
		$rows = $sheets->getRows($sheet_schema, $table_data);
		$tpl->assign('rows', $rows);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/database-schema/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'schemaKataPopup':
					return $this->_configAction_schemaKataPopup();
			}
		}
		return false;
	}

	// The live schema in the same KATA shape as `assets/cerb.schema.kata`, so a drifted install can be
	// pasted straight back into the reference file. `only_differences` narrows it to the tables (and
	// within them, the columns) that actually drifted.
	private function _configAction_schemaKataPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!($schema = $this->_computeSchema()))
			DevblocksPlatform::dieWithHttpError(null, 500);

		// `references:` is hand-maintained enrichment in the schema file -- the database can't report a
		// pointer it doesn't enforce -- so carry it onto matching columns rather than dropping it.
		$funcWithReferences = function(array $tables) use ($schema) : array {
			foreach($tables as $table_name => $table) {
				foreach(array_keys($table['columns'] ?? []) as $column_name) {
					if(($references = $schema['reference'][$table_name]['columns'][$column_name]['references'] ?? null))
						$tables[$table_name]['columns'][$column_name]['references'] = $references;
				}
			}

			return $tables;
		};

		// Everything the live database has, minus the per-index runtime tables the page also ignores
		$tpl->assign('kata_all', $kata->emit(['tables' => $funcWithReferences($schema['live'])]));

		// Only what drifted: new tables whole, changed tables narrowed to their changed columns.
		// `$ignore_collation` drops columns whose ONLY difference is the collation -- while the schema migrates
		// off utf8mb3 those are expected everywhere and drown out the structural changes.
		$funcDrift = function(bool $ignore_collation) use ($schema) : array {
			$drift = [];

			foreach($schema['extra'] as $table_name => $table) {
				$drift[$table_name] = $table;
			}

			foreach($schema['diffs'] as $table_name => $table_diff) {
				if(array_key_exists($table_name, $drift))
					continue;

				// A drifted `storage_*` table is fixed by altering it, not by pasting a per-install
				// table into the shared reference file
				if(DevblocksPlatform::strStartsWith($table_name, 'storage_'))
					continue;

				$table = [];

				foreach($table_diff['columns'] ?? [] as $column_name => $column_diff) {
					if($ignore_collation && !array_diff_key($column_diff, ['collation' => true]))
						continue;

					if(array_key_exists($column_name, $schema['live'][$table_name]['columns'] ?? []))
						$table['columns'][$column_name] = $schema['live'][$table_name]['columns'][$column_name];
				}

				// treeDiff reports changed columns before added ones; the reference file is alphabetical
				if(array_key_exists('columns', $table))
					ksort($table['columns']);

				if(array_key_exists('indexes', $table_diff))
					$table['indexes'] = $schema['live'][$table_name]['indexes'] ?? [];

				if($table)
					$drift[$table_name] = $table;
			}

			ksort($drift);

			return $drift;
		};

		foreach(['kata_differences' => false, 'kata_differences_no_collation' => true] as $key => $ignore_collation) {
			$drift = $funcDrift($ignore_collation);
			$tpl->assign($key, $drift ? $kata->emit(['tables' => $funcWithReferences($drift)]) : '');
		}

		// Reference tables with no live counterpart can't be emitted from the database; name them instead
		$tpl->assign('missing_tables', implode(', ', array_keys($schema['missing'])));

		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/database-schema/kata_popup.tpl');
	}

	// Compares `assets/cerb.schema.kata` against the live database. Returns null if the reference
	// file can't be parsed. Shared by the page and the KATA popup so both report the same drift.
	private function _computeSchema() : ?array {
		$db = DevblocksPlatform::services()->database();
		$kata = DevblocksPlatform::services()->kata();

		$error = null;

		if(!($reference_kata = $kata->parse(file_get_contents(APP_PATH . '/features/cerberusweb.core/assets/cerb.schema.kata'), $error))) {
			DevblocksPlatform::logError($error, true);
			return null;
		}

		$reference_kata_custom_record = $kata->parse($this->_getCustomRecordReferenceKata());
		$reference_kata_storage = $kata->parse($this->_getStorageReferenceKata());

		$schema_kata = $db->dumpSchemaKata(false);

		$live_tables = [];
		$missing_tables = [];
		$extra_tables = [];
		$existing_tables = [];
		$diff_tables = [];

		$funcTheirsOurs = function($diff, $table_name, $reference_kata) {
			// Rewrite with theirs/ours
			foreach($diff['columns'] ?? [] as $column_name => $column_attrs) {
				foreach($column_attrs as $column_attr_key => $column_attr_value) {
					$diff['columns'][$column_name][$column_attr_key] = [
						'ours' => $column_attr_value ?? '',
						'theirs' => $reference_kata['columns'][$column_name][$column_attr_key] ?? '',
					];
				}
			}

			return $diff;
		};

		foreach(array_keys($reference_kata['tables'] ?? []) as $table_name) {
			// The database storage engine creates `storage_<namespace>` on demand, so a namespace kept
			// on disk or S3 has no table at all. That's configuration, not drift.
			if(DevblocksPlatform::strStartsWith($table_name, 'storage_'))
				continue;

			if(!array_key_exists($table_name, $schema_kata['tables'])) {
				$missing_tables[$table_name] = $reference_kata['tables'][$table_name];
			}
		}

		foreach(array_keys($schema_kata['tables'] ?? []) as $table_name) {
			// Ignore numbered search index tables
			if(preg_match('/^search_index_\d+$/', $table_name))
				continue;

			$live_tables[$table_name] = $schema_kata['tables'][$table_name];

			if(DevblocksPlatform::strStartsWith($table_name, 'custom_record_')) {
				$existing_tables[$table_name] = $schema_kata['tables'][$table_name];

				$diff = $kata->treeDiff($reference_kata_custom_record, $schema_kata['tables'][$table_name]);

				if ($diff) {
					$diff_tables[$table_name] = $funcTheirsOurs($diff, $table_name, $reference_kata_custom_record);
				}

			} else if(DevblocksPlatform::strStartsWith($table_name, 'storage_')) {
				$existing_tables[$table_name] = $schema_kata['tables'][$table_name];

				$diff = $kata->treeDiff($reference_kata_storage, $schema_kata['tables'][$table_name]);

				if($diff) {
					$diff_tables[$table_name] = $funcTheirsOurs($diff, $table_name, $reference_kata_storage);
				}

			} else if(!array_key_exists($table_name, $reference_kata['tables'])) {
				$extra_tables[$table_name] = $schema_kata['tables'][$table_name];

			} else {
				$existing_tables[$table_name] = $schema_kata['tables'][$table_name];

				$diff = $kata->treeDiff($reference_kata['tables'][$table_name], $schema_kata['tables'][$table_name]);

				if($diff) {
					$diff_tables[$table_name] = $funcTheirsOurs($diff, $table_name, $reference_kata['tables'][$table_name]);
				}
			}
		}

		return [
			'reference' => $reference_kata['tables'] ?? [],
			'live' => $live_tables,
			'missing' => $missing_tables,
			'extra' => $extra_tables,
			'existing' => $existing_tables,
			'diffs' => $diff_tables,
		];
	}

	// The shape every `custom_record_*` table is expected to have; they're created per-install, so
	// they have no entry in the reference file to diff against.
	private function _getCustomRecordReferenceKata() : string {
		return <<< EOD
        columns:
          created_at:
            field: created_at
            type: int unsigned
            collation@text:
            nullable: NOT NULL
            key: MUL
            default: 0
            extra@text:
          id:
            field: id
            type: int unsigned
            collation@text:
            nullable: NOT NULL
            key: PRI
            default@text:
            extra: auto_increment
          name:
            field: name
            type: varchar(255)
            collation: utf8mb3_unicode_ci
            nullable: NULL
            key: MUL
            default@text:
            extra@text:
          owner_context:
            field: owner_context
            type: varchar(255)
            collation: utf8mb3_unicode_ci
            nullable: NULL
            key: MUL
            default@text:
            extra@text:
          owner_context_id:
            field: owner_context_id
            type: int unsigned
            collation@text:
            nullable: NOT NULL
            key@text:
            default: 0
            extra@text:
          updated_at:
            field: updated_at
            type: int unsigned
            collation@text:
            nullable: NOT NULL
            key: MUL
            default: 0
            extra@text:
        indexes:
          PRIMARY:
            columns:
              id:
                column_name: id
                index_type: BTREE
                subpart@text:
                unique: 1
          created_at:
            columns:
              created_at:
                column_name: created_at
                index_type: BTREE
                subpart@text:
                unique@text:
          name:
            columns:
              name:
                column_name: name
                index_type: BTREE
                subpart: 6
                unique@text:
          owner:
            columns:
              owner_context:
                column_name: owner_context
                index_type: BTREE
                subpart@text:
                unique@text:
              owner_context_id:
                column_name: owner_context_id
                index_type: BTREE
                subpart@text:
                unique@text:
          updated_at:
            columns:
              updated_at:
                column_name: updated_at
                index_type: BTREE
                subpart@text:
                unique@text:
        EOD;
	}

	// The shape every `storage_*` table is expected to have. The database storage engine creates one
	// per namespace on demand (`_DevblocksStorageEngineDatabase::_createTable`), so which ones exist
	// depends on the install's storage profiles rather than on the schema.
	private function _getStorageReferenceKata() : string {
		return <<< EOD
        columns:
          chunk:
            field: chunk
            type: smallint unsigned
            collation@text:
            nullable: NULL
            key@text:
            default: 1
            extra@text:
          data:
            field: data
            type: blob
            collation@text:
            nullable: NULL
            key@text:
            default@text:
            extra@text:
          id:
            field: id
            type: int unsigned
            collation@text:
            nullable: NOT NULL
            key: MUL
            default: 0
            extra@text:
        indexes:
          id_and_chunk:
            columns:
              id:
                column_name: id
                index_type: BTREE
                subpart@text:
                unique@text:
              chunk:
                column_name: chunk
                index_type: BTREE
                subpart@text:
                unique@text:
        EOD;
	}
}