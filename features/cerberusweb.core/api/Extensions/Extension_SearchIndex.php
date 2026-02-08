<?php

namespace Cerb\Extensions;

use Context_SearchIndex;
use DevblocksExtension;
use DevblocksExtensionGetterTrait;
use Model_SearchIndex;

abstract class Extension_SearchIndex extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.search.index';
	
	static $_registry = [];
	
	abstract function renderConfig(Model_SearchIndex $model) : void;
	
	abstract function invokeConfig($config_action, Model_SearchIndex $model) : void;
	
	function saveConfig(array $fields, $id, &$error = null): bool {
		return true;
	}
	
	abstract public function indexDocumentsByModel(Model_SearchIndex $model, int $limit=25) : array;
	
	abstract public function queryJoinFromRecordQuickSearch(Model_SearchIndex $model, string $query): string;
	
	abstract public function queryDocumentsWithScore(Model_SearchIndex $model, string $query, int $limit = 100): array;
	
	abstract public function deleteIndex(Model_SearchIndex $model): bool;
	
	/**
	 * @internal
	 */
	public function export(Model_SearchIndex $model): string {
		$source_json = [
			'search_index' => [
				'uid' => 'search_index_' . $model->id,
				'_context' => Context_SearchIndex::ID,
				'extension_id' => $model->extension_id,
				'extension_params' => $model->extension_params,
				'name' => $model->name,
				'record_filter' => $model->record_filter,
				'record_type' => $model->record_type,
				'uri' => $model->uri,
			]
		];
		
		return json_encode($source_json);
	}
}

;