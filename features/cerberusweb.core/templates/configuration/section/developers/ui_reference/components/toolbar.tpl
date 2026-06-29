	<div class="cerb-uiref-component" id="toolbar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-toolbox"></span>Toolbar</div>

		{* Example 1 (the canonical case): the default strip + the full API — slots above a textarea / editor *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A strip of icon buttons + pop-up menus that open interactions &mdash; the plain-JS successor to <code>.cerbToolbar()</code>. Enhance a nested <code>ul/li</code>: the top level is the strip, an item with a child <code>&lt;ul&gt;</code> opens a <code>CerbUI.Menu</code> (submenus cascade), an empty top-level <code>&lt;li&gt;</code> is a divider. A leaf fires <code>onSelect</code>; add <code>data-interaction-uri</code> and it also runs the interaction (popup/await) via <code>cerbBotTrigger</code>. <b>Labels are the <code>&lt;li&gt;</code>'s text</b> (same as <code>CerbUI.Menu</code>); icons via <code>data-icon</code>, an optional count via <code>data-badge</code> (<code>data-badge-color</code> tints it)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-strip">
					<li data-icon="bold" data-value="bold" title="Bold"></li>
					<li data-icon="italic" data-value="italic" title="Italic"></li>
					<li></li>
					<li data-icon="magic">Generate
						<ul>
							<li data-icon="sparkles" data-value="generate.summary">Summarize thread</li>
							<li data-icon="translate" data-value="generate.translate">Translate…</li>
							<li></li>
							<li data-icon="bookmark">Snippets
								<ul>
									<li data-value="snippet.greeting">Greeting</li>
									<li data-value="snippet.signature">Signature</li>
								</ul>
							</li>
						</ul>
					</li>
					<li></li>
					<li data-icon="paperclip" data-value="attach" data-badge="2" title="Attachments"></li>
					<li data-icon="bell" data-value="alerts" data-badge="3" data-badge-color="rgb(220,70,70)" title="Alerts"></li>
				</ul>
				<textarea rows="3" style="display:block;width:100%;box-sizing:border-box;" placeholder="Reply…"></textarea>
				<div class="cerb-uiref-result">Selected: <b id="uiref-toolbar-strip-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- The component hides this &lt;ul&gt; and renders the strip in its place (above the textarea). --&gt;
&lt;ul class="cerb-ui-toolbar" id="tb"&gt;
	&lt;li data-icon="bold" data-value="bold" title="Bold"&gt;&lt;/li&gt;
	&lt;li data-icon="italic" data-value="italic" title="Italic"&gt;&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;                                       &lt;!-- empty top-level li = a divider --&gt;
	&lt;li data-icon="magic"&gt;Generate                     &lt;!-- has a child ul =&gt; a menu trigger --&gt;
		&lt;ul&gt;
			&lt;li data-icon="sparkles" data-value="generate.summary"&gt;Summarize thread&lt;/li&gt;
			&lt;li data-icon="translate" data-value="generate.translate"&gt;Translate…&lt;/li&gt;
			&lt;li&gt;&lt;/li&gt;                                 &lt;!-- empty li = menu separator --&gt;
			&lt;li data-icon="bookmark"&gt;Snippets             &lt;!-- nested submenu (label = the li's TEXT) --&gt;
				&lt;ul&gt;
					&lt;li data-value="snippet.greeting"&gt;Greeting&lt;/li&gt;
					&lt;li data-value="snippet.signature"&gt;Signature&lt;/li&gt;
				&lt;/ul&gt;
			&lt;/li&gt;
		&lt;/ul&gt;
	&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;
	&lt;li data-icon="paperclip" data-value="attach" data-badge="2" title="Attachments"&gt;&lt;/li&gt;
	&lt;li data-icon="bell" data-value="alerts" data-badge="3" data-badge-color="rgb(220,70,70)" title="Alerts"&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;textarea rows="3"&gt;&lt;/textarea&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const tb = new CerbUI.Toolbar(document.getElementById('tb'), {
	// ── Presentation ──────────────────────────────────────────────
	bare:    false,   // false = strip with chrome; true = icons + menus only; 'tiny' = small muted icons
	// tiny: true,    // alias for bare: 'tiny'

	// ── Selection ─────────────────────────────────────────────────
	// Fires for ANY leaf (a strip button OR a menu item). The item descriptor is read off the source &lt;li&gt;:
	//   item = { key, value, label, interactionUri, interactionParams }
	onSelect: (item, sourceLi, e) => { console.log('picked', item.value || item.label); },

	// ── Interaction firing ────────────────────────────────────────
	// When a chosen &lt;li&gt; carries data-interaction-uri, the component ALSO runs it by delegating to the
	// existing $.fn.cerbBotTrigger (the startInteraction AJAX + await popup) — these pass straight through:
	caller:  { name: 'cerb.toolbar.demo', params: {} }, // policy caller identity the interaction runs under
	target:  null,    // a jQuery element → render the interaction INLINE into it; null → a popup
	width:   '50%',   // await-popup width
	hover:   false,   // open every menu on hover (a per-item data-hover overrides one item)
	start:   (formData) => {},  // append extra params just before the POST
	done:    (e) => {},         // interaction returned
	error:   (e) => {},         // interaction errored
	reset:   (e) => {},         // interaction reset
});

// An interaction item — the uri/params live on the &lt;li&gt;; selecting it fires the interaction AND onSelect:
//   &lt;li data-icon="magic" data-interaction-uri="cerb:automation:my.worker.interaction"
//       data-interaction-params="ticket_id=123"&gt;Do it&lt;/li&gt;

// Instance:  CerbUI.Toolbar.from(el)
//   tb.refresh()  — re-read the source &lt;ul&gt; (badges / hidden changed) and rebuild the strip
//   tb.destroy()  — tear down the menus + strip{/literal}</pre>
			</div>
		</div>

		{* Example 2: the bare variant embedded in a searchquery --right slot *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">The <code>--bare</code> variant &mdash; just icons + menus, no strip chrome &mdash; for tight slots like a <code>CerbUI.SearchQuery</code> <code>--right</code> toolbar. Here an <span class="cerb-icons cerb-icon-magic"></span> menu offers a natural-language &ldquo;build from description&rdquo; action and hard-coded query presets that drop straight into the field</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-toolbar-sq">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search tickets…"></textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<ul class="cerb-ui-toolbar cerb-ui-toolbar--bare" id="uiref-toolbar-sq-tb">
							<li data-icon="magic">Assist
								<ul>
									<li data-icon="sparkles" data-value="__nl2query">Build from description…</li>
									<li></li>
									<li data-icon="bookmark">Presets
										<ul>
											<li data-value="status:o">Open tickets</li>
											<li data-value="status:w">Waiting on us</li>
											<li data-value='status:o updated:"-1 week to now"'>Stale (1w+)</li>
										</ul>
									</li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
				<div class="cerb-uiref-result">Query: <b id="uiref-toolbar-sq-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Drop a bare toolbar into the searchquery --right slot (it owns the chrome). --&gt;
&lt;div class="cerb-ui-searchquery--right"&gt;
	&lt;ul class="cerb-ui-toolbar cerb-ui-toolbar--bare" id="sq-tb"&gt;
		&lt;li data-icon="magic"&gt;Assist
			&lt;ul&gt;
				&lt;li data-icon="sparkles" data-value="__nl2query"&gt;Build from description…&lt;/li&gt;
				&lt;li&gt;&lt;/li&gt;
				&lt;li data-icon="bookmark"&gt;Presets
					&lt;ul&gt;
						&lt;li data-value="status:o"&gt;Open tickets&lt;/li&gt;
						&lt;li data-value='status:o updated:"-1 week to now"'&gt;Stale (1w+)&lt;/li&gt;
					&lt;/ul&gt;
				&lt;/li&gt;
			&lt;/ul&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// bare:true (or the --bare class) drops the strip frame. A preset's data-value is a ready query; the
// special __nl2query item could call an LLM to turn a description into a search query.
new CerbUI.Toolbar(document.getElementById('sq-tb'), {
	bare: true,
	onSelect: (item) => {
		if(item.value === '__nl2query') { /* prompt + LLM -> query */ return; }
		if(item.value) sq.setValue(item.value).focus();  // a preset query drops into the field
	},
});{/literal}</pre>
			</div>
		</div>

		{* Example 3: the bare-tiny variant — small muted icons like SearchQuery's own --right actions *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">The <code>--bare-tiny</code> variant (a.k.a. <code>bare: 'tiny'</code>) &mdash; smaller, muted icons that match <code>CerbUI.SearchQuery</code>'s own built-in <code>--right</code> actions (the <span class="cerb-icons cerb-icon-autocomplete"></span>/<span class="cerb-icons cerb-icon-bookmark"></span> look). Use it when the toolbar should read as quiet inline affordances rather than buttons</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-toolbar-sq-tiny">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search tickets…"></textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<ul class="cerb-ui-toolbar cerb-ui-toolbar--bare-tiny" id="uiref-toolbar-sq-tiny-tb">
							<li data-icon="sparkles" data-value="__suggest" title="Suggestions"></li>
							<li data-icon="bookmark">Saved
								<ul>
									<li data-value="status:o">My open tickets</li>
									<li data-value="status:w">Waiting on us</li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
				<div class="cerb-uiref-result">Query: <b id="uiref-toolbar-sq-tiny-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;ul class="cerb-ui-toolbar cerb-ui-toolbar--bare-tiny" id="sq-tb"&gt;
	&lt;li data-icon="sparkles" data-value="__suggest" title="Suggestions"&gt;&lt;/li&gt;
	&lt;li data-icon="bookmark"&gt;Saved
		&lt;ul&gt;&lt;li data-value="status:o"&gt;My open tickets&lt;/li&gt;&lt;/ul&gt;
	&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The --bare-tiny class is auto-detected, or pass bare: 'tiny' (equivalently tiny: true).
new CerbUI.Toolbar(document.getElementById('sq-tb'), {
	bare: 'tiny',
	onSelect: (item) => {
		if(item.value === '__suggest') return sq.openAutocomplete();  // the sparkles = open suggestions
		if(item.value) sq.setValue(item.value).focus();              // a saved query drops into the field
	},
});{/literal}</pre>
			</div>
		</div>

		{* Example 4: conditional items via `hidden` + refresh() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Conditional items &mdash; mark a <code>&lt;li&gt;</code> <code>hidden</code> (or <code>.cerb-ui-toolbar--hidden</code>) to omit it, e.g. gating by role or record type. Flip the attribute and call <code>refresh()</code> to re-render</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-gated">
					<li data-icon="eye-open" data-value="view" title="View"></li>
					<li data-icon="edit" data-value="edit" title="Edit"></li>
					<li data-icon="trash" data-value="delete" title="Delete (admin)" hidden></li>
				</ul>
				<div class="cerb-uiref-result" style="margin-top:0.75em;">
					<label><input type="checkbox" id="uiref-toolbar-admin"> I'm an admin (show destructive tools)</label>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const tb = new CerbUI.Toolbar(document.getElementById('tb'));
const del = document.querySelector('#tb [data-value=delete]');

adminCheckbox.addEventListener('change', (e) => {
	del.hidden = !e.target.checked;  // toggle the source &lt;li&gt;…
	tb.refresh();                    // …then re-render the strip
});{/literal}</pre>
			</div>
		</div>

		{* Example 5: toggle buttons — independent client-state pressed buttons (editor toolbar look) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Toggle buttons &mdash; mark a <code>&lt;li&gt;</code> <code>data-toggle</code> and it becomes a client-state button that stays <b>pressed</b> (the yellow <code>--item-active</code> wash, matching the editor toolbar's old <code>--enabled</code> look). Clicking flips its state and calls <code>onSelect</code> with the new <code>item.pressed</code> &mdash; it <b>never</b> fires an interaction. Toggles are <b>independent</b> (not a radio group); seed one pressed with <code>data-pressed</code>, give each a stable <code>data-key</code>, and read/drive state with <code>tb.isPressed(key)</code> / <code>tb.setPressed(key, on)</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-toggle">
					<li data-icon="bold" data-value="bold" title="Bold"></li>
					<li></li>
					<li data-icon="placeholders" data-toggle data-key="placeholders" data-pressed title="Placeholders"></li>
					<li data-icon="lab" data-toggle data-key="tester" title="Test"></li>
				</ul>
				<div class="cerb-uiref-result">Pressed: <b id="uiref-toolbar-toggle-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;ul class="cerb-ui-toolbar" id="tb"&gt;
	&lt;li data-icon="bold" data-value="bold" title="Bold"&gt;&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;
	&lt;!-- data-toggle = a pressed/unpressed button; data-pressed seeds it on; data-key targets it from JS --&gt;
	&lt;li data-icon="placeholders" data-toggle data-key="placeholders" data-pressed title="Placeholders"&gt;&lt;/li&gt;
	&lt;li data-icon="lab" data-toggle data-key="tester" title="Test"&gt;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const tb = new CerbUI.Toolbar(document.getElementById('tb'), {
	onSelect: (item) => {
		// A toggle reports its NEW state on item.pressed (no interaction is fired).
		if(item.toggle) console.log(item.key, 'is now', item.pressed ? 'on' : 'off');
	},
});

tb.isPressed('placeholders');        // true (seeded via data-pressed)
tb.setPressed('tester', true);       // drive a toggle from code (silent — no onSelect)
tb.setPressed('tester', false, { fireCallback: true }); // …or fire onSelect too{/literal}</pre>
			</div>
		</div>

		{* Example 6: badge styles — the calm leading `count` tally vs the default corner pill *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>data-badge</code> renders a count. By default it's a floating corner <b>pill</b> that reads as an alert (Example 1). Pass <code>badgeStyle: 'count'</code> for a calmer <b>leading inline tally</b> to the left of the label (the old <code>DIV.badge-count</code> look) &mdash; for totals/tallies that shouldn't shout, like a record's search-count buttons</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-counts">
					<li data-value="status:o" data-badge="12">Open</li>
					<li data-value="status:w" data-badge="3">Waiting</li>
					<li data-value="status:c" data-badge="0">Closed</li>
				</ul>
				<div class="cerb-uiref-result">Picked: <b id="uiref-toolbar-counts-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Labels with leading counts; data-badge is the tally (0 shows too). --&gt;
&lt;ul class="cerb-ui-toolbar" id="tb"&gt;
	&lt;li data-value="status:o" data-badge="12"&gt;Open&lt;/li&gt;
	&lt;li data-value="status:w" data-badge="3"&gt;Waiting&lt;/li&gt;
	&lt;li data-value="status:c" data-badge="0"&gt;Closed&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// badgeStyle: 'count' renders each data-badge as a calm leading tally (left of the label) instead of the
// default floating corner pill — for counts that shouldn't read as alerts (e.g. search-tally buttons).
new CerbUI.Toolbar(document.getElementById('tb'), {
	badgeStyle: 'count',                 // 'pill' (default) = floating corner alert; 'count' = leading tally
	onSelect: (item) => { /* e.g. run item.value as a search query */ },
});{/literal}</pre>
			</div>
		</div>

		{* Example 7: overflow — wrap (default) vs collapse trailing items into a `…` menu, in a resizable box *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Overflow handling for tight containers (e.g. a sidebar). <code>overflow:'wrap'</code> (default) flows to multiple rows; <code>overflow:'menu'</code> keeps one row and collapses the trailing items into a <code>…</code> (more-vertical) <code>CerbUI.Menu</code> as space shrinks (via a <code>ResizeObserver</code>). <b>Drag the box's right edge</b> to resize and watch items move in/out of the <code>…</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="width:240px;resize:horizontal;overflow:auto;border:1px dashed var(--cerb-color-background-contrast-200);padding:6px;min-width:90px;max-width:100%;">
					<ul class="cerb-ui-toolbar" id="uiref-toolbar-overflow">
						<li data-icon="bold" data-value="bold" title="Bold"></li>
						<li data-icon="italic" data-value="italic" title="Italic"></li>
						<li data-icon="link" data-value="link" title="Link"></li>
						<li data-icon="picture" data-value="image" title="Image"></li>
						<li data-icon="paperclip" data-value="attach" title="Attach"></li>
						<li data-icon="quote" data-value="quote" title="Quote"></li>
						<li data-icon="list" data-value="list" title="List"></li>
						<li data-icon="magic">Generate
							<ul>
								<li data-icon="sparkles" data-value="gen.summary">Summarize</li>
								<li data-icon="translate" data-value="gen.translate">Translate…</li>
							</ul>
						</li>
					</ul>
				</div>
				<div class="cerb-uiref-result">Picked: <b id="uiref-toolbar-overflow-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- The component watches this strip's container; trailing items collapse into a `…` menu. --&gt;
&lt;div style="resize:horizontal;overflow:auto;"&gt;          &lt;!-- any width-constrained container (e.g. a sidebar) --&gt;
	&lt;ul class="cerb-ui-toolbar" id="tb"&gt;
		&lt;li data-icon="bold" data-value="bold" title="Bold"&gt;&lt;/li&gt;
		&lt;li data-icon="italic" data-value="italic" title="Italic"&gt;&lt;/li&gt;
		&lt;!-- … more items; a trailing menu item works too (it cascades inside the `…`) … --&gt;
		&lt;li data-icon="magic"&gt;Generate&lt;ul&gt;&lt;li data-value="gen.summary"&gt;Summarize&lt;/li&gt;&lt;/ul&gt;&lt;/li&gt;
	&lt;/ul&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// overflow: 'menu' keeps a single row; trailing items collapse into a '…' more-vertical menu as the
// container shrinks (ResizeObserver). Defaults to 'wrap' (flow to multiple rows); 'none' clips.
new CerbUI.Toolbar(document.getElementById('tb'), {
	overflow: 'menu',
	onSelect: (item) => { /* item.value — interactions still fire via the moved source li */ },
});{/literal}</pre>
			</div>
		</div>

		{* Example 8: hybrid — merge several cerb-ui-toolbar <ul>s into one strip via `sections` *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>sections</code> &mdash; compose ONE strip from several authored or record-rendered (<code>ui/toolbar/render.tpl</code>) <code>cerb-ui-toolbar</code> <code>&lt;ul&gt;</code>s, a divider between each. Each section's <code>&lt;li&gt;</code>s move into the primary list (interaction bindings intact). <code>onSelect(item, sourceLi)</code> hands you the clicked item AND its original <code>&lt;li&gt;</code> &mdash; read arbitrary <code>data-*</code> or classes (e.g. <code>.cerb-bot-trigger</code>) off it. This is how the editor family builds a <b>built-in formatting + host-configured</b> hybrid toolbar</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-hybrid">
					<li data-value="bold" data-icon="bold" title="Bold"></li>
					<li data-value="italic" data-icon="italic" title="Italics"></li>
				</ul>
				<ul class="cerb-ui-toolbar" id="uiref-toolbar-hybrid-more" hidden>
					<li data-value="preview" data-icon="eye-open" title="Preview"></li>
					<li class="cerb-bot-trigger" data-value="generate" data-icon="magic" title="Generate (from a record)"></li>
				</ul>
				<div class="cerb-uiref-result">Clicked: <b id="uiref-toolbar-hybrid-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;ul class="cerb-ui-toolbar" id="main"&gt;
	&lt;li data-value="bold" data-icon="bold" title="Bold"&gt;&lt;/li&gt;
	&lt;li data-value="italic" data-icon="italic" title="Italics"&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;!-- a second section (e.g. a worker-configured toolbar record) --&gt;
&lt;ul class="cerb-ui-toolbar" id="more" hidden&gt;
	&lt;li data-value="preview" data-icon="eye-open" title="Preview"&gt;&lt;/li&gt;
	&lt;li class="cerb-bot-trigger" data-value="generate" data-icon="magic" title="Generate"&gt;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// `sections` folds the extra &lt;ul&gt;(s) into one strip (a divider between each).
new CerbUI.Toolbar(document.getElementById('main'), {
	sections: [document.getElementById('more')],
	onSelect: (item, sourceLi) => {
		// item.value is the clicked &lt;li&gt;'s data-value; sourceLi is that original &lt;li&gt;.
		const fromBot = sourceLi.classList.contains('cerb-bot-trigger');
		console.log(item.value, fromBot);
	},
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Toolbar #1 — the default strip above a textarea
	(function() {
		const el = document.getElementById('uiref-toolbar-strip');
		const out = document.getElementById('uiref-toolbar-strip-out');
		if(el && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(el, {
				onSelect: function(item) { if(out) out.textContent = item.value || item.label || '—'; },
			});
		}
	})();

	// Toolbar #2 — the bare variant inside a searchquery --right slot
	(function() {
		const el = document.getElementById('uiref-toolbar-sq');
		const tbUl = document.getElementById('uiref-toolbar-sq-tb');
		const out = document.getElementById('uiref-toolbar-sq-out');
		if(el && tbUl && window.CerbUI && CerbUI.Toolbar && CerbUI.SearchQuery) {
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query || '—'; },
			});
			new CerbUI.Toolbar(tbUl, {
				bare: true,
				onSelect: function(item) {
					if(item.value === '__nl2query') {
						if(out) out.textContent = '(would call an LLM to build a query…)';
						return;
					}
					if(item.value) { sq.setValue(item.value).focus(); if(out) out.textContent = item.value; }
				},
			});
		}
	})();

	// Toolbar #3 — the bare-tiny variant (small muted icons, like SearchQuery's own --right)
	(function() {
		const el = document.getElementById('uiref-toolbar-sq-tiny');
		const tbUl = document.getElementById('uiref-toolbar-sq-tiny-tb');
		const out = document.getElementById('uiref-toolbar-sq-tiny-out');
		if(el && tbUl && window.CerbUI && CerbUI.Toolbar && CerbUI.SearchQuery) {
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query || '—'; },
			});
			new CerbUI.Toolbar(tbUl, {
				bare: 'tiny',
				onSelect: function(item) {
					if(item.value === '__suggest') { sq.focus(); sq.openAutocomplete(); return; } // the sparkles = open suggestions
					if(item.value && item.value.charAt(0) !== '_') { sq.setValue(item.value).focus(); if(out) out.textContent = item.value; }
				},
			});
		}
	})();

	// Toolbar #4 — conditional `hidden` items + refresh()
	(function() {
		const el = document.getElementById('uiref-toolbar-gated');
		const admin = document.getElementById('uiref-toolbar-admin');
		if(el && admin && window.CerbUI && CerbUI.Toolbar) {
			const tb = new CerbUI.Toolbar(el);
			const del = el.querySelector('[data-value=delete]');
			admin.addEventListener('change', function(e) {
				if(del) del.hidden = !e.target.checked;
				tb.refresh();
			});
		}
	})();

	// Toolbar #5 — independent toggle buttons (client-state pressed)
	(function() {
		const el = document.getElementById('uiref-toolbar-toggle');
		const out = document.getElementById('uiref-toolbar-toggle-out');
		if(el && window.CerbUI && CerbUI.Toolbar) {
			const keys = ['placeholders', 'tester'];
			const readout = function() {
				const on = keys.filter(function(k) { return tb.isPressed(k); });
				if(out) out.textContent = on.length ? on.join(', ') : '(none)';
			};
			const tb = new CerbUI.Toolbar(el, {
				onSelect: function(item) { if(item.toggle) readout(); },
			});
			readout(); // placeholders starts pressed via data-pressed
		}
	})();

	// Toolbar #6 — badge styles: a calm leading 'count' tally vs the default corner pill
	(function() {
		const el = document.getElementById('uiref-toolbar-counts');
		const out = document.getElementById('uiref-toolbar-counts-out');
		if(el && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(el, {
				badgeStyle: 'count',
				onSelect: function(item) { if(out) out.textContent = item.value || item.label || '—'; },
			});
		}
	})();

	// Toolbar #7 — overflow:'menu' in a resizable box (drag narrower → items collapse into '…')
	(function() {
		const el = document.getElementById('uiref-toolbar-overflow');
		const out = document.getElementById('uiref-toolbar-overflow-out');
		if(el && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(el, {
				overflow: 'menu',
				onSelect: function(item) { if(out) out.textContent = item.value || item.label || '—'; },
			});
		}
	})();

	// Toolbar #8 — hybrid: merge a second <ul> via `sections`; read the source <li> in onSelect
	(function() {
		const el = document.getElementById('uiref-toolbar-hybrid');
		const more = document.getElementById('uiref-toolbar-hybrid-more');
		const out = document.getElementById('uiref-toolbar-hybrid-out');
		if(el && more && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(el, {
				sections: [more],
				onSelect: function(item, sourceLi) {
					const tag = (sourceLi && sourceLi.classList.contains('cerb-bot-trigger')) ? ' (cerb-bot-trigger)' : '';
					if(out) out.textContent = (item.value || item.label || '—') + tag;
				},
			});
		}
	})();
})();
</script>
