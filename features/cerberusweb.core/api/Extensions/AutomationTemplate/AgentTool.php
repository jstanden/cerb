<?php
namespace Cerb\Extensions\AutomationTemplate;

use Cerb\Extensions\Extension_AutomationTemplate;

// "AI Agent Tool" — a static starter for an automation an AI agent can call (an `llm.tool` trigger). No wizard;
// build() just seeds the editor from the shipped asset files. Targets the llm.tool trigger (declared in plugin.xml).
class AgentTool extends Extension_AutomationTemplate {
	const ID = 'cerb.automation.template.ai.agent_tool';

	function build(array $answers) : array {
		$seed = self::_loadAsset('ai-agent-tool.kata');
		$seed['extension_id'] = $this->getTriggerId();

		return $seed;
	}
}
