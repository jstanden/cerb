<?php
/*
 * The automation behind an `agent_tool` record: an AI agent calls the tool, this runs, and its `content:` is
 * what the model reads back.
 *
 * The automation declares its own `inputs:`, exactly as an `llm.tool` one did, and THAT is the schema the model
 * is shown -- so a tool is self-documenting and there is only one place to change what it takes. The
 * `agent_tool` record supplies everything an automation has nowhere to say: the name the model calls, the
 * description it reads, the transcript's icon and wording. One automation can still back several records --
 * read `tool_name` and branch.
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
				'key' => 'inputs.*',
				'type' => 'dict',
				'notes' => "The arguments for this call, read as `inputs.<name>` (e.g. `{{inputs.query}}`). Declare "
					. "them in this automation's own `inputs:` block: that block IS the schema the model is shown, "
					. "so its `description`, `required`, `allowed_values` and `default` are what the model reads and "
					. "obeys. An agent may also FIX an argument on its reference to the tool, in which case the "
					. "fixed value arrives here and the model was never offered the choice.",
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
	
}
