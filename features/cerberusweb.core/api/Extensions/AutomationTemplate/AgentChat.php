<?php
namespace Cerb\Extensions\AutomationTemplate;

use AutomationTrigger_InteractionWorker;
use DAO_Automation;
use DAO_ConnectedAccount;
use DevblocksPlatform;
use Cerb\Extensions\Extension_AutomationTemplate;

// "AI Agent Chat" — a code-driven CerbUI wizard (model cards + system prompt + tools) that GENERATES the
// agent-loop KATA from the author's answers. Targets the interaction.worker trigger (declared in plugin.xml).
class AgentChat extends Extension_AutomationTemplate {
	const ID = 'cerb.automation.template.ai.agent_chat';

	function hasWizard() : bool {
		return true;
	}

	// The wizard: model cards + system prompt + tool selection (the model catalog comes from the trigger).
	function renderWizard() : string {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('agent_providers_json', json_encode(AutomationTrigger_InteractionWorker::getAgentProviders()));
		$tpl->assign('model_presets_json', json_encode(AutomationTrigger_InteractionWorker::getAgentModelPresets()));
		$tpl->assign('agent_models_json', json_encode(AutomationTrigger_InteractionWorker::getAgentModelChoices()));
		$tpl->assign('account_uris_json', json_encode((object) $this->_connectedAccountUris()));

		// Tools are added live via a CerbUI.RecordChooser scoped to `trigger:cerb.trigger.llm.tool`.
		return $tpl->fetch('devblocks:cerberusweb.core::internal/automation/editor/wizard_agent_chat.tpl');
	}

	function build(array $answers) : array {
		// The loop is generated from the wizard's answers; the policy only ever needs to allow the agent turn
		// (the tools are separate llm.tool automations with their own policies).
		$policy_kata = "commands:\n  llm.agent:\n    allow@bool: yes";

		return [
			'extension_id' => $this->getTriggerId(),
			'script' => $this->_generateAgentChatScript($answers),
			'policy_kata' => $policy_kata,
		];
	}

