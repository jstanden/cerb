<?php
class AutomationTrigger_InteractionWorkerExplore extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.interaction.worker.explore';
	
	static function getFormComponentMeta() {
		return [];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'explore_hash',
				'notes' => 'The unique identifier of the explore set.',
			],
			[
				'key' => 'explore_page',
				'notes' => 'The custom page action returned by `await:explore:` (e.g. `next`).',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'await' => [
				[
					'key' => 'explore:title',
					'notes' => 'The title of the next item',
				],
				[
					'key' => 'explore:url',
					'notes' => 'The URL of the next item',
				],
				[
					'key' => 'explore:label',
					'notes' => 'The optional label for the next item',
				],
				[
					'key' => 'explore:toolbar',
					'notes' => 'The toolbar KATA of the next item',
				],
			],
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		$toolbar_autocompletion = CerberusApplication::kataAutocompletions()->toolbar();
		
		$autocompletion = [
			'*' => [
				'(.*):await:' => [
					[
						'caption' => 'explore:',
						'snippet' => "explore:\n\ttitle: \${1:Item}\n\turl: \${2:https://example.com/}\n\t#toolbar:\n",
					]
				],
				'(.*):await:explore:' => [
					'title:',
					'label:',
					'url:',
					'toolbar:',
				],
				'(.*):await:explore:toolbar:' => $toolbar_autocompletion[''],
			]
		];
		
		// Merge the toolbar schema
		foreach($toolbar_autocompletion['*'] as $key => $item) {
			$autocompletion['*']['(.*):await:explore:toolbar:' . $key] = $item;
		}
		
		$autocompletion['*']['(.*):await:explore:toolbar:(.*):?interaction:after:'] = [
			'explore_page: next',
			'refresh_widgets@csv: Widget Name, Other Widget',
			'refresh_widgets@bool: yes',
		];
		
		return $autocompletion;
	}
}