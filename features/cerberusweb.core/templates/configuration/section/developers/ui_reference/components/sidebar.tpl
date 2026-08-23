	<div class="cerb-uiref-component" id="sidebar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-window-left"></span>Sidebar</div>

		{* Example: a collapsible nav rail — sections, icon/pip + label + right badge, chevron toggle, footer slot *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Collapsible rail &mdash; sections, items (icon/pip &middot; label &middot; right badge), a chevron toggle that collapses to an icon strip, a non-scrolling footer. The body scrolls; head/foot stay put. An item's <code>data-icon</code> is a <a href="#icon">cerb-icons</a> name; prefix a dot for literal CSS class(es) &mdash; <code>data-icon=".fa.fa-star"</code> &mdash; to use a non-cerb glyph</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sidebar-layout" style="height:400px;border:1px solid var(--cerb-color-background-contrast-220);border-radius:8px;overflow:hidden;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-basic">
						<div class="cerb-ui-sidebar--head">
							<span class="cerb-icons cerb-icon-inbox" style="color:var(--cerb-color-tag-orange);font-size:18px;"></span>
							<strong>Acme Workspace</strong>
						</div>
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Workspace</div>
								<ul>
									<li data-id="dashboard" data-icon="dashboard">Dashboard</li>
									<li data-id="tickets" data-icon="ticket" data-badge="142">Tickets</li>
									<li data-id="incidents" data-icon="alert" data-badge="4">Incidents</li>
									<li data-id="contacts" data-icon="users" data-badge="8.4k">Contacts</li>
									<li data-id="orgs" data-icon="building-office" data-badge="312">Organizations</li>
									<li data-id="contracts" data-icon="file-document" data-badge="87">Contracts</li>
									<li data-id="automations" data-icon="automation">Automations</li>
								</ul>
							</div>
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Tickets &middot; Views</div>
								<ul>
									<li data-id="view-myopen" data-pip="gray" data-badge="12">My open</li>
									<li data-id="view-watched" data-pip="green" data-pip-live data-badge="3">Watched</li>
									<li data-id="view-mentions" data-pip="blue">Mentions</li>
								</ul>
							</div>
						</div>
						<div class="cerb-ui-sidebar--foot">
							<div class="cerb-ui-sidebar--item" style="cursor:default;margin:0;">
								<span class="cerb-ui-sidebar--icon"><span class="cerb-ui-pip cerb-ui-pip--green"></span></span>
								<span class="cerb-ui-sidebar--label">Jeff Standen</span>
								<span class="cerb-icons cerb-icon-gear" style="opacity:0.6;"></span>
							</div>
						</div>
					</aside>
					<div class="cerb-ui-sidebar-layout--content" style="padding:1em;">
						<div class="cerb-uiref-result">Selected: <b id="uiref-sidebar-basic-out">&mdash;</b></div>
						<p style="color:var(--cerb-color-background-contrast-150);">Click the <span class="cerb-icons cerb-icon-chevron-left"></span> at the top to collapse the rail to an icon strip.</p>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Slots are optional: --head (fixed; JS injects the toggle), --body (the ONLY scroll region), --foot (fixed). --&gt;
&lt;!-- A bare &lt;aside&gt; with just sections is auto-wrapped into a --body. Item: data-icon|data-pip|data-avatar, label, data-badge|data-right. --&gt;
&lt;aside class="cerb-ui-sidebar" id="nav"&gt;
	&lt;div class="cerb-ui-sidebar--head"&gt;&lt;strong&gt;Acme&lt;/strong&gt;&lt;/div&gt;
	&lt;div class="cerb-ui-sidebar--body"&gt;
		&lt;div class="cerb-ui-sidebar--section"&gt;
			&lt;div class="cerb-ui-sidebar--label"&gt;Workspace&lt;/div&gt;
			&lt;ul&gt;
				&lt;li data-id="tickets" data-icon="ticket" data-badge="142"&gt;Tickets&lt;/li&gt;
				&lt;li data-id="watched" data-pip="green" data-pip-live data-badge="3"&gt;Watched&lt;/li&gt;
			&lt;/ul&gt;
		&lt;/div&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-sidebar--foot"&gt;&lt;!-- optional; non-scrolling (e.g. a user card) --&gt;&lt;/div&gt;
&lt;/aside&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// every option + method (defaults shown)
const sb = new CerbUI.Sidebar(document.getElementById('nav'), {
	side:          'left',   // 'left' | 'right' — border side + chevron direction
	collapsed:     false,    // start collapsed (icon-only strip)
	fullHeight:    false,    // true = sticky 100vh (body scrolls within the viewport); else a normal in-flow block
	storageKey:    null,     // e.g. 'cerbNav' — persist the collapsed state in localStorage
	filter:        false,    // search box in the head (shares its row with the collapse chevron); winnows items by label.
	                         // ArrowDown from the box focuses the list — then Up/Down rove, Enter/Space select, Esc returns
	filterPlaceholder: 'Filter…',
	onToggle:      function(collapsed) {},          // after expand/collapse
	onSelect:      function(li, sb, e) {            // item click; return truthy to handle it (skips the default action)
		out.textContent = li.dataset.id;
		return true;
	},
	onRenderItem:  function(renderedLi, srcLi) {},  // after icon+label, before right content — extra adornment hook
	onFilterItem:  null,                            // (li, query) => bool — overrides the default substring match
	contentTarget: null,                            // element|selector — default destination for data-item-ajax loads
	tooltips:      true,                            // when collapsed, show each item's label on hover (CerbUI.Tooltip)
});
sb.toggle(); sb.collapse(); sb.expand(); sb.isCollapsed();
sb.setActive(liOrId);   // mark the current item (a &lt;li&gt; or its data-id / id)
sb.setFilter('inv'); sb.getFilter();
sb.destroy();
CerbUI.Sidebar.from(el);   // -> the instance for a rail element{/literal}</pre>
			</div>
		</div>

		{* Example: --hide-collapsed — drop arbitrary content from the collapsed strip (no per-page CSS) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Hide-on-collapse (<code>cerb-ui-sidebar--hide-collapsed</code>) &mdash; the standard parts (<code>--label</code>/<code>--filter</code>/<code>--badge</code>/<code>--right</code>/<code>--foot</code>) drop from the icon strip automatically; tag <strong>any other</strong> element with <code>cerb-ui-sidebar--hide-collapsed</code> to vanish it too, so callers never hand-write a <code>#fooNav.cerb-ui-sidebar--collapsed &hellip;</code> rule. It's <code>display:none !important</code> under <code>--collapsed</code>, so it also beats a page <code>#id</code> rule</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sidebar-layout" style="height:340px;border:1px solid var(--cerb-color-background-contrast-220);border-radius:8px;overflow:hidden;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-hidecollapsed">
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Workspace</div>
								<ul>
									<li data-id="dashboard" data-icon="dashboard">Dashboard</li>
									<li data-id="tickets" data-icon="ticket" data-badge="142">Tickets</li>
									<li data-id="reports" data-icon="chart-bar">Reports</li>
								</ul>
								{* Arbitrary content (not a standard part) — tagged to disappear on the collapsed strip *}
								<div class="cerb-ui-sidebar--hide-collapsed cerb-ui-panel cerb-u-fs-n2" style="margin:0.5em 0.6em;">
									<strong>Pro tip</strong> &mdash; you're on the Free plan. <a href="javascript:;">Upgrade</a> for unlimited views.
								</div>
							</div>
						</div>
					</aside>
					<div class="cerb-ui-sidebar-layout--content" style="padding:1em;">
						<p style="color:var(--cerb-color-background-contrast-150);">Collapse the rail with the <span class="cerb-icons cerb-icon-chevron-left"></span>: the items become icons, but the &ldquo;Pro tip&rdquo; card (tagged <code>cerb-ui-sidebar--hide-collapsed</code>) disappears entirely.</p>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Standard parts (--label / --filter / --badge / --right / --foot) auto-hide when collapsed. --&gt;
&lt;!-- For ANY other content, add --hide-collapsed so it drops from the icon strip (no per-page CSS). --&gt;
&lt;aside class="cerb-ui-sidebar" id="nav"&gt;
	&lt;div class="cerb-ui-sidebar--body"&gt;
		&lt;div class="cerb-ui-sidebar--section"&gt;
			&lt;div class="cerb-ui-sidebar--label"&gt;Workspace&lt;/div&gt;
			&lt;ul&gt;&lt;li data-icon="ticket"&gt;Tickets&lt;/li&gt;&lt;/ul&gt;
			&lt;div class="cerb-ui-sidebar--hide-collapsed cerb-ui-panel"&gt;Pro tip — upgrade for more&lt;/div&gt;
		&lt;/div&gt;
	&lt;/div&gt;
&lt;/aside&gt;</pre>
			</div>
		</div>

		{* Example: right-anchored + type-to-filter *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Right-anchored (<code>side:'right'</code>) + type-to-filter (<code>filter:true</code>) &mdash; the search box winnows items by label and hides sections that empty out; collapsed state persists via <code>storageKey</code></div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="height:340px;display:flex;justify-content:flex-end;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-filter">
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Records</div>
								<ul>
									<li data-id="tickets" data-icon="ticket">Tickets</li>
									<li data-id="messages" data-icon="inbox">Messages</li>
									<li data-id="contacts" data-icon="users">Contacts</li>
									<li data-id="orgs" data-icon="building-office">Organizations</li>
									<li data-id="contracts" data-icon="file-document">Contracts</li>
								</ul>
							</div>
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Tools</div>
								<ul>
									<li data-id="saved" data-icon="list">Saved searches</li>
									<li data-id="calendar" data-icon="calendar">Calendar</li>
									<li data-id="reports" data-icon="chart-bar">Reports</li>
								</ul>
							</div>
						</div>
					</aside>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Sidebar(el, {
	side:       'right',                // [/* … */] flips the border + toggle to the other edge
	filter:     true,                   // search box in the head; default match = item label contains the query
	storageKey: 'cerbNavFilter',        // remember collapsed across reloads
	// onFilterItem: (li, q) => li.dataset.tags.includes(q),  // custom matching instead of label text
});{/literal}</pre>
			</div>
		</div>

		{* Example: a --content section (free-form controls) + collapseTo:'closed' *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Free-form sections (<code>--content</code>) &mdash; a section can hold arbitrary markup (a chooser, toggles, a small form) instead of item rows; it shares the label's gutter so the controls line up under the header. Only <code>&lt;ul&gt; &gt; &lt;li&gt;</code> is enhanced, so nothing else is touched. Pair it with <code>collapseTo:'closed'</code>: forms have no icon to shrink to, so the rail folds away to just its handle</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;align-items:flex-start;gap:1.5rem;">
					<div style="flex:1 1 0;min-width:0;color:var(--cerb-color-background-contrast-150);">The page content keeps the width the rail gives back when it folds.</div>
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-content" style="--cerb-ui-sidebar-width:280px;">
						<div class="cerb-ui-sidebar--head"><strong>Session</strong></div>
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Mounts</div>
								<div class="cerb-ui-sidebar--content">
									<div class="cerb-ui-record-chooser" id="uiref-sidebar-content-chooser"></div>
								</div>
							</div>
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Commands</div>
								<div class="cerb-ui-sidebar--content cerb-u-flex cerb-u-flex-column cerb-u-gap-2">
									<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
										<label class="cerb-ui-toggle"><input type="checkbox" id="uiref-sidebar-content-records" checked="checked"><span class="cerb-ui-toggle--slider"></span></label>
										<label for="uiref-sidebar-content-records"><code>cerb records</code></label>
									</div>
									<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
										<label class="cerb-ui-toggle"><input type="checkbox" id="uiref-sidebar-content-search"><span class="cerb-ui-toggle--slider"></span></label>
										<label for="uiref-sidebar-content-search"><code>cerb search</code></label>
									</div>
								</div>
							</div>
						</div>
					</aside>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-sidebar--section"&gt;
	&lt;div class="cerb-ui-sidebar--label"&gt;Mounts&lt;/div&gt;
	&lt;div class="cerb-ui-sidebar--content"&gt;&lt;!-- any markup: a chooser, toggles, a form --&gt;&lt;/div&gt;
&lt;/div&gt;

{literal}new CerbUI.Sidebar(el, {
	side:       'right',
	collapseTo: 'closed',              // no icon vocabulary to collapse to — hide the body, keep the handle
	storageKey: 'cerbConfigRail',
});{/literal}</pre>
			</div>
		</div>

		{* Example: monogram avatars (data-avatar) + the filter row's inline chevron + keyboard nav *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Monogram avatars (<code>data-avatar</code>) &mdash; each item paints a hash-locked <code>CerbUI.Avatar</code> in the icon slot instead of a shared glyph, so the <strong>collapsed strip stays legible</strong> (T&middot;M&middot;W&middot;O&hellip; not one repeated icon). The collapse chevron sits inline at the right of the filter; press <kbd>&darr;</kbd> in the filter to focus the list, then <kbd>&uarr;</kbd>/<kbd>&darr;</kbd> to move, <kbd>Enter</kbd> to pick, <kbd>Esc</kbd> to return. Add <code>data-avatar-image</code> for a photo that swaps in, or <code>data-avatar-icon</code> (a <a href="#icon">cerb-icons</a> name) to paint a glyph inside the color-locked circle instead of initials</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sidebar-layout" style="height:360px;border:1px solid var(--cerb-color-background-contrast-220);border-radius:8px;overflow:hidden;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-avatars" style="--cerb-ui-sidebar-width:230px;">
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Record types</div>
								<ul>
									<li data-id="ticket" data-avatar="Tickets" data-avatar-seed="cerb.contexts.ticket">Tickets</li>
									<li data-id="message" data-avatar="Messages" data-avatar-seed="cerb.contexts.message">Messages</li>
									<li data-id="worker" data-avatar="Workers" data-avatar-seed="cerb.contexts.worker">Workers</li>
									<li data-id="org" data-avatar="Organizations" data-avatar-seed="cerb.contexts.org">Organizations</li>
									<li data-id="contact" data-avatar="Contacts" data-avatar-seed="cerb.contexts.contact">Contacts</li>
									<li data-id="task" data-avatar="Tasks" data-avatar-seed="cerb.contexts.task">Tasks</li>
									<li data-id="calendar" data-avatar="Calendar" data-avatar-seed="cerb.contexts.calendar">Calendar</li>
									<li data-id="bots" data-avatar="Bots" data-avatar-seed="cerb.contexts.bot" data-avatar-icon="bot">Bots</li>
								</ul>
							</div>
						</div>
					</aside>
					<div class="cerb-ui-sidebar-layout--content" style="padding:1em;">
						<p style="color:var(--cerb-color-background-contrast-150);">Collapse the rail with the chevron beside the filter to see the monogram strip. Click the filter, then press <span class="cerb-icons cerb-icon-chevron-down"></span> to drive the list from the keyboard.</p>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- data-avatar = the monogram label; data-avatar-seed = the stable color key (optional); --&gt;
&lt;!-- data-avatar-image = a photo URL (optional, swaps in); data-avatar-icon = a cerb-icons name (optional, a glyph instead of initials). --&gt;
&lt;ul&gt;
	&lt;li data-id="ticket" data-avatar="Tickets" data-avatar-seed="cerb.contexts.ticket"&gt;Tickets&lt;/li&gt;
	&lt;li data-id="worker" data-avatar="Workers" data-avatar-seed="cerb.contexts.worker"&gt;Workers&lt;/li&gt;
	&lt;li data-id="bots" data-avatar="Bots" data-avatar-seed="cerb.contexts.bot" data-avatar-icon="bot"&gt;Bots&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// filter:true puts the search box + collapse chevron on one row, and enables keyboard nav:
// ArrowDown from the box focuses the first item; Up/Down rove; Enter/Space select; Esc returns.
new CerbUI.Sidebar(el, { filter: true });{/literal}</pre>
			</div>
		</div>

		{* Example: default action — data-item-url nav + data-item-ajax content loading (no onSelect needed) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Default action (no <code>onSelect</code>) &mdash; <code>data-item-ajax</code> loads a fragment into the <code>contentTarget</code> via <code>genericAjaxGet</code> (its nonce'd &lt;script&gt; runs); <code>data-item-url</code> navigates (<code>http(s)://</code> or <code>//</code> &rarr; new tab, relative &rarr; this page)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sidebar-layout" style="height:320px;border:1px solid var(--cerb-color-background-contrast-220);border-radius:8px;overflow:hidden;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-actions" style="--cerb-ui-sidebar-width:200px;">
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Load (AJAX)</div>
								<ul>
									<li data-id="activity" data-icon="chart-bar" data-item-ajax="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=1">Activity</li>
									<li data-id="audit" data-icon="clipboard" data-item-ajax="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=2">Audit</li>
								</ul>
							</div>
						</div>
					</aside>
					<div class="cerb-ui-sidebar-layout--content" id="uiref-sidebar-actions-target" style="padding:1em;overflow:auto;">Pick an item to load its panel here.</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- data-item-ajax = the ajax args (query after ajax.php?); loads into the contentTarget, runs returned &lt;script&gt;. --&gt;
&lt;!-- data-item-url = full-page nav (relative = this app; http(s):// or // = a new tab). Per-item data-item-target overrides contentTarget. --&gt;
&lt;ul&gt;
	&lt;li data-icon="chart-bar" data-item-ajax="c=config&amp;a=invoke&amp;module=…&amp;action=…"&gt;Activity&lt;/li&gt;
	&lt;li data-icon="folder" data-item-url="/contacts"&gt;Contacts&lt;/li&gt;
	&lt;li data-icon="book" data-item-url="https://cerb.ai/docs"&gt;Docs &#8599;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// No onSelect — the built-in default action routes each click by its data-* attributes.
new CerbUI.Sidebar(el, { contentTarget: '#content' });

// Hosts can also invoke the default routing on any &lt;li&gt; directly:
// CerbUI.Sidebar.defaultSelect(li, sidebar);{/literal}</pre>
			</div>
		</div>

		{* Example: palette mode — draggable tiles dropped onto a canvas (clone-out; the palette stays intact) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Palette mode (<code>palette:true</code>) &mdash; <code>tile</code> items become draggable (<code>CerbUI.Draggable</code>, clone helper); drag onto a <code>CerbUI.Droppable</code> canvas. The source palette stays intact (clone-out); a plain tap still fires <code>onSelect</code>; the strip still collapses</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sidebar-layout" style="height:360px;border:1px solid var(--cerb-color-background-contrast-220);border-radius:8px;overflow:hidden;">
					<aside class="cerb-ui-sidebar" id="uiref-sidebar-palette" style="--cerb-ui-sidebar-width:210px;">
						<div class="cerb-ui-sidebar--body">
							<div class="cerb-ui-sidebar--section">
								<div class="cerb-ui-sidebar--label">Fields</div>
								<ul>
									<li data-id="f-text" data-icon="text" data-kind="input" data-name="Text" data-color="blue"></li>
									<li data-id="f-number" data-icon="hash" data-kind="input" data-name="Number" data-color="green"></li>
									<li data-id="f-date" data-icon="calendar" data-kind="input" data-name="Date" data-color="purple"></li>
									<li data-id="f-select" data-icon="list" data-kind="choice" data-name="Dropdown" data-color="orange"></li>
									<li data-id="f-link" data-icon="link" data-kind="input" data-name="Link" data-color="red"></li>
								</ul>
							</div>
						</div>
					</aside>
					<div class="cerb-ui-sidebar-layout--content cerb-uiref-formcanvas" id="uiref-sidebar-palette-canvas" style="padding:1em;overflow:auto;">
						<span style="color:var(--cerb-color-background-contrast-150);">Drag a field here to build the form.</span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- tile items: data-icon / data-kind / data-name / data-color (the icon-square bg; bareword = a tag color). --&gt;
&lt;aside class="cerb-ui-sidebar" id="palette"&gt;
	&lt;div class="cerb-ui-sidebar--body"&gt;
		&lt;div class="cerb-ui-sidebar--section"&gt;
			&lt;div class="cerb-ui-sidebar--label"&gt;Fields&lt;/div&gt;
			&lt;ul&gt;
				&lt;li data-id="f-text" data-icon="type" data-kind="input" data-name="Text" data-color="blue"&gt;&lt;/li&gt;
				&lt;li data-id="f-date" data-icon="calendar" data-kind="input" data-name="Date" data-color="purple"&gt;&lt;/li&gt;
			&lt;/ul&gt;
		&lt;/div&gt;
	&lt;/div&gt;
&lt;/aside&gt;
&lt;div id="canvas"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// palette:true == variant:'tile' + draggable:true. The Sidebar wires CerbUI.Draggable on its items
// (clone helper, tilt); the HOST owns the drop target as a CerbUI.Droppable.
new CerbUI.Sidebar(document.getElementById('palette'), {
	palette: true,
	onItemDragStart: function(li, e) {},   // optional
	onSelect: function(li) { /* taps still select */ return true; },
});

new CerbUI.Droppable(document.getElementById('canvas'), {
	accept: '.cerb-ui-sidebar--item',
	onDrop: function(info) { addField(info.payload); },  // payload = the tile's data-* (id/icon/kind/name/color)
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Sidebar: collapsible nav rail — sections, icon/pip + badge, chevron collapse, footer slot
	(function() {
		const el = document.getElementById('uiref-sidebar-basic');
		const out = document.getElementById('uiref-sidebar-basic-out');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			const sb = new CerbUI.Sidebar(el, {
				onSelect: function(li) { if(out) out.textContent = li.dataset.id; return true; }
			});
			sb.setActive('dashboard');
		}
	})();

	// Sidebar: --hide-collapsed — arbitrary content (the "Pro tip" card) vanishes on the collapsed strip
	(function() {
		const el = document.getElementById('uiref-sidebar-hidecollapsed');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			new CerbUI.Sidebar(el, { storageKey: 'uirefSidebarHideCollapsed' });
		}
	})();

	// Sidebar: right-anchored + type-to-filter (collapsed state persisted via storageKey)
	(function() {
		const el = document.getElementById('uiref-sidebar-filter');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			new CerbUI.Sidebar(el, { side: 'right', filter: true, storageKey: 'uirefSidebarFilter' });
		}
	})();

	// Sidebar: free-form --content sections + collapseTo:'closed'
	(function() {
		const el = document.getElementById('uiref-sidebar-content');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			new CerbUI.Sidebar(el, { side: 'right', collapseTo: 'closed', storageKey: 'uirefSidebarContent' });
		}

		const chooser = document.getElementById('uiref-sidebar-content-chooser');
		if(chooser && window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser(chooser, { context: 'worker', emptyIcon: 'user', searchPlaceholder: 'Add a mount\u2026' });
		}

		if(el && window.CerbUI && CerbUI.Toggle) {
			el.querySelectorAll('.cerb-ui-toggle').forEach(function(t) { new CerbUI.Toggle(t); });
		}
	})();

	// Sidebar: monogram avatars (data-avatar) + filter row (inline chevron) + keyboard nav
	(function() {
		const el = document.getElementById('uiref-sidebar-avatars');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			new CerbUI.Sidebar(el, { filter: true, storageKey: 'uirefSidebarAvatars' });
		}
	})();

	// Sidebar: default action — data-item-ajax loads a fragment into the content target (scripts run under the nonce)
	(function() {
		const el = document.getElementById('uiref-sidebar-actions');
		if(el && window.CerbUI && CerbUI.Sidebar) {
			new CerbUI.Sidebar(el, { contentTarget: '#uiref-sidebar-actions-target' });
		}
	})();

	// Sidebar: palette mode — draggable tiles dropped onto a canvas (clone-out; the palette stays intact)
	(function() {
		const el = document.getElementById('uiref-sidebar-palette');
		const canvas = document.getElementById('uiref-sidebar-palette-canvas');
		if(el && canvas && window.CerbUI && CerbUI.Sidebar && CerbUI.Droppable) {
			let n = 0;
			new CerbUI.Sidebar(el, { palette: true });
			new CerbUI.Droppable(canvas, {
				accept: '.cerb-ui-sidebar--item',
				onDrop: function(info) {
					if(!n++) canvas.textContent = '';
					const card = document.createElement('div');
					card.className = 'cerb-ui-panel cerb-u-mb-2';
					card.textContent = (info.payload.name || 'Field') + ' — ' + (info.payload.kind || 'field');
					canvas.appendChild(card);
				}
			});
		}
	})();
})();
</script>
