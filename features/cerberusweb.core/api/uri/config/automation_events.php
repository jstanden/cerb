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

class PageSection_SetupDevelopersAutomationEvents extends Extension_PageSection {
	private ?Extension_DevblocksContext $_ctx_automation = null;
	private ?Extension_DevblocksContext $_ctx_listener = null;
	private ?Extension_DevblocksContext $_ctx_behavior = null;
	private ?Extension_DevblocksContext $_ctx_event = null;

	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$visit->set(ChConfigurationPage::ID, 'automation_events');

		$this->_ctx_automation = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_AUTOMATION, true) ?: null;
		$this->_ctx_listener = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, true) ?: null;
		$this->_ctx_behavior = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BEHAVIOR, true) ?: null;
		$this->_ctx_event = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_AUTOMATION_EVENT, true) ?: null;

		// Each flow (execution-ordered chain) becomes one read-only node graph.
		$flows = [];
		foreach($this->_getFlows() as $flow) {
			$graph = $this->_buildFlowGraph($flow);

			$flows[] = [
				'id' => $flow['id'],
				'icon' => $flow['icon'] ?? 'zap',
				'label' => $flow['label'],
				'description' => $flow['description'] ?? '',
				'slug' => 'flow-' . DevblocksPlatform::strToPermalink($flow['id']),
				'graph_json' => DevblocksPlatform::strEscapeHtml(json_encode(['nodes' => $graph['nodes'], 'edges' => $graph['edges']])),
			];
		}

		$tpl->assign('flows', $flows);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/automation_events/index.tpl');
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}

	// ── Flow topology (manual execution-order ordering) ──────────────────
	// Each step is typed: 'event' (automation event, optional priority `band` + display `label`), 'legacy' (a
	// cerb.behaviors.legacy event_point), 'trigger' (a direct-bind automation trigger, no listener layer), or
	// 'structural' (a non-event pipeline step rendered as a labeled marker). Ordering verified against Parser.php /
	// Mail.php / profiles.php. Events deliberately repeat across flows so each chain is complete on its own.
	private function _getFlows() : array {
		$trig = fn(string $name) => ['type' => 'trigger', 'extension_id' => 'cerb.trigger.' . $name, 'name' => $name];

		$flows = [
			[
				'id' => 'inbound-mail',
				'icon' => 'inbox',
				'label' => 'On new inbound mail',
				'description' => 'A message arrives and is parsed into a ticket (api/app/Parser.php).',
				'steps' => [
					['type' => 'event', 'event' => 'mail.filter'],
					['type' => 'structural', 'label' => 'Spam scoring', 'desc' => 'Content spam probability is calculated before routing.'],
					['type' => 'event', 'event' => 'mail.route'],
					['type' => 'structural', 'label' => 'Routing Rules (KATA)', 'desc' => 'Mail routing rules run if no mail.route automation returned a destination.'],
					['type' => 'structural', 'label' => 'Routing Rules (Legacy)', 'desc' => 'Legacy group routing rules run if no KATA rule matched.'],
					['type' => 'structural', 'label' => 'Group Routing (KATA)', 'desc' => "Bucket-level routing for the destination group's default bucket."],
					['type' => 'event', 'event' => 'mail.received', 'band' => [0, 127], 'label' => 'mail.received (priority < 128)'],
					['type' => 'legacy', 'event_point' => 'event.mail.received.app', 'label' => 'Filter new incoming message'],
					['type' => 'legacy', 'event_point' => 'event.mail.received', 'label' => 'New message added to ticket'],
					['type' => 'legacy', 'event_point' => 'event.mail.received.group', 'label' => 'New message in group'],
					['type' => 'legacy', 'event_point' => 'event.mail.received.watcher', 'label' => 'New message (watcher)'],
					['type' => 'event', 'event' => 'mail.received', 'band' => [128, 255], 'label' => 'mail.received (priority 128+)'],
				],
			],
			[
				'id' => 'outbound-mail',
				'icon' => 'send',
				'label' => 'On worker reply / compose sent',
				'description' => 'A worker reply or compose is delivered (api/app/Mail.php).',
				'steps' => [
					['type' => 'event', 'event' => 'mail.send'],
					['type' => 'event', 'event' => 'mail.sent', 'band' => [0, 127], 'label' => 'mail.sent (priority < 128)'],
					['type' => 'legacy', 'event_point' => 'event.mail.after.sent', 'label' => 'After sending worker reply'],
					['type' => 'legacy', 'event_point' => 'event.mail.after.sent.group', 'label' => 'After sending in group'],
					['type' => 'legacy', 'event_point' => 'event.mail.received', 'label' => 'Sent copy received by system'],
					['type' => 'legacy', 'event_point' => 'event.mail.received.group', 'label' => 'Sent copy in group'],
					['type' => 'event', 'event' => 'mail.sent', 'band' => [128, 255], 'label' => 'mail.sent (priority 128+)'],
				],
			],
			[
				'id' => 'compose-validate',
				'icon' => 'edit',
				'independent' => true,
				'label' => 'Composing & validating mail (UI)',
				'description' => 'Draft load and client-side validation — these run in the worker UI, not in the send pipeline.',
				'steps' => [
					['type' => 'event', 'event' => 'mail.draft'],
					['type' => 'event', 'event' => 'mail.draft.validate'],
					['type' => 'event', 'event' => 'mail.reply.validate'],
				],
			],
			[
				'id' => 'ticket-changes',
				'icon' => 'ticket',
				'label' => 'Ticket changes',
				'description' => 'Group behaviors and automations when a ticket is moved, assigned, or closed.',
				'lanes' => [
					['label' => 'On ticket moved', 'steps' => [
						['type' => 'legacy', 'event_point' => 'event.mail.moved.group', 'label' => 'Ticket moved in group'],
						['type' => 'event', 'event' => 'mail.moved'],
					]],
					['label' => 'On ticket assigned', 'steps' => [
						['type' => 'legacy', 'event_point' => 'event.mail.assigned.group', 'label' => 'Ticket assigned in group'],
					]],
					['label' => 'On ticket closed', 'steps' => [
						['type' => 'legacy', 'event_point' => 'event.mail.closed.group', 'label' => 'Ticket closed in group'],
					]],
				],
			],
			[
				'id' => 'record-viewed',
				'icon' => 'collection',
				'label' => 'On record viewed',
				'description' => 'A worker opens a record profile or card (api/uri/profiles.php).',
				'steps' => [
					['type' => 'event', 'event' => 'record.viewed', 'band' => [0, 127], 'label' => 'record.viewed (priority < 128)'],
					['type' => 'legacy', 'event_point' => 'event.ticket.viewed.worker', 'label' => 'Ticket opened by worker'],
					['type' => 'event', 'event' => 'record.viewed', 'band' => [128, 255], 'label' => 'record.viewed (priority 128+)'],
				],
			],
			[
				'id' => 'record-changed',
				'icon' => 'refresh',
				'label' => 'On record changed',
				'description' => 'A record field value changes.',
				'steps' => [
					['type' => 'event', 'event' => 'record.changed'],
					['type' => 'legacy', 'event_point' => 'event.record.changed', 'label' => 'Record changed'],
				],
			],
			[
				'id' => 'record-merge',
				'icon' => 'merge',
				'label' => 'On record merge',
				'description' => 'Records are merged (before, then after).',
				'steps' => [
					['type' => 'event', 'event' => 'record.merge', 'label' => 'record.merge (before)'],
					['type' => 'event', 'event' => 'record.merged', 'label' => 'record.merged (after)'],
				],
			],
			[
				'id' => 'worker-auth',
				'icon' => 'key',
				'independent' => true,
				'label' => 'On worker authentication',
				'description' => 'A worker authenticates a new session (or fails to).',
				'steps' => [
					['type' => 'event', 'event' => 'worker.authenticated'],
					['type' => 'event', 'event' => 'worker.authenticate.failed'],
				],
			],
			[
				'id' => 'worker-interactions',
				'icon' => 'comments',
				'independent' => true,
				'label' => 'Worker interactions',
				'description' => 'Chat/interaction entry points for workers.',
				'steps' => [
					$trig('interaction.worker'),
					$trig('interaction.worker.explore'),
					['type' => 'legacy', 'event_point' => 'event.interactions.get.worker', 'label' => 'Get chat interactions for worker'],
					['type' => 'legacy', 'event_point' => 'event.interaction.chat.worker', 'label' => 'Conversation with worker'],
					['type' => 'legacy', 'event_point' => 'event.message.chat.worker', 'label' => 'New message in chat'],
					['type' => 'legacy', 'event_point' => 'event.form.interaction.worker', 'label' => 'Form interaction'],
				],
			],
			[
				'id' => 'website-interactions',
				'icon' => 'globe',
				'label' => 'Website interactions',
				'description' => 'Interaction entry points for the website portal.',
				'steps' => [
					$trig('interaction.website'),
				],
			],
			[
				'id' => 'reminders',
				'icon' => 'bell',
				'label' => 'Reminders',
				'description' => 'A reminder fires.',
				'steps' => [
					['type' => 'event', 'event' => 'reminder.remind'],
					['type' => 'legacy', 'event_point' => 'event.macro.reminder', 'label' => 'Custom behavior on reminder'],
				],
			],
			[
				'id' => 'scheduled',
				'icon' => 'clock',
				'independent' => true,
				'label' => 'Scheduled / recurrent',
				'description' => 'Time-based automations and recurrent legacy behaviors.',
				'steps' => [
					$trig('automation.timer'),
					['type' => 'legacy', 'event_point' => 'event.behavior.recurrent', 'label' => 'Recurrent behavior'],
				],
			],
			[
				'id' => 'webhooks',
				'icon' => 'plug',
				'independent' => true,
				'label' => 'Webhooks',
				'description' => 'Inbound HTTP requests handled by automations or legacy behaviors.',
				'steps' => [
					$trig('webhook.respond'),
					['type' => 'legacy', 'event_point' => 'event.ajax.request', 'label' => 'Respond to Ajax HTTP request'],
					['type' => 'legacy', 'event_point' => 'event.api.custom_request', 'label' => 'Custom API request'],
				],
			],
			[
				'id' => 'data-ui',
				'icon' => 'database',
				'independent' => true,
				'label' => 'On trigger (Data & UI)',
				'description' => 'Direct-bind triggers — automations attach straight to these (no listener layer).',
				'steps' => [
					$trig('data.query'),
					$trig('ui.widget'),
					$trig('ui.chart.data'),
					$trig('ui.sheet.data'),
					$trig('resource.get'),
					$trig('scripting.function'),
					$trig('automation.function'),
					$trig('behavior.action'),
					$trig('llm.tool'),
					$trig('interaction.internal'),
					$trig('queue.consumer'),
					$trig('map.clicked'),
				],
			],
			[
				'id' => 'bot-macros',
				'icon' => 'bot',
				'independent' => true,
				'label' => 'Bot macros (on-demand)',
				'description' => 'Legacy per-record-type macro behaviors run on demand.',
				'steps' => array_map(
					fn($p) => ['type' => 'legacy', 'event_point' => 'event.macro.' . $p, 'label' => ucfirst($p)],
					['ticket', 'address', 'worker', 'contact', 'org', 'message', 'group', 'bot', 'calendar', 'calendar_event', 'notification', 'task']
				),
			],
			[
				'id' => 'project-board',
				'icon' => 'clipboard',
				'independent' => true,
				'label' => 'Project board',
				'description' => 'Project board card automations.',
				'steps' => [
					$trig('projectBoard.cardAction'),
					$trig('projectBoard.renderCard'),
				],
			],
			[
				'id' => 'dashboard-widgets',
				'icon' => 'dashboard',
				'independent' => true,
				'label' => 'Dashboard widgets (legacy)',
				'description' => 'Legacy dashboard/worklist render behaviors.',
				'steps' => [
					['type' => 'legacy', 'event_point' => 'event.dashboard.widget.render', 'label' => 'Render dashboard widget'],
					['type' => 'legacy', 'event_point' => 'event.dashboard.widget.get_metric', 'label' => 'Get widget metric'],
					['type' => 'legacy', 'event_point' => 'event.ui.worklist.render.worker', 'label' => 'Render worklist'],
				],
			],
			[
				'id' => 'worker-ui',
				'icon' => 'form',
				'independent' => true,
				'label' => 'Worker UI events (legacy)',
				'description' => 'Legacy worker UI hooks.',
				'steps' => [
					['type' => 'legacy', 'event_point' => 'event.mail.compose.pre.ui.worker', 'label' => 'Before composing new message'],
					['type' => 'legacy', 'event_point' => 'event.mail.reply.pre.ui.worker', 'label' => 'Before composing reply'],
					['type' => 'legacy', 'event_point' => 'event.notification.received.worker', 'label' => 'Notification received'],
					['type' => 'legacy', 'event_point' => 'event.comment.created.worker', 'label' => 'Comment created'],
					['type' => 'legacy', 'event_point' => 'event.comment.ticket.group', 'label' => 'New comment in group'],
					['type' => 'legacy', 'event_point' => 'event.task.created.worker', 'label' => 'Task created'],
				],
			],
		];

		// Legacy event points only fire when the legacy behaviors plugin is enabled. With it off, drop those
		// steps (and any lane/flow left empty) so the page shows only what can actually run.
		if(!DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy'))
			$flows = $this->_pruneLegacySteps($flows);

		return $flows;
	}

	// Remove 'legacy' steps from every flow, then drop lanes/flows that end up empty. Only 'steps' /
	// 'lanes[].steps' carry step types, so one filter covers the linear, 'independent', and 'lanes' shapes.
	private function _pruneLegacySteps(array $flows) : array {
		$keep = fn(array $step) => ($step['type'] ?? '') !== 'legacy';
		$out = [];

		foreach($flows as $flow) {
			if(!empty($flow['lanes'])) {
				$lanes = [];
				foreach($flow['lanes'] as $lane) {
					$steps = $this->_collapseBands(array_values(array_filter($lane['steps'], $keep)));
					if($steps) {
						$lane['steps'] = $steps;
						$lanes[] = $lane;
					}
				}
				if(!$lanes)
					continue;
				$flow['lanes'] = $lanes;
			} else {
				$steps = $this->_collapseBands(array_values(array_filter($flow['steps'], $keep)));
				if(!$steps)
					continue;
				$flow['steps'] = $steps;
			}
			$out[] = $flow;
		}

		return $out;
	}

	// An event split into priority bands (e.g. mail.received < 128 / 128+) only reads as two nodes because
	// legacy behaviors interleave between them. With legacy gone there's nothing in the gap, so collapse each
	// event's bands into a single unbanded node (its first occurrence; the duplicate is dropped). The unbanded
	// node sheds its band 'label' so _buildStep falls back to the plain event name and shows all listeners.
	private function _collapseBands(array $steps) : array {
		$out = [];
		$seen = [];

		foreach($steps as $step) {
			if(($step['type'] ?? '') === 'event' && !empty($step['band'])) {
				if(isset($seen[$step['event']]))
					continue;
				$seen[$step['event']] = true;
				unset($step['band'], $step['label']);
			}
			$out[] = $step;
		}

		return $out;
	}

	// ── Build one flow's read-only graph ─────────────────────────────────
	// Tier 0 = the spine (start + ordered steps); tier 1 = listeners / behaviors / direct-bind automations;
	// tier 2 = a listener's automations. Edges are spine (tier→same tier) and fan-out (tier→tier+1).
	// A 'linear' flow is one start → chained steps. An 'independent' flow gives each step its own green flag
	// start (a separate lane, rendered as a side-by-side column).
	private function _buildFlowGraph(array $flow) : array {
		$nodes = [];
		$edges = [];
		$seq = 0;
		$item_count = 0;

		if(!empty($flow['lanes'])) {
			// Each lane is its own start flag → a linear chain of steps (rendered as a side-by-side column).
			foreach($flow['lanes'] as $lane) {
				$flag_id = $flow['id'] . '-f' . (++$seq);
				$nodes[] = ['id' => $flag_id, 'type' => 'start', 'tier' => 0, 'label' => $lane['label']];
				$prev = $flag_id;

				foreach($lane['steps'] as $step) {
					$built = $this->_buildStep($flow['id'], $seq, $step, $item_count);
					$nodes = array_merge($nodes, $built['nodes']);
					$edges = array_merge($edges, $built['edges']);
					$edges[] = ['source' => $prev, 'target' => $built['id']];
					$prev = $built['id'];
				}
			}
		} else if(!empty($flow['independent'])) {
			foreach($flow['steps'] as $step) {
				$flag_id = $flow['id'] . '-f' . (++$seq);
				$nodes[] = ['id' => $flag_id, 'type' => 'start', 'tier' => 0, 'label' => 'On ' . $this->_stepDisplayName($step)];

				$built = $this->_buildStep($flow['id'], $seq, $step, $item_count);
				$nodes = array_merge($nodes, $built['nodes']);
				$edges = array_merge($edges, $built['edges']);
				$edges[] = ['source' => $flag_id, 'target' => $built['id']];
			}
		} else {
			$start_id = $flow['id'] . '-start';
			$nodes[] = ['id' => $start_id, 'type' => 'start', 'tier' => 0, 'label' => $flow['label']];
			$prev = $start_id;

			foreach($flow['steps'] as $step) {
				$built = $this->_buildStep($flow['id'], $seq, $step, $item_count);
				$nodes = array_merge($nodes, $built['nodes']);
				$edges = array_merge($edges, $built['edges']);
				$edges[] = ['source' => $prev, 'target' => $built['id']];
				$prev = $built['id'];
			}
		}

		return ['nodes' => $nodes, 'edges' => $edges, 'item_count' => $item_count];
	}

	// A step's display name (for the per-lane flag label).
	private function _stepDisplayName(array $step) : string {
		switch($step['type']) {
			case 'event':   return $step['label'] ?? $step['event'];
			case 'trigger': return $step['name'] ?? $step['extension_id'];
			case 'legacy':  return $step['label'] ?? $step['event_point'];
			default:        return $step['label'] ?? 'Step';
		}
	}

	// Build one step's subgraph (the step node + any listener canvas nodes / contained children, and the internal
	// event→listener / listener-chain edges). The caller connects the spine/flag edge to the returned `id`.
	private function _buildStep(string $flow_id, int &$seq, array $step, int &$item_count) : array {
		$nodes = [];
		$edges = [];
		$step_id = $flow_id . '-n' . (++$seq);
		$type = $step['type'];

		if('event' === $type) {
			$event = DAO_AutomationEvent::getByName($step['event']);

			$listeners = [];
			if($event) {
				$listeners = DAO_AutomationEventListener::getByEvent($event->name, true);
				uasort($listeners, fn($a, $b) => $a->priority <=> $b->priority);

				if(!empty($step['band']) && is_array($step['band'])) {
					[$min, $max] = $step['band'];
					$listeners = array_filter($listeners, fn($l) => $l->priority >= $min && $l->priority <= $max);
				}
			}

			$event_node = ['id' => $step_id, 'type' => 'event', 'tier' => 0, 'label' => $step['label'] ?? $step['event']];
			$data = $event ? $this->_recordData($this->_ctx_event, CerberusContexts::CONTEXT_AUTOMATION_EVENT, $event->id) : [];
			if($event && $event->description)
				$data['description'] = $event->description;
			if($data)
				$event_node['data'] = $data;
			// One inner branch outlet feeds the first listener; listeners chain to the right (curved edges).
			if($listeners)
				$event_node['branches'] = [['name' => 'children', 'label' => '']];
			$nodes[] = $event_node;

			// Listeners are canvas nodes (tier 1) chained vertically; each CONTAINS its automations.
			$prev_listener = null;
			foreach($listeners as $listener) {
				$lid = $flow_id . '-n' . (++$seq);
				$label = $listener->name . ($listener->is_disabled ? ' (disabled)' : '');

				$autos = [];
				foreach($this->_buildListenerAutomations($listener, $event) as $auto) {
					$autos[] = $this->_automationChild($flow_id . '-n' . (++$seq), $auto['name'], $auto['id'], $auto['missing']);
					$item_count++;
				}

				$listener_node = [
					'id' => $lid, 'type' => 'listener', 'tier' => 1, 'label' => $label,
					'data' => $this->_recordData($this->_ctx_listener, CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, $listener->id),
				];
				if($autos)
					$listener_node['children'] = $autos;
				// The first listener is fed from the event's side branch — pin its inlet to the top-left corner.
				if($prev_listener === null)
					$listener_node['inletCorner'] = true;
				$nodes[] = $listener_node;

				// First listener hangs off the event's branch; the rest chain. Curved with a tangent arrow.
				if($prev_listener === null)
					$edges[] = ['source' => $step_id, 'target' => $lid, 'sourceHandle' => 'branch:children', 'curve' => true];
				else
					$edges[] = ['source' => $prev_listener, 'target' => $lid, 'curve' => true];
				$prev_listener = $lid;
			}

		} else if('legacy' === $type) {
			$children = [];
			foreach($this->_buildLegacyBehaviors($step['event_point']) as $b) {
				$label = $b['title'] . ($b['is_disabled'] ? ' (disabled)' : '');
				$children[] = [
					'id' => $flow_id . '-n' . (++$seq), 'type' => 'behavior', 'label' => $label,
					'data' => $this->_recordData($this->_ctx_behavior, CerberusContexts::CONTEXT_BEHAVIOR, $b['id']),
				];
				$item_count++;
			}

			$legacy_node = ['id' => $step_id, 'type' => 'legacy_event', 'tier' => 0, 'label' => $step['label'] ?? $step['event_point']];
			if($children)
				$legacy_node['children'] = $children;
			$nodes[] = $legacy_node;

		} else if('trigger' === $type) {
			$automations = DAO_Automation::getByTrigger($step['extension_id']);
			if(!is_array($automations))
				$automations = [];
			uasort($automations, fn($a, $b) => strcasecmp($a->name, $b->name));

			$children = [];
			foreach($automations as $automation) {
				$children[] = $this->_automationChild($flow_id . '-n' . (++$seq), $automation->name, $automation->id, false);
				$item_count++;
			}

			$trigger_node = ['id' => $step_id, 'type' => 'trigger', 'tier' => 0, 'label' => $step['name'] ?? $step['extension_id']];
			if($children)
				$trigger_node['children'] = $children;
			$nodes[] = $trigger_node;

		} else { // structural
			$nodes[] = [
				'id' => $step_id, 'type' => 'structural', 'tier' => 0,
				'label' => $step['label'] ?? 'Step',
				'data' => ['description' => $step['desc'] ?? ''],
			];
		}

		return ['id' => $step_id, 'nodes' => $nodes, 'edges' => $edges];
	}

	// A contained automation child node (lives in a listener's / trigger's container socket). Unresolved bindings
	// render without a peek link and carry a `missing` flag.
	private function _automationChild(string $id, string $label, $automation_id, bool $missing) : array {
		$node = ['id' => $id, 'type' => 'automation', 'label' => $label];
		if($missing || !$automation_id) {
			$node['data'] = ['missing' => true];
		} else {
			$node['data'] = $this->_recordData($this->_ctx_automation, CerberusContexts::CONTEXT_AUTOMATION, $automation_id);
		}
		return $node;
	}

	// Node `data` for a clickable record: a context + id (drives the peek) plus a profile url fallback.
	private function _recordData(?Extension_DevblocksContext $ctx, string $context, $context_id) : array {
		return [
			'context' => $context,
			'contextId' => (string) $context_id,
			'url' => $ctx ? $ctx->profileGetUrl($context_id) : '',
		];
	}

	// Legacy bot behaviors bound to an event_point (plugin cerb.behaviors.legacy), priority-ordered. Empty when
	// the legacy plugin is disabled — the legacy node still renders as a marker.
	private function _buildLegacyBehaviors(string $event_point) : array {
		if(!DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy') || !class_exists('DAO_TriggerEvent'))
			return [];

		$behaviors = DAO_TriggerEvent::getByEvent($event_point, true);

		if(!is_array($behaviors))
			return [];

		uasort($behaviors, fn($a, $b) => $a->priority <=> $b->priority);

		$out = [];
		foreach($behaviors as $behavior) {
			$out[] = [
				'id' => $behavior->id,
				'title' => $behavior->title,
				'is_disabled' => $behavior->is_disabled,
			];
		}
		return $out;
	}

	// Parse a listener's `event_kata` into its bound automations. Unlike the runtime dispatcher
	// (DevblocksUiEventHandler::parse, which drops disabled handlers), we keep every binding — bindings resolve
	// `disabled` dynamically at runtime, so the overview treats them all as present.
	private function _buildListenerAutomations(Model_AutomationEventListener $listener, Model_AutomationEvent $event) : array {
		$out = [];

		if(!trim($listener->event_kata ?? ''))
			return $out;

		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		if(false === ($tree = $kata->parse($listener->event_kata, $error, true)))
			return $out;

		if(!is_array($tree))
			return $out;

		foreach($tree as $key => $data) {
			[$type, $slug] = array_pad(explode('/', $key, 2), 2, null);

			if(!in_array($type, ['automation', 'behavior'], true) || !is_array($data))
				continue;

			$uri = $data['uri'] ?? '';
			$model = ('automation' === $type) ? $this->_resolveAutomation($uri, $event->extension_id) : null;

			$out[] = [
				'type' => $type,
				'id' => $model ? $model->id : 0,
				'name' => $model ? $model->name : ($uri ?: $slug),
				'missing' => !$model,
			];
		}

		return $out;
	}

	// Resolve an event_kata `uri:` to a Model_Automation: `cerb:automation:<name>` or bare name → name+trigger;
	// a numeric uri → direct id lookup. Returns null when unresolved (rendered as a "missing" node).
	private function _resolveAutomation($uri, $extension_id) : ?Model_Automation {
		if(!$uri)
			return null;

		if(is_numeric($uri))
			return DAO_Automation::get((int) $uri);

		$name = $uri;

		if(DevblocksPlatform::strStartsWith($uri, 'cerb:')) {
			if(($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri)))
				$name = $uri_parts['context_id'] ?? $uri;
		}

		if(is_numeric($name))
			return DAO_Automation::get((int) $name);

		return DAO_Automation::getByNameAndTrigger($name, [$extension_id]);
	}
}
