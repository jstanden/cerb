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
		$db = DevblocksPlatform::services()->database();
		$kata = DevblocksPlatform::services()->kata();
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // database_schema
		
		$visit->set(ChConfigurationPage::ID, 'database_schema');
		
		$error = null;
		
		if(!($reference_kata = $kata->parse(file_get_contents(APP_PATH . '/features/cerberusweb.core/assets/cerb.schema.kata'), $error))) {
			DevblocksPlatform::logError($error, true);
			return;
		}
		
		$reference_kata_custom_record = $kata->parse(<<< EOD
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
        EOD);
		
		$schema_kata = $db->dumpSchemaKata(false);
		
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
			if(!array_key_exists($table_name, $schema_kata['tables'])) {
				$missing_tables[$table_name] = $reference_kata['tables'][$table_name];
			}
		}
		
		foreach(array_keys($schema_kata['tables'] ?? []) as $table_name) {
			// Ignore numbered search index tables
			if(preg_match('/^search_index_\d+$/', $table_name))
				continue;

			if(DevblocksPlatform::strStartsWith($table_name, 'custom_record_')) {
				$existing_tables[$table_name] = $schema_kata['tables'][$table_name];
				
				$diff = $kata->treeDiff($reference_kata_custom_record, $schema_kata['tables'][$table_name]);
				
				if ($diff) {
					$diff_tables[$table_name] = $funcTheirsOurs($diff, $table_name, $reference_kata_custom_record);
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
		return false;
	}
}