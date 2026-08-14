<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

/**
 * A browser round-trip to the host editor. The automation issues `uiCommand/<var>: { command: <name>, params: {…} }`
 * inside an `await:form:`. On render the element FILLS its hidden `prompts[<var>]` input by synchronously invoking
 * the host's `command` callback (which cerbBotTrigger stored on the interaction form) — it does NOT submit itself.
 * The form's own `submit:` posts the value, which lands in state under `<var>` (standard value path), so an
 * `on_tool:` branch can do `tool.return: content@key: <var>`.
 *
 * `<var>` (the element name) is the RETURN variable; `command:` names which host command to run — independent of
 * each other. Deliberately minimal: no consent/presentation knob (that's the `on_tool:` author's job to compose).
 * Always include a `submit:` (e.g. `is_automatic@bool: yes` for an auto round-trip). Advertised only on
 * `interaction.worker.agent` -- on any other trigger the element is silently skipped at render, so the tool
 * result comes back empty. (It briefly rode on `interaction.internal`, which was expedient and wrong: agent
 * panes are the worker's own conversations, not Cerb's internal plumbing.)
 *
 * `disabled@bool: <expr>` renders the element INERT — it emits its (empty) return var so the field still validates
 * and `{{<var>}}` resolves to '', but it does NOT round-trip to the host. This lets ONE `await:form:` carry several
 * `uiCommand`s and enable just the one matching the current tool (`disabled@bool: {{ __tool.parameters.command !=
 * 'setField' }}`), instead of a near-duplicate `on_tool:` branch per command.
 */
class UiCommandAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}

	function validate(_DevblocksValidationService $validation) {
		// Register the ELEMENT NAME (`uiCommand/<name>`) as the return field — the value the browser command
		// produced lands in the dict under `<name>` (independent of the `command:` that was run). Without this the
		// runtime's validateAll() rejects the posted value as an "unknown field". The value is free-form (an editor
		// value, JSON, etc.) and may be empty (e.g. reading an empty editor).
		$validation->addField($this->_key)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength('32 bits')
			->setNotEmpty(false)
		;
	}

	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();

		$command = is_array($this->_data) ? ($this->_data['command'] ?? '') : '';
		$params = (is_array($this->_data) && is_array($this->_data['params'] ?? null)) ? $this->_data['params'] : [];
		$disabled = is_array($this->_data) && ($this->_data['disabled'] ?? false);

		$tpl->assign('var', $this->_key);
		$tpl->assign('disabled', $disabled);
		$tpl->assign('command', $command);
		// Encode for safe inlining into the command call (autoescape would corrupt JSON — printed with nofilter).
		$tpl->assign('command_json', json_encode($command));
		$tpl->assign('params_json', json_encode((object) $params));

		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/ui_command.tpl');
	}
}
