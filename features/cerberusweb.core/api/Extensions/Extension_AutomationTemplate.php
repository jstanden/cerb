<?php

namespace Cerb\Extensions;

use DevblocksExtension;
use DevblocksExtensionGetterTrait;
use DevblocksPlatform;

// The Automation Builder opens a new automation on a filterable list of starter templates ("candidates"),
// grouped by `section`. Picking one seeds the trigger + a working script/policy into the ephemeral (id 0)
// editor — nothing is saved until Save. A candidate may drive a code-driven CerbUI wizard whose answers
// build() turns into the seed. Each template is its own extension (declared in the same plugin.xml as the
// trigger it targets, so it disables naturally with that plugin). Metadata rides the manifest params.
abstract class Extension_AutomationTemplate extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;

	const POINT = 'cerb.automation.template';

	function getLabel() : string       { return strval($this->manifest->params['label'] ?? $this->manifest->name); }
	function getDescription() : string { return strval($this->manifest->params['description'] ?? ''); }
	function getIcon() : string        { return strval($this->manifest->params['icon'] ?? 'zap'); }
	function getSection() : string     { return strval($this->manifest->params['section'] ?? 'Other'); }
	function getTriggerId() : string   { return strval($this->manifest->params['trigger'] ?? ''); }
	function getSortOrder() : int      { return intval($this->manifest->params['sort'] ?? 0); }

	// Does this candidate need a config step before it can be applied?
	function hasWizard() : bool { return false; }

	// The code-driven CerbUI slide-in for the config step ('' = no wizard, apply immediately).
	function renderWizard() : string { return ''; }

	// Turn the wizard answers into the editor seed: ['extension_id'=>…, 'script'=>…, 'policy_kata'=>…].
	// `extension_id` is the automation TRIGGER this template targets (defaults to getTriggerId()).
	abstract function build(array $answers) : array;

	// Load a static starter from `assets/automations/templates/`. The file is in the WORKFLOW export format —
	// byte-for-byte what the Automation Builder's Export tab emits for a single automation — so a starter is
	// authored in the builder and pasted here rather than hand-assembled, and its script and policy stay in one
	// file instead of two that can drift apart.
	//
	// Only `script` and `policy_kata` are read. The rest of the record (name, description, extension_id) rides
	// along so a pasted export needs no editing; the TRIGGER is always the manifest's, because that is what the
	// picker groups and filters on, and a second source of truth for it could disagree.
	//
	// Returns empty strings if the file is missing or unparseable — a broken asset opens a blank editor rather
	// than blocking the picker.
	static protected function _loadAsset(string $filename) : array {
		$empty = ['script' => '', 'policy_kata' => ''];

		if(false === ($kata_string = @file_get_contents(APP_PATH . '/features/cerberusweb.core/assets/automations/templates/' . $filename)))
			return $empty;

		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		if(!is_array($tree = $kata->parse($kata_string, $error)))
			return $empty;

		// $wrap_raw keeps the `@raw` bodies literal, so a starter's `{{placeholders}}` reach the editor as text
		// rather than being evaluated on the way in.
		if(!is_array($workflow = $kata->formatTree($tree, null, $error, true)))
			return $empty;

		foreach(($workflow['records'] ?? []) as $record_key => $record) {
			if(!str_starts_with($record_key, 'automation/'))
				continue;

			return [
				'script' => strval($record['fields']['script'] ?? ''),
				'policy_kata' => strval($record['fields']['policy_kata'] ?? ''),
			];
		}

		return $empty;
	}

	// Every registered template, grouped by section, for the builder's picker:
	//   [{ section, templates:[{ id, label, description, icon, has_wizard }] }]
	static function getGrouped() : array {
		$by_section = [];

		foreach(self::getAll(true) as $template) { /* @var $template Extension_AutomationTemplate */
			$section = $template->getSection() ?: 'Other';
			$by_section[$section][] = [
				'id' => $template->id,
				'label' => $template->getLabel(),
				'description' => $template->getDescription(),
				'icon' => $template->getIcon(),
				'has_wizard' => $template->hasWizard(),
				'sort' => $template->getSortOrder(),
			];
		}

		$groups = [];
		foreach($by_section as $section => $templates) {
			usort($templates, fn($a, $b) => [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']]);
			$groups[] = ['section' => $section, 'templates' => array_values($templates)];
		}
		usort($groups, fn($a, $b) => strcmp($a['section'], $b['section']));

		return $groups;
	}
}
