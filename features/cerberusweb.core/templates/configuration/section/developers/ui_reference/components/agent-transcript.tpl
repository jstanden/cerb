	<div class="cerb-uiref-component" id="agent-transcript">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-bot-message"></span>AgentTranscript</div>

		<p class="cerb-u-text-muted" style="margin:0 0 1em;">A read-only transcript of an LLM agent session — the read-side counterpart to <a href="#agentprompt">cerb-ui-agentprompt</a>. You author <b>bare, semantic markup</b> and <code>CerbUI.AgentTranscript.enhance(scope)</code> builds the chrome: avatars, the sender header, the per-turn <a href="#toolbar">CerbUI.Toolbar</a> (with a built-in copy action), the Markdown/Text switcher, the indented sub-thread of thinking / tool-call bubbles, and JSON tool payloads promoted to a read-only <a href="#jsoneditor">CerbUI.JsonEditor</a>. Structure attributes are <code>data-cerb-transcript-*</code>; values are plain <code>data-role</code> / <code>data-seq</code> / <code>data-tool-name</code>. Authored nodes are <b>moved, never cloned</b> — so a <code>data-cerb-peek</code> link keeps its handlers.</p>

		<p class="cerb-u-text-muted" style="margin:0 0 1em;">The <code>view</code> option takes <code>'toggle'</code> (offer the reader a Markdown/Text switcher), or <code>'markdown'</code> / <code>'text'</code> to pin the view and render no switcher. Text swaps each turn's rendered body for its raw <code>data-cerb-transcript-source</code>; a turn without one keeps its body. Pass <code>viewStorageKey</code> to remember the reader's pick (<code>'toggle'</code> only). <b>The default is <code>'markdown'</code></b> — the switcher is a reader affordance a host asks for, not chrome every transcript should grow. The example above passes <code>'toggle'</code>, as does Setup &rarr; Developers.</p>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-agent-transcript" data-cerb-agent-transcript data-cerb-uiref-transcript>
					<div data-cerb-transcript-turn data-role="user" data-seq="1">
						<span data-cerb-transcript-avatar data-avatar="Jane Doe" data-avatar-seed="worker:7"></span>
						<div data-cerb-transcript-sender>
							<span class="cerb-ui-agent-transcript--sender-name">Jane Doe</span> <span class="cerb-u-text-muted">Support</span>
						</div>
						<div data-cerb-transcript-meta title="Tue, 14 Jul 2026 09:12">2 hours ago</div>
						<div data-cerb-transcript-body><div class="commentBodyHtml">How do I reset my password?</div></div>
						<pre data-cerb-transcript-source>How do I reset my password?</pre>
					</div>

					<div data-cerb-transcript-turn data-role="assistant" data-seq="2">
						<span data-cerb-transcript-avatar data-avatar-icon="logo-claude" data-avatar-seed="agent:demo" data-avatar-color="#d97757"></span>
						<div data-cerb-transcript-sender>
							<span class="cerb-ui-agent-transcript--sender-name">Agent</span> <span class="cerb-ui-pill">anthropic &middot; claude-opus-4-8</span>
						</div>
						<div data-cerb-transcript-meta title="Tue, 14 Jul 2026 09:12">2 hours ago</div>
						<div data-cerb-transcript-aside>
							<div class="cerb-ui-chip" style="flex:0 0 auto;">
								<div class="cerb-ui-chip--head">Tokens</div>
								<div><div class="cerb-ui-chip--label">In</div><div class="cerb-ui-chip--value">1,204</div></div>
								<div><div class="cerb-ui-chip--label">Out</div><div class="cerb-ui-chip--value">86</div></div>
							</div>
						</div>
						<div data-cerb-transcript-body><div class="commentBodyHtml">You can reset it from <b>Settings &rarr; Security</b>.</div></div>
						<pre data-cerb-transcript-source>You can reset it from **Settings &rarr; Security**.</pre>

						<div data-cerb-transcript-thinking>
							<div class="commentBodyHtml">The user wants a password reset. Check the docs tool first.</div>
						</div>

						<div data-cerb-transcript-tool data-tool-name="search_docs" data-tool-id="toolu_01">
							<textarea data-cerb-transcript-tool-params spellcheck="false">{literal}{
    "query": "reset password"
}{/literal}</textarea>
							<pre data-cerb-transcript-tool-result>Password reset lives under Settings &rarr; Security.</pre>
						</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;div class="cerb-ui-agent-transcript" data-cerb-agent-transcript&gt;
    &lt;div data-cerb-transcript-turn data-role="user" data-seq="1"&gt;
        &lt;span data-cerb-transcript-avatar data-avatar="Jane Doe" data-avatar-seed="worker:7"&gt;&lt;/span&gt;
        &lt;div data-cerb-transcript-sender&gt;
            &lt;span class="cerb-ui-agent-transcript--sender-name"&gt;Jane Doe&lt;/span&gt;
        &lt;/div&gt;
        &lt;div data-cerb-transcript-meta title="Tue, 14 Jul 2026 09:12"&gt;2 hours ago&lt;/div&gt;
        &lt;div data-cerb-transcript-body&gt;&lt;div class="commentBodyHtml"&gt;How do I reset my password?&lt;/div&gt;&lt;/div&gt;
        &lt;pre data-cerb-transcript-source&gt;How do I reset my password?&lt;/pre&gt;
    &lt;/div&gt;

    &lt;div data-cerb-transcript-turn data-role="assistant" data-seq="2"&gt;
        &lt;span data-cerb-transcript-avatar data-avatar-icon="logo-claude" data-avatar-seed="agent:demo"&gt;&lt;/span&gt;
        &lt;div data-cerb-transcript-sender&gt;
            &lt;span class="cerb-ui-agent-transcript--sender-name"&gt;Agent&lt;/span&gt;
            &lt;span class="cerb-ui-pill"&gt;anthropic&lt;/span&gt;
        &lt;/div&gt;

        &lt;!-- Always visible; the hover toolbar fades in to its LEFT so this never shifts --&gt;
        &lt;div data-cerb-transcript-aside&gt;&lt;div class="cerb-ui-chip"&gt;…&lt;/div&gt;&lt;/div&gt;

        &lt;!-- Caller-supplied turn actions; the component prepends its built-in copy item --&gt;
        &lt;ul class="cerb-ui-toolbar" data-cerb-transcript-turn-toolbar&gt;
            &lt;li data-value="fork-from-here" data-icon="hierarchy" title="Fork from here"&gt;&lt;/li&gt;
        &lt;/ul&gt;

        &lt;div data-cerb-transcript-body&gt;&lt;div class="commentBodyHtml"&gt;You can reset it from …&lt;/div&gt;&lt;/div&gt;
        &lt;pre data-cerb-transcript-source&gt;You can reset it from **Settings** …&lt;/pre&gt;
        &lt;div data-cerb-transcript-images&gt;&lt;img src="…"&gt;&lt;/div&gt;

        &lt;!-- Thinking + tool calls become an indented sub-thread of bubbles, in author order --&gt;
        &lt;div data-cerb-transcript-thinking&gt;&lt;div class="commentBodyHtml"&gt;…&lt;/div&gt;&lt;/div&gt;

        &lt;!-- &lt;textarea&gt; = JSON (→ JsonEditor); &lt;pre&gt; = plain text --&gt;
        &lt;div data-cerb-transcript-tool data-tool-name="search_docs" data-tool-id="toolu_01"&gt;
            &lt;textarea data-cerb-transcript-tool-params&gt;{"query": "reset password"}&lt;/textarea&gt;
            &lt;pre data-cerb-transcript-tool-result&gt;Password reset lives under Settings.&lt;/pre&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;script&gt;
