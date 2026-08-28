<?php
namespace Cerb\Extensions\AutomationTemplate;

use Cerb\Extensions\Extension_AutomationTemplate;

// "AI Agent Tool" — a static starter for the automation behind an `agent_tool` record. No wizard; build() just
// seeds the editor from the shipped asset file. Targets the agent.tool trigger (declared in plugin.xml).
class AgentTool extends Extension_AutomationTemplate {
	const ID = 'cerb.automation.template.ai.agent_tool';

	function build(array $answers) : array {
		$seed = self::_loadAsset('agent-tool.kata');
		$seed['extension_id'] = $this->getTriggerId();

		return $seed;
	}
}
