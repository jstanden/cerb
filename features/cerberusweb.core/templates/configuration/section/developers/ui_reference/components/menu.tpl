	<div class="cerb-uiref-component" id="menu">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-menu-hamburger"></span>Menu</div>

		{* Example: small cascading menu — icons via onRenderItem, an empty-LI separator, submenus *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Cascading menu: icons (onRenderItem), a separator, submenus &mdash; keyboard nav (&uarr;&darr; &rarr;&larr; Enter Esc Home End)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-small-trigger"><span class="cerb-icons cerb-icon-menu-hamburger"></span> Open menu</button>
				<ul id="uiref-menu-small" hidden>
					<li data-id="new" data-icon="file-document">New
						<ul>
							<li data-id="new.doc">Document</li>
							<li data-id="new.folder">Folder</li>
						</ul>
					</li>
					<li data-id="open" data-icon="folder">Open</li>
					<li data-id="save" data-icon="inbox">Save</li>
					<li></li>
					<li data-id="export" data-icon="download">Export
						<ul>
							<li data-id="export.pdf">PDF</li>
							<li data-id="export.csv">CSV</li>
							<li data-id="export.json">JSON</li>
						</ul>
					</li>
					<li data-id="quit" data-icon="sign-out">Quit</li>
				</ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-small-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- progressive enhancement: an authored UL&gt;LI; submenu = a nested UL; empty LI = separator. --&gt;
&lt;!-- Only data-* is mirrored onto rendered items; icons are injected via onRenderItem (not markup). --&gt;
&lt;button type="button" id="trigger"&gt;Open menu&lt;/button&gt;
&lt;ul id="my-menu" hidden&gt;
	&lt;li data-id="new" data-icon="file-document"&gt;New
		&lt;ul&gt;
			&lt;li data-id="new.doc"&gt;Document&lt;/li&gt;
			&lt;li data-id="new.folder"&gt;Folder&lt;/li&gt;
		&lt;/ul&gt;
	&lt;/li&gt;
	&lt;li data-id="open" data-icon="folder"&gt;Open&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;
	&lt;li data-id="quit" data-icon="sign-out"&gt;Quit&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ul = document.getElementById('my-menu');
const trigger = document.getElementById('trigger');

const menu = new CerbUI.Menu(ul, {
	clickTrigger:    trigger, // element that TOGGLES the menu on click (anchored to it); no manual handler needed
	onSelect:        function(li, src, e) { out.textContent = src.dataset.id; }, // leaf click / Enter
	onClose:         function() {},        // menu fully closed (all panels removed)
	// closeOnSelect: true,                // false = stay open after a pick (add several in a row)
	onRenderItem:    function(li, src) {   // after the label, before the arrow — inject icons here
		const icon = src.dataset.icon;       // bare name e.g. "folder"; ".my-icon" = raw class(es) for non-cerb icons
		if(icon) {
			const ico = document.createElement('span');
			ico.className = icon.charAt(0) === '.' ? icon.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + icon);
			ico.style.marginRight = '0.5em';
			li.insertBefore(ico, li.firstChild);
		}
	},
	itemHeight:      28,    // px; MUST match the .cerb-ui-menu--item height
	maxHeight:       380,   // px before a panel scrolls
	virtThreshold:   60,    // virtualize panels larger than this
	openDelay:       80,    // ms hover delay before a submenu opens
	virtBuffer:      6,     // extra rows above/below the visible window
	inline:          false, // render the root in document flow vs. floating
	hoverTrigger:    null,  // opens on mouseenter / closes on mouseleave (the hover counterpart to clickTrigger)
	hoverGroup:      null,  // links sibling hover menus (only one open per group)
	hoverCloseDelay: 150,   // ms before a hover menu closes after the mouse leaves
	fixed:           false, // position:fixed instead of absolute
	filter:          false, // type-to-filter: start typing to reveal a search box that narrows the list
	filterPlaceholder: 'Filter…',
	filterEmptyText: 'No matches',
	filterDedupe:    false, // de-dupe flattened filter matches (nested menus only): true keys each item by
	                        // interaction uri+params / behavior id / href / else lowercased label; or pass a
	                        // fn(sourceLi)->string. First match wins, and root leaves flatten before nested
	                        // ones, so a top-level copy beats the same item buried in a submenu
	filterShowPath:  true,  // show the ancestor breadcrumb (eyebrow) above each flattened deep match; false hides it
	filterFlatten:   true,  // nested menus only: search every LEAF, so a deep item is findable by name. false =
	                        // filter the top-level rows in place and keep their submenus -- for a tree whose
	                        // branches ARE the choices and whose leaves only refine one (a model, its effort levels)
	filterText:      null   // fn(sourceLi, label) -> extra searchable text for that row, merged into the haystack.
	                        // For an item someone would reasonably find by typing something the label doesn't show
});

