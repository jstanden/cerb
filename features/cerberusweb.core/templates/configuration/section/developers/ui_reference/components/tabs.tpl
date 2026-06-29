	<div class="cerb-uiref-component" id="tabs">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-folder-open"></span>Tabs</div>

		{* Static panels (#anchor href -> a sibling div), remembered across reloads *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Static tabs &mdash; #anchor panels, <code>remember</code> (localStorage), <code>onTabSelected</code>; keyboard &larr;/&rarr; Home/End</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-tabs-static">
					<li><a href="#uiref-tabs-p1">Overview</a></li>
					<li><a href="#uiref-tabs-p2">Details</a></li>
					<li><a href="#uiref-tabs-p3">History</a></li>
				</ul>
				<div id="uiref-tabs-p1">Overview panel &mdash; static content.</div>
				<div id="uiref-tabs-p2">Details panel &mdash; static content.</div>
				<div id="uiref-tabs-p3">History panel &mdash; static content.</div>
				<div class="cerb-uiref-result">Active tab: <b id="uiref-tabs-static-out">&mdash;</b> <span style="color:var(--cerb-color-background-contrast-150);">(reload the page &mdash; it's remembered)</span></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- #anchor tabs toggle sibling panel divs by id --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#p1"&gt;Overview&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="#p2"&gt;Details&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;div id="p1"&gt;Overview panel&lt;/div&gt;
&lt;div id="p2"&gt;Details panel&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// every option + method (defaults shown)
const tabs = new CerbUI.Tabs(ul, {
	active:          0,            // initial 0-based index (overrides `remember`)
	variant:         'folder',     // visual skin: 'folder' (default) | 'underline' | 'segmented'
	remember:        'myTabs',     // persist active tab; localStorage key = `${storagePrefix}[remember]`
	storagePrefix:   'cerb-tabs',  // default 'cerb-tabs'
	onTabSelected:   function(i, tab) {},  // after a tab is shown
	onBeforeTabLoad: function(i, tab) {},  // before activation; return false to cancel
	onAfterTabLoad:  function(i, tab) {},  // panel ready (static/cached now, dynamic after fetch)
	onTabLoadError:  function(i, tab, status) {}, // dynamic fetch failed; return false to suppress the message
});
tabs.select(1);   // activate by index
tabs.refresh();   // re-fetch the active dynamic tab (refresh(i) for a specific one)
tabs.setVariant('underline'); // switch skin at runtime: 'folder' | 'underline' | 'segmented'
tabs.sync();      // re-parse the <ul> after adding/removing <li>
tabs.active; tabs.activeTab; tabs.allTabs; tabs.el;  // getters
tabs.destroy();
CerbUI.Tabs.from(ul);   // -> the instance for a source UL{/literal}</pre>
			</div>
		</div>

		{* Variants — three interchangeable skins; mix them for nested tab sets *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Variants &mdash; three interchangeable skins via the <code>variant</code> option (or just author <code>class="cerb-ui-tabs--&lt;skin&gt;"</code>): <code>folder</code> (default), <code>underline</code>, <code>segmented</code>. Mix skins for nested tab sets.</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;flex-direction:column;gap:1.5em;">
					<div>
						<ul id="uiref-tabs-v-folder">
							<li><a href="#uiref-tabs-vf1">Overview</a></li>
							<li><a href="#uiref-tabs-vf2">Log</a></li>
							<li><a href="#uiref-tabs-vf3">&nbsp;<span class="cerb-icons cerb-icon-gear"></span>&nbsp;</a></li>
						</ul>
						<div id="uiref-tabs-vf1"><code>folder</code> (default) &mdash; Overview panel.</div>
						<div id="uiref-tabs-vf2"><code>folder</code> &mdash; Log panel.</div>
						<div id="uiref-tabs-vf3"><code>folder</code> &mdash; Settings panel.</div>
					</div>
					<div>
						<ul id="uiref-tabs-v-underline">
							<li><a href="#uiref-tabs-vu1">Overview</a></li>
							<li><a href="#uiref-tabs-vu2">Log</a></li>
							<li><a href="#uiref-tabs-vu3">&nbsp;<span class="cerb-icons cerb-icon-gear"></span>&nbsp;</a></li>
						</ul>
						<div id="uiref-tabs-vu1"><code>underline</code> &mdash; Overview panel.</div>
						<div id="uiref-tabs-vu2"><code>underline</code> &mdash; Log panel.</div>
						<div id="uiref-tabs-vu3"><code>underline</code> &mdash; Settings panel.</div>
					</div>
					<div>
						<ul id="uiref-tabs-v-segmented">
							<li><a href="#uiref-tabs-vs1">Overview</a></li>
							<li><a href="#uiref-tabs-vs2">Log</a></li>
							<li><a href="#uiref-tabs-vs3">&nbsp;<span class="cerb-icons cerb-icon-gear"></span>&nbsp;</a></li>
						</ul>
						<div id="uiref-tabs-vs1"><code>segmented</code> &mdash; Overview panel.</div>
						<div id="uiref-tabs-vs2"><code>segmented</code> &mdash; Log panel.</div>
						<div id="uiref-tabs-vs3"><code>segmented</code> &mdash; Settings panel.</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Pick a skin with the `variant` option (default 'folder')…
new CerbUI.Tabs(folderUl);                          // folder (default)
new CerbUI.Tabs(underlineUl, { variant: 'underline' });
new CerbUI.Tabs(segmentedUl, { variant: 'segmented' });

// …or author it on the &lt;ul&gt; (the component honors an existing skin class):
//   &lt;ul class="cerb-ui-tabs--underline"&gt;…&lt;/ul&gt;{/literal}</pre>
			</div>
		</div>

		{* Dynamic AJAX panels via genericAjaxGet (proves a nonce'd <script> in the fragment runs) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Dynamic tabs &mdash; an ajax-args href loads via <code>genericAjaxGet</code> on first activation (spinner &rarr; content); the fragment's nonce'd &lt;script&gt; runs (no CSP error)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-tabs-dyn">
					<li><a href="#uiref-tabs-dyn-static">Local</a></li>
					<li><a href="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=1">Activity (AJAX)</a></li>
					<li><a href="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=2">Audit (AJAX)</a></li>
				</ul>
				<div id="uiref-tabs-dyn-static">A static panel alongside the dynamic ones.</div>
				<div class="cerb-uiref-result">Last load: <b id="uiref-tabs-dyn-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a non-#  href = a dynamic tab: the href is the ajax args (query after ajax.php?). --&gt;
&lt;!-- The panel div is auto-created after the &lt;ul&gt;; content loads via genericAjaxGet on first click. --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#local"&gt;Local&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="c=config&amp;a=invoke&amp;module=…&amp;action=…"&gt;Activity (AJAX)&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;div id="local"&gt;A static panel alongside dynamic ones.&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Dynamic tabs load via Cerb's genericAjaxGet (href = the ajax args). It injects the fragment with
// jQuery, so any &lt;script&gt; in the returned HTML runs under the page CSP nonce — no extra wiring needed.
new CerbUI.Tabs(ul, {
	onAfterTabLoad: function(i, tab) { /* tab.isDynamic, tab.href, tab.panel */ },
	onTabLoadError: function(i, tab, status) { /* status = HTTP code or null */ },
});{/literal}</pre>
			</div>
		</div>

		{* Wrapping — a long tab set (common on custom workspaces/dashboards) flows onto multiple rows *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Wrapping &mdash; a long tab set (custom workspaces/dashboards do this) flows onto multiple rows; the active tab stays legible on any row. No extra config &mdash; the strip is a <code>flex-wrap</code> row</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				{assign var=uiref_many_tabs value=['Overview','Inbox','My Tickets','Team Queue','Assigned','Watching','Mentions','Drafts','Sent','Snoozed','SLA Breaches','Escalations','Open','Pending','Waiting','Closed','Spam','Reports','Activity','Calendar','Tasks','Notes','Files','Contacts','Settings']}
				<ul id="uiref-tabs-many">
				{foreach $uiref_many_tabs as $i => $label}
					<li><a href="#uiref-tabs-many-{$i}">{$label}</a></li>
				{/foreach}
				</ul>
				{foreach $uiref_many_tabs as $i => $label}
				<div id="uiref-tabs-many-{$i}">&ldquo;{$label}&rdquo; panel &mdash; static content.</div>
				{/foreach}
				<div class="cerb-uiref-result">Active tab: <b id="uiref-tabs-many-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Nothing special: 25 #anchor tabs. The strip wraps to as many rows as needed. --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#p1"&gt;Overview&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="#p2"&gt;Inbox&lt;/a&gt;&lt;/li&gt;
	&lt;!-- …23 more… --&gt;
&lt;/ul&gt;
&lt;div id="p1"&gt;Overview panel&lt;/div&gt;
&lt;div id="p2"&gt;Inbox panel&lt;/div&gt;</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Tabs: static panels + remember + onTabSelected
	(function() {
		const ul = document.getElementById('uiref-tabs-static');
		const out = document.getElementById('uiref-tabs-static-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				remember: 'uirefStatic',
				onTabSelected: function(i, tab) { if(out) out.textContent = i + ' — ' + (tab.li.textContent || '').trim(); },
			});
		}
	})();

	// Tabs: the three visual variants (folder / underline / segmented)
	(function() {
		const variants = { 'uiref-tabs-v-folder': 'folder', 'uiref-tabs-v-underline': 'underline', 'uiref-tabs-v-segmented': 'segmented' };
		if(!(window.CerbUI && CerbUI.Tabs)) return;
		for(const id in variants) {
			const ul = document.getElementById(id);
			if(ul) new CerbUI.Tabs(ul, { variant: variants[id] });
		}
	})();

	// Tabs: dynamic AJAX via genericAjaxGet (proves the fragment's nonce'd inline script runs)
	(function() {
		const ul = document.getElementById('uiref-tabs-dyn');
		const out = document.getElementById('uiref-tabs-dyn-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				onAfterTabLoad: function(i, tab) { if(out) out.textContent = tab.isDynamic ? ('fetched #' + i) : ('static #' + i); },
				onTabLoadError: function(i, tab, status) { if(out) out.textContent = 'error ' + status; },
			});
		}
	})();

	// Tabs: many static tabs that wrap onto multiple rows (custom workspace / dashboard)
	(function() {
		const ul = document.getElementById('uiref-tabs-many');
		const out = document.getElementById('uiref-tabs-many-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				onTabSelected: function(i, tab) { if(out) out.textContent = i + ' — ' + (tab.li.textContent || '').trim(); },
			});
		}
	})();
})();
</script>