	// Generate the Agent Chat loop KATA from the wizard's answers (title / models / system prompt / tools baked in).
	private function _generateAgentChatScript(array $answers) : string {
		// The chat window title is single-line; strip newlines and fall back to a default.
		$title = trim(preg_replace('/[\r\n]+/', ' ', strval($answers['title'] ?? '')));
		if('' === $title)
			$title = 'Agent Chat';

		// System prompt (default if blank), each line indented under `system_prompt@text:` (16 spaces).
		$system_prompt = trim(strval($answers['system_prompt'] ?? ''));
		if('' === $system_prompt)
			$system_prompt = 'You are a helpful AI agent.';
		$sys_block = '';
		foreach(preg_split('/\r?\n/', $system_prompt) as $line)
			$sys_block .= '                ' . $line . "\n";

		// The client posts array/object answers (tools, models) as JSON strings — decode them.
		$tools_answer = $answers['tools'] ?? [];
		if(is_string($tools_answer))
			$tools_answer = json_decode($tools_answer, true) ?: [];
		$models_answer = $answers['models'] ?? [];
		if(is_string($models_answer))
			$models_answer = json_decode($models_answer, true) ?: [];

		// Tools — each answer is { id, alias, label_summary, label_active }. Resolve id → automation name for
		// the uri; key by the author's readable alias (default = the name) so the model sees `docs_search`,
		// not `tool123`, and carry the transcript labels ("Searching…" / "Searched…").
		$tools_block = '';
		$tools_by_id = [];
		foreach((array) $tools_answer as $t) {
			if(!is_array($t))
				continue;
			$id = intval($t['id'] ?? 0);
			if($id)
				$tools_by_id[$id] = $t;
		}
		if($tools_by_id) {
			$automations = DAO_Automation::getIds(array_keys($tools_by_id));
			$tools_block .= "              tools:\n";
			foreach($tools_by_id as $id => $t) {
				if(!isset($automations[$id]))
					continue;
				$name = $automations[$id]->name;
				$alias = $this->_toolAlias(strval($t['alias'] ?? '')) ?: $this->_toolAlias($name);
				$tools_block .= "                automation/{$alias}:\n";
				$tools_block .= "                  uri: cerb:automation:{$name}\n";

				$active = trim(strval($t['label_active'] ?? ''));
				$summary = trim(strval($t['label_summary'] ?? ''));
				if('' !== $active || '' !== $summary) {
					$tools_block .= "                  labels:\n";
					if('' !== $active)
						$tools_block .= "                    active: {$active}\n";
					if('' !== $summary)
						$tools_block .= "                    summary: {$summary}\n";
				}
			}
		}

		// Models — each answer REFERENCES an agent_model record by name, optionally overriding a few knobs. The
		// record supplies provider/model/auth/vision/context window; we emit `models: <name>: <overrides>`.
		$models = is_array($models_answer) ? $models_answer : [];
		$models_block = "              models:\n";
		$emitted = 0;
		foreach($models as $m) {
			$name = trim(strval($m['name'] ?? ''));
			if('' === $name)
				continue;

			$models_block .= "                {$name}:\n";

			if('' !== ($cw = trim(strval($m['context_window'] ?? ''))) && ctype_digit($cw))
				$models_block .= "                  context_window@int: {$cw}\n";
			if('' !== ($effort_choices = trim(strval($m['effort_choices'] ?? ''))))
				$models_block .= "                  effort_choices: {$effort_choices}\n";
			if('' !== ($disabled = trim(strval($m['disabled'] ?? '')))) {
				$expr = (str_starts_with($disabled, '{{')) ? $disabled : ('{{' . $disabled . '}}');
				$models_block .= "                  disabled@bool: {$expr}\n";
			}

			$emitted++;
		}
		if(!$emitted) {
			// No model configured — leave a working example the author must review.
			$models_block .= "                # [TODO] Add at least one model (reference an agent_model record by name).\n";
			$models_block .= "                claude-sonnet-5:\n";
		}

		$skeleton = <<<'KATA'
# An interactive AI agent chat (generated by the Automation Builder). Each turn shows the transcript + a
# prompt, sends your message to the agent (running any tools it calls), and loops. Resume an earlier
# transcript by passing its Session ID.

start:
  set:
    prompt_agent@text:
    session_id: {{uuid()}}

  while:
    if@bool: yes
    do:
      # If the user sent a message, run an agent turn (this also runs any tools the agent calls).
      outcome/prompt:
        if@bool: {{prompt_agent}}
        then:
          llm.agent:
            output: results
            inputs:
              session_id@key: session_id
              system_prompt@text:
__SYSTEM_PROMPT__
              messages:
                message:
                  role: user
                  content@key: prompt_agent
                  images@key,optional: prompt_agent__images
__TOOLS__
            on_tool:
              await:
                form:
                  elements:
                    llmTranscript/prompt_preview:
                      session_id@key: session_id
                      thinking: raw
                      tools: raw
                    submit:
                      is_automatic@bool: yes
            on_success:
              set:
                session_id: {{results.session_id}}
            # Catch a failed turn (a bad/missing model, a provider error, or a transient timeout) so the chat
            # doesn't dead-end: control falls back to the loop, the transcript + composer re-render, and the
            # worker can retry — instead of the whole interaction ending on an error. To surface the reason,
            # add an `await: form:` here with a `say` element (`{{results.error}}`); that costs an extra step.
            on_error:

      # Show the transcript and prompt for the next message.
      await:
        form:
          title: __TITLE__
          elements:
            llmTranscript/prompt_transcript:
              session_id: {{session_id}}
              tools: raw
              thinking: raw
            agentPrompt/prompt_agent:
              placeholder: Message the agent...
              session_id: {{session_id}}
              required@bool: yes
              # What `@` autocompletes. Opt-in: without this block `@` completes nothing. Add
              # `filesystems:` (keyed by volume name) to also complete `@<volume>/<path>` file
              # references — independent of what the llm.agent above actually mounts.
              references:
                workers:
__MODELS__
            submit:
              continue@bool: no
              reset@bool: no

KATA;

		$script = str_replace('__TITLE__', $title, $skeleton);
		$script = str_replace('__SYSTEM_PROMPT__' . "\n", $sys_block, $script);
		$script = str_replace('__TOOLS__' . "\n", $tools_block, $script);
		$script = str_replace('__MODELS__' . "\n", $models_block, $script);

		return $script;
	}

	// A readable, KATA-safe alias (function name the model sees): lowercase, non-word → underscore.
	private function _toolAlias(string $name) : string {
		$alias = trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower($name)), '_');
		return $alias ?: 'tool';
	}

	// Map of connected-account id → uri (for readable `cerb:connected_account:<uri>` auth refs).
	private function _connectedAccountUris() : array {
		$out = [];
		foreach(DAO_ConnectedAccount::getAll() as $account) {
			if(strlen(strval($account->uri ?? '')))
				$out[$account->id] = $account->uri;
		}
		return $out;
	}
}
