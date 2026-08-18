<?php
class Toolbar_GlobalMenu extends Extension_Toolbar {
	const ID = 'cerb.toolbar.global.menu';
	
	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'component',
				'notes' => "Always `commandbar` here. The command bar's menu is this toolbar PLUS the `agent.pane` "
					. "toolbar's `commandbar` items, and this is what those gate on -- so a "
					. "`hidden@bool: {{ component != 'commandbar' }}` written on `agent.pane` reads the same way it "
					. "does on an editor pane. Items on THIS toolbar don't need it: they only ever appear here.",
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getInteractionInputsMeta() : array {
		return [
		];
	}
	
	function getInteractionOutputMeta(): array {
		return [
		];
	}
	
	function getInteractionAfterMeta() : array {
		return [
		];
	}
	
	public static function getInteractionsMenu() {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if(null == $active_worker)
			return [];
		
		$are_behaviors_enabled = DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy');
		
		$legacy_interactions = [];
		
		if(
			$are_behaviors_enabled
			&& class_exists('Event_GetInteractionsForWorker')
		) {
			$legacy_interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('global', [], $active_worker);
		}
		
		$toolbar_kata = '';
		
		if(null != ($toolbar = DAO_Toolbar::getByName('global.menu')))
			$katas[] = $toolbar->getKata();

		$toolbar_kata = Model_Toolbar::mergeKata($katas);

		if($are_behaviors_enabled && $legacy_interactions) {
			$legacy_kata = "\nmenu/legacy:\n  label: (Legacy Chat Bots)\n  icon: bot-message\n  items:\n";
			
			foreach ($legacy_interactions as $interaction) {
				$legacy_kata .= sprintf("    behavior/%s:\n      label: %s\n      id: %d\n      interaction: %s\n      image: %s\n      params:\n",
					uniqid(),
					$interaction['label'],
					$interaction['behavior_id'],
					$interaction['interaction'],
					$url_writer->write(sprintf('c=avatars&context=bot&context_id=%d', $interaction['bot_id'])) . '?v=0',
				);
				
				if ($interaction['params']) {
					foreach ($interaction['params'] as $k => $v) {
						$legacy_kata .= sprintf("        %s: %s\n",
							$k,
							$v
						);
					}
				}
			}
			
			$toolbar_kata .= $legacy_kata;
		}
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);

		$menu = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict);

		// Supply each item's `description` (the command-bar subtitle) from its automation's own description, when
		// the KATA didn't set one explicitly. `description` is a first-class field so non-interaction entries we
		// add later (e.g. "Compose email") can supply their own.
		$automation_names = [];
		self::_collectInteractionAutomationNames($menu, $automation_names);

		if($automation_names) {
			$descriptions = [];

			$interaction_triggers = [
				AutomationTrigger_InteractionWorker::ID,
				AutomationTrigger_InteractionWorkerAgent::ID,
			];

			foreach(DAO_Automation::getByUris(array_values($automation_names), $interaction_triggers) as $automation) {
				if($automation->description)
					$descriptions[$automation->name] = $automation->description;
			}

			if($descriptions)
				self::_applyInteractionDescriptions($menu, $descriptions);
		}

		// Lead with the worker's own resumable interactions (closed but still awaiting) so they can pick one up.
		$menu = self::_prependResumableInteractions($menu, $active_worker);

		return $menu;
	}

	// The worker's parked worker-popup continuations become `resume` rows keyed by continuation token. Named by the
	// automation's own `await:form: resume:` block, with the last prompt as a preview and how long it's been idle.
	private static function _prependResumableInteractions(array $menu, Model_Worker $active_worker) : array {
		// Shared with the agent pane's History (DAO_AutomationContinuation::getResumableRowsForScopes) so one
		// conversation never reads as two different things depending on where it's listed. The menu we're about
		// to prepend to IS the launcher list, so a resumed row can inherit the label and icon of the item that
		// started it -- for free, and still correct after the toolbar is edited.
		$identity = DAO_AutomationContinuation::launcherIdentityFromToolbarItems($menu);

		if(!($rows = DAO_AutomationContinuation::getResumableRows($active_worker->id, 25, $identity)))
			return $menu;

		$make_item = fn(array $row) : array => ['type' => 'resume'] + $row;

		$resume_items = [];

		// The most recent handful inline; everything older tucks into a submenu so the bar stays scannable.
		$inline = array_slice($rows, 0, 4, true);
		$overflow = array_slice($rows, 4, null, true);

		foreach($inline as $token => $row)
			$resume_items['resume/' . $token] = $make_item($row);

		if($overflow) {
			$overflow_items = [];

			foreach($overflow as $token => $row)
				$overflow_items['resume/' . $token] = $make_item($row);

			$resume_items['menu/resume-more'] = [
				'type' => 'menu',
				'label' => sprintf('%d more…', count($overflow)),
				'icon' => 'more',
				'items' => $overflow_items,
			];
		}

		$resume_items['divider/resume'] = ['type' => 'divider'];

		return $resume_items + $menu;
	}

	// Top-level parsed items are keyed by their bare sub-key and carry a `type`; nested `menu > items` keep their
	// full `type/key` KATA keys and have no `type`. Resolve the type from whichever is present.
	private static function _itemType(string $key, array $item) : string {
		return $item['type'] ?? explode('/', $key, 2)[0];
	}

	// The bare automation name for an interaction URI. Top-level URIs are already stripped by the parser; nested
	// ones keep the `cerb:automation:` prefix — normalize both the way the parser does.
	private static function _automationNameFromUri(string $uri) : string {
		if(DevblocksPlatform::strStartsWith($uri, 'cerb:')) {
			if($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri))
				return $uri_parts['context_id'] ?? '';
			return '';
		}
		return $uri;
	}

	private static function _collectInteractionAutomationNames(array $items, array &$names) : void {
		foreach($items as $key => $item) {
			if(!is_array($item))
				continue;

			$type = self::_itemType($key, $item);

			if('interaction' == $type) {
				if(!empty($item['uri']) && ($name = self::_automationNameFromUri($item['uri'])))
					$names[$name] = $name;
			} else if('menu' == $type && !empty($item['items']) && is_array($item['items'])) {
				self::_collectInteractionAutomationNames($item['items'], $names);
			}
		}
	}

	private static function _applyInteractionDescriptions(array &$items, array $descriptions) : void {
		foreach($items as $key => &$item) {
			if(!is_array($item))
				continue;

			$type = self::_itemType($key, $item);

			if('interaction' == $type) {
				if(empty($item['description']) && !empty($item['uri'])) {
					$name = self::_automationNameFromUri($item['uri']);
					if($name && !empty($descriptions[$name]))
						$item['description'] = $descriptions[$name];
				}
			} else if('menu' == $type && !empty($item['items']) && is_array($item['items'])) {
				self::_applyInteractionDescriptions($item['items'], $descriptions);
			}
		}

		unset($item);
	}
}