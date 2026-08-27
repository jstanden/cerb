<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DAO_AgentModel;
use DAO_AutomationContinuation;
use DAO_LlmAgentMessage;
use DAO_LlmAgentSession;
use DAO_Worker;
use DevblocksPlatform;
use Exception_DevblocksAjaxValidationError;
use Model_AutomationContinuation;

/*
 * agentPrompt — an agentic chat input (CerbUI.AgentPrompt). Prose value plus structured extras:
 * the posted prompt is an array `prompts[<name>][text|model|effort|images[]|mentions[]]`, split by
 * setValue() into `<name>` (text) and `<name>_mentions` / `<name>__model` / `<name>__effort` /
 * `<name>__llm` / `<name>__images` siblings so the automation can inherit the selected provider/model
 * and expand references. The `<name>__` siblings survive the continuation save (only a leading `__` is
 * dropped), so render() re-seeds the picker from them and the choice doesn't revert each turn.
 *
 * The model dropdown is inert until submit — no live server round-trip. A genuine provider change is
 * forked+rewritten once, at submit, by the llm.agent node (not here).
 *
 * invoke() handles the SERVER-side events (image upload is client-side via the shared asResource
 * endpoint, so it isn't here):
 *   - rewind      : fork the session at a message seq (exclusive of it), returning that turn as a
 *                   reusable `draft` — the same fork-from-here contract as the transcript viewer.
 *   - references  : `@` completion candidates, scoped to this element's declared `references:`.
 *
 * `/command`s are carried in the submitted prompt text (the automation acts on the leading token), so
 * they need no server round-trip here. The element `commands:` config feeds the client picker, and a
 * `rewrite/<name>:` entry is EXPANDED in the browser before submit — the automation only ever sees the
 * expanded text, never the alias.
 */
class AgentPromptAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return match ($action) {
			'rewind' => $this->_promptAction_rewind(),
			'references' => $this->_promptAction_references(),
			default => false,
		};
	}

	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		$models = $this->_getModels();

		// The posted value is an array {text, model, attachments, mentions}. A custom validator on the
		// field inspects the sub-values (a non-empty array would satisfy a plain `required`, so an empty
		// prompt needs an explicit text check).
		$validation->addField($this->_key, $prompt_label)
			->stringOrArray()
			->addValidator(function($value, &$error) use ($is_required, $models) {
				$text = is_array($value) ? ($value['text'] ?? '') : $value;
				$model_id = is_array($value) ? ($value['model'] ?? '') : '';
				$effort = is_array($value) ? DevblocksPlatform::strLower(trim(strval($value['effort'] ?? ''))) : '';

				if($is_required && !strlen(trim(strval($text)))) {
					$error = 'requires a message.';
					return false;
				}

				if(strlen($model_id) && $models && !array_key_exists($model_id, $models)) {
					$error = sprintf("was sent an unavailable model (%s).", $model_id);
					return false;
				}

				// An out-of-range level is an ERROR, including when the model offers no levels at all. The
				// `$allowed &&` short-circuit that used to sit here made the empty case fall through to
				// _resolveEffort(), which quietly returned '' -- so the turn ran at the provider's default while
				// the UI showed the level the worker picked. Reasoning silently off is the one failure here
				// nobody can see from the outside; fail loudly instead.
				if('' !== $effort && '' !== $model_id && ($m = $models[$model_id] ?? null)) {
					$allowed = is_array($m['effort_choices'] ?? null) ? $m['effort_choices'] : [];
					$default = strval($m['default_effort'] ?? '');
					if('' !== $default && !in_array($default, $allowed, true))
						$allowed[] = $default;

					if(!in_array($effort, $allowed, true)) {
						$error = sprintf("was sent an unavailable effort level (%s).", $effort);
						return false;
					}
				}

				return true;
			})
		;
	}

	function formatValue() {
		// On submit (formatValue runs once, AFTER validateAll passes and BEFORE the automation executes),
		// prime the session's LLM block from the picked model — so `llm.agent` can pure-resume it with just
		// `session_id` (no `inputs.llm` wiring). This is the one place the agentPrompt mutates the session.
		$this->_primeSessionFromSelection();

		// Own the pasted images with the transcript, now that the session exists (setValue runs BEFORE this, so
		// the session may not have existed yet there): link the durable attachments to the session (int id) and
		// drop the uploader's default worker link, so cleanup is transcript-scoped (reaped when the session is
		// deleted / by the orphan sweep) rather than lingering under the worker's files.
		[, $attachment_ids] = $this->_resolveImages();

		if($attachment_ids && ($session_id = DAO_LlmAgentSession::getIdByUuid(strval($this->_data['session_id'] ?? '')))) {
			if(($worker = \CerberusApplication::getActiveWorker()))
				\DAO_Attachment::unattach(\CerberusContexts::CONTEXT_WORKER, $worker->id, $attachment_ids);

			\DAO_Attachment::addLinks(DAO_LlmAgentSession::CONTEXT, $session_id, $attachment_ids);
		}

		// The automation sees the clean prompt text at `<name>`; extras arrive via setValue() siblings.
		return is_array($this->_value) ? ($this->_value['text'] ?? '') : $this->_value;
	}

	// Resolve the posted image uris (durable `cerb:attachment:<id>`; a legacy `cerb:automation_resource:` token is
	// still accepted) to validated `{mime_type, uri}` descriptors — authoritative mime from the record, since a
	// client-supplied mime is spoofable, and non-images are dropped. Returns [descriptors, attachment_ids] and
	// empties both when the picked model lacks vision (the command re-gates too). Shared by setValue (which emits
	// the `<name>__images` descriptors) and formatValue (which links the attachment_ids to the session).
	private function _resolveImages() : array {
		$posted_images = (is_array($this->_value) && is_array($this->_value['images'] ?? null))
			? array_values($this->_value['images']) : [];

		$images = [];
		$attachment_ids = [];

		foreach($posted_images as $posted) {
			$uri = is_array($posted) ? strval($posted['uri'] ?? '') : strval($posted);

			if('' === $uri)
				continue;

			if(DevblocksPlatform::strStartsWith($uri, 'cerb:attachment:')) {
				$attachment_id = intval(substr($uri, strlen('cerb:attachment:')));

				if(!($attachment = \DAO_Attachment::get($attachment_id)))
					continue;

				$mime_type = strval($attachment->mime_type);

				if(!DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($mime_type), 'image/'))
					continue;

				$attachment_ids[] = $attachment_id;
				$images[] = ['mime_type' => $mime_type, 'uri' => 'cerb:attachment:' . $attachment_id];
			} else {
				$token = DevblocksPlatform::strStartsWith($uri, 'cerb:automation_resource:')
					? substr($uri, strlen('cerb:automation_resource:'))
					: $uri;

				if(!($resource = \DAO_AutomationResource::getByToken($token)))
					continue;

				$mime_type = strval($resource->mime_type);

				if(!DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($mime_type), 'image/'))
					continue;

				$images[] = ['mime_type' => $mime_type, 'uri' => 'cerb:automation_resource:' . $token];
			}
		}

		$model = is_array($this->_value) ? strval($this->_value['model'] ?? '') : '';

		if($images && $model) {
			$models = $this->_getModels();

			if(empty($models[$model]['vision'])) {
				$images = [];
				$attachment_ids = [];
			}
		}

		return [$images, $attachment_ids];
	}

	/*
	 * Turn the posted `@` tokens into what the automation reads at `<name>_mentions`.
	 *
	 * A filesystem reference stays TEXT — `{path: 'cerb-dev/guides/setup.md'}` — and is never resolved to a
	 * record id. It doesn't need to be: the reference can name a whole volume or a directory (which is virtual,
	 * derived from path prefixes, and has no record at all), and the agent dereferences a path itself through
	 * its `agent_terminal` tool. Resolving would also mean validating, and this element deliberately isn't responsible
	 * for a reference being correct — a stale path just rides through as text the agent says it can't find.
	 *
	 * Worker handles and explicit `@type:id` record pairs keep their existing shapes.
	 */
	private function _resolveMentions(array $posted) : array {
		$out = [];

		foreach($posted as $token) {
			$token = trim(strval($token));

			if('' === $token)
				continue;

			$body = ltrim($token, '@');

			// `<volume>/<path…>` — a slash is what distinguishes a filesystem reference from a worker handle
			// (`@jeff`) or a record pair (`@ticket:123`), neither of which can contain one. Passed through
			// verbatim, trailing slash and all (a volume or directory reference keeps it).
			if(false !== strpos($body, '/')) {
				$out[] = ['path' => $body];
				continue;
			}

			// Everything else keeps its posted form — `@type:id` for records, a bare `@handle` for workers —
			// exactly as before this element grew `references:`.
			if(false !== strpos($body, ':')) {
				[$context, $id] = array_pad(explode(':', $body, 2), 2, '');

				if('' !== $context && '' !== $id)
					$out[] = ['context' => $context, 'id' => $id];

			} else {
				$out[] = ['handle' => $body];
			}
		}

		return $out;
	}

	// Reconcile the referenced session's provider block to the picked model: same provider → refresh params
	// (model/auth — even when the provider didn't change but the session had none); different → in-place
	// switch; missing → create/prime at that id (owned by the active worker). Idempotent.
	private function _primeSessionFromSelection() : void {
		$session_id = strval($this->_data['session_id'] ?? '');
		$model_id = is_array($this->_value) ? strval($this->_value['model'] ?? '') : '';

		if('' === $session_id || '' === $model_id)
			return;

		$models = $this->_getModels();

		if(!($m = $models[$model_id] ?? null) || !$m['provider'])
			return;

		$create_fields = [];
		if(($worker = \CerberusApplication::getActiveWorker())) {
			$create_fields['user_type'] = 'worker';
			$create_fields['user_id'] = $worker->id;
		}

		// Inject the resolved effort into the params bag (as `provider_params['effort']`) so a change of effort
		// reconciles as a capability boundary — same primitive as a model/provider switch. Empty (no choices +
		// no fixed `effort:`) leaves the bag untouched (provider default).
		$params = $m['params'];
		if(($effort = $this->_resolveEffort($model_id, is_array($this->_value) ? strval($this->_value['effort'] ?? '') : '')))
			$params['effort'] = $effort;

		DevblocksPlatform::services()->llm()->reconcileSession($session_id, $m['provider'], $params, $create_fields);
	}

	// Resolve the posted effort against the picked model: allowed = its `effort_choices` PLUS its fixed
	// `default_effort`. Posted-if-allowed, else the model's default_effort (the fixed `effort:`), else '' (the
	// model offers no choices and has no fixed effort → provider default).
	private function _resolveEffort(string $model_id, string $posted) : string {
		if('' === $model_id)
			return '';

		$models = $this->_getModels();

		if(!($m = $models[$model_id] ?? null))
			return '';

		$default = strval($m['default_effort'] ?? '');
		$allowed = is_array($m['effort_choices'] ?? null) ? $m['effort_choices'] : [];
		if('' !== $default && !in_array($default, $allowed, true))
			$allowed[] = $default;

		$posted = DevblocksPlatform::strLower(trim($posted));

		return in_array($posted, $allowed, true) ? $posted : $default;
	}

	function setValue($key, $value, $dict) {
		$text = is_array($value) ? ($value['text'] ?? '') : $value;
		$model = is_array($value) ? ($value['model'] ?? '') : '';
		$mentions = $this->_resolveMentions(
			(is_array($value) && is_array($value['mentions'] ?? null)) ? array_values($value['mentions']) : []
		);

		// The chosen reasoning-effort level, validated against the model's `effort_choices` + fixed `effort:`
		// (falls back to that model's default). Empty when the model has neither.
		$effort = $this->_resolveEffort(strval($model), is_array($value) ? strval($value['effort'] ?? '') : '');

		// Validated image descriptors for `<name>__images` — a plain string IS one text block; images resolve to
		// `{mime_type, uri}` (base64 only at send, not stored as a blob). The durable attachments are linked to
		// the session in formatValue() (which runs after the session is created), not here.
		[$images, ] = $this->_resolveImages();

		$pairs = [
			$key => $text,
			$key . '_mentions' => $mentions,
			// Durable UI state: the picker seeds from `__model` on re-render so the choice doesn't revert.
			// `__llm` is the ready-to-drop `{<provider>: <params>}` block the automation feeds to
			// `llm.agent inputs.llm@key`, which then reconciles the session (same provider → update params;
			// different → in-place switch). `<name>__` survives the continuation save (not leading-`__`).
			$key . '__model' => $model,
			// `<name>__effort` seeds the effort selector on re-render (like `__model`) so it doesn't revert.
			$key . '__effort' => $effort,
			$key . '__llm' => $this->_getLlmBlock(strval($model), $effort),
			// The picked provider's brand mark, so an automation can label its own conversation with it
			// (`await:form: resume: icon:`) without needing a provider-to-icon map it has no way to write.
			// Empty for a provider with no brand color, which is the caller's cue to fall back.
			$key . '__llm_icon' => $this->_getLlmProviderMark(strval($model), 'icon'),
			$key . '__llm_color' => $this->_getLlmProviderMark(strval($model), 'color'),
			// `<name>__images` = the picked images (empty when none / non-vision model).
			$key . '__images' => $images,
		];

		foreach($pairs as $k => $v) {
			if($dict instanceof \DevblocksDictionaryDelegate) {
				$dict->set($k, $v);
			} elseif(is_array($dict)) {
				$dict[$k] = $v;
			}
		}

		return $dict;
	}

	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();

		$label = $this->_data['label'] ?? null;
		$placeholder = $this->_data['placeholder'] ?? null;
		$default = $this->_data['default'] ?? null;
		$session_id = $this->_data['session_id'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];

		// FIRST render resolves the offered models and FREEZES them onto the continuation; every render and
		// validate after this one reads them back as an ordinary authored `models:`. See _freezeModels().
		$models = $this->_getModels($this->_surfaceFor($continuation));

		$this->_freezeModels($continuation, array_keys($models));

		// Seed the picker from the last-chosen model (persisted in setValue) so it doesn't revert each turn;
		// otherwise the first catalog entry (the client resolves that to the first ENABLED model).
		$persisted_model = $continuation->state_data['dict'][$this->_key . '__model'] ?? null;
		$default_model = (is_string($persisted_model) && array_key_exists($persisted_model, $models))
			? $persisted_model
			: array_key_first($models);

		$commands = $this->_getCommands();
		$references = $this->_getReferences();

		// A turn that failed hands its message back here (LlmAgentNode::_failTurn), so a rate-limited or timed-out
		// send costs a click instead of a retyped paragraph. Written into the dict alongside `__llm_token_usage`
		// and cleared by the next turn, so it can only ever describe the most recent failure.
		//
		// An explicit `default:` still wins — an author who seeds the composer means it, and silently overriding
		// them would be a worse surprise than losing a draft.
		$retry = $continuation->state_data['dict']['__llm_retry'] ?? null;

		if(!is_array($retry))
			$retry = [];

		if(!strlen(strval($default ?? '')) && strlen(strval($retry['prompt'] ?? '')))
			$default = strval($retry['prompt']);

		// Progress bar: the durable running estimate the LLM node maintains after each turn
		// (`__llm_token_usage` — window-aware, so it stays correct after a compaction). No re-summing.
		$context_tokens = intval($continuation->state_data['dict']['__llm_token_usage'] ?? 0);

		// On resume the running total isn't in the dict yet (no turn has run this visit), but the
		// referenced session already holds prior messages — seed the bar from them so a resumed chat
		// doesn't read 0% until the first new turn.
		if($context_tokens <= 0 && $session_id)
			$context_tokens = $this->_estimateSessionTokens(strval($session_id));

		// The component wants a JS array of model objects — display bits only. NEVER send the `params` bag
		// (it holds `authentication`); keep it server-side.
		$models_list = [];
		foreach($models as $id => $m)
			$models_list[] = [
				'id' => $id,
				'label' => $m['label'],
				'icon' => $m['icon'],
				'provider' => $m['provider'],
				'vision' => $m['vision'],
				'thinking' => $m['thinking'],
				'ratings' => $this->_filterRatingMeters($m['ratings']),
				'context_window' => $m['context_window'],
				'context_ratio' => $m['context_ratio'],
				'cache_ttl' => $m['cache_ttl'],
				'disabled' => $m['disabled'],
				'effort_choices' => $m['effort_choices'],
				'default_effort' => $m['default_effort'],
			];

		// Seed the effort selector from the last-chosen level (persisted in setValue), like the model picker.
		$persisted_effort = $continuation->state_data['dict'][$this->_key . '__effort'] ?? null;

