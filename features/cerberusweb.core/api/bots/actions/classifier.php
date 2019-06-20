<?php
class BotAction_ClassifierPrediction extends Extension_DevblocksEventAction {
	const ID = 'core.va.action.classifier_prediction';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'classifier_id' => [
					'type' => 'id',
					'required' => true,
					'notes' => 'The ID of the [classifier](/docs/records/types/classifier/) to use',
				],
				'content' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The text to give the classifier',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the classifier results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		$classifiers = DAO_Classifier::getReadableByActor(CerberusContexts::CONTEXT_BOT, $trigger->bot_id);
		$tpl->assign('classifiers', $classifiers);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_classifier_prediction.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$classifier_id = $params['classifier_id'];
		@$content = $tpl_builder->build($params['content'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_prediction';

		if(false == (DAO_Classifier::get($classifier_id)))
			return "[ERROR] The configured classifier does not exist.";
		
		if(empty($content))
			return "[ERROR] Content is required.";
		
		$out = sprintf(">>> Making a classifier prediction (%s):\n%s\n", $classifier_id, $content);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving result to {{%1\$s}}\n".
				" * {{%1\$s.classification.name}}\n".
				" * {{%1\$s.confidence}}\n".
				" * {{%1\$s.params}}\n".
				"\n",
				$object_placeholder
			);
		}
		
		$this->run($token, $trigger, $params, $dict);
		
		// [TODO] Append raw output?
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$bayes = DevblocksPlatform::services()->bayesClassifier();
		
		@$classifier_id = $params['classifier_id'];
		@$content = $tpl_builder->build($params['content'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_prediction';
		
		$environment = [
			'lang' => 'en_US',
			'timezone' => '',
		];
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker()))
			$environment['me'] = ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id, 'model' => $active_worker];
		
		if(false === ($result = $bayes::predict($content, $classifier_id, $environment)))
			return;
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = $result['prediction'];
		}
	}
	
};