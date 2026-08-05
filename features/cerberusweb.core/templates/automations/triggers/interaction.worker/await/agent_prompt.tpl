{$element_id = uniqid('agentprompt_')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-agent-prompt" id="{$element_id}">
	<div data-cerb-agentprompt-composer>
		{if $label}<h6>{$label}</h6>{/if}

		<div>
			<textarea class="cerb-ui-agentprompt--input"></textarea>
			<div data-cerb-agentprompt-hidden style="display:none;"></div>
		</div>
	</div>

	{* Takes the composer's place for the FIRST post-submit turn, when no transcript has re-rendered yet and its
	   own Stop button doesn't exist — otherwise a runaway agent has no brake until the first tool call lands.
	   Ephemeral by design: the next render replaces this whole element, and from then on the transcript owns Stop.
	   The composer stays hidden even after a click; it isn't ours to bring back, the refresh rebuilds it. *}
	<div data-cerb-agentprompt-busy style="display:none;margin-top:0.75em;">
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-agentprompt-stop-btn title="Stop the agent">
			<span class="cerb-icons cerb-icon-square"></span> Stop
		</button>
	</div>

	<script type="application/json" data-cerb-agentprompt-config>{$config_json nofilter}</script>
</div>

{if $is_automation_simulated|default:false}
<script type="application/json" data-cerb-agentprompt-simulated>true</script>
{/if}

{* Design-time builder preview: nothing is really in flight, so the composer must stay put on submit. The
   form-fill simulator is also "simulated" but mirrors runtime, so it's excluded. *}
{if ($is_automation_simulated|default:false) && !($is_automation_form_fill|default:false)}
<script type="application/json" data-cerb-agentprompt-inert>true</script>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
$(function() {
	// Initialize any agentPrompt container that hasn't been wired yet (idempotent across awaits).
	$('div.cerb-form-builder-prompt-agent-prompt').each(function() {
		var $prompt = $(this);

		if($prompt.data('cerbAgentpromptInit'))
			return;

		var el = $prompt.find('textarea.cerb-ui-agentprompt--input').get(0);
		var cfgEl = $prompt.find('script[data-cerb-agentprompt-config]').get(0);
		var isSimulated = !!$prompt.find('script[data-cerb-agentprompt-simulated]').length;
		var isInert = !!$prompt.find('script[data-cerb-agentprompt-inert]').length;

		if(!el || !cfgEl || !(window.CerbUI && CerbUI.AgentPrompt))
			return;

		var cfg;
		try { cfg = JSON.parse(cfgEl.textContent); } catch(e) { return; }

		$prompt.data('cerbAgentpromptInit', true);

		var $form = $prompt.closest('form.cerb-form-builder');
		var $hidden = $prompt.find('[data-cerb-agentprompt-hidden]');
		var $composer = $prompt.find('[data-cerb-agentprompt-composer]');
		var $busy = $prompt.find('[data-cerb-agentprompt-busy]');
		var sessionId = cfg.session_id || '';

		var mentionSource = (CerbUI.MarkdownEditor && CerbUI.MarkdownEditor.mentionSource)
			? CerbUI.MarkdownEditor.mentionSource() : null;

		var commandsByName = {};
		(cfg.commands || []).forEach(function(c) {
			if(c && c.name) commandsByName[String(c.name).toLowerCase()] = c;
		});

		// A `rewrite/` command is a CLIENT-side text expansion: the leading `/name` token is replaced by the
		// author's declared text and everything typed after it is preserved, so `/cerb-dev fix the login bug`
		// sends the skill's instructions followed by the task. `llm.agent` never learns the alias existed.
		// Only when the command LEADS the message — "explain /flatten" is conversation, same rule the node uses.
		function expandCommand(text) {
			var m = String(text == null ? '' : text).match(/^(\s*)\/([a-z0-9_.-]+)(.*)$/is);

			if(!m) return text;

			var command = commandsByName[m[2].toLowerCase()];

			if(!command || command.type !== 'rewrite') return text;

			var expansion = String(command.text || '').replace(/\s+$/, '');
			var rest = m[3].replace(/^\s+/, '');

			if(rest === '') return m[1] + expansion;

			// A one-liner splices in place (`/flatten now` → `/compact hard now`); a multi-line expansion gets a
			// blank line before the remainder so the instructions and the task don't run together.
			return m[1] + expansion + ((expansion.indexOf('\n') === -1) ? ' ' : '\n\n') + rest;
		}

		// Fire a server-side prompt action against this await (fork on model change, rewind, /command).
		function invokePrompt(action, extra, cb, options) {
			var fd = new FormData();
			fd.set('c', 'profiles');
			fd.set('a', 'invoke');
			fd.set('module', 'automation');
			fd.set('action', 'invokePrompt');
			fd.set('prompt_key', cfg.prompt_key);
			fd.set('prompt_action', action);
			fd.set('continuation_token', cfg.continuation_token);
			Object.keys(extra || {}).forEach(function(k) { fd.set(k, extra[k]); });
			genericAjaxPost(fd, null, null, cb, options);
		}

		// `@` file references. Matched SERVER-side: the declared volumes live on the element's own config, so
		// there's no volume parameter here to widen — asking for something the author didn't declare just
		// returns nothing. Debounced so a fast typist doesn't fire a request per keystroke, and sequenced so a
		// slow response can't replace a newer menu.
		var _refTimer = null;
		var _refSeq = 0;

		function fileSource(ctx) {
			return new Promise(function(resolve) {
				if(_refTimer) clearTimeout(_refTimer);

				var seq = ++_refSeq;

				_refTimer = setTimeout(function() {
					invokePrompt('references', { term: ctx.prefix || '' }, function(json) {
						if(seq !== _refSeq) return resolve([]);   // superseded while in flight
						resolve((json && json.items) ? json.items : []);
					}, {
						// genericAjaxPost only calls `done` on success, so without this a failed lookup would
						// leave the promise pending and the menu spinning forever. An empty menu is the right
						// failure mode for a typing aid.
						fail: function() { resolve([]); }
					});
				}, 120);
			});
		}

		// Stop a running agent: raise an out-of-band interrupt flag for this session. The running llm.agent node
		// consumes it at its next tree-safe boundary (after a complete tool tuple, before the next turn) and yields
		// control back here, which re-renders a fresh composer.
		function interruptAgent() {
			var fd = new FormData();
			fd.set('c', 'profiles');
			fd.set('a', 'invoke');
			fd.set('module', 'automation');
			fd.set('action', 'interruptAgent');
			fd.set('continuation_token', cfg.continuation_token);
			fd.set('session_id', sessionId);
			genericAjaxPost(fd, null, null, function() {});
		}

		function addHidden(name, value) {
			$('<input type="hidden">')
				.attr('name', name)
				.val(value == null ? '' : value)
				.appendTo($hidden);
		}

		var ap = new CerbUI.AgentPrompt(el, {
			placeholder: cfg.placeholder,
			models: cfg.models || [],
			defaultModel: cfg.default_model || null,
			defaultEffort: cfg.default_effort || '',
			contextTokens: cfg.context_tokens || 0,
			cacheElapsed: cfg.cache_elapsed,

			images: cfg.images !== false,
			autofocus: !isSimulated,

			onAutocomplete: function(ctx) {
				// `/command` — declared on the element config (client-side filter, no round trip).
				if(ctx.path[0] === '/') {
					var term = (ctx.prefix || '').toLowerCase();
					return (cfg.commands || [])
						.filter(function(c) { return c.name.toLowerCase().indexOf(term) === 0; })
						.map(function(c) {
							return {
								caption: '/' + c.name,
								value: '/' + c.name + ' ',
								subtitle: c.description || c.label || null,
								icon: 'zap'
							};
						});
				}

				// `@reference` — OPT-IN via the element's `references:` block. No block means `@` completes
				// nothing at all (there is no implicit source); an author lists `workers:` and/or
				// `filesystems:` to turn each on. Both can be declared, so the menu merges them.
				if(ctx.path[0] === '@') {
					var refs = cfg.references || {};
					var sources = [];

					if(refs.workers && mentionSource)
						sources.push(Promise.resolve(mentionSource(ctx)));

					if(refs.files)
						sources.push(fileSource(ctx));

					if(!sources.length)
						return [];

					return Promise.all(sources).then(function(lists) {
						return lists.reduce(function(all, items) {
							return all.concat(Array.isArray(items) ? items : []);
						}, []);
					});
				}

				return [];
			},

			// The model dropdown is inert until submit — changing it only updates the local selection
			// (no server round-trip / no live fork). The chosen model rides to the server on submit; a
			// genuine provider change is forked+rewritten once, server-side, by the llm.agent node.

			// Expand a leading `rewrite/` command before the turn is serialized. Runs in the component (not
			// below in onSubmit) so the expansion's own `@` references are extracted as mentions too.
			onBeforeSubmit: expandCommand,

			// Serialize the turn into the form's prompts[] and submit the interaction.
			onSubmit: function(payload) {
				$hidden.empty();

				addHidden('prompts[' + cfg.var + '][text]', payload.text);
				addHidden('prompts[' + cfg.var + '][model]', payload.model_id || '');
				addHidden('prompts[' + cfg.var + '][effort]', payload.effort || '');

				// Just the resource uris — the server looks each up (authoritative mime + validation) and writes
				// the `<name>__images` shape.
				(payload.attachments || []).forEach(function(a) {
					addHidden('prompts[' + cfg.var + '][images][]', a.uri || '');
				});

				// Post each mention back in its literal `@` form; the server parses and resolves it (a file
				// reference becomes an `agent_file` record pair, an unknown one is simply dropped).
				(payload.mentions || []).forEach(function(m) {
					var token;

					if(m.path) token = '@' + m.path;                       // @<volume>/<path>
					else if(m.context) token = '@' + m.context + ':' + m.id; // @<type>:<id>
					else token = '@' + m.handle;                            // @<worker>

					addHidden('prompts[' + cfg.var + '][mentions][]', token);
				});

				// Hide the composer for the turn and put a Stop button in its place — a submitted prompt isn't
				// editable, and leaving it up invites typing into a field whose next render throws the text away.
				// (The hidden `prompts[]` inputs above still post: `display:none` doesn't exclude a field from
				// FormData — only `disabled` does.) In the design-time preview (inert) nothing is actually
				// running, so leave the composer put.
				if(!isInert) {
					$composer.hide();
					$busy.show();

					// Tell any sibling transcript what we just posted so it can echo the turn immediately. An async
					// turn suspends on `await:queue:`, whose response is an invisible poll marker — the panel leaves
					// the transcript untouched for the whole turn — so without this the message just sent is nowhere
					// on screen until the agent answers. We only announce; the transcript owns the rendering (it has
					// the view/layout config and the identity), and does its own round trip.
					$form.trigger('cerb-agentprompt-submitted', [{
						text: payload.text,
						images: (payload.attachments || []).map(function(a) { return a.uri || ''; })
					}]);
				}

				$form.triggerHandler('cerb-form-builder-submit');
			}
		});

		// Stop, from the button that replaced the composer. Confirms in place ("Stopping…", disabled) exactly like
		// the transcript's own Stop — the interrupt is only honored at the node's next tree-safe boundary, so the
		// click needs visible acknowledgement or it reads as a dead button. Deliberately does NOT restore the
		// composer: the yield re-renders this whole element with a fresh one.
		$busy.on('click', '[data-cerb-agentprompt-stop-btn]', function() {
			var btn = this;

			if(btn.disabled)
				return;

			btn.disabled = true;
			btn.title = 'Stopping the agent at the next safe point…';
			btn.innerHTML = '<span class="cerb-icons cerb-icon-square"></span> Stopping…';

			interruptAgent();
		});

		// The submit never made it (a 500, a dropped connection) and no render is coming to rebuild us. Undo the
		// in-flight state so the reader can retry or rewrite, instead of staring at a dead Stop button. The
		// hidden `prompts[]` inputs are left intact on purpose — panel.tpl's Retry re-posts this same turn, and
		// typing a fresh one rebuilds them from scratch on the next submit.
		//
		// Bound ONCE per form (the form outlives every render of this element) and resolved against the live DOM,
		// so renders don't stack handlers holding detached composers.
		if($form.length && !$form.data('cerbAgentpromptFailBound')) {
			$form.data('cerbAgentpromptFailBound', true);

			$form.on('cerb-interaction-submit-failed', function() {
				$form.find('[data-cerb-agentprompt-busy]').hide();
				$form.find('[data-cerb-agentprompt-composer]').show();
				$form.find('textarea.cerb-ui-agentprompt--input').last().trigger('focus');
			});
		}

		// Fork-from-here: a sibling transcript can ask the prompt to rewind to a message seq (seed draft).
		//
		// Bound ONCE per form, like the submit-failed handler above. The form outlives every render, so a
		// per-render bind stacks a handler — and therefore an extra rewind POST — for each render the
		// conversation has seen. Latent while nothing emits this event; it stops being latent the moment a
		// transcript gains a per-turn seq to fork from.
		if(!$form.data('cerbAgentpromptRewindBound')) {
		$form.data('cerbAgentpromptRewindBound', true);

		$form.on('cerb-agentprompt-rewind', function(e, data) {
			data = data || {};
			var atSeq = data.at_seq || e.at_seq || 0;
			if(!atSeq) return;

			invokePrompt('rewind', { session_id: sessionId, at_seq: atSeq }, function(json) {
				if(json && json.status) {
					if(json.session_id) sessionId = json.session_id;
					ap.seedDraft(json.draft || '');
					$form.trigger($.Event('cerb-agentprompt-session-changed', { session_id: sessionId }));
				}
			});
		});
		}

		if(cfg.default_value)
			ap.setValue(cfg.default_value);
	});
});
{/literal}
</script>
