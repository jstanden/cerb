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

class PageSection_SetupDevelopersLlmAgentTranscripts extends Extension_PageSection {
	function render() {
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // llm_agent_transcripts
		$transcript_id = array_shift($stack); // a1b2c3d4-a1b2-c3d4-e5f6-a1b2c3d4e5f6
		
		$visit->set(ChConfigurationPage::ID, 'llm_agent_transcripts');
		
		$limit = 100;
		// Default to active (unarchived) transcripts only — a small, auditable list, not everything.
		$transcripts = DAO_LlmAgentSession::search($limit, false);
		
		$tpl->assign('limit', $limit);
		$tpl->assign('transcripts', $transcripts);
		$tpl->assign('transcript_counts', $this->_transcriptCounts());
		$tpl->assign('transcript_id', $transcript_id);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope = null) {
		if ('configAction' == $scope) {
			switch ($action) {
				case 'compactPreview':
					return $this->_configAction_compactPreview();
				case 'compactPreviewForm':
					return $this->_configAction_compactPreviewForm();
				case 'deleteTranscript':
					return $this->_configAction_deleteTranscript();
				case 'forkFromHere':
					return $this->_configAction_forkFromHere();
				case 'forkTranscript':
					return $this->_configAction_forkTranscript();
				case 'forkTranscriptForm':
					return $this->_configAction_forkTranscriptForm();
				case 'getTranscript':
					return $this->_configAction_getTranscript();
				case 'loadTranscripts':
					return $this->_configAction_loadTranscripts();
				case 'markTranscriptRead':
					return $this->_configAction_markTranscriptRead();
			}
		}
		return false;
	}
	
	/**
	 * The Open/Archived scope totals in display form: an abbreviated `label` for the switcher badge (a
	 * six-figure tally has no room to spell itself out at that size) plus the exact `title` for its tooltip.
	 * Formatted here rather than in JS so there's one implementation of the abbreviation, not two that drift.
	 */
	private function _transcriptCounts() : array {
		$counts = [];
		
		foreach(DAO_LlmAgentSession::getCounts() as $scope => $count) {
			// One decimal so a large tally still reads as `1.2M` instead of flattening to `1M`. A count is
			// always a whole number, though, so a round one has no fraction worth showing -- drop the trailing
			// `.0` (`1.0K` -> `1K`). Only the abbreviated forms carry a decimal; under 1,000 is printed as-is.
			$label = preg_replace('/\.0(?=[KMBT]$)/', '', DevblocksPlatform::strPrettyNumber($count, 1));
			
			$counts[$scope] = [
				'label' => $label,
				'title' => number_format($count),
			];
		}
		
		return $counts;
	}
	
	private function _configAction_loadTranscripts(): void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$before_id = DevblocksPlatform::importGPC($_POST['before_id'] ?? null, 'string', '');
		$limit = DevblocksPlatform::importGPC($_POST['limit'] ?? null, 'integer', 0);
		// 'active' (default) = unarchived only, 'archived' = archived only.
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string', 'active');
		$is_read = ('archived' == $filter);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			$transcripts = DAO_LlmAgentSession::search($limit ?: 100, $is_read, $before_id);
			
			$tpl->assign('limit', $limit);
			$tpl->assign('transcripts', $transcripts);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcripts.tpl');
			