CerbUI.AgentTranscript.enhance(document, undefined, {
    onTurnAction: function(value, ctx) {         // ctx = {turn, role, seq, source, …}
        if('fork-from-here' !== value) return false;
        forkAt(ctx.seq);
        return true;                             // truthy = handled
    }
});
&lt;/script&gt;{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// ── Markup contract ─────────────────────────────────────────────────────────────
// Structure is data-cerb-transcript-*; values are plain data-*. Classes are CSS only —
// the component never keys off them. Authored nodes are MOVED, never cloned.
//
//   [data-cerb-agent-transcript]          root (add .cerb-ui-agent-transcript)
//   [data-cerb-transcript-turn]           one turn
//        data-role="user|assistant|system"    NOT data-cerb-transcript-role (taken)
//        data-seq="43"                        opaque cursor, surfaced as ctx.seq
//   [data-cerb-transcript-avatar]         a CerbUI.Avatar spec (data-avatar*); sized by the component
//   [data-cerb-transcript-sender]         name + sub; .cerb-ui-agent-transcript--sender-name enlarges the name
//   [data-cerb-transcript-meta]           timestamp; moved to the header's right, beside the toolbar
//   [data-cerb-transcript-aside]          always-visible right side (token chips)
//   [data-cerb-transcript-turn-toolbar]   a bare CerbUI.Toolbar &lt;ul&gt;; copy is prepended
//   [data-cerb-transcript-body]           rendered body (repeats per message, author order)
//   [data-cerb-transcript-source]         raw markdown — drives Text view AND copy
//   [data-cerb-transcript-images]         &lt;img&gt; row
//   [data-cerb-transcript-thinking]       sub-thread bubble
//   [data-cerb-transcript-tool]           sub-thread bubble
//        data-tool-name  data-tool-id
//        data-icon="search"               cerb-icons name; default hammer (brain for thinking).
//                                         An ACTIVE node's glyph gets cerb-u-anim-pulse.
//        data-summary-active / -past      phrasing for 'summary' mode
//        data-display="raw|summary|hide"  overrides the component default for THIS node
//     [data-cerb-transcript-tool-label]   optional; may be a peek-trigger &lt;a&gt;
//     [data-cerb-transcript-tool-params]  &lt;textarea&gt; = JSON, &lt;pre&gt; = text
//     [data-cerb-transcript-tool-result]  …its absence is what makes a call "active"
//        data-content-type="application/json"  forces JSON either way
//
// Modifiers: --banded (compact, no avatars) · --sample (dimmed placeholder data)

// ── Construct ───────────────────────────────────────────────────────────────────
CerbUI.AgentTranscript.enhance(scope, selector, opts);  // batch; scope = el | selector | document
const t = new CerbUI.AgentTranscript(el, opts);         // one
CerbUI.AgentTranscript.from(el);                        // the instance for an element

// ── Options (defaults shown) ────────────────────────────────────────────────────
new CerbUI.AgentTranscript(el, {
    view:             'markdown',    // 'toggle' (Markdown/Text switcher) | 'markdown' | 'text'
    viewStorageKey:   null,          // localStorage key remembering the reader's pick ('toggle' only)

    controls:         true,          // the transcript-level controls row (just the view switcher today)

    copy:             true,          // built-in per-turn copy (needs a [data-cerb-transcript-source])
    copyRoles:        ['assistant'], // …only on these roles; null = any role with a source

    avatars:          true,
    avatarSize:       44,
    bubbleAvatarSize: 32,

    thinking:         'summary',     // 'raw' | 'summary' | 'hide'  — DEFAULT-DENY, see above
    tools:            'summary',     // 'raw' | 'summary' | 'hide'  — DEFAULT-DENY, see above

    expand:           'latest',      // which 'raw' bubbles start open:
                                     //   'latest' = the newest agent turn's only (a live conversation)
                                     //   'all'    = every one (reviewing a whole session)
                                     //   'none'   = all folded behind their summaries

    json:             true,          // promote JSON payloads to a read-only CerbUI.JsonEditor
    jsonSniff:        true,          // also sniff a &lt;pre&gt; whose text parses as JSON
    jsonMaxBytes:     262144,        // …but never sniff beyond this
    jsonOpts:         { readOnly: true, minLines: 1, maxLines: 25 },  // → CerbUI.JsonEditor

    toolbarOpts:      {},            // → each turn's CerbUI.Toolbar (which defaults to tiny:true — small muted
                                     //   icons, no strip chrome; override it here)
    onTurnAction:     null,          // (value, ctx) => truthy = handled, suppresses the :turn-action event
    onEnhance:        null,          // (turnEl, ctx) — host wiring, after the turn's chrome is built
});

// ── Methods ─────────────────────────────────────────────────────────────────────
t.getView();                 // 'markdown' | 'text'
t.setView('text');
t.getTurns();                // [{el, role, seq}, …]
t.getTurnSource(turnEl);     // that turn's raw markdown
t.appendTurn(htmlOrEl);      // parse + enhance + append; scroll pinning is the host's job
t.refresh();                 // idempotent — enhances any turns added since
t.destroy();

// ── Events (bubbling; payload in e.detail) ──────────────────────────────────────
// cerb-ui-agent-transcript:ready        {transcript}
// cerb-ui-agent-transcript:view         {view}
// cerb-ui-agent-transcript:turn-added   {turn}
// cerb-ui-agent-transcript:turn-action  {value, turn, role, seq, source, item, sourceLi}
//                                       …fires only when onTurnAction returned falsy{/literal}</pre>
			</div>
		</div>

		<p class="cerb-u-text-muted" style="margin:1.5em 0 1em;">How much of the agent's work to surface is configurable, since not every audience should see every tool call. The <code>thinking</code> and <code>tools</code> options each take <code>'summary'</code> (the bubble holds only its summary line), <code>'raw'</code> (that same summary, plus a disclosure revealing the <b>actual</b> request/response payloads the agent sent and got back &mdash; the tool's real name becomes the eyebrow over its params, matching <code>RESULT</code> below it), or <code>'hide'</code>. A <code>raw</code> bubble reads identically open or closed, so the summary never disappears. <code>expand</code> seeds which start open: <code>'latest'</code> (the newest agent turn's only &mdash; what a live interaction wants), <code>'all'</code> (reviewing a whole session, like Setup &rarr; Developers), or <code>'none'</code>. A node can override the default with <code>data-display</code>, so a host can gate one tool differently from the rest.<br><br><b><code>'summary'</code> is the default, deliberately.</b> A tool's raw params and results are whatever the agent fetched &mdash; routinely records the reader isn't cleared for &mdash; so surfacing them is <b>opt-in</b> at both layers: a host that passes nothing gets summaries, and an <code>llmTranscript:</code> element that omits <code>tools:</code> does too. An unrecognized value normalizes to <code>'summary'</code> as well, so a typo can't become a disclosure. The example above opts in because the UI Reference is a superuser page; so does Setup &rarr; Developers, where seeing the raw exchange is the point.<br><br>A summarized node declares <b>two</b> phrasings &mdash; <code>data-summary-active</code> and <code>data-summary-past</code> &mdash; and the component picks by state. A node is <b>active</b> while still running (a tool with no <code>data-cerb-transcript-tool-result</code> yet; a thinking block with nothing to show), so a live transcript reads &ldquo;Searching the documentation&rdquo; / &ldquo;Thinking&hellip;&rdquo; and settles to &ldquo;Searched the documentation&rdquo; / &ldquo;Thought for 5 seconds&rdquo;. Either may be omitted &mdash; it falls back to the other, then to a generic verb (&ldquo;Working&rdquo; / &ldquo;Worked&rdquo;, pairing with thinking's &ldquo;Thinking&rdquo; / &ldquo;Thought&rdquo;), never the machine tool name. A <b>duration is the caller's to compute</b> &mdash; the component has no clock.<br><br><code>data-icon</code> names the work (<code>search</code>, <code>book-open</code>); <code>hammer</code> / <code>brain</code> are the fallbacks, and one icon covers both states. An <b>active</b> node's glyph gets <a href="#effects"><code>cerb-u-anim-pulse</code></a>, so a live transcript reads as in-progress at a glance rather than only in the wording. Below: <code>{literal}{tools:'summary'}{/literal}</code> with the last call still running (watch its icon), and a thinking block summarized via <code>data-display</code>.</p>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-agent-transcript" data-cerb-agent-transcript data-cerb-uiref-transcript-summary>
					<div data-cerb-transcript-turn data-role="assistant">
						<span data-cerb-transcript-avatar data-avatar-icon="logo-claude" data-avatar-seed="agent:demo" data-avatar-color="#d97757"></span>
						<div data-cerb-transcript-sender>
							<span class="cerb-ui-agent-transcript--sender-name">Agent</span> <span class="cerb-ui-pill">anthropic</span>
						</div>
						<div data-cerb-transcript-body><div class="commentBodyHtml">Let me look that up for you.</div></div>
						<pre data-cerb-transcript-source>Let me look that up for you.</pre>

						<div data-cerb-transcript-thinking data-display="summary"
							data-summary-active="Thinking…"
							data-summary-past="Thought for 5 seconds">
							<div class="commentBodyHtml">The user wants a password reset. Check the docs tool first.</div>
						</div>

						<div data-cerb-transcript-tool data-tool-name="search_docs" data-tool-id="toolu_01"
							data-icon="book-open"
							data-summary-active="Searching the documentation"
							data-summary-past="Searched the documentation">
							<pre data-cerb-transcript-tool-result>Password reset lives under Settings.</pre>
						</div>

						<div data-cerb-transcript-tool data-tool-name="search_tickets" data-tool-id="toolu_02"
							data-icon="search"
							data-summary-active="Searching recent tickets"
							data-summary-past="Searched recent tickets"></div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;!-- Thinking summarizes the same way. The duration is yours to compute — the component has no clock. --&gt;
&lt;div data-cerb-transcript-thinking data-display="summary"
     data-summary-active="Thinking…"
     data-summary-past="Thought for 5 seconds"&gt;
    &lt;div class="commentBodyHtml"&gt;…&lt;/div&gt;
&lt;/div&gt;

&lt;!-- Settled: has a result → reads "Searched the documentation" --&gt;
&lt;div data-cerb-transcript-tool data-tool-name="search_docs"
     data-icon="book-open"
     data-summary-active="Searching the documentation"
     data-summary-past="Searched the documentation"&gt;
    &lt;pre data-cerb-transcript-tool-result&gt;Password reset lives under Settings.&lt;/pre&gt;
&lt;/div&gt;

&lt;!-- Active: no result yet → reads "Searching recent tickets", and its icon sweeps --&gt;
&lt;div data-cerb-transcript-tool data-tool-name="search_tickets"
     data-icon="search"
     data-summary-active="Searching recent tickets"
     data-summary-past="Searched recent tickets"&gt;&lt;/div&gt;

&lt;!-- Per-node override, e.g. one noisy tool hidden while the rest show their payloads --&gt;
&lt;div data-cerb-transcript-tool data-tool-name="dump_state" data-display="hide"&gt;…&lt;/div&gt;

&lt;script&gt;
CerbUI.AgentTranscript.enhance(el, undefined, {
    tools: 'summary',      // the default; 'raw' opts into the actual payloads
    thinking: 'hide'
});
&lt;/script&gt;{/literal}</pre>
			</div>
		</div>

		<p class="cerb-u-text-muted" style="margin:1.5em 0 1em;"><b>Banded</b> (<code>--banded</code>) is the compact, full-width variant: one bordered container, a role eyebrow above each body, and no avatars — for dense hosts like the interaction awaits, where a 44px avatar per turn would crowd the surface. <b>Sample</b> (<code>--sample</code>) dims it as design-time placeholder data. Pass <code>{literal}{avatars:false, controls:false}{/literal}</code> alongside <code>--banded</code>.</p>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-agent-transcript cerb-ui-agent-transcript--banded" data-cerb-agent-transcript data-cerb-uiref-transcript-banded>
					<div data-cerb-transcript-turn data-role="user">
						<div class="cerb-ui-agent-transcript--role"><span class="cerb-icons cerb-icon-user"></span> User</div>
						<div data-cerb-transcript-body><div class="commentBodyHtml">How do I reset my password?</div></div>
					</div>
					<div data-cerb-transcript-turn data-role="assistant">
						<div class="cerb-ui-agent-transcript--role"><span class="cerb-icons cerb-icon-bot"></span> Agent</div>
						<div data-cerb-transcript-body><div class="commentBodyHtml">You can reset it from Settings, then Security.</div></div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;div class="cerb-ui-agent-transcript cerb-ui-agent-transcript--banded" data-cerb-agent-transcript&gt;
    &lt;div data-cerb-transcript-turn data-role="user"&gt;
        &lt;div class="cerb-ui-agent-transcript--role"&gt;&lt;span class="cerb-icons cerb-icon-user"&gt;&lt;/span&gt; User&lt;/div&gt;
        &lt;div data-cerb-transcript-body&gt;&lt;div class="commentBodyHtml"&gt;How do I reset my password?&lt;/div&gt;&lt;/div&gt;
    &lt;/div&gt;
    &lt;div data-cerb-transcript-turn data-role="assistant"&gt;
        &lt;div class="cerb-ui-agent-transcript--role"&gt;&lt;span class="cerb-icons cerb-icon-bot"&gt;&lt;/span&gt; Agent&lt;/div&gt;
        &lt;div data-cerb-transcript-body&gt;&lt;div class="commentBodyHtml"&gt;You can reset it from Settings.&lt;/div&gt;&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;script&gt;
CerbUI.AgentTranscript.enhance(el, undefined, { avatars: false, controls: false });
&lt;/script&gt;{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    if(!(window.CerbUI && CerbUI.AgentTranscript))
        return;

    CerbUI.AgentTranscript.enhance(document, '[data-cerb-uiref-transcript]', {
        view: 'toggle',
        thinking: 'raw',
        tools: 'raw',
        onTurnAction: function(value, ctx) {
            if('fork-from-here' !== value)
                return false;

            Devblocks.createAlert('Fork from seq ' + ctx.seq);
            return true;
        }
    });

    CerbUI.AgentTranscript.enhance(document, '[data-cerb-uiref-transcript-summary]', {
        tools: 'summary',
        thinking: 'hide'
    });

    CerbUI.AgentTranscript.enhance(document, '[data-cerb-uiref-transcript-banded]', {
        avatars: false,
        controls: false
    });
});
</script>
