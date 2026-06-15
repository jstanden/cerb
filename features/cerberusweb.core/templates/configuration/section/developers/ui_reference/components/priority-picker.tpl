	<div class="cerb-uiref-component" id="priority-picker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-collection"></span>PriorityPicker</div>

		{* Example: progressive enhancement — select + drag-to-reorder a set of projects *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Select + drag-to-reorder &mdash; the collapsed summary expands to a floating panel. Selection and ordering are independent (drag = priority, toggle = today's foci)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-pp-basic"><ul hidden>
					<li data-id="cerb"      data-color="green"  data-selected="true">Cerb</li>
					<li data-id="wgm"       data-color="blue"   data-selected="true">WGM</li>
					<li data-id="home"      data-color="orange" data-selected="true">Home</li>
					<li data-id="sortbrain" data-color="purple">SortBrain</li>
					<li data-id="research"  data-color="gray">Research</li>
					<li data-id="videos"    data-color="red">Videos</li>
					<li data-id="docvec"    data-color="#22d3ee">DocVec</li>
				</ul></div>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Selected: <b id="uiref-pp-basic-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- progressive enhancement: a hidden &lt;ul&gt; of items (data-id / data-color / data-icon / data-selected).
     data-color is a tag name (green, blue, orange, purple, red, gray) or any CSS color. --&gt;
&lt;div id="projects"&gt;&lt;ul hidden&gt;
    &lt;li data-id="cerb" data-color="green" data-selected="true"&gt;Cerb&lt;/li&gt;
    &lt;li data-id="wgm"  data-color="blue"  data-selected="true"&gt;WGM&lt;/li&gt;
    &lt;li data-id="research" data-color="gray"&gt;Research&lt;/li&gt;
&lt;/ul&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const pp = new CerbUI.PriorityPicker(el, {
	icon:            'collection',    // optional leading glyph on the collapsed trigger
	headerLabel:     'Projects',
	headerSubtitle:  'Select multiple projects to view tasks simultaneously',
	emptyText:       'No projects',   // shown collapsed when nothing is selected
	maxHeight:       380,             // px before the list scrolls
	applyText:       'Apply Changes',
	cancelText:      'Cancel',
	onChange: function(state) { /* after Apply: {selected:[ids], order:[ids], items:[...]} — persist here */ },
	// onRender:        function(state) { /* whenever the collapsed summary re-renders */ },
	// onRenderItem:    function(rowLi, item) { /* customize a popover row */ },
	// onRenderSummary: function(item) { /* return a node for a collapsed summary entry */ },
	// itemActions:     function(item) { /* return a node for the per-row --right slot (edit/delete) */ },
	// headerActions:   node | html,    // the header --right slot (e.g. a "New project" button)
});

// Edits live in a working copy; Apply/Cancel appears once dirty. Apply commits + fires onChange;
// Cancel / click-outside / Escape discard.
// API: pp.getState(); pp.getSelected(); pp.setItems(items, {preserveSelection:true});
//      pp.setSelected(ids); pp.setOrder(ids); pp.open(); pp.close(); pp.destroy();{/literal}</pre>
			</div>
		</div>

		{* Example: hooks-only CRUD via slots + dynamic setItems() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hooks-only item CRUD (<code>headerActions</code> / <code>itemActions</code>) + dynamic <code>setItems()</code> &mdash; the core never mutates the list, you do</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-pp-crud"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Pass items directly (no markup needed). CRUD is the consumer's job — wire it through the slots.
const pp = new CerbUI.PriorityPicker(el, {
	headerLabel: 'Projects',
	items: [
		{ id: 'cerb', label: 'Cerb', color: 'green', selected: true },
		{ id: 'wgm',  label: 'WGM',  color: 'blue' },
	],
	headerActions: newProjectButton,        // your "+ New project" control in the header --right slot
	itemActions: function(item) {           // per-row edit/delete in the --right slot
		const del = document.createElement('button');
		del.className = 'cerb-ui-button cerb-ui-button--subtle';
		del.textContent = 'Delete';
		del.addEventListener('click', function() {
			pp.setItems(pp.getItems().filter(function(i) { return i.id !== item.id; }));
		});
		return del;
	},
});

// Change the list from anywhere and re-render; selection + priority order of survivors are preserved.
pp.setItems(updatedItems, { preserveSelection: true });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// PriorityPicker: select + drag-to-reorder projects, reporting the selection on Apply
	(function() {
		const el = document.getElementById('uiref-pp-basic');
		const out = document.getElementById('uiref-pp-basic-result');
		if(el && window.CerbUI && CerbUI.PriorityPicker) {
			const report = function(state) { if(out) out.textContent = state.selected.length ? state.selected.join(', ') : '—'; };
			const pp = new CerbUI.PriorityPicker(el, {
				icon:           'collection',
				headerLabel:    'Projects',
				headerSubtitle: 'Select multiple projects to view tasks simultaneously',
				emptyText:      'No projects',
				onChange:       report,
			});
			report(pp.getState());
		}
	})();

	// PriorityPicker: hooks-only CRUD (header "New project" + per-row delete) + dynamic setItems()
	(function() {
		const el = document.getElementById('uiref-pp-crud');
		if(!el || !window.CerbUI || !CerbUI.PriorityPicker) return;

		const PALETTE = ['green', 'blue', 'orange', 'purple', 'red', 'gray'];
		let nextId = 3;

		// "+ New project" control for the header --right slot
		const newBtn = document.createElement('button');
		newBtn.type = 'button';
		newBtn.className = 'cerb-ui-button cerb-ui-button--subtle';
		newBtn.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> New project';

		const pp = new CerbUI.PriorityPicker(el, {
			headerLabel: 'Projects',
			items: [
				{ id: 'cerb', label: 'Cerb', color: 'green', selected: true },
				{ id: 'wgm',  label: 'WGM',  color: 'blue' },
			],
			headerActions: newBtn,
			itemActions: function(item) {
				const del = document.createElement('button');
				del.type = 'button';
				del.className = 'cerb-ui-button cerb-ui-button--subtle';
				del.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span>';
				del.title = 'Delete';
				del.addEventListener('click', function() {
					pp.setItems(pp.getItems().filter(function(i) { return i.id !== item.id; }));
				});
				return del;
			},
		});

		const addProject = function() {
			const id = 'p' + (nextId++);
			const items = pp.getItems();
			items.push({ id: id, label: 'Project ' + nextId, color: PALETTE[items.length % PALETTE.length] });
			pp.setItems(items, { preserveSelection: true });
		};
		newBtn.addEventListener('click', addProject);
	})();
})();
</script>
