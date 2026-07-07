<?php
class Toolbar_GlobalSearch extends Extension_Toolbar {
	const ID = 'cerb.toolbar.global.search';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getInteractionInputsMeta() : array {
		return [
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
		];
	}
	
	function getInteractionAfterMeta() : array {
		return [
		];
	}
	
	public static function getSearchMenu() {
		$db = DevblocksPlatform::services()->database();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == $active_worker)
			return [];
		
		$toolbar_kata = '';
		$legacy_kata = '';
		$results = [];
		
		if(null != ($toolbar = DAO_Toolbar::getByName('global.search')))
			$toolbar_kata = $toolbar->getKata();
		
		// Start with the worker's search favorites
		if(null != ($search_favorites = DAO_WorkerPref::getAsJson($active_worker->id, 'search_favorites_json'))) {
			foreach($search_favorites as $favorite_ext_id) {
				if(($context_mft = Extension_DevblocksContext::get($favorite_ext_id, false))) {
					$results[$context_mft->params['alias']] = 100;
				}
			}
		}
		
		$search_history_limit = 50;
		
		// Get suggested record types based on this worker's recent activity
		$sql = sprintf("SELECT (SELECT name FROM metric_dimension WHERE id = metric_value.dim0_value_id) AS record_type FROM metric_value WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.record.search') AND granularity = 300 AND dim1_value_id = %d ORDER BY bin DESC LIMIT %d",
			$active_worker->id,
			$search_history_limit
		);
		
		if(($metric_results = $db->GetArrayReader($sql))) {
			$metric_results = array_count_values(array_column($metric_results, 'record_type'));
			
			// Sort by the most used record types
			arsort($metric_results);
			
			// Keep the top 10
			$metric_results = array_slice($metric_results, 0, 10, true);
			
			// Merge results
			foreach ($metric_results as $k => $count)
				$results[$k] = ($results[$k] ?? 0) + $count;
		}
		
		// Sanitize the metric aliases
		$results = array_filter($results, function($k) {
			return false != Extension_DevblocksContext::getByAlias($k);
		}, ARRAY_FILTER_USE_KEY);
		
		// Load record type metadata
		$record_types = 
			array_combine(
				array_keys($results),
				array_map(
					function($record_type) {
						$context_mft = Extension_DevblocksContext::getByAlias($record_type, false);
						$context_aliases = Extension_DevblocksContext::getAliasesForContext($context_mft);
						return [
							'id' => $context_mft->id,
							'icon' => $context_mft->params['icon'] ?? 'circle',
							'label' => DevblocksPlatform::strTitleCase($context_aliases['plural'] ?? $context_aliases['singular'] ?? $record_type),
						];
					},
					array_keys($results)
				)
		);
		
		DevblocksPlatform::sortObjects($record_types, '[label]');
		
		foreach($record_types as $record_type => $record_type_data) {
			$legacy_kata .= sprintf("\ninteraction/%s:\n  label: %s\n  icon: %s\n  uri: cerb:automation:%s\n  inputs:\n    record_type: %s\n",
				$record_type,
				$record_type_data['label'],
				$record_type_data['icon'],
				'ai.cerb.interaction.search',
				$record_type_data['id'],
			);
		}
		
		if($legacy_kata)
			$legacy_kata .= "\ndivider/divByRecordType:\n";

		// "All record types" submenu — every searchable type, alphabetized. Each row fires the same
		// ai.cerb.interaction.search interaction (just pre-targeted), so usage still records metrics.
		// Note: toolbar parse() only resolves `cerb:` URIs on TOP-LEVEL items, so these nested items use
		// the already-resolved automation name directly.
		$all_record_types = [];

		foreach(Extension_DevblocksContext::getAll(false, 'search') as $context_mft) {
			if(!($alias = $context_mft->params['alias'] ?? null))
				continue;
			
			/* @var DevblocksExtensionManifest $context_mft */

			$context_aliases = Extension_DevblocksContext::getAliasesForContext($context_mft);

			$all_record_types[$alias] = [
				'id' => $context_mft->id,
				'icon' => $context_mft->params['icon'] ?? 'circle',
				'label' => DevblocksPlatform::strTitleCase($context_aliases['plural'] ?? $context_aliases['singular'] ?? $alias),
			];
		}

		DevblocksPlatform::sortObjects($all_record_types, '[label]');

		$legacy_kata .= "\nmenu/allRecordTypes:\n  label: All record types\n  icon: collection\n  items:\n";

		foreach($all_record_types as $alias => $record_type_data) {
			$legacy_kata .= sprintf("    interaction/%s:\n      label: %s\n      icon: %s\n      uri: ai.cerb.interaction.search\n      inputs:\n        record_type: %s\n",
				$alias,
				$record_type_data['label'],
				$record_type_data['icon'],
				$record_type_data['id'],
			);
		}

		$toolbar_kata .= $legacy_kata;
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		return DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict);		
	}
 }