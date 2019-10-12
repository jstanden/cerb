<?php
class _DevblocksDataProviderClassifierPrediction extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions = [
			'' => [
				[
					'caption' => 'classifier:',
					'snippet' => 'classifier:(${1})',
				],
				[
					'caption' => 'text:',
					'snippet' => 'text:"${1}"',
				],
				'format:',
			],
			'classifier:' => [],
			'format:' => [
				'dictionaries',
			],
		];
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias('classifier', true)))
			return [];
		
		if(false == ($view = $context_ext->getTempView()))
			return [];
		
		$of_schema = $view->getQueryAutocompleteSuggestions();
		
		foreach($of_schema as $of_path => $of_suggestions) {
			if('_contexts' == $of_path) {
				if(!array_key_exists('_contexts', $suggestions))
					$suggestions['_contexts'] = [];
				
				foreach($of_suggestions as $ctx_path => $ctx_suggestion) {
					$suggestions['_contexts']['classifier:' . $ctx_path] = $ctx_suggestion;
				}
				
			} else {
				$suggestions['classifier:' . $of_path] = $of_suggestions;
			}
		}
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$chart_model = [
			'type' => 'classifier.prediction',
			'text' => '',
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				// Do nothing
				
			} else if($field->key == 'classifier') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['classifier_query'] = $data_query;
			
			} else if($field->key == 'text') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['text'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$classifier_id = 0;
		
		if(array_key_exists('classifier_query', $chart_model)) {
			$classifier_ext = Extension_DevblocksContext::get(Context_Classifier::ID, true);
			
			$view = $classifier_ext->getTempView();
			$view->addParamsWithQuickSearch($chart_model['classifier_query']);
			$view->renderLimit = 1;
			$view->renderTotal = false;
			
			$results = $view->getData()[0];
			
			if($results)
				$classifier_id = key($results);
			
		} else {
			$error = "The `classifier:` field is required.";
			return false;
		}
		
		if(!$classifier_id) {
			$error = "The specified classifier does not exist.";
			return false;
		}
		
		if(!$chart_model['text']) {
			$error = "The `text:` field is required.";
			return false;
		}
		
		$predict = DevblocksPlatform::services()->bayesClassifier();
		
		if(false == ($prediction = $predict::predict($chart_model['text'], $classifier_id)))
			return false;
		
		$chart_model['data'] = $prediction;
		
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
				'type' => 'classifier.prediction',
				'format' => 'dictionaries',
			]
		];
		
		return $meta;
	}
};