			// Scope totals ride the list response so the switcher badges stay honest without a second trip.
			echo json_encode([
				'status' => true,
				'html' => $html,
				'counts' => $this->_transcriptCounts(),
			]);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
		}
	}
	
	private function _configAction_getTranscript() : void {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!($llm_session = DAO_LlmAgentSession::get($transcript_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid transcript ID.');
				
			if(!($llm_provider = $llm->getProvider($llm_session->provider, [], validate: false)))
				throw new Exception_DevblocksAjaxValidationError('Invalid LLM provider.');
			
			if(!($raw_messages = DAO_LlmAgentMessage::getMessagesBySession($transcript_id, 250)))
				$raw_messages = [];

			// Group messages into turns for display: each user message is its own panel; a run of
			// agent/tool messages (one prompt's worth of LLM round-trips) collapses into a single
			// agent panel. Tool-result messages render inline under their originating tool call, and
			// each turn keeps first/last timestamps so the viewer can show idle gaps (e.g. resumes).
			$tool_results = [];
			$turns = [];

			// Neutral usage accumulates per turn (over each turn's assistant round-trips) and per session.
			$zero_usage = DAO_LlmAgentMessage::USAGE_ZERO;
			$session_usage = $zero_usage;
			$add_usage = static fn(array $a, array $b) : array => DAO_LlmAgentMessage::addUsage($a, $b);

			// A tool's elapsed time is the gap between the message carrying its CALL and the one carrying its
			// RESULT — both insert times, so it's the round trip as the session experienced it.
			$tool_call_at = [];
			$tool_result_at = [];

			// Calls still waiting on a result, in emission order. Only load-bearing for a result that arrived
			// without an id (see DAO_LlmAgentMessage::matchToolResultKey) -- an id pairs itself.
			$pending_tool_calls = [];

			foreach($raw_messages as $model) {
				$neutral = $llm_provider->convertToGenericMessage($model->data, $model->uuid);

				$model_at = ($model->created_at * 1000000) + $model->created_at_usec;

				foreach($neutral->getToolCalls() as $tool_call) {
					$tool_call_at[$tool_call->getId()] = $model_at;
					$pending_tool_calls[] = ['id' => $tool_call->getId(), 'name' => $tool_call->getName()];
				}

				foreach($neutral->getToolResults() as $tool_id => $tool_result) {
					$tool_id = DAO_LlmAgentMessage::matchToolResultKey(strval($tool_id), $pending_tool_calls);
					$tool_results[$tool_id] = $tool_result;
					$tool_result_at[$tool_id] = $model_at;
				}

				if('tool' === $neutral->getRole())
					continue;

				// The boundary node (role=user, kind=summary) is a real user turn — SHOWN — but flagged as a
				// compaction checkpoint so the viewer marks where earlier turns were folded (the terminal
				// ancestor). Its assistant answer (kind=text) follows as a normal agent turn.
				if('summary' === $model->kind) {
					$turns[] = [
						'role' => 'user',
						'is_checkpoint' => true,
						'messages' => [$neutral],
						'ts_first' => $model->created_at,
						'ts_last' => $model->created_at,
						'seq_last' => $model->seq,
						'usage' => $zero_usage,
					];
					continue;
				}

				$group_role = ('user' === $neutral->getRole()) ? 'user' : 'agent';
				$last = count($turns) - 1;

				// A user message always opens a new panel; agent messages accrete into the open one.
				if($last < 0 || 'user' === $group_role || 'agent' !== $turns[$last]['role']) {
					$turns[] = [
						'role' => $group_role,
						'messages' => [$neutral],
						'ts_first' => $model->created_at,
						'ts_last' => $model->created_at,
						'seq_last' => $model->seq,
						'usage' => $zero_usage,
					];
					$idx = count($turns) - 1;
				} else {
					$turns[$last]['messages'][] = $neutral;
					$turns[$last]['ts_last'] = $model->created_at;
					$turns[$last]['seq_last'] = $model->seq;
					$idx = $last;
				}

				// Real API usage rides assistant turns (user/tool/summary carry none → these are no-op adds).
				// Per-turn: FOLD (output sums, prompt side = the turn's final round-trip) so a multi-tool turn
				// shows its real context, not the sum of every re-sent (cached) prompt. Session: a true cumulative
				// total of every billed token.
				$turns[$idx]['usage'] = DAO_LlmAgentMessage::foldTurnUsage($turns[$idx]['usage'], $model->usage);
				$session_usage = $add_usage($session_usage, $model->usage);

				// Why the LAST reporting round-trip in this turn stopped. Last-non-empty-wins: an agent turn is
				// several round-trips (the tool loop) and it's the final one that says how the turn ended.
				if('' !== $model->finish_reason)
					$turns[$idx]['finish_reason'] = $model->finish_reason;
			}

			// Detect tool results that are valid JSON objects/arrays and pre-format them (pretty-print) so
			// the template can promote them to a read-only CerbUI.JsonEditor (folding + highlighting). Scalars
			// and plain-text results are skipped — they stay as a <pre> (with the collapsible for long ones).
			$tool_results_json = [];
			foreach($tool_results as $tool_id => $result_text) {
				$decoded = json_decode($result_text, true);
				if(is_array($decoded) && json_last_error() === JSON_ERROR_NONE)
					$tool_results_json[$tool_id] = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			}

			// Idle gap between consecutive turns (surfaces resume switchovers).
			$prev_last_ts = null;
			foreach($turns as $i => $turn) {
				$gap = ($prev_last_ts !== null) ? max(0, $turn['ts_first'] - $prev_last_ts) : 0;
				$turns[$i]['gap'] = $gap;
				$turns[$i]['gap_label'] = $this->_transcriptGapLabel($gap);
				$prev_last_ts = $turn['ts_last'];
			}

			// Derive prompt total + cache-coverage % in PHP (keep the template dumb).
			$session_usage = DAO_LlmAgentMessage::deriveUsage($session_usage);
			foreach($turns as $i => $turn) {
				$turns[$i]['usage'] = DAO_LlmAgentMessage::deriveUsage($turn['usage'] ?? $zero_usage);
				// The turn ended abnormally — its content is cut off (`length`) or was withheld (`filter`). Flagged
				// so a reader isn't left guessing why an answer stops mid-sentence.
				$turns[$i]['is_truncated'] = in_array($turn['finish_reason'] ?? '', ['length', 'filter'], true);
			}

			// Message counts by KIND (provider-agnostic — Anthropic tool results are role=user, so count on kind):
			// tool_result → tools; real user text → user; assistant → agent (the synthetic summary boundary is
			// excluded from all).
			$message_counts = ['user' => 0, 'agent' => 0, 'tools' => 0];
			foreach($raw_messages as $model) {
				if('tool_result' === $model->kind)
					$message_counts['tools']++;
				elseif('user' === $model->role && 'summary' !== $model->kind)
					$message_counts['user']++;
				elseif('assistant' === $model->role)
					$message_counts['agent']++;
			}

			// Sanitize links to the `#cerb-external-link` + click-to-confirm popup (matches email display /
			// sheets). NOT URIFilter_Extract — that rewrites links to dead `#uri-<code>` anchors.
			$tpl->assign('filter_links', new \Cerb_HTMLPurifier_URIFilter_Email(true));

			// Resolve the model + authentication connected account for the header (primed sessions only —
			// a not-yet-primed/migrated one has NULL provider_params, so we simply omit these fields).
			$auth_account = null;
			if($llm_session->isPrimed() && ($auth_uri = strval($llm_session->provider_params['authentication'] ?? ''))) {
				$uri_parts = DevblocksPlatform::services()->ui()->parseURI($auth_uri);
				$auth_cid = $uri_parts['context_id'] ?? '';
				$auth_account = is_numeric($auth_cid) ? DAO_ConnectedAccount::get($auth_cid) : DAO_ConnectedAccount::getByUri($auth_cid);
			}

			// Resolve `automation` tools' `cerb:automation:<name>` URIs to their records so the transcript's Tools
			// section can peek them (keyed by tool alias, e.g. `tool96`). Batched to one query via getByUris.
			$tool_map = $llm_session->getToolMap();
			$tool_automations = [];

			$tool_uris = array_filter(array_map(
				fn($tool) => ('automation' == $tool['type'] && $tool['uri']) ? $tool['uri'] : null,
				$tool_map
			));

			if($tool_uris) {
				$by_uri = DAO_Automation::getByUris(array_values($tool_uris));
				$resolved = [];
				foreach($by_uri as $automation) /* @var $automation Model_Automation */
					$resolved['cerb:automation:' . $automation->name] = $automation;

				foreach($tool_uris as $tool_name => $uri)
					if(isset($resolved[$uri]))
						$tool_automations[$tool_name] = $resolved[$uri];
			}

			$tpl->assign('tool_map', $tool_map);
			$tpl->assign('tool_automations', $tool_automations);

			// The reasoning level that actually shipped on this session's turns. Asked of the provider rather
			// than read off provider_params because the level a provider sends isn't always the level that was
			// configured: OpenAI's /v1/chat/completions forces `none` whenever tools are present, so a
			// tool-using session runs with reasoning OFF while every stored artifact still says `medium`.
			// Null = we sent no level and the model used its own default, which is NOT the same as `none`.
			$effective_effort = null;

			if($llm_session->isPrimed() && ($effort_provider = DevblocksPlatform::services()->llm()->getProvider($llm_session->provider, $llm_session->provider_params, false)))
				$effective_effort = $effort_provider->getEffectiveEffort(boolval($tool_map));

			$tpl->assign('effective_effort', $effective_effort);

			// The volumes the `agent_terminal` tool actually reached. Resolved through `describeSpecs()` rather than
			// `fromSpecs()` so a mount whose volume was since deleted or disabled is REPORTED instead of
			// vanishing — that's a silent loss of capability the agent never announced, and the transcript is
			// the only place anyone would think to look for it.
			$tpl->assign('mount_map', is_null($llm_session->mounts)
				? null
				: \Cerb\Agent\Filesystem::describeSpecs($llm_session->mounts)
			);

			$tpl->assign('llm_session', $llm_session);
			$tpl->assign('llm_session_automation', $llm_session->getAutomation());
			$tpl->assign('llm_session_user', $llm_session->getUser());
			$tpl->assign('llm_session_agent', $llm_session->getAgent());
			$tpl->assign('llm_session_auth', $auth_account);
			$tpl->assign('turns', $turns);
			// Milliseconds per tool call. Only where BOTH ends are known — a call with no result yet has no
			// duration to report.
			$tool_durations = [];

			foreach($tool_result_at as $tool_id => $ended_at) {
				if(!array_key_exists($tool_id, $tool_call_at))
					continue;

				$tool_durations[$tool_id] = intdiv(max(0, $ended_at - $tool_call_at[$tool_id]), 1000);
			}

			$tpl->assign('tool_results', $tool_results);
			$tpl->assign('tool_durations', $tool_durations);
			$tpl->assign('tool_results_json', $tool_results_json);
			$tpl->assign('session_usage', $session_usage);
			$tpl->assign('message_counts', $message_counts);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcript.tpl');
			
			echo json_encode([
				'status' => true,
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			return;
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
	
	private function _configAction_compactPreviewForm() : void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');

		if(!($llm_session = DAO_LlmAgentSession::get($transcript_id))) {
			echo 'Invalid transcript ID.';
			return;
		}

		// Compaction needs a provider (to summarize) + context_window (for the threshold), so it only
		// applies to a primed session. The Compact button is hidden otherwise; this fails closed too.
		if(!$llm_session->isPrimed()) {
			echo 'This transcript has no LLM provider details (it was never primed), so it can\'t be compacted.';
			return;
		}

		$tpl->assign('llm_session', $llm_session);
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/compact.tpl');
	}

	private function _configAction_compactPreview() : void {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		// Checkbox toggle: present ('1') only when checked; absent → off. So default off.
		$summarize = DevblocksPlatform::importGPC($_POST['summarize'] ?? null, 'integer', 0);
		// Keep as raw strings so an explicit '0' is distinguishable from a blank field (blank = use default).
		$context_ratio = DevblocksPlatform::importGPC($_POST['context_ratio'] ?? null, 'string', '');
		$tail_ratio = DevblocksPlatform::importGPC($_POST['tail_ratio'] ?? null, 'string', '');

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!($llm_session = DAO_LlmAgentSession::get($transcript_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid transcript ID.');

			// Compaction needs a provider + context_window — only a primed session qualifies (fail closed).
			if(!$llm_session->isPrimed())
				throw new Exception_DevblocksAjaxValidationError('This transcript has no LLM provider details, so it can\'t be compacted.');

			// Load the provider WITH its stored credentials (provider_params) — we render messages neutrally
			// AND make a one-off LIVE summarization call below, so it needs auth. validate:false so a partial
			// bag doesn't throw at construction (auth surfaces on the request; summarizeMessages falls back).
			if(!($session_provider = $llm->getProvider($llm_session->provider, $llm_session->provider_params, validate: false)))
				throw new Exception_DevblocksAjaxValidationError('Invalid LLM provider.');

			// One strategy: compaction. Ratios are fractions of the model's context_window (turnkey — no
			// absolute token knobs).
			$config = ['summarize' => $summarize ? 1 : 0];
			if('' !== $context_ratio) $config['context_ratio'] = floatval($context_ratio);
			if('' !== $tail_ratio) $config['tail_ratio'] = floatval($tail_ratio);

			$history = $llm->getCompaction($config, intval($llm_session->provider_params['context_window'] ?? 0));
			$memory_store = $llm->getMemoryStore($transcript_id);

			$plan = $history->plan($memory_store);

			$preview = [
				'strategy' => 'compaction',
				'mode' => $plan['mode'],
				'compacted' => $plan['compacted'],
				'reason' => $plan['reason'] ?? '',
				'tokens' => $plan['tokens'],
				'max_tokens' => $plan['max_tokens'],
				'keep_tail_tokens' => $plan['keep_tail_tokens'] ?? 0,
				'folded' => [],
				'send' => [],
				// Set only when a live summary actually ran (see below).
				'summary_usage' => null,
				'summary_cold_fallback' => false,
			];

			if($plan['compacted']) {
				$preview['folded'] = array_map(fn($m) => $this->_compactPreviewView($session_provider, $m->data), $plan['older']);

				// The new branch root: a LIVE summary of the whole window (summarize) or an empty boundary
				// marker (truncate — WORM never drops nodes). The summary actually calls the LLM once with the
				// session's own credentials so the preview shows the real thing; nothing is persisted.
				@set_time_limit(60);

				$boundary_text = '[Earlier turns omitted]';

				if('summarize' === $plan['mode']) {
					// EXACTLY the path live compaction takes — the warm prefix (same system prompt, same tools,
					// same native messages + one instruction turn). That's what makes this preview a real dry run
					// of `/compact` rather than a lookalike, and it's why the usage readout below is meaningful:
					// it's the same request shape the real thing will make.
					$summary_usage = null;
					$native_window = array_map(fn($m) => $m->data, $plan['window']);

					$summary = $llm->summarizeSessionWindow($session_provider, $transcript_id, $native_window, '', $summary_usage);

					// Cold fallback (no session row, a throw, an empty reply) — same fallback Compaction makes.
					if('' === $summary) {
						$summary = $llm->summarizeMessages($session_provider, $plan['window']);
						$preview['summary_cold_fallback'] = true;
					}

					// Derived `prompt` + cache `coverage`, same definitions the transcript chips use — a reader
					// shouldn't have to hold two mental models of "tokens in".
					if(is_array($summary_usage))
						$preview['summary_usage'] = DAO_LlmAgentMessage::deriveUsage($summary_usage);

					$boundary_text = "[Summary of earlier conversation]\n" . $summary;
				}

				$preview['send'][] = [
					'role' => 'summary',
					'text' => $boundary_text,
					'tools' => '',
					'is_tool_result' => false,
					'placeholder' => true,
				];

				$tail = $this->_compactPreviewSanitize($session_provider, array_map(fn($m) => $m->data, $plan['tail']));
				foreach($tail as $native)
					$preview['send'][] = $this->_compactPreviewView($session_provider, $native);
			} else {
				$window = array_map(fn($m) => $m->data, $plan['window']);
				$window = $this->_compactPreviewSanitize($session_provider, $window);
				foreach($window as $native)
					$preview['send'][] = $this->_compactPreviewView($session_provider, $native);
			}

			$tpl->assign('preview', $preview);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/compact_preview.tpl');

			echo json_encode([
				'status' => true,
				'html' => $html,
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);

			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
		}
	}

	// Compact human label for the idle gap between two turns (e.g. "45s", "12m", "2h 5m", "1d 3h").
	private function _transcriptGapLabel(int $secs) : string {
		if($secs <= 0)
			return '';
		if($secs < 60)
			return $secs . 's';
		if($secs < 3600)
			return floor($secs / 60) . 'm';
		if($secs < 86400)
			return floor($secs / 3600) . 'h ' . floor(($secs % 3600) / 60) . 'm';
		return floor($secs / 86400) . 'd ' . floor(($secs % 86400) / 3600) . 'h';
	}

	/**
	 * Mirror production before rendering the preview: providers prune leading non-user turns
	 * (a leading assistant, or an orphaned tool_result whose tool_use was trimmed away) right
	 * before the request, since the chat APIs require the conversation to start with a user
	 * turn. So the preview shows the actually-sent list, not the raw strategy output.
	 */
	private function _compactPreviewSanitize(\Extension_DevblocksLlmProvider $provider, array $messages) : array {
		if(method_exists($provider, 'sanitizeMessages'))
			return $provider->sanitizeMessages($messages);

		return $messages;
	}

	private function _compactPreviewView(\Extension_DevblocksLlmProvider $provider, array $native) : array {
		$response = $provider->convertToGenericMessage($native);

		$text = '';
		foreach($response->getMessages() as $block)
			$text .= ($block['content'] ?? '');

		$tools = [];
		foreach($response->getToolCalls() as $tool)
			$tools[] = $tool->getName();

		// Match the transcript viewer: the assistant role is surfaced as "agent".
		$role = $response->getRole() ?: 'message';

		return [
			'role' => 'assistant' === $role ? 'agent' : $role,
			'text' => $text,
			'tools' => implode(', ', $tools),
			'is_tool_result' => count($response->getToolResults()) > 0,
			'placeholder' => false,
		];
	}

	private function _configAction_forkFromHere() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		$at_seq = DevblocksPlatform::importGPC($_POST['at_seq'] ?? null, 'integer', 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!DAO_LlmAgentSession::get($transcript_id))
				throw new Exception_DevblocksAjaxValidationError('Invalid transcript ID.');

			// Always fork EXCLUSIVE of the target user message: copy the prefix strictly before it so the
			// branch ends on the preceding assistant turn — a clean, appendable transcript that never ends
			// on a user turn. (A chat `/rewind` that edits/regenerates a user turn lives elsewhere, in
			// `AgentPromptAwait::_promptAction_rewind`, not this dev tool.)
			$fork_seq = $at_seq - 1;

			// `fork($id, $seq)` treats `$seq <= 0` as "fork at the tip" (copies EVERYTHING), so never pass
			// 0 — there's simply nothing before the first message to branch.
			if($fork_seq < 1)
				throw new Exception_DevblocksAjaxValidationError('There\'s nothing before the first message to fork.');

			// Same provider, original left intact.
			if(!($fork = DAO_LlmAgentSession::fork($transcript_id, $fork_seq)))
				throw new Exception_DevblocksAjaxValidationError('Failed to fork the transcript.');

			echo json_encode([
				'status' => true,
				'transcript_id' => $fork->uuid,
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);

			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
		}
	}

	private function _configAction_forkTranscriptForm() : void {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');

		if(!($llm_session = DAO_LlmAgentSession::get($transcript_id))) {
			echo 'Invalid transcript ID.';
			return;
		}

		$tpl->assign('llm_session', $llm_session);
		$tpl->assign('chat_providers', $llm->getChatProviders());
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/fork.tpl');
	}

	private function _configAction_forkTranscript() : void {
		$llm = DevblocksPlatform::services()->llm();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		$target_provider = DevblocksPlatform::importGPC($_POST['target_provider'] ?? null, 'string', '');
		$model = DevblocksPlatform::importGPC($_POST['model'] ?? null, 'string', '');

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!DAO_LlmAgentSession::get($transcript_id))
				throw new Exception_DevblocksAjaxValidationError('Invalid transcript ID.');

			if(!array_key_exists($target_provider, $llm->getChatProviders()))
				throw new Exception_DevblocksAjaxValidationError('Invalid target provider.');

			$params = [];
			if('' !== $model)
				$params['model'] = $model;

			if(!($fork = $llm->forkSession($transcript_id, $target_provider, $params)))
				throw new Exception_DevblocksAjaxValidationError('Failed to fork the transcript.');

			echo json_encode([
				'status' => true,
				'transcript_id' => $fork->uuid,
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);

			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
		}
	}

	private function _configAction_deleteTranscript() : void {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if (!(DAO_LlmAgentSession::get($transcript_id))) {
				echo json_encode([
					'status' => false,
					'error' => 'Invalid LLM transcript ID.',
				]);
				return;
			}
			
			DAO_LlmAgentSession::delete($transcript_id);
			
			echo json_encode([
				'status' => true,
				'counts' => $this->_transcriptCounts(),
			]);
			return;
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
	
	private function _configAction_markTranscriptRead() : void {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$transcript_id = DevblocksPlatform::importGPC($_POST['transcript_id'] ?? null, 'string', '');
		$is_read = true;
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if (!(DAO_LlmAgentSession::get($transcript_id))) {
				echo json_encode([
					'status' => false,
					'error' => 'Invalid LLM transcript ID.',
				]);
				return;
			}
			
			DAO_LlmAgentSession::markRead($transcript_id, $is_read);
			
			echo json_encode([
				'status' => true,
				'counts' => $this->_transcriptCounts(),
			]);
			return;
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'status' => false,
				'error' => 'An unknown error occurred.',
			]);
			return;
		}
	}
}