// clickTrigger (above) wires the toggle for you; to drive it yourself: menu.open(anchor) / menu.close() / menu.isOpen()
// CerbUI.Menu.from(ul) -> the instance for a source UL{/literal}</pre>
			</div>
		</div>

		{* Example: the headline — a virtualized 100,000-item menu *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Virtualized: 100,000 items, ~25 DOM nodes (opens instantly, scrolls smoothly). With <code>filter: true</code>, just start typing &mdash; a search box appears, narrows the list, and tucks away when emptied (arrows/Enter to pick)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-huge-trigger"><span class="cerb-icons cerb-icon-database"></span> Open 100k menu</button>
				<ul id="uiref-menu-huge" hidden></ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-huge-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Build a big source UL once (the menu parses it into a data model; only ~25 LIs ever render)
const frag = document.createDocumentFragment();
for(let i = 0; i &lt; 100000; i++) {
	const li = document.createElement('li');
	li.dataset.id = 'item-' + i;
	li.textContent = 'Item ' + i.toLocaleString();
	frag.appendChild(li);
}
ul.appendChild(frag);

// virtThreshold (60) / itemHeight (28) / maxHeight (380) govern the windowed scroll
// filter:true — start typing to reveal a search box that narrows the flat model (re-windows the matches);
// it hides again when emptied, so it's unobtrusive enough to enable on any menu
const menu = new CerbUI.Menu(ul, { filter: true, onSelect: (li, src) => { out.textContent = src.dataset.id; } });
trigger.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(trigger));{/literal}</pre>
			</div>
		</div>

		{* Example: deep cascade (viewport flip/clamp) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Deep cascade &mdash; submenus flip left / clamp when they'd leave the viewport. With <code>filter: true</code>, typing flattens the whole tree: type <code>blank</code> to jump straight to <em>File &rsaquo; New &rsaquo; Document &rsaquo; Blank</em></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-deep-trigger"><span class="cerb-icons cerb-icon-hierarchy"></span> Open deep menu</button>
				<ul id="uiref-menu-deep" hidden>
					<li data-id="file">File
						<ul>
							<li data-id="file.new">New
								<ul>
									<li data-id="file.new.doc">Document
										<ul>
											<li data-id="file.new.doc.blank">Blank</li>
											<li data-id="file.new.doc.tpl">From template</li>
										</ul>
									</li>
									<li data-id="file.new.folder">Folder</li>
								</ul>
							</li>
							<li data-id="file.open">Open</li>
						</ul>
					</li>
					<li data-id="edit">Edit
						<ul>
							<li data-id="edit.cut">Cut</li>
							<li data-id="edit.copy">Copy</li>
						</ul>
					</li>
				</ul>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Same API — depth is just nested ULs. Positioning flips/clamps automatically near edges.
const menu = new CerbUI.Menu(ul, { onSelect: (li, src) => console.log(src.dataset.id) });
trigger.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(trigger));{/literal}</pre>
			</div>
		</div>

		{* Example: inline mode — root renders in document flow *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Inline mode &mdash; the root panel renders in document flow (submenus still float)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-menu-inline" hidden>
					<li data-id="dashboards">Dashboards</li>
					<li data-id="reports">Reports
						<ul>
							<li data-id="reports.daily">Daily</li>
							<li data-id="reports.weekly">Weekly</li>
						</ul>
					</li>
					<li></li>
					<li data-id="settings">Settings</li>
				</ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-inline-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// inline:true mounts the root after the source UL and opens on construction (no trigger).
// Selecting a leaf collapses submenus but leaves the root in place.
new CerbUI.Menu(ul, { inline: true, onSelect: (li, src) => { out.textContent = src.dataset.id; } });{/literal}</pre>
			</div>
		</div>

		{* Example: hover navbar — linked menus that swap on hover *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hover navbar &mdash; a shared <code>hoverGroup</code> swaps menus as you move between triggers</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-nav1">Configure</button>
				<button type="button" class="cerb-ui-button" id="uiref-menu-nav2">Records</button>
				<ul id="uiref-menu-nav1-src" hidden>
					<li data-id="cfg.mail">Mail</li>
					<li data-id="cfg.bots">Bots</li>
					<li data-id="cfg.plugins">Plugins</li>
				</ul>
				<ul id="uiref-menu-nav2-src" hidden>
					<li data-id="rec.tickets">Tickets</li>
					<li data-id="rec.orgs">Organizations</li>
					<li data-id="rec.workers">Workers</li>
				</ul>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Each trigger gets its own Menu; a shared hoverGroup means hovering one closes the others.
new CerbUI.Menu(ul1, { hoverTrigger: btn1, hoverGroup: 'nav' });
new CerbUI.Menu(ul2, { hoverTrigger: btn2, hoverGroup: 'nav' });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Menu: small cascading menu — icons via onRenderItem, a separator, submenus
	(function() {
		const ul = document.getElementById('uiref-menu-small');
		const trigger = document.getElementById('uiref-menu-small-trigger');
		const out = document.getElementById('uiref-menu-small-out');
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			new CerbUI.Menu(ul, {
				clickTrigger: trigger,
				onSelect: function(li, src) { if(out) out.textContent = src.dataset.id || li.textContent; },
				onRenderItem: function(li, src) {
					const icon = src.dataset.icon; // bare name -> a cerb-icon; ".foo" -> raw class(es)
					if(icon) {
						const ico = document.createElement('span');
						ico.className = icon.charAt(0) === '.' ? icon.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + icon);
						ico.style.marginRight = '0.5em';
						li.insertBefore(ico, li.firstChild);
					}
				}
			});
		}
	})();

	// Menu: 100,000 items, virtualized — built lazily on first open
	(function() {
		const ul = document.getElementById('uiref-menu-huge');
		const trigger = document.getElementById('uiref-menu-huge-trigger');
		const out = document.getElementById('uiref-menu-huge-out');
		let menu = null;
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			trigger.addEventListener('click', function() {
				if(!menu) {
					const frag = document.createDocumentFragment();
					for(let i = 0; i < 100000; i++) {
						const li = document.createElement('li');
						li.dataset.id = 'item-' + i;
						li.textContent = 'Item ' + i.toLocaleString();
						frag.appendChild(li);
					}
					ul.appendChild(frag);
					menu = new CerbUI.Menu(ul, { filter: true, onSelect: function(li, src) { if(out) out.textContent = src.dataset.id; } });
				}
				menu.isOpen() ? menu.close() : menu.open(trigger);
			});
		}
	})();

	// Menu: deep cascade (viewport flip/clamp)
	(function() {
		const ul = document.getElementById('uiref-menu-deep');
		const trigger = document.getElementById('uiref-menu-deep-trigger');
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			const menu = new CerbUI.Menu(ul, { filter: true });
			trigger.addEventListener('click', function() { menu.isOpen() ? menu.close() : menu.open(trigger); });
		}
	})();

	// Menu: inline mode — auto-opens in document flow
	(function() {
		const ul = document.getElementById('uiref-menu-inline');
		const out = document.getElementById('uiref-menu-inline-out');
		if(ul && window.CerbUI && CerbUI.Menu) {
			new CerbUI.Menu(ul, { inline: true, onSelect: function(li, src) { if(out) out.textContent = src.dataset.id; } });
		}
	})();

	// Menu: hover navbar — linked menus via a shared hoverGroup
	(function() {
		if(!(window.CerbUI && CerbUI.Menu)) return;
		[['uiref-menu-nav1', 'uiref-menu-nav1-src'], ['uiref-menu-nav2', 'uiref-menu-nav2-src']].forEach(function(p) {
			const btn = document.getElementById(p[0]);
			const ul = document.getElementById(p[1]);
			if(btn && ul) new CerbUI.Menu(ul, { hoverTrigger: btn, hoverGroup: 'uiref-nav' });
		});
	})();
})();
</script>
