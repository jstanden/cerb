<?php
class BotAction_EmailParser extends Extension_DevblocksEventAction {
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'message_source' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The raw email message (RFC-2822) to process',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Run the email parser in the simulator: `0`=no, `1`=yes',
				],
				'response_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the email parser results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_email_parser.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$message_source = $tpl_builder->build($params['message_source'], $dict);
		@$run_in_simulator = $params['run_in_simulator'];
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($message_source))
			return "[ERROR] Message source is required.";
		
		if(empty($response_placeholder))
			return "[ERROR] Return placeholder is required.";
		
		// Output
		$out = sprintf(">>> Executing email parser:\n\n%s\n\n",
			$message_source
		);
		
		if($run_in_simulator) {
			// Run the parser
			$this->run($token, $trigger, $params, $dict);
			
			$response = $dict->$response_placeholder;
			
			if(isset($response['error']) && !empty($response['error'])) {
				$out .= sprintf(">>> Error:\n%s\n", $response['error']);
				
			} else {
				$out .= sprintf(">>> Response:\n%s\n\n", DevblocksPlatform::strFormatJson(json_encode($response)));
			}
			
		} else {
			$out .= ">>> NOTE: The email parser is not configured to run in the simulator.\n";
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$result = [];
		
		// Stash the current state
		$node_log = EventListener_Triggers::getNodeLog();
		
		try {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			@$message_source = $tpl_builder->build($params['message_source'], $dict);
			@$response_placeholder = $params['response_placeholder'];
			
			if(empty($message_source))
				throw new Exception_DevblocksValidationError("The message is empty.");
			
			if(false == ($message = CerberusParser::parseMimeString($message_source)))
				throw new Exception_DevblocksValidationError("Failed to parse MIME message.");
			
			if(false == ($ticket_id = CerberusParser::parseMessage($message)))
				throw new Exception_DevblocksValidationError("Failed to parse message into ticket.");
			
			if(empty($response_placeholder))
				throw new Exception_DevblocksValidationError("The response placeholder is empty.");
			
			$result = [
				'success' => true,
				'ticket_id' => $ticket_id,
			];
			
		} catch(Exception_DevblocksValidationError $e) {
			$result = ['error' => $e->getMessage()];
			
			// Pop the current state
			EventListener_Triggers::setNodeLog($node_log);
		}
		
		// Pop the current state
		EventListener_Triggers::setNodeLog($node_log);
		
		// Set the result
		$dict->$response_placeholder = $result;
	}
};