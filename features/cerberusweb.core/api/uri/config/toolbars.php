<?php /** @noinspection PhpUnused */
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupDevelopersToolbars extends Extension_PageSection {
	private ?Extension_DevblocksContext $_ctx_automation = null;
	private ?Extension_DevblocksContext $_ctx_behavior = null;
	private ?Extension_DevblocksContext $_ctx_section = null;
	private ?Extension_DevblocksContext $_ctx_workflow = null;

	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'toolbars');

		$this->_ctx_automation = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_AUTOMATION, true);
		$this->_ctx_behavior = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BEHAVIOR, true) ?: null;
		$this->_ctx_section = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TOOLBAR_SECTION, true);
		$this->_ctx_workflow = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKFLOW, true);

		// Every seeded toolbar (global.menu, mail.read, record.profile, …), alphabetized by name
		$toolbars = DAO_Toolbar::getAll();
		uasort($toolbars, fn($a, $b) => strcasecmp($a->name, $b->name));

		$toolbar_views = [];
		foreach($toolbars as $toolbar)
			$toolbar_views[] = $this->_buildToolbar($toolbar);

		$tpl->assign('toolbars', $toolbar_views);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/toolbars/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}

	// A single toolbar row: its sections (priority-ordered) and the inert menu tree each section contributes.
	private function _buildToolbar(Model_Toolbar $toolbar) : array {
		$section_views = [];
		$item_total = 0;

		$sections = DAO_ToolbarSection::getByToolbar($toolbar->name, true);
		uasort($sections, fn($a, $b) => $a->priority <=> $b->priority);

		foreach($sections as $section) {
			$items = $this->_buildSectionItems($section);
			$item_total += $this->_countItems($items);

			// Sections managed by a workflow are version-controlled; hand-edits get overwritten on re-import.
			$workflow_name = '';
			$workflow_url = '';

			$workflow_id = DAO_WorkflowResource::getWorkflowIdByRecord(CerberusContexts::CONTEXT_TOOLBAR_SECTION, $section->id);

			if($workflow_id && ($workflow = DAO_Workflow::get($workflow_id))) {
				$workflow_name = $workflow->name;
				$workflow_url = $this->_ctx_workflow ? $this->_ctx_workflow->profileGetUrl($workflow_id) : '';
			}

			$section_views[] = [
				'id' => $section->id,
				'name' => $section->name,
				'priority' => $section->priority,
				'is_disabled' => $section->is_disabled,
				'url' => $this->_ctx_section ? $this->_ctx_section->profileGetUrl($section->id) : '',
				'workflow_id' => $workflow_id,
				'workflow_name' => $workflow_name,
				'workflow_url' => $workflow_url,
				'items' => $items,
				'missing_items' => $this->_collectMissing($items),
			];
		}

		// The toolbar's glyph is declared as an `icon` param on its cerb.toolbar extension; default to 'toolbox'.
		$manifest = $toolbar->getExtension(false);
		$icon = ($manifest && !empty($manifest->params['icon'])) ? $manifest->params['icon'] : 'toolbox';

		return [
			'name' => $toolbar->name,
			'description' => $toolbar->description,
			'icon' => $icon,
			'slug' => 'toolbar-' . DevblocksPlatform::strToPermalink($toolbar->name),
			'section_count' => count($section_views),
			'item_count' => $item_total,
			'sections' => $section_views,
		];
	}

	// Parse a section's raw `toolbar_kata` into an inert menu tree. Unlike the runtime renderer
	// (Model_ToolbarSection::getKata / DevblocksUiToolbar::parse, which evaluate Twig placeholders and
	// enforce caller policy via formatTree), we parse the KATA directly and keep every item so the overview
	// shows the full configuration. `hidden` is ignored — it's resolved dynamically at runtime.
	private function _buildSectionItems(Model_ToolbarSection $section) : array {
		if(!trim($section->toolbar_kata ?? ''))
			return [];

		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		if(false === ($tree = $kata->parse($section->toolbar_kata, $error, true)))
			return [];

		if(!is_array($tree))
			return [];

		return $this->_walkItems($tree);
	}

	// Recursively map a parsed toolbar tree (keyed by `type/slug`) into render-ready nodes. Menu children
	// live under the node's `items:` map. Interaction items resolve to a linked automation profile.
	private function _walkItems(array $tree) : array {
		$out = [];

		foreach($tree as $key => $node) {
			[$type, $slug] = array_pad(explode('/', $key, 2), 2, null);

			if(!in_array($type, ['interaction', 'menu', 'divider', 'behavior'], true))
				continue;

			if('divider' === $type) {
				$out[] = ['type' => 'divider', 'label' => '', 'icon' => '', 'slug' => $slug, 'url' => '', 'id' => 0, 'missing' => false, 'children' => []];
				continue;
			}

			if(!is_array($node))
				$node = [];

			$props = $this->_nodeProps($node);
			$children = [];

			if('menu' === $type && is_array($node['items'] ?? null))
				$children = $this->_walkItems($node['items']);

			// Resolve a clickable record for the item: an `interaction/` binds an automation by default, but a
			// `cerb:behavior:` uri (consistent with automation-event bindings) binds a legacy behavior instead;
			// a `behavior/` item binds a legacy behavior by id or uri. Unresolved bindings are flagged "missing".
			$context = '';
			$id = 0;
			$url = '';
			$missing = false;

			if('interaction' === $type) {
				if($model = $this->_resolveAutomation($props['uri'])) {
					$context = CerberusContexts::CONTEXT_AUTOMATION;
					$id = $model->id;
					$url = $this->_ctx_automation ? $this->_ctx_automation->profileGetUrl($id) : '';
				} else if($behavior = $this->_resolveBehavior($props['uri'])) {
					$context = CerberusContexts::CONTEXT_BEHAVIOR;
					$id = $behavior->id;
					$url = $this->_ctx_behavior ? $this->_ctx_behavior->profileGetUrl($id) : '';
				} else {
					$missing = true;
				}
			} else if('behavior' === $type) {
				if($behavior = $this->_resolveBehavior($props['id'] ?: $props['uri'])) {
					$context = CerberusContexts::CONTEXT_BEHAVIOR;
					$id = $behavior->id;
					$url = $this->_ctx_behavior ? $this->_ctx_behavior->profileGetUrl($id) : '';
				} else {
					$missing = true;
				}
			}

			$out[] = [
				'type' => $type,
				'label' => $props['label'] ?: $slug,
				'icon' => $props['icon'],
				'tooltip' => $props['tooltip'],
				'keyboard' => $props['keyboard'],
				'uri' => $props['uri'],
				'slug' => $slug,
				'context' => $context,
				'id' => $id,
				'url' => $url,
				'missing' => $missing,
				'children' => $children,
			];
		}

		return $out;
	}

	// Pull scalar properties off a node, tolerating KATA annotations on keys (e.g. `hidden@bool`, `icon@text`).
	private function _nodeProps(array $node) : array {
		$props = ['label' => '', 'icon' => '', 'tooltip' => '', 'uri' => '', 'keyboard' => '', 'id' => ''];

		foreach($node as $k => $v) {
			$base = explode('@', $k, 2)[0];

			if(array_key_exists($base, $props) && is_scalar($v))
				$props[$base] = (string) $v;
		}

		return $props;
	}

	// Count leaf items (interaction/behavior) across the inert tree, for the per-toolbar summary.
	private function _countItems(array $items) : int {
		$count = 0;

		foreach($items as $item) {
			if(in_array($item['type'], ['interaction', 'behavior'], true))
				$count++;

			if(!empty($item['children']))
				$count += $this->_countItems($item['children']);
		}

		return $count;
	}

	// Flatten the inert tree to interaction items whose `uri:` didn't resolve to an automation, for the
	// per-section "unresolved" diagnostic. Each entry is {label, uri}.
	private function _collectMissing(array $items) : array {
		$out = [];

		foreach($items as $item) {
			if(!empty($item['missing']))
				$out[] = ['label' => $item['label'], 'uri' => $item['uri'] ?? ''];

			if(!empty($item['children']))
				$out = array_merge($out, $this->_collectMissing($item['children']));
		}

		return $out;
	}

	// Resolve a toolbar interaction `uri:` to a Model_Automation: `cerb:automation:<name>` or a bare name →
	// looked up by name across any interaction trigger; a numeric uri → direct id lookup. Returns null when
	// unresolved (rendered as a plain "missing" label, never an error).
	private function _resolveAutomation($uri) : ?Model_Automation {
		if(!$uri)
			return null;

		if(is_numeric($uri))
			return DAO_Automation::get((int) $uri);

		return DAO_Automation::getByUri($uri);
	}

	// Resolve a legacy behavior binding to a Model_TriggerEvent by numeric id or by behavior uri. Returns null
	// when the legacy behaviors plugin is disabled or the binding is unresolved (rendered as "missing").
	private function _resolveBehavior($id_or_uri) {
		if(!$id_or_uri || !class_exists('DAO_TriggerEvent'))
			return null;

		// Normalize a `cerb:behavior:<id-or-uri>` reference (interaction items keep the prefix here) down to its
		// bare token first — it may be a numeric id or a behavior uri — then resolve by the appropriate lookup.
		if(DevblocksPlatform::strStartsWith($id_or_uri, 'cerb:')) {
			if($uri_parts = DevblocksPlatform::services()->ui()->parseURI($id_or_uri))
				$id_or_uri = $uri_parts['context_id'] ?? $id_or_uri;
		}

		if(is_numeric($id_or_uri))
			return DAO_TriggerEvent::get((int) $id_or_uri);

		return DAO_TriggerEvent::getByUri($id_or_uri);
	}
}
