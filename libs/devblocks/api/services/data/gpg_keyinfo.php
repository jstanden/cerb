<?php
class _DevblocksDataProviderGpgKeyInfo extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions  = [
			'' => [
				'fingerprint:',
				'filter:',
			],
			'filter:' => [
				'subkeys',
				'uids',
			],
			'format:' => [
				'dictionaries',
			]
		];
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'gpg.keyinfo',
			'fingerprint' => '',
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				// Do nothing
			} else if($field->key == 'fingerprint') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['fingerprint'] = $value;
				
			} else if($field->key == 'filter') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['filter'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Sanitize
		
		if(empty($chart_model['fingerprint'])) {
			$error = "The `fingerprint:` field is required.";
			return false;
		}
		
		if(array_key_exists('filter', $chart_model) && !in_array($chart_model['filter'], ['uids', 'subkeys'])) {
			$error = "The `filter:` field must be one ofL uids, subkeys";
			return false;
		}
		
		// Data
		
		if(false == ($gpg = DevblocksPlatform::services()->gpg())) {
			$error = "The `gnupg` extension is not installed.";
			return false;
		}
		
		$keyinfo = $gpg->keyinfo($chart_model['fingerprint']);
		
		$data = [];
		
		if(count($keyinfo) > 0) {
			$keyinfo = array_shift($keyinfo);
			
			switch (@$chart_model['filter']) {
				case 'uids':
					$data = $keyinfo['uids'];
					break;
				
				case 'subkeys':
					$data = $keyinfo['subkeys'];
					break;
				
				default:
					$data = $keyinfo;
					break;
			}
		}
		
		$chart_model['data'] = $data;
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'dictionaries';
		
		switch($format) {
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($chart_model);
				break;
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: dictionaries",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsDictionaries($chart_model) {
		$meta = [
			'data' => $chart_model['data'],
			'_' => [
				'type' => 'gpg.keyinfo',
				'format' => 'dictionaries',
			]
		];
		
		return $meta;
	}
};