// Anchor for the composer's cache TimeRing: seconds since the last agent turn (updated_at is stamped at
		// each turn's append/token-usage write). Only when a prior turn exists (token_usage > 0) — there's no
		// prompt cache to lapse before the first turn. The client counts up from here against the model's cache_ttl.
		$cache_elapsed = null;
		if($session_id && ($cache_session = DAO_LlmAgentSession::get(strval($session_id)))
				&& $cache_session->isPrimed() && $cache_session->token_usage > 0 && $cache_session->updated_at > 0)
			$cache_elapsed = max(0, time() - $cache_session->updated_at);

		// One JSON config blob (parsed in the template's {literal} script — avoids Smarty/JS brace clashes).
		$config = [
			'var' => $this->_key,
			'prompt_key' => 'agentPrompt/' . $this->_key,
			'continuation_token' => $continuation->token,
			'session_id' => $session_id ?: '',
			'placeholder' => $placeholder ?: 'Message the agent…',
			'default_value' => strval($default ?? ''),
			'default_model' => $default_model,
			'default_effort' => is_string($persisted_effort) ? $persisted_effort : '',
			'context_tokens' => intval($context_tokens),
			// Seconds since the last agent turn (null = no prior turn → no cache ring).
			'cache_elapsed' => is_null($cache_elapsed) ? null : intval($cache_elapsed),
			// Image paste is gated per-model by the selected model's `vision` capability (from the catalog),
			// not a separate element flag. The component defaults `images:true`; vision does the gating.
			'models' => $models_list,
			// The whole picker, including each `rewrite/`'s expansion text — the expansion IS a client-side
			// concern, and it's the same worker's own composer config either way.
			'commands' => array_values($commands),
			// What `@` completes — booleans only. The client needs to know WHETHER to offer each source; WHICH
			// volumes stays server-side (the `references` action re-reads it from this element's config), so the
			// declared set can't be widened from the browser.
			'references' => [
				'workers' => $references['workers'],
				'files' => (bool) $references['filesystems'],
			],
		];

		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('is_required', $is_required);
		$tpl->assign('retry_error', $retry['error'] ?? '');
		$tpl->assign('config_json', json_encode($config));

		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/agent_prompt.tpl');
	}

	// Estimate the active-context token count from a resumed session's stored messages, mirroring what
	// the LLM node writes to `__llm_token_usage` after each turn (using the session's own history strategy).
	private function _estimateSessionTokens(string $session_id) : int {
		if('' === $session_id)
			return 0;

		$llm = DevblocksPlatform::services()->llm();

		// Estimate from stored messages regardless of priming: the default sliding-window strategy only
		// sums each message's token_est, so a session that isn't fully primed yet (provider set but
		// provider_params still NULL — e.g. a resumed/migrated one) still has a real token count. Load the
		// provider when we can (a token-aware strategy could use it) but never require it.
		$session = DAO_LlmAgentSession::get($session_id);

		// Fast path: the node denormalizes the post-turn estimate onto the session, so a resumed chat can
		// read it directly instead of re-summing the tree. Fall back to a live estimate when it's unset
		// (never ran / migrated).
		if($session && $session->token_usage > 0)
			return $session->token_usage;

		$provider = null;
		$compaction_config = [];
		$context_window = 0;
		if($session && $session->provider) {
			$provider = $llm->getProvider($session->provider, [], false);
			// Mirror the node: compaction policy rides the session's `llm:<provider>:compaction:` block,
			// so the bar reflects the picked model's own strategy (not a hardcoded default) once primed.
			if(is_array($session->provider_params['compaction'] ?? null))
				$compaction_config = $session->provider_params['compaction'];
			$context_window = intval($session->provider_params['context_window'] ?? 0);
		}

		return $llm->getCompaction($compaction_config, $context_window)->estimateContextTokens($llm->getMemoryStore($session_id), $provider);
	}

	// The model catalog, keyed by `agent_model` NAME. Each `models:` entry references a first-class model
	// record by name (the same grammar as `llm.agent:`/`llm.chat:` `model:`), optionally overriding its
	// provider knobs; the record supplies provider/model/auth/vision/context_window. Falls back to the
	// referenced session's own block when no models are configured.
	private function _getModels(string $surface = '') : array {
		$out = [];
		$models = $this->_data['models'] ?? [];

		if(is_array($models) && $models) {
			foreach($models as $name => $overrides) {
				$name = strval($name);
				$overrides = is_array($overrides) ? $overrides : [];

				if(($entry = $this->_resolveModelEntry($name, $overrides)))
					$out[$name] = $entry;
			}

			if($out)
				return $out;
		}

		// Nothing configured -> the pool the AGENT may use, or every AVAILABLE model in the admin's `priority`
		// order when no agent is named or it sets no policy. This is the zero-config path: an agentPrompt that
		// says nothing about models offers whatever the environment prefers, so a shipped interaction never has
		// to name one -- and now an agent's Models field reaches the picker without the script resolving an
		// `llm.router:` and passing `models@key:`. Entries whose record is missing or disabled are still
		// dropped by _resolveModelEntry().
		$agent_pool = \Cerb\Agent\Config::resolveModelPool(
			\Cerb\Agent\Config::forWorker($this->_getAgentWorkerId(), $surface)
		);

		foreach($agent_pool as $name => $overrides) {
			if(($entry = $this->_resolveModelEntry(strval($name), is_array($overrides) ? $overrides : [])))
				$out[strval($name)] = $entry;
		}

		if($out)
			return $out;

		// Fallback: a single entry from the referenced (primed) session.
		$session_id = $this->_data['session_id'] ?? null;

		if($session_id && ($session = DAO_LlmAgentSession::get($session_id)) && $session->isPrimed()) {
			$id = $session->getModel() ?: $session->provider;
			$out[$id] = $this->_buildModelEntry($id, $session->provider, $session->provider_params);
		}

		return $out;
	}

	/**
	 * Which surface this interaction is running on, for an agent's per-surface model policy.
	 *
	 * Only reachable from a render, which is the only place a continuation is in hand -- and it only has to be
	 * reachable ONCE, because _freezeModels() turns the answer into stored config.
	 */
	private function _surfaceFor(Model_AutomationContinuation $continuation) : string {
		$caller_params = $continuation->state_data['dict']['caller_params'] ?? null;

		return is_array($caller_params) ? strval($caller_params['component'] ?? '') : '';
	}

	/**
	 * Freeze the offered models onto the continuation's own copy of this element, once.
	 *
	 * Two problems, one fix. `validate()` runs BEFORE `setValue()` and is handed no dict, so it can't resolve
	 * an agent's per-surface policy the way `render()` can -- and a picker narrower than its validator is the
	 * direction that rejects a legitimate choice. And re-resolving per turn would let an admin's edit change
	 * the list under an open conversation.
	 *
	 * Writing the resolved NAMES into `__return.form.elements[<key>].models` solves both: that is exactly the
	 * config `validate()` is later constructed from (`_applyAwaitFormPromptValues()` reads
	 * `$continuation->state_data['dict']['__return']['form']['elements']`), so from the second call onward both
	 * paths take the ordinary authored-`models:` branch and agree by construction. The list is fixed for the
	 * interaction, which is what it should be: the agent can't change mid-chat, so neither should its pool.
	 *
	 * NAMES, not hydrated entries -- the records stay authoritative, so one that is deleted or disabled mid-chat
	 * still drops out through `_resolveModelEntry()`. Once a model is picked it is stamped onto the session
	 * anyway, so the transcript keeps its single `llm:` branch regardless of what this list later says.
	 *
	 * Never overwrites: an authored `models:` block took the branch above and never reaches here, and a frozen
	 * list is only written when there isn't one.
	 */
	private function _freezeModels(Model_AutomationContinuation $continuation, array $names) : void {
		if(!$names || ($this->_data['models'] ?? []))
			return;

		$element_key = 'agentPrompt/' . $this->_key;
		$elements = $continuation->state_data['dict']['__return']['form']['elements'] ?? null;

		// The synthesized renders (a between-tool-calls repaint, the async-turn poll) build their own element
		// data and have nothing on the continuation to write to. They read the frozen list like everyone else.
		if(!is_array($elements) || !array_key_exists($element_key, $elements))
			return;

		if(($elements[$element_key]['models'] ?? []))
			return;

		$continuation->state_data['dict']['__return']['form']['elements'][$element_key]['models'] =
			array_fill_keys(array_map('strval', $names), []);

		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
		]);
	}

	/**
	 * The AI worker this composer is attached to, from `agent:`.
	 *
	 * Through the SHARED resolver, not a local copy. This method used to reimplement it and was never called at
	 * all -- exactly the drift `LlmTranscriptAwait` warns about when it says one resolver keeps `agent:` meaning
	 * the same thing everywhere. An author who writes the same reference on `llm.agent:` and here must get the
	 * same worker, and a form one accepts must be accepted by the other.
	 */
	private function _getAgentWorkerId() : int {
		if('' === ($ref = trim(strval($this->_data['agent'] ?? ''))))
			return 0;

		$worker = \Cerb\AutomationBuilder\Node\LlmAgentNode::resolveAgentWorker($ref);

		return $worker ? intval($worker->id) : 0;
	}

	// Resolve one `models:` entry — an `agent_model` NAME + optional overrides — to a normalized picker model.
	// The record supplies provider/model/auth/vision/context_window (getProviderParams); the overrides win on
	// top in that model's provider grammar, plus the agentPrompt-only knobs (effort_choices/disabled/
	// compaction) which ride the params bag and are read by _buildModelEntry. A missing record, or one whose
	// record is disabled (governance/retired → gone everywhere), drops out of the picker entirely.
	private function _resolveModelEntry(string $name, array $overrides) : ?array {
		// `<name>/<alias>` mounts the same record more than once with different settings; the record name is
		// always the first segment. The full key stays the picker's entry id (it's opaque downstream), so two
		// mounts of one record remain distinct entries.
		$record_name = DevblocksPlatform::services()->string()->strBefore($name, '/') ?: $name;

		if(!($record = DAO_AgentModel::getByName($record_name)))
			return null;

		if(!$record->isUsable())
			return null;

		$error = null;

		if(!($block = DevblocksPlatform::services()->llm()->resolveAgentModelBlock($record, $overrides, $error)))
			return null;

		list($provider, $params) = $block;

		// The record's has_vision is authoritative for the picker: getProviderParams only emits `vision` when
		// true, so make the false case explicit (unless an override set it) — image paste gates on this.
		if(!array_key_exists('vision', $overrides) && !array_key_exists('vision', $params))
			$params['vision'] = (bool) $record->has_vision;

		return $this->_buildModelEntry($name, $provider, $params);
	}

	// Normalize a model: server `params` bag (kept whole) + display fields, with the vision/context_window
	// default cascade (explicit → provider-ext per-model default → global) and auto-derived icon/label.
	/**
	 * Narrow the meters to the element's `ratings:` list, in ITS order -- so an author shows only what
	 * actually informs the choice here, leftmost first.
	 *
	 * Omitted = all of them (what the picker did before this existed, so nothing changes by default). An
	 * explicitly EMPTY list draws no meters at all, which collapses the rows back to one line -- the right
	 * shape for a portal chat where a rating is an internal judgement the end user shouldn't be reading.
	 *
	 * ⚠ This is DISPLAY, never enforcement. A rating that must be honored is a router query
	 * (`privacy:>=zdr`) -- that's what keeps a non-compliant model out of the catalog entirely. Hiding a
	 * meter hides information; it permits nothing and forbids nothing.
	 */
	private function _filterRatingMeters(array $meters) : array {
		if(!array_key_exists('ratings', $this->_data))
			return $meters;

		$allowed = $this->_data['ratings'];
		$allowed = is_array($allowed) ? $allowed : DevblocksPlatform::parseCsvString(strval($allowed));
		$allowed = array_values(array_filter(array_map(fn($k) => DevblocksPlatform::strLower(trim(strval($k))), $allowed)));

		if(!$allowed)
			return [];

		$by_key = array_column($meters, null, 'key');
		$out = [];

		foreach($allowed as $key) {
			if(array_key_exists($key, $by_key))
				$out[] = $by_key[$key];
		}

		return $out;
	}

	/**
	 * Stored rating tiers -> what the picker draws: one meter per axis, `level` of `of`, with the tier's own
	 * word for the tooltip plus the axis's glyph and hue. Resolved HERE rather than on the client so
	 * `Model_AgentModel` stays the single place a stored value becomes a label, a glyph, or a color.
	 *
	 * Every axis is emitted, rated or not -- an unrated axis draws an EMPTY track, which is what keeps the
	 * columns comparable down the list instead of leaving a hole.
	 */
	private function _buildRatingMeters(mixed $ratings) : array {
		$ratings = is_array($ratings) ? $ratings : [];
		$colors = \Model_AgentModel::getRatingColors();
		$icons = \Model_AgentModel::getRatingIcons();
		$out = [];

		foreach(\Model_AgentModel::getRatings() as $key) {
			$scale = \Model_AgentModel::getRatingScaleLabels($key);
			$tiers = array_keys($scale);
			$value = intval($ratings[$key] ?? 0);
			$level = $value ? (array_search($value, $tiers, true) + 1) : 0;

			$out[] = [
				'key' => $key,
				'level' => $level ?: 0,
				'of' => count($tiers),
				'label' => $scale[$value] ?? '',
				'color' => $colors[$key] ?? '',
				'icon' => $icons[$key] ?? '',
			];
		}

		return $out;
	}

	private function _buildModelEntry(string $id, string $provider, array $params) : array {
		$strings = DevblocksPlatform::services()->string();
		$provider_ext = DevblocksPlatform::services()->llm()->getProvider($provider, [], false);

		$model = strval($params['model'] ?? '');
		$defaults = $provider_ext ? $provider_ext->getModelDefaults($model) : [];

		$vision = array_key_exists('vision', $params)
			? $strings->toBool($params['vision'])
			: (bool) ($defaults['vision'] ?? false);

		$context_window = array_key_exists('context_window', $params)
			? intval($params['context_window'])
			: intval($defaults['context_window'] ?? 150000);

		// The auto-compaction trigger fraction (context_ratio, default 0.9) — surfaced so the client can mark
		// the threshold on the context bar. Safe to send (just a number; the auth-bearing params stay server-side).
		$compaction = is_array($params['compaction'] ?? null) ? $params['compaction'] : [];
		$context_ratio = isset($compaction['context_ratio']) ? floatval($compaction['context_ratio']) : 0.9;
		if($context_ratio <= 0 || $context_ratio > 1)
			$context_ratio = 0.9;

		// The stamped `display:` block wins, then the provider's brand mark / the agent_model NAME (a handle the
		// author chose, shown as-is with no provider suffix). Reading it off `$params` covers both entry paths
		// for free — a record-resolved model and the session fallback both carry the block in that bag.
		$display = is_array($params['display'] ?? null) ? $params['display'] : [];
		$icon = trim(strval($display['icon'] ?? '')) ?: ($provider_ext ? $provider_ext->getIcon() : null);
		$label = trim(strval($display['name'] ?? '')) ?: $id;

		// `disabled` lives inside the provider block (next to vision/context_window); it hides this model
		// from the picker. Accept the KATA annotation stripped (`disabled`) or intact (`disabled@bool`).
		$disabled = $params['disabled'] ?? $params['disabled@bool'] ?? false;

		// `effort_choices` = the agentPrompt-only offering list surfaced as a per-model effort SUBMENU (scalar
		// or list). `default_effort` = the fixed canonical `effort:` (the pre-selected level / what a bare model
		// pick uses), or '' = provider default. The chosen level is stored as `provider_params['effort']` and
		// translated per-provider. Both are safe to send to the client (plain level strings).
		$effort_choices = $this->_parseEffortChoices($params['effort_choices'] ?? null);
		$default_effort = DevblocksPlatform::strLower(trim(strval($params['effort'] ?? '')));

		$has_thinking = $strings->toBool($params['has_thinking'] ?? false);

		// A thinking model nobody hand-declared choices for still gets a submenu, seeded from what the provider
		// knows about THAT MODEL. Without a list the picker has nothing to offer, _resolveEffort() returns ''
		// for anything the client posts, and the level is dropped on the floor between here and the wire.
		//
		// Deliberately getModelDefaults() and not getEffortLevels(): the latter answers for the provider, and
		// on an OpenAI-compatible endpoint that means a local Qwen would be offered `xhigh`/`max` off the GPT
		// scale and 400 when someone picked one. A model the provider doesn't recognize yields no list, so the
		// picker offers nothing and the record has to say -- an empty submenu beats a wrong one.
		//
		// Hand-declared `effort_choices` still wins; `has_thinking` gates the whole fallback.
		if(!$effort_choices && $has_thinking && $provider_ext && $provider_ext->supportsReasoning())
			$effort_choices = $this->_parseEffortChoices($defaults['effort_levels'] ?? null);

		// Prompt-cache lifetime (seconds) for the composer's cache TimeRing. The agentPrompt drives `llm.agent`,
		// where cache defaults ON (LlmAgentNode::_defaultCache), so gate on that here — the provider only reports
		// its rolling-tail TTL. null = caching off or this provider does no cacheable prompt (→ no ring).
		$cache_on = !array_key_exists('cache', $params) || $strings->toBool($params['cache']);
		$cache_ttl = ($cache_on && $provider_ext) ? $provider_ext->getCacheHintSeconds($params) : null;

		return [
			'provider' => $provider,
			'params' => $params,               // server-only (holds authentication) — never sent to the client
			'model' => $model,
			'label' => $label,
			'icon' => $icon,
			'vision' => $vision,
			'thinking' => $has_thinking,
			'ratings' => $this->_buildRatingMeters($params['ratings'] ?? null),
			'context_window' => $context_window,
			'context_ratio' => $context_ratio,
			'cache_ttl' => $cache_ttl,
			'disabled' => $strings->toBool($disabled),
			'effort_choices' => $effort_choices,
			'default_effort' => $default_effort,
		];
	}

	// Normalize an author-declared `effort_choices` (a `@list`/`@csv` array, a comma-joined scalar, or a single
	// scalar) into an ordered, de-duplicated array of lowercased level strings.
	private function _parseEffortChoices(mixed $raw) : array {
		if(is_array($raw)) {
			$parts = $raw;
		} elseif(is_string($raw) && '' !== trim($raw)) {
			$parts = explode(',', $raw);
		} else {
			return [];
		}

		$out = [];
		foreach($parts as $p) {
			$level = DevblocksPlatform::strLower(trim(strval($p)));
			if('' !== $level && !in_array($level, $out, true))
				$out[] = $level;
		}

		return $out;
	}

	// The ready-to-drop `inputs.llm` block for a picked catalog id: `{<provider>: <params bag>}`, with the
	// chosen effort merged into the params bag when the model resolves a non-empty effort.
	private function _getLlmBlock(string $id, string $effort='') : array {
		$models = $this->_getModels();

		if(!($m = $models[$id] ?? null) || !$m['provider'])
			return [];

		$params = $m['params'];
		if('' !== $effort)
			$params['effort'] = $effort;

		return [$m['provider'] => $params];
	}

	// The picked model's provider brand mark: a `cerb-icons` name or a hex background color. The catalog entry's
	// own `icon` already honors a `display:` override, so that wins for the icon; color has no such override and
	// comes straight from the provider.
	private function _getLlmProviderMark(string $id, string $which) : string {
		$models = $this->_getModels();

		if(!($m = $models[$id] ?? null) || !$m['provider'])
			return '';

		if('icon' === $which)
			return strval($m['icon'] ?? '');

		return DevblocksPlatform::services()->llm()->getProviderIconColor($m['provider']);
	}

	/*
	 * Automation-declared `/` commands from the element config, normalized and keyed by name.
	 *
	 *   commands:
	 *     compact:                          # bare key = opt into a built-in BY NAMING IT
	 *       description: Compact the thread
	 *     rewrite/flatten:                  # `rewrite/` = a new name this element DEFINES
	 *       description: Aggressively compact the thread
	 *       text@text: /compact hard
	 *     rewrite/cerb-dev:                 # kebab is fine — the key IS the command name
	 *       description: Use the cerb-dev skill
	 *       text@text: Read @cerb-dev/SKILL.md and follow it.
	 *
	 * A bare key mirrors `tools:` / `mounts:` / `models:` — it references something that already exists, so the
	 * key alone is the opt-in and the body only annotates it. `rewrite/` is the one form that earns a type
	 * prefix, because it mints a name the composer wouldn't otherwise have and needs a body to define it. (A
	 * generic `command/` prefix would wrongly suggest one built-in could be bound twice.)
	 *
	 * A `rewrite/` is expanded CLIENT-side, before submit — see the template's `onBeforeSubmit`. This layer is
	 * only what the composer OFFERS; what the agent ACTS on is the `llm.agent` node's own `commands:`, and the
	 * two are deliberately decoupled (same as `references:` vs `mounts:`). So an alias can shadow a built-in,
	 * and aliasing to a built-in the node didn't opt into just reaches the model as prose.
	 *
	 * @return array<string,array{name:string,type:string,label:string,description:string,text?:string}>
	 */
	private function _getCommands() : array {
		$commands = $this->_data['commands'] ?? [];

		// Nothing authored -> what the AGENT acts on, so the picker offers exactly the commands the node will
		// honor. Without this a script that declares nothing (because its agent declares everything) would show
		// an empty command picker while `/compact` quietly worked anyway.
		if(!is_array($commands) || !$commands)
			$commands = \Cerb\Agent\Config::forWorker($this->_getAgentWorkerId())['commands'] ?? [];

		if(!is_array($commands))
			return [];

		$results = [];

		foreach($commands as $key => $command) {
			if(!is_array($command))
				$command = [];

			list($type, $name) = array_pad(explode('/', strval($key), 2), 2, null);

			// No prefix: the key IS the name of a built-in being opted into.
			if(!$name) {
				$name = $type;
				$type = 'builtin';
			}

			// Anything else is a type we haven't built yet — drop it rather than render a dead menu item.
			if(!in_array($type, ['builtin', 'rewrite'], true))
				continue;

			$name = ltrim(trim(strval($name)), '/');

			// Must match the client's command-scope regex (`agentprompt.js` `_scopePathAt`), or it could be
			// offered but never recognized once typed.
			if(!preg_match('/^[a-z0-9_.-]+$/i', $name))
				continue;

			$entry = [
				'name' => $name,
				'type' => $type,
				'label' => strval($command['label'] ?? ''),
				'description' => strval($command['description'] ?? ''),
			];

			if('rewrite' == $type) {
				$text = strval($command['text'] ?? '');

				// Nothing to expand to.
				if('' === trim($text))
					continue;

				$entry['text'] = $text;
			}

			// Keyed by name so a duplicate replaces rather than double-listing.
			$results[$name] = $entry;
		}

		return $results;
	}

	/*
	 * `references:` — what `@` completes, OPT-IN. No block (or an empty one) means `@` completes nothing; there
	 * is no implicit source. Shape mirrors `llm.agent`'s `mounts:` — an open map keyed by handle. (It was
	 * originally shaped that way because a KATA `type/name:` name couldn't hold a `-` and volume handles are
	 * kebab; that constraint is gone, but the open map is still the right shape for a list of handles.)
	 *
	 *   references:
	 *     workers:
	 *     filesystems:
	 *       cerb-dev:
	 *       cerb-docs:
	 *
	 * DELIBERATELY DECOUPLED from the `llm.agent` node's `mounts:`. Declaring a volume here only offers its paths
	 * for completion — it does NOT mount anything, and this element never checks that the agent can actually read
	 * what was referenced. That's the point: a volume can be mounted for the agent (memory) without appearing in
	 * the human's autocomplete, and a reference that goes stale just becomes text the agent says it can't find.
	 *
	 * @return array{workers:bool, filesystems:string[]}
	 */
	private function _getReferences() : array {
		$refs = $this->_data['references'] ?? null;

		// Nothing authored -> `@` completes the volumes the AGENT actually mounts, plus workers. Naming them in
		// the script would be a second copy of the mount list, drifting the moment someone edits the agent.
		if(!is_array($refs)) {
			$mounts = \Cerb\Agent\Config::forWorker($this->_getAgentWorkerId())['mounts'] ?? [];

			if(is_array($mounts) && $mounts)
				$refs = ['workers' => [], 'filesystems' => $mounts];
		}

		if(!is_array($refs))
			return ['workers' => false, 'filesystems' => []];

		// A childless `workers:` key parses to [] — presence is the opt-in, so test the KEY, never the value.
		$workers = array_key_exists('workers', $refs);

		$filesystems = [];

		if(is_array($refs['filesystems'] ?? null)) {
			foreach(array_keys($refs['filesystems']) as $name) {
				$name = trim(strval($name));

				if('' !== $name)
					$filesystems[] = $name;
			}
		}

		return ['workers' => $workers, 'filesystems' => $filesystems];
	}

	// The declared filesystems resolved to `name => id`, dropping unknown/disabled volumes. Resolution is by
	// NAME (case-insensitive) exactly as `Cerb\Agent\Filesystem::fromSpecs()` does, so the two agree on what a
	// handle means.
	private function _getReferenceFilesystems() : array {
		$names = $this->_getReferences()['filesystems'];

		if(!$names)
			return [];

		$wanted = array_flip(array_map(fn($n) => DevblocksPlatform::strLower($n), $names));
		$out = [];

		foreach(\DAO_AgentFilesystem::getAll() as $fs) {
			if($fs->is_disabled)
				continue;

			if(array_key_exists(DevblocksPlatform::strLower($fs->name), $wanted))
				$out[$fs->name] = $fs->id;
		}

		return $out;
	}

	/*
	 * Subsequence match — the query's characters appear in the candidate in order, not necessarily contiguous,
	 * so `mountpatexa` matches `mount/path/example.md`. Case-insensitive; an empty query matches everything.
	 *
	 * This is a deliberate port of `CerbUI.editorCore.match(text, query, 'subsequence')`
	 * (`resources/js/cerb-ui/editor-core.js`) so the KATA editor's suggestion menus and the agentPrompt's `@`
	 * completion behave identically. Keep the two in step — if one gains scoring, the other should too.
	 */
	/*
	 * Relevance for one `@` reference candidate. Subsequence matching is intentionally loose — it has to be, for
	 * `mountpatexa` to find `mount/path/example.md` — but that looseness also lets `pricing` match
	 * `plugins/cerberusweb.timetracking.md` by scattering across it. Without a score those coincidences sort
	 * beside a real hit like `references/pricing.md`. Higher is better; ties keep collection order.
	 *
	 * Three tiers, because CONTIGUITY and WHERE the match lands are what a human actually means:
	 *   1. the whole term appears in the identifying tail (the filename)  — by far the strongest signal
	 *   2. the whole term appears somewhere in the path                   — right file family, wrong segment
	 *   3. scattered subsequence only                                     — ranked by how TIGHT the scatter is
	 *
	 * Within a tier: a match at the start of the tail beats one mid-word, a match after a separator beats one
	 * inside a word, an earlier match beats a later one, and a shorter path breaks the remaining ties (a deep
	 * path had more room to match by accident).
	 */
	private static function _scoreReference(string $ref, string $tail, string $term) : int {
		$ref_l = DevblocksPlatform::strLower($ref);
		$tail_l = DevblocksPlatform::strLower($tail);
		$q = DevblocksPlatform::strLower($term);
		$qlen = strlen($q);

		if(0 === $qlen)
			return 0;

		$score = 0;

		if('' !== $tail_l && false !== ($at = strpos($tail_l, $q))) {
			// Tier 1 — contiguous in the filename.
			$score = 10000 - $at * 10;

			if(0 === $at) {
				$score += 4000;                                    // the filename STARTS with the term
			} else if(false !== strpos('-_. /', $tail_l[$at - 1])) {
				$score += 2000;                                    // starts a word within the filename
			}

			// A filename that is essentially just the term (`pricing.md`) beats one that merely contains it.
			$score += intval(2000 * $qlen / max(1, strlen($tail_l)));

		} else if(false !== ($at = strpos($ref_l, $q)) && self::_isWordStart($ref_l, $at)) {
			// Tier 2 — contiguous AND starting a word, just in a directory segment rather than the filename.
			$score = 5000 - $at * 5;

		} else if(false !== ($acronym = self::_scoreAcronym($tail_l, $q))) {
			// Tier 2b — an initialism of the FILENAME: `ati` → `apple-touch-icon.png`. Preferred over the same
			// reading spread across directories (below), because that's the one people actually mean — and both
			// readings are usually available on a deep path, so without this the two land within noise of
			// each other.
			$score = 4400 + $acronym;

		} else if(false !== ($acronym = self::_scoreAcronym($ref_l, $q))) {
			// Tier 2c — an initialism of the whole path (`api/triggers/interaction`). Still deliberate enough to
			// beat an accidental mid-word run (`ati` inside `autom·ati·ons`), which falls to tier 3 below.
			$score = 3500 + $acronym;

		} else if(false !== ($at = strpos($ref_l, $q))) {
			// Tier 3 — contiguous but MID-WORD. Real, but weak evidence: it's the `automations` case.
			$score = 2200 - $at * 5;

		} else {
			// Tier 3 — scattered. Score the SHAPE of the scatter, not just its width. This is the rule IDEs use
			// (IntelliJ's camel-hump matcher, fzf's bonus table): a matched character is meaningful when it
			// CONTINUES A RUN or lands on a WORD BOUNDARY, and is usually coincidence when it's isolated
			// mid-word. That's what separates a real abbreviation from noise — `ati` → `apple-touch-icon` is
			// three isolated characters and should rank WELL because each starts a word, while `card` smeared
			// through `cerberusweb.core/templates/border.tpl` hits nothing but word interiors.
			//
			// A blanket "reject single-character runs" rule would get the second case right and the first wrong,
			// which is why the boundary test carries it instead of the run length alone.
			$prev_at = -2;
			$run = 0;
			$qi = 0;

			for($ti = 0, $tlen = strlen($ref_l); $ti < $tlen && $qi < $qlen; $ti++) {
				if($ref_l[$ti] !== $q[$qi])
					continue;

				if($ti === $prev_at + 1) {
					// Consecutive — worth more the longer the run gets.
					$run++;
					$score += 60 + 30 * $run;

				} else if(0 === $ti || false !== strpos('-_. /', $ref_l[$ti - 1])) {
					// Starts a word.
					$run = 1;
					$score += 80;

				} else {
					// Isolated, mid-word. This is the coincidence case.
					$run = 1;
					$score -= 15;
				}

				$prev_at = $ti;
				$qi++;
			}

			// Not actually a match (shouldn't happen — callers pre-filter) — sort it to the bottom.
			if($qi < $qlen)
				return -100000;

			// Keep the whole tier below a contiguous path match (tier 2), however well-shaped it is.
			$score = min(2500, $score);
		}

		// Shorter paths win the remaining ties: a longer path simply had more characters to match against.
		return $score - intval(strlen($ref_l) / 4);
	}

	// Does the character at $at begin a word? Start-of-string, or preceded by a separator. Path segments,
	// hyphens, underscores and dots all count — they're what a filename uses instead of camelCase.
	private static function _isWordStart(string $text, int $at) : bool {
		return 0 === $at || false !== strpos('-_. /', $text[$at - 1]);
	}

	/*
	 * Can the term be read as an INITIALISM of this path — every character starting a word, in order?
	 * `ati` → **a**pple-**t**ouch-**i**con. Returns a small bonus (favouring a compact acronym over one
	 * scattered across a deep path), or false when it doesn't read that way.
	 *
	 * This exists because a plain left-to-right subsequence scan is greedy and will happily consume the `a` of
	 * `assets` and the `t` of `asse-t-s` long before it reaches `apple-touch-icon`, scoring a genuine acronym
	 * as if it were noise. Testing the boundary interpretation separately sidesteps that without the cost of a
	 * full best-alignment search.
	 */
	private static function _scoreAcronym(string $ref, string $q) : int|false {
		$qi = 0;
		$qlen = strlen($q);
		$first = -1;
		$last = 0;

		for($ti = 0, $tlen = strlen($ref); $ti < $tlen && $qi < $qlen; $ti++) {
			if($ref[$ti] !== $q[$qi] || !self::_isWordStart($ref, $ti))
				continue;

			if($first < 0)
				$first = $ti;

			$last = $ti;
			$qi++;
		}

		if($qi < $qlen)
			return false;

		// Tighter initialisms first; a term whose letters begin words spread across a deep path is weaker.
		return max(0, 500 - ($last - $first) * 4);
	}

	// The identifying tail of a reference — the filename, or a directory's own name with its trailing slash.
	// Pinned by the menu so middle-truncation eats the shared prefix instead. A ref with no interior slash has
	// nothing to pin (the whole caption already IS the identifying part), so it returns ''.
	private static function _refTail(string $ref) : string {
		$trimmed = rtrim($ref, '/');
		$at = strrpos($trimmed, '/');

		if(false === $at)
			return '';

		return substr($ref, $at + 1);
	}

	private static function _matchSubsequence(string $text, string $query) : bool {
		if('' === $query)
			return true;

		$text = DevblocksPlatform::strLower($text);
		$query = DevblocksPlatform::strLower($query);

		$qi = 0;
		$qlen = strlen($query);

		for($ti = 0, $tlen = strlen($text); $ti < $tlen && $qi < $qlen; $ti++)
			if($text[$ti] === $query[$qi])
				$qi++;

		return $qi === $qlen;
	}

	// ── Server actions ──────────────────────────────────────────────────

	// Fork the session at a message seq (exclusive), returning that turn's text as a reusable `draft`
	// — mirrors the transcript viewer's fork-from-here (edit → variant, resubmit → regenerate).
	private function _promptAction_rewind() : bool {
		$this->_jsonHeader();

		$session_id = DevblocksPlatform::importGPC($_POST['session_id'] ?? null, 'string', '');
		$at_seq = DevblocksPlatform::importGPC($_POST['at_seq'] ?? null, 'integer', 0);

		try {
			if(!$session_id || !($session = DAO_LlmAgentSession::get($session_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid session.');

			if($at_seq <= 0)
				throw new Exception_DevblocksAjaxValidationError('Invalid fork point.');

			$draft = $this->_boundaryText($session_id, $session->provider, $at_seq);
			$fork_seq = max(1, $at_seq - 1);

			if(!($fork = DAO_LlmAgentSession::fork($session_id, $fork_seq)))
				throw new Exception_DevblocksAjaxValidationError('Failed to fork the session.');

			echo json_encode([
				'status' => true,
				'session_id' => $fork->uuid,
				'draft' => $draft,
			]);
			return true;

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(['status' => false, 'error' => $e->getMessage()]);
			return false;

		} catch(\Throwable $e) {
			DevblocksPlatform::logException($e);
			echo json_encode(['status' => false, 'error' => 'An unexpected error occurred.']);
			return false;
		}
	}

	/*
	 * `@` completion candidates for the typed term. Rides `invokePrompt` rather than a `c=ui` endpoint on
	 * purpose: that action already loads the continuation, enforces the actor check, and hands us this
	 * element's own config — so **the volume list comes from the automation, never from the request**. A client
	 * that asks for a filesystem the author didn't declare simply gets nothing back; there is no parameter to
	 * tamper with. Same trust model as `llm.agent`'s mounts being server-resolved and never model-controlled.
	 *
	 * Matching is server-side so a large volume doesn't have to ship its whole path list to the browser.
	 */
	private function _promptAction_references() : bool {
		$this->_jsonHeader();

		$term = DevblocksPlatform::importGPC($_POST['term'] ?? null, 'string', '');
		$limit = 25;

		$items = [];

		try {
			// Three kinds of candidate, all matched by the same subsequence pass against the `volume/path` string
			// the item would insert. Kept in separate buckets so CONTAINERS sort before leaves — the same
			// dirs-first order `ls`/`find` use in the terminal — rather than a volume landing below its own files.
			$volumes = [];
			$dirs = [];
			$files = [];

			foreach($this->_getReferenceFilesystems() as $fs_name => $fs_id) {
				// A volume is a drill-down: picking one inserts `@<volume>/` with NO trailing space, leaving the
				// caret mid-reference so the next pass completes inside it. Typing `@cerb` and getting the volume
				// beats scrolling a flat list of everything in it.
				if(self::_matchSubsequence($fs_name, $term))
					$volumes[$fs_name . '/'] = true;

				foreach(array_keys(\Cerb\Agent\Filesystem::listPaths($fs_id)) as $path) {
					$path = ltrim(strval($path), '/');
					$ref = $fs_name . '/' . $path;

					if(self::_matchSubsequence($ref, $term))
						$files[$ref] = true;

					// Directories are VIRTUAL here — there are no dir rows, only `agent_file.name` prefixes — so
					// synthesize each ancestor of this file. Deduped by key: many files share a prefix.
					$at = $fs_name;

					foreach(array_slice(explode('/', $path), 0, -1) as $segment) {
						$at .= '/' . $segment;

						if(self::_matchSubsequence($at, $term))
							$dirs[$at . '/'] = true;
					}
				}
			}

			// Containers keep the caret inside them (no trailing space) so you can keep drilling; a file is a
			// finished reference and gets the space.
			//
			// No `subtitle` on any of these — ext/size aren't worth it: ONE item carrying a subtitle switches the
			// whole menu to two-line rich rows (`editorCore`'s `richRows`), so a handful of files would push
			// everything else off screen. The icon is what distinguishes the rows.
			// `wide` asks editorCore for the wider menu panel WITHOUT the taller two-line rows — a deep path is a
			// long single line, and the composer often lives in a narrow right-hand agent pane. `captionTail` pins
			// the identifying last segment so truncation eats the MIDDLE of the path, never the filename: sibling
			// files share a long prefix, so a trailing ellipsis would hide the only part that tells them apart.
			foreach(array_keys($volumes) as $ref)
				$items[] = ['caption' => $ref, 'value' => '@' . $ref, 'icon' => 'folder', 'wide' => true];

			foreach(array_keys($dirs) as $ref)
				$items[] = ['caption' => $ref, 'value' => '@' . $ref, 'icon' => 'folder', 'wide' => true,
					'captionTail' => self::_refTail($ref)];

			foreach(array_keys($files) as $ref)
				$items[] = ['caption' => $ref, 'value' => '@' . $ref . ' ', 'icon' => 'file', 'wide' => true,
					'captionTail' => self::_refTail($ref)];

			// RANK before capping — subsequence matching is deliberately loose, so `pricing` also matches
			// `plugins/cerberusweb.timetracking.md` (p…r…i…c…i…n…g scattered across it) and without a score that
			// noise can outrank `references/pricing.md`, which contains the whole term. Sorting after collection
			// (rather than capping during it) is what makes the cap keep the BEST 25 instead of the first 25.
			//
			// usort is stable in PHP 8+, so equal scores keep insertion order — which is how the
			// volumes → dirs → files grouping survives an empty term (everything scores 0) and `@` still opens
			// on the drill-down containers.
			if('' !== $term) {
				$scores = [];

				foreach($items as $i => $item)
					$scores[$i] = self::_scoreReference(
						strval($item['caption']),
						strval($item['captionTail'] ?? ''),
						$term
					);

				uasort($scores, fn($a, $b) => $b <=> $a);

				$ranked = [];
				foreach(array_keys($scores) as $i)
					$ranked[] = $items[$i];

				$items = $ranked;
			}

			// Cap across EVERYTHING, not per volume or per kind — the menu is one list, and an unfiltered `@` on a
			// large volume would otherwise bury every later entry.
			$items = array_slice($items, 0, $limit);

			echo json_encode(['status' => true, 'items' => $items]);
			return true;

		} catch(\Throwable $e) {
			DevblocksPlatform::logException($e);
			// A completion failure is never worth breaking the composer over — return an empty menu.
			echo json_encode(['status' => true, 'items' => []]);
			return false;
		}
	}

	// The neutral text of the message at $at_seq (the fork boundary user turn) via the session provider.
	private function _boundaryText(string $session_id, string $provider_id, int $at_seq) : string {
		$llm = DevblocksPlatform::services()->llm();

		if(!($provider = $llm->getProvider($provider_id, [], false)))
			return '';

		foreach(DAO_LlmAgentMessage::getMessagesBySession($session_id, 0) as $model) {
			if($model->seq !== $at_seq)
				continue;

			$text = '';
			foreach($provider->convertToGenericMessage($model->data, $model->uuid)->getMessages() as $block)
				$text .= ($block['content'] ?? '');
			return $text;
		}

		return '';
	}

	private function _jsonHeader() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
	}
}
