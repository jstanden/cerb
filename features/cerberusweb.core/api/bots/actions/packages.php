<?php
class BotAction_PackageImport extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.package.import';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'package_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The [package](/docs/packages/) manifest',
				],
				'prompts_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The prompted input for the package',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Import packages in the simulator: `0`=no, `1`=yes',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the imported package results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$actor = $trigger->getBot();
		
		// [TODO] When roles apply to bots, we'll allow non-admins to import packages
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			echo "This action is only available to administrators.";
			return false;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_package_import.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();

		$out = null;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = "This action is only available to administrators.";
			return $error;
		}
		
		$package_json = DevblocksPlatform::importVar($params['package_json'] ?? null,'string','');
		$prompts_json = $tpl_builder->build(DevblocksPlatform::importVar($params['prompts_json'] ?? null,'string',''), $dict);
		$object_placeholder = DevblocksPlatform::importVar($params['object_placeholder'] ?? null,'string','');
		
		if(!$package_json || false == @json_decode($package_json, true))
			return "Invalid package JSON: " . json_last_error_msg();
		
		if(false === @json_decode($prompts_json, true))
			return "Invalid prompts JSON: " . json_last_error_msg();
		
		$out = sprintf(">>> Importing package\n%s\n", $package_json);
		
		if($prompts_json)
			$out .= sprintf("\n>>> Configuration\n%s\n", $prompts_json);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
			
			if($object_placeholder) {
				$out .= sprintf("\n>>> Setting placeholder {{%s}}:\n%s",
					$object_placeholder,
					DevblocksPlatform::strFormatJson(json_encode($dict->$object_placeholder))
				);
			}
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$actor = $trigger->getBot();
		
		$response = [];
		
		try {
			if(!CerberusContexts::isActorAnAdmin($actor))
				throw new Exception_DevblocksValidationError("This action is only available to administrators.");
			
			@$package_json = DevblocksPlatform::importVar($params['package_json'],'string','');
			@$prompts_json = json_decode($tpl_builder->build(DevblocksPlatform::importVar($params['prompts_json'] ?? '','string',''), $dict), true);
			@$object_placeholder = DevblocksPlatform::importVar($params['object_placeholder'],'string','');
			
			if(!is_array($prompts_json))
				$prompts_json = [];
			
			CerberusApplication::packages()->import($package_json, $prompts_json, $response);
			
		} catch(Exception_DevblocksValidationError $e) {
			$response = [
				'_status' => false,
				'error' => $e->getMessage()
			];
			
		} catch(Exception $e) {
			$response = [
				'_status' => false,
				'error' => "An unexpected error occurred."
			];
		}
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = $response;
		}
	}
};