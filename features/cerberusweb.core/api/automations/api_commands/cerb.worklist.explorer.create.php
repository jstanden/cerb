<?php
class ApiCommand_CerbWorklistExplorerCreate extends Extension_AutomationApiCommand {
	const ID = 'cerb.commands.worklist.explorer.create';
	
	function run(array $params=[], &$error=null) : array|false {
		$url_writer = \DevblocksPlatform::services()->url();
		
		$title = strval($params['title'] ?? null);
		$interaction = strval($params['interaction'] ?? null);
		$interaction_inputs = $params['interaction_inputs'] ?? [];
		
		if(!$interaction) {
			$error = '`params:interaction:` is required and must be a string.';
			return false;
		}
		
		// Verify that the interaction exists and is of the right type
		if(!($automation = DAO_Automation::getByUri($interaction, [AutomationTrigger_InteractionWorkerExplore::ID]))) {
			$error = '`params:interaction:` is not a valid `interaction.worker.explore` automation.';
			return false;
		}
		
		$model = new Model_ExplorerSet();
		$model->pos = 0;
		$model->hash = sha1(random_bytes(255));
		$model->params = [
			'title' => $title ?: 'Explore',
			'created' => time(),
			'worker_id' => 0,
			'total' => 0,
			'interaction' => $automation->name,
			'interaction_inputs' =>
				($interaction_inputs && is_array($interaction_inputs))
				? $interaction_inputs
				: [],
			'return_url' => $url_writer->writeNoProxy('', true),
		];
		
		\DAO_ExplorerSet::createFromModels([$model]);
		
		return [
			'hash' => $model->hash,
		];
	}
	
	public function getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script) : array {
		if('interaction:' == $key_path) {
			$interactions = DAO_Automation::getByTrigger(AutomationTrigger_InteractionWorkerExplore::ID);
			return array_map(
				fn($name) => 'cerb:automation:' . $name,
				array_column($interactions, 'name'),
			);
			
		} else if('interaction_inputs:' == $key_path) {
			$kata = DevblocksPlatform::services()->kata();
			
			if(
				!$script
				|| !($script = $kata->parse($script))
			) {
				return [];
			}
			
			$dict = DevblocksDictionaryDelegate::instance($script);
			
			$rel_path = explode(':', rtrim($key_fullpath,':'));
			array_pop($rel_path);
			array_push($rel_path, 'interaction:');
			$rel_path = implode(':', $rel_path);
			
			$interaction_uri = $dict->getKeyPath(rtrim($rel_path,':'), null, ':');
			
			if(
				!$interaction_uri
				|| !($interaction = DevblocksPlatform::services()->ui()->parseURI($interaction_uri))
				|| !CerberusContexts::isSameContext($interaction['context'], CerberusContexts::CONTEXT_AUTOMATION)
				|| !($automation = DAO_Automation::getByUri($interaction['context_id'], AutomationTrigger_InteractionWorkerExplore::ID))
			) {
				return [];
			}
			
			$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($automation,CerberusContexts::CONTEXT_AUTOMATION, ['inputs']);
			
			$inputs = $dict->get('inputs', []);
			
			return array_values(
				array_map(
					function($input_key) use ($inputs) {
						return [
							'caption' => $input_key . ':',
							'snippet' => $inputs[$input_key]['key'] . ': ',
						];
					},
					array_keys($inputs)
				)
			);
		
		} elseif('' == $key_path) {
			return [
				'interaction:',
				'interaction_inputs:',
				'title:',
			];
		}
		
		return [];
	}
}