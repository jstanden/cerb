<?php
class _DevblocksDataProviderAttachmentManifest extends _DevblocksDataProvider {
	public function getSuggestions($type, array $params = []) {
		return [
			'' => [
				'filter:',
				'format:',
				'id:',
				'limit:',
				'offset:',
			],
			'filter:' => [
				"*.png",
				"example/path/*",
			],
			'format:' => [
				'dictionaries',
			],
			'id' => [
				'123',
			],
			'limit' => [
				'1000',
			],
			'offset' => [
				'0',
			],
		];
	}
	
	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$chart_model = [
			'type' => 'attachment.manifest',
			'filter' => null,
			'format' => 'dictionaries',
			'id' => null,
			'offset' => 0,
			'limit' => 1000,
		];
		
		$allowed_formats = [
			'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'filter') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['filter'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$format = DevblocksPlatform::strLower($value);
				
				if(false === array_search($format, $allowed_formats)) {
					$error = sprintf("Unknown `format:` (%s). Must be one of: %s",
						$format,
						implode(', ', $allowed_formats)
					);
					return false;
				}
				
				$chart_model['format'] = $format;
				
			} else if($field->key == 'id') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['id'] = intval($value);
				
			} else if($field->key == 'offset') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if(!is_numeric($value)) {
					$error = "`offset:` must be a number.";
					return false;
				}
				
				$chart_model['offset'] = DevblocksPlatform::intClamp($value, 0, PHP_INT_MAX);
				
			} else if($field->key == 'limit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if(!is_numeric($value)) {
					$error = "`limit:` must be a number.";
					return false;
				}
				
				$chart_model['limit'] = DevblocksPlatform::intClamp($value, 0, PHP_INT_MAX);
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$error = null;
		
		if(false === ($results = $this->_getAttachmentManifest($chart_model, $error))) {
			return [
				'error' => $error
			];
		}
		
		return ['data' => $results, '_' => [
			'type' => 'attachment.manifest',
			'format' => 'dictionaries',
		]];
	}
	
	private function _getAttachmentManifest(array $chart_model, &$error=null) {
		if(!extension_loaded('zip')) {
			$error = 'The `zip` PHP extension is not loaded.';
			return false;
		}
		
		if(!array_key_exists('id', $chart_model)) {
			$error = 'The `id:` parameter is required.';
			return false;
		}
		
		if(false == ($file = DAO_Attachment::get($chart_model['id']))) {
			$error = sprintf('Invalid attachment `id:` (%d)', $chart_model['id']);
			return false;
		}
		
		$fp_zip = DevblocksPlatform::getTempFile();
		$fp_zip_info = DevblocksPlatform::getTempFileInfo($fp_zip);
		
		if(false === $file->getFileContents($fp_zip)) {
			$error = 'Failed to load attachment contents.';
			return false;
		}
		
		$zip = new \ZipArchive();
		
		if(true !== $zip->open($fp_zip_info)) {
			$error = 'The attachment is not a valid ZIP archive.';
			return false;
		}
		
		$offset = $chart_model['offset'] ?? 0;
		$limit = $chart_model['limit'] ?? 1000;
		$filter = $chart_model['filter'] ?? null;
		$filter_pattern = $filter ? DevblocksPlatform::strToRegExp($filter) : null;
		$count = 0;
		
		$results = [
			'cursor' => [
				'num_files' => $zip->numFiles,
				'offset_start' => $offset,
				'offset_end' => $offset,
				'limit' => $limit,
			],
			'files' => [],
		];
		
		for($i=$offset; $i < $zip->numFiles; $i++) {
			if($count >= $limit || false === ($fstat = $zip->statIndex($i))) {
				break;
			}
			
			$results['cursor']['offset_end'] = $i;
			
			// If we're filtering the results
			if($filter_pattern && !preg_match($filter_pattern, $fstat['name'] ?? null)) {
				continue;
			}
			
			$results['files'][] = array_intersect_key(
				$fstat,
				array_fill_keys(['name','key','size','mtime','index'], true)
			);
			
			$count++;
		}
		
		if($zip instanceof ZipArchive)
			$zip->close();
		
		return $results;		
	}
}