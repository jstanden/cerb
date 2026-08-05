	<div class="cerb-uiref-component" id="agentprompt">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-bot"></span>AgentPrompt</div>

		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">An <b>agentic chat input</b> (a fourth editor-core-family member) &mdash; a <code>&lt;textarea&gt;</code> with <b>grow-as-you-type</b>, caret-anchored autocomplete for <code>@</code>-context mentions and automation-defined <code>/</code>-commands, <b>image paste</b> &rarr; attachments, <b>up-arrow</b> prompt history, and a footer with <b>model selection</b>, a pre-compaction <b>context progress bar</b>, and a <b>Send</b> button. <b>Enter</b> submits, <b>Shift+Enter</b> is a newline. No particular syntax &mdash; the mirror only tints inserted reference tokens (<code>@record_type:id</code>, <code>worker:id</code>) and a leading <code>/command</code>. The component owns the input behaviors; the host supplies content via <code>onAutocomplete</code> / <code>onSubmit</code> / <code>onModelChange</code>.</div>
		</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<textarea id="uiref-agentprompt"></textarea>
				<div class="cerb-uiref-result">Last turn &middot; <b id="uiref-agentprompt-out">(none)</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Author just the textarea — the component builds its shell + footer around it. --&gt;
&lt;textarea id="prompt"&gt;&lt;/textarea&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ap = new CerbUI.AgentPrompt(document.getElementById('prompt'), {
	placeholder: 'Message the agent…',
	models: [                                        // the catalog (drives the dropdown, auth, vision, progress window)
		{ id: 'sonnet', label: 'Claude Sonnet 5', provider: 'anthropic', model: 'claude-sonnet-5', icon: 'logo-claude', vision: true,  context_window: 200000 },
		{ id: 'gpt4o',  label: 'GPT-4o',          provider: 'openai',    model: 'gpt-4o',          icon: 'logo-openai', vision: true,  context_window: 128000 },
	],
	defaultModel: 'sonnet',
	onAutocomplete: (ctx) => {                        // ctx.path[0] is '@' (mentions) or '/' (commands)
		if(ctx.path[0] === '@') return myMentionItems(ctx.prefix);   // records / workers / a RecordChooser panel
		if(ctx.path[0] === '/') return myCommandItems(ctx.prefix);   // automation-declared commands
		return [];
	},
	onModelChange: (model, { providerChanged }) => {  // host auto-forks the session on a provider change
		if(providerChanged) { /* llm.forkSession(...) */ }
	},
	onSubmit: ({ text, model_id, attachments, mentions }) => {
		// feed llm.agent: inputs.llm = { &lt;provider&gt;: { model, authentication } }, messages = text + resolved refs + attachments
	},
});
ap.setContextUsage(48000);                            // update the progress bar (token_est vs the model's window){/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-agentprompt');
	const out = document.getElementById('uiref-agentprompt-out');
	if(!el || !(window.CerbUI && CerbUI.AgentPrompt))
		return;

	{literal}
	// Stub autocomplete for the gallery (no backend): `@` → record types + worker mentions, `/` → commands.
	const stubAutocomplete = function(ctx) {
		if(!ctx) return [];
		const term = (ctx.prefix || '').toLowerCase();

		if(ctx.path[0] === '@') {
			const workers = [
				{ name: 'Kina Halpue', handle: '@khalpue' },
				{ name: 'Janey Youve', handle: '@jyouve' },
				{ name: 'Karl Kwota',  handle: '@kkwota' },
				{ name: 'Milo Dade',   handle: '@mdade' },
			];
			const types = [
				{ label: 'Ticket',       ctx: 'ticket' },
				{ label: 'Organization', ctx: 'org' },
				{ label: 'Contact',      ctx: 'contact' },
			];
			const items = [];
			// Worker @mentions keep the whole @handle (@jeff), no conversion.
			workers.filter(w => w.name.toLowerCase().startsWith(term) || w.handle.toLowerCase().startsWith('@' + term))
				.forEach(w => items.push({ caption: w.name, value: w.handle + ' ', handle: w.handle, subtitle: 'Worker',
					avatar: { label: w.name, seed: 'worker:' + w.handle } }));
			// Record types would open a CerbUI.RecordChooser; the gallery just seeds `@type:`.
			types.filter(t => t.label.toLowerCase().startsWith(term))
				.forEach(t => items.push({ caption: t.label, value: '@' + t.ctx + ':', icon: 'collection', subtitle: 'Opens record chooser' }));
			return items;
		}

		if(ctx.path[0] === '/') {
			const cmds = [
				{ name: 'summarize', desc: 'Summarize the thread so far' },
				{ name: 'handoff',   desc: 'Escalate to a human' },
			];
			return cmds.filter(c => c.name.startsWith(term))
				.map(c => ({ caption: '/' + c.name, value: '/' + c.name + ' ', subtitle: c.desc, icon: 'zap' }));
		}
		return [];
	};

	const ap = new CerbUI.AgentPrompt(el, {
		placeholder: 'Message the agent…  (type @ or /, Shift+Enter for a newline)',
		minHeight: 72,
		models: [
			{ id: 'sonnet', label: 'Claude Sonnet 5', provider: 'anthropic', model: 'claude-sonnet-5',  icon: 'logo-claude', vision: true, context_window: 200000 },
			{ id: 'opus',   label: 'Claude Opus 4.8',  provider: 'anthropic', model: 'claude-opus-4-8',  icon: 'logo-claude', vision: true, context_window: 200000 },
			{ id: 'gpt4o',  label: 'GPT-4o',           provider: 'openai',    model: 'gpt-4o',           icon: 'logo-openai', vision: true, context_window: 128000 },
		],
		defaultModel: 'sonnet',
		contextTokens: 64000,
		onAutocomplete: stubAutocomplete,
		onModelChange: function(model, meta) {
			if(out) out.textContent = 'model → ' + model.label + (meta.providerChanged ? ' (provider changed → would fork)' : '');
		},
		onSubmit: function(payload) {
			if(out) out.textContent = '[' + payload.model_id + '] ' + payload.text
				+ (payload.attachments.length ? ' (+' + payload.attachments.length + ' image)' : '');
		},
	});
	{/literal}
})();
</script>
