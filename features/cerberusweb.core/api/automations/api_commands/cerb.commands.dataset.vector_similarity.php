<?php
class ApiCommand_CerbDatasetVectorSimilarity extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.dataset.vector_similarity';
	
	function run(array $params=[], &$error=null) : array|false {
		$stats = DevblocksPlatform::services()->stats();
		
		$vector = $params['vector'] ?? null;
		$resource_uri = $params['uri'] ?? null;
		$embeddings_key = $params['embeddings_key'] ?? null;
		$return_keys = $params['return_keys'] ?? ['id'];
		$limit = DevblocksPlatform::intClamp($params['limit'] ?? 10, 1, 500);
		
		$fp = null;
		$scores = [];
		
		$funcPrune = function(array $scores, int $limit) : array {
			DevblocksPlatform::sortObjects($scores, '[score]', false);
			return array_slice($scores, 0, $limit);
		};
		
		try {
			if(!$resource_uri || !($uri_parts = DevblocksPlatform::services()->ui()->parseURI($resource_uri)))
				throw new Exception_DevblocksValidationError('The given `uri:` was invalid.');
			
			if(!($resource = DAO_Resource::getByNameAndType($uri_parts['context_id'] ?? 0, ResourceType_DatasetJsonl::ID)))
				throw new Exception_DevblocksValidationError('The given `uri:` was not found.');
			
			// Below 10MB load the dataset into memory, otherwise stream to disk
			if($resource->storage_size <= 10_000_000) {
				$fp = fopen('php://memory', 'r+');
			} else {
				$fp = DevblocksPlatform::getTempFile();
			}
			
			if(!is_resource($fp) || !($resource->getFileContents($fp)))
				throw new Exception_DevblocksValidationError('Failed to load the dataset of the given `uri:`.');
			
			while(!feof($fp)) {
				if(!($line = fgets($fp)))
					continue;
				
				if(!($json = json_decode($line, true)))
					continue;
				
				if(!array_key_exists($embeddings_key, $json))
					continue;
				
				$scores[] = [
					'score' => $stats->cosineSimilarity($vector, $json[$embeddings_key] ?? []),
					'data' => array_intersect_key($json, array_fill_keys($return_keys, true))
				];
				
				if(count($scores) > max($limit*2, 100))
					$scores = $funcPrune($scores, $limit);
			}
			
			return [
				'matches' => $funcPrune($scores, $limit),
			];
			
		} catch(Exception_DevblocksValidationError $e) {
			$error = $e->getMessage();
			return false;
			
		} catch(Throwable) {
			$error = 'An unexpected error occurred.';
			return false;
			
		} finally {
			if(is_resource($fp))
				fclose($fp);
		}
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		if('uri:' == $key_path) {
			$resources = DAO_Resource::getWhere(sprintf('%s = %s',
				DAO_Resource::EXTENSION_ID,
				Cerb_ORMHelper::qstr(ResourceType_DatasetJsonl::ID)
			));
			
			return array_map(
				fn($resource) => 'cerb:resource:' . $resource->name,
				array_values($resources)
			);
			
		} elseif ('' == $key_path) {
			return [
				'uri:',
				'vector@key:',
				'embeddings_key:',
				'return_keys@csv:',
				'limit@int: 10',
			];
			
		} else {
			return [];
		}
	}
}