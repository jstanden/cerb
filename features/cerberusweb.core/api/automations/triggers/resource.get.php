<?php
class AutomationTrigger_ResourceGet extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.resource.get';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'resource_*',
				'notes' => 'The [resource](https://cerb.ai/docs/records/types/resource/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'actor_*',
				'notes' => 'The current actor [record](https://cerb.ai/docs/records/types/). Supports key expansion. `actor__type` is the record type alias (e.g. `worker`).',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'file:content',
					'type' => 'string',
					'notes' => 'The file contents in text (base64-encode if binary)',
				],
				[
					'key' => 'file:expires_at',
					'type' => 'timestamp',
					'notes' => 'Cache this response until this UNIX timestamp',
				],
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		if(($linked_resources = DAO_Resource::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_Resource::AUTOMATION_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_resources = array_filter($linked_resources, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->automation_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_resources)
				$results['resource'] = array_column($linked_resources, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'file:',
				],
				'(.*):return:file:' => [
					'content@text:',
					'expires_at@date: 1 hour',
				],
			]
		];
	}
}