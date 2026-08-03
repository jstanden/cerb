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
		$transcripts = DAO_LlmAgentSession::search($limit);
		
		$tpl->assign('limit', $limit);
		$tpl->assign('transcripts', $transcripts);
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
	
	private function _configAction_loadTranscripts(): void {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$before_id = DevblocksPlatform::importGPC($_POST['before_id'] ?? null, 'string', '');
		$limit = DevblocksPlatform::importGPC($_POST['limit'] ?? null, 'integer', 0);
		$is_unread = DevblocksPlatform::importGPC($_POST['is_unread'] ?? null, 'bool', false);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$transcripts = DAO_LlmAgentSession::search($limit ?: 100, $is_unread, $before_id);
			
			$tpl->assign('limit', $limit);
			$tpl->assign('transcripts', $transcripts);
			$html = $tpl->fetch('devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcripts.tpl');
			
			echo json_encode([
				'status' => true,
				'html' => $html,
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
			
			if(!($messages = DAO_LlmAgentMessage::getMessagesBySession($transcript_id, 250)))
				$messages = [];
			
			// Convert the messages into a neutral format using providers
			$messages = array_map(fn($message) => $llm_provider->convertToGenericMessage($message->data, $message->uuid), $messages);
			
			$tpl->assign('filter_links', new \Cerb_HTMLPurifier_URIFilter_Extract());
			
				// Why the LAST reporting round-trip in this turn stopped. Last-non-empty-wins: an agent turn is
				// several round-trips (the tool loop) and it's the final one that says how the turn ended.
				if('' !== $model->finish_reason)
					$turns[$idx]['finish_reason'] = $model->finish_reason;
				// The turn ended abnormally — its content is cut off (`length`) or was withheld (`filter`). Flagged
				// so a reader isn't left guessing why an answer stops mid-sentence.
				$turns[$i]['is_truncated'] = in_array($turn['finish_reason'] ?? '', ['length', 'filter'], true);
			$tpl->assign('llm_session', $llm_session);
			$tpl->assign('llm_session_automation', $llm_session->getAutomation());
			$tpl->assign('llm_session_user', $llm_session->getUser());
			$tpl->assign('messages', $messages);
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