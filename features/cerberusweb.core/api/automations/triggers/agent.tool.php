<?php
/*
 * The automation behind an `agent_tool` record: an AI agent calls the tool, this runs, and its `content:` is
 * what the model reads back.
 *
 * The split from the older `llm.tool` trigger is where the parameters live. There, a tool automation declared
 * its own `inputs:` block and that block did two unrelated jobs at once -- telling the model what it may send,
 * and telling a caller what it must pass. Here the `agent_tool` RECORD owns the model-facing schema, so the
 * arguments arrive as `params.*` and the script declares nothing. That is also what lets one automation back
 * several tool records: read `tool_name` and branch.
 *
 * It receives an environment as well as arguments -- which agent is running, whom it serves, which surface it
 * was called from -- so a tool can refuse work that doesn't belong where it was invoked.
 */
class AutomationTrigger_AgentTool extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.agent.tool';
	
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
				'key' => 'params.*',
				'type' => 'dict',
				'notes' => "The arguments for this call, read as `params.<name>` (e.g. `{{params.query}}`). The "
					. "[agent tool](https://cerb.ai/docs/records/types/agent_tool/) record declares which ones the "
					. "model may send; a `defaults:` value fills one it left out, and a `pinned:` value overrides "
					. "whatever it sent. **Do not declare an `inputs:` block** -- a tool's parameters live on its "
					. "record, and a script that declares one is rejected on save.",
			],
			[
				'key' => 'tool_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'agent_tool',
				],
				'notes' => "The [agent tool](https://cerb.ai/docs/records/types/agent_tool/) record that was "
					. "called. Supports key expansion, so `tool_name` is the name the model used. One automation "
					. "can back several tool records -- branch on this rather than writing a script per tool.",
			],
			[
				'key' => 'agent_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
					'query' => 'isAi:y',
				],
				'notes' => "The AI [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) "
					. "running this tool. Supports key expansion, so `agent_name` and `agent__image_url` are the "
					. "agent's own name and avatar.",
			],
			[
				'key' => 'transcript_uuid',
				'type' => 'text',
				'notes' => "The conversation this call belongs to. Empty when the tool is run from the simulator.",
			],
			[
				'key' => 'transcript_trigger',
				'type' => 'text',
				'notes' => "The trigger of the automation that called this tool, e.g. "
					. "`cerb.trigger.interaction.worker.agent`. Says which KIND of conversation this is -- a "
					. "worker's chat, a website visitor's, another agent's -- so a tool can refuse to run "
					. "somewhere it doesn't belong.",
			],
			[
				'key' => 'transcript_surface',
				'type' => 'text',
				'notes' => "Where the conversation is running, when it's an agent pane: `automation`, "
					. "`automation_scripting`, `data_query`, `icon`, `mail_reply`, `worklist`, or `commandbar`. "
					. "Empty outside a pane.",
			],
			[
				'key' => 'transcript_user_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
				],
				'notes' => "The [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) the "
					. "agent is serving. Unset when the conversation belongs to a portal visitor rather than a "
					. "worker.",
			],
			[
				'key' => 'transcript_agent_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
					'query' => 'isAi:y',
				],
				'notes' => "The AI worker the conversation itself runs as. The same record as `agent_*` in every "
					. "normal case; they differ only if a turn was handed to a different agent than the one the "
					. "session was stamped with.",
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'content',
					'notes' => 'The result of the tool call, as the model should read it.',
					'required' => true,
				],
			],
		];
	}
	
	/**
	 * Who calls this tool -- an exact lookup, not a search.
	 *
	 * `llm.tool` had to `LIKE '%name%'` every automation script and re-tokenize each hit, and even then it
	 * could not see an agent referencing the tool at all. A tool is a record now, so its `uri` column answers
	 * this in one query. That exactness is one of the two things naming a tool buys.
	 */
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		if(($tools = DAO_AgentTool::getByUri('cerb:automation:' . $automation_name)))
			$results['agent_tool'] = array_keys($tools);
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'content@text:',
				],
			]
		];
	}
	
	/**
	 * The simulator can't fan one field out from another -- the descriptor list is built once, server-side,
	 * before the popup renders. So `params:` is primed as a KATA document the author types, rather than as a
	 * generated form derived from whichever tool they picked above it.
	 *
	 * `emit: none` is exactly for this: the field renders, and getSimulationState() below maps it.
	 */
	function getSimulationInputs() : array {
		$inputs = parent::getSimulationInputs();
		
		foreach($inputs as $idx => $input) {
			if('params' !== ($input['key'] ?? ''))
				continue;
			
			$inputs[$idx] = [
				'emit' => 'none',
				'key' => 'params',
				'component' => 'scripting_editor',
				'label' => 'Parameters',
				'default' => "query: example\n",
			];
		}
		
		return $inputs;
	}
	
	function getSimulationState(array $answers, &$error = null) : array {
		$state = parent::getSimulationState($answers, $error);
		
		if(is_string($error) && $error)
			return $state;
		
		$params_kata = trim(strval($answers['params'] ?? ''));
		
		if('' !== $params_kata) {
			$kata = DevblocksPlatform::services()->kata();
			$parse_error = null;
			
			if(false === ($params = $kata->parse($params_kata, $parse_error))) {
				$error = 'Parameters: ' . $parse_error;
				return $state;
			}
			
			$state['params'] = $kata->formatTree($params, null, $parse_error) ?: [];
		}
		
		return $state;
	}
}
