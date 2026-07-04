{*
	Shared WYSIWYG field picker for the "record fields" widgets (cards, profiles, workspaces).
	Params:
	  uid        - unique id suffix for element ids (widget id, or a uniqid for new card widgets)
	  css_scope  - CSS selector that scopes the layout styles (the Fields tab container, incl. '#')
	  base_label - heading for the base (non-fieldset) field group, e.g. the record type name
	Reads from the parent template (assigned in PHP via \Cerb\Records\RecordFieldsPreview::build):
	  preview_properties        - all base/standalone fields (key => {label,type,value,params})
	  preview_custom_fieldsets  - all custom fieldsets (id => {model, properties})
	  preview_selected          - saved [group_id => [tokens]] map (which fields are included)
	  dict, custom_field_values - context for the live cell renderer

	Each field is shown roughly as it renders in the widget (sample/real value) with a checkbox; the
	form posts only checked fields as params[properties][{group_id}][] (unchanged save format). Base
	fields are drag-reorderable (order is honored at render time); custom fieldsets are collapsible,
	membership-only panels (their order isn't honored at render time).
*}
<style nonce="{DevblocksPlatform::getRequestNonce()}">
{$css_scope} .cerb-fieldpicker { display:flex; flex-direction:column; gap:14px; }
{$css_scope} .cerb-fieldpicker-section--header { display:flex; align-items:center; gap:0.5em; font-weight:bold; margin-bottom:6px; }
{$css_scope} details.cerb-fieldpicker-section > summary.cerb-fieldpicker-section--header { cursor:pointer; margin-bottom:0; padding:3px 0; }
{$css_scope} details.cerb-fieldpicker-section[open] > summary.cerb-fieldpicker-section--header { margin-bottom:6px; }
{$css_scope} .cerb-fieldpicker-section--header .cerb-icons { flex-shrink:0; opacity:0.7; }
{$css_scope} .cerb-fieldpicker-section--hint, {$css_scope} .cerb-fieldpicker-section--count { font-weight:normal; opacity:0.65; }
{$css_scope} .cerb-fieldpicker-cell { box-sizing:border-box; flex:0 0 240px; max-width:240px; cursor:pointer; }
{$css_scope} .cerb-fieldpicker-cell:not(.is-selected) { opacity:0.4; }
{$css_scope} .cerb-fieldpicker-section--base .cerb-fieldpicker-cell { cursor:grab; }
{$css_scope} .cerb-fieldpicker-section--base .cerb-fieldpicker-cell:active { cursor:grabbing; }
{$css_scope} .cerb-fieldpicker-cell--cb { flex-shrink:0; margin-left:2px; }
{$css_scope} .cerb-fieldpicker-cell--body { flex:1; min-width:0; pointer-events:none; }
{$css_scope} .cerb-fieldpicker-cell--placeholder { opacity:0.6; font-style:italic; }
</style>

<div class="cerb-fieldpicker" id="fieldsPicker{$uid}">
	{* Base / standalone fields — draggable, order is honored at render time *}
	<div class="cerb-fieldpicker-section cerb-fieldpicker-section--base" data-cerb-section>
		<div class="cerb-fieldpicker-section--header">
			<span>{$base_label}</span>
			<span class="cerb-fieldpicker-section--hint">(drag to reorder)</span>
			<span class="cerb-fieldpicker-section--count" data-cerb-count></span>
			<button type="button" class="cerb-ui-selectall cerb-u-ml-auto" data-cerb-toggleall><span class="cerb-icons cerb-icon-checked"></span></button>
		</div>
		<div class="cerb-ui-tile-grid" id="fieldsPickerBase{$uid}">
			{foreach from=$preview_properties item=v key=k}
				{$fp_sel = false}
				{if isset($preview_selected.0) && is_array($preview_selected.0) && in_array($k, $preview_selected.0)}{$fp_sel = true}{/if}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields_picker_cell.tpl" k=$k v=$v group=0 selected=$fp_sel}
			{/foreach}
		</div>
	</div>

	{* Custom fieldsets — collapsible, membership-only panels *}
	{foreach from=$preview_custom_fieldsets item=fieldset key=fsid}
		{if !empty($fieldset.properties)}
		<details class="cerb-fieldpicker-section cerb-fieldpicker-section--fieldset" data-cerb-section>
			<summary class="cerb-fieldpicker-section--header">
				<span class="cerb-icons cerb-icon-collection"></span>
				<span>{$fieldset.model->name}</span>
				<span class="cerb-fieldpicker-section--count" data-cerb-count></span>
				<button type="button" class="cerb-ui-selectall cerb-u-ml-auto" data-cerb-toggleall><span class="cerb-icons cerb-icon-checked"></span></button>
			</summary>
			<div class="cerb-ui-tile-grid">
				{foreach from=$fieldset.properties item=v key=k}
					{$fp_sel = false}
					{if isset($preview_selected.$fsid) && is_array($preview_selected.$fsid) && in_array($k, $preview_selected.$fsid)}{$fp_sel = true}{/if}
					{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields_picker_cell.tpl" k=$k v=$v group=$fsid selected=$fp_sel}
				{/foreach}
			</div>
		</details>
		{/if}
	{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const fpRoot = document.getElementById('fieldsPicker{$uid}');
	if(!fpRoot) return;

	// Each section (base + each fieldset) tracks its own count + select-all/clear toggle
	fpRoot.querySelectorAll('[data-cerb-section]').forEach(function(section) {
		const countEl = section.querySelector('[data-cerb-count]');
		const toggleAll = section.querySelector('[data-cerb-toggleall]');

		const boxes = function() { return section.querySelectorAll('input.cerb-fieldpicker-cell--cb'); };

		const update = function() {
			const all = boxes();
			let n = 0;
			all.forEach(function(cb) {
				const cell = cb.closest('.cerb-fieldpicker-cell');
				if(cell) cell.classList.toggle('is-selected', cb.checked);
				if(cb.checked) n++;
			});
			if(countEl) countEl.textContent = n + ' / ' + all.length;
			if(toggleAll) {
				const allSel = all.length && n === all.length;
				const icon = toggleAll.querySelector('.cerb-icons');
				if(icon) icon.className = 'cerb-icons ' + (allSel ? 'cerb-icon-checked' : 'cerb-icon-unchecked');
				toggleAll.title = allSel ? 'Clear all' : 'Select all';
			}
		};

		// Toggling any field updates this section's count/state
		section.addEventListener('change', function(e) {
			if(e.target.matches('input.cerb-fieldpicker-cell--cb')) update();
		});

		// Select-all / clear (preventDefault so it doesn't also expand/collapse a <details> summary)
		if(toggleAll) toggleAll.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const all = boxes();
			const target = !Array.from(all).every(function(cb) { return cb.checked; });
			all.forEach(function(cb) { cb.checked = target; });
			update();
		});

		update();
	});

	// Base fields are reorderable (drag the whole cell); their DOM order = the saved/render order.
	// grid:true frees the source slot and marks the drop spot with an absolute insertion bar — the
	// grid shows its near-final layout and stays put as the cursor moves (no churn), with row-aware
	// 2D insertion. helper:'clone' drags a copy appended to <body> (so position:fixed isn't thrown
	// off by the popup's transformed ancestors). Sortable ignores pointer-downs on the checkbox and
	// only drags past a move threshold, so a plain click still toggles.
	const fpBase = document.getElementById('fieldsPickerBase{$uid}');
	if(fpBase && window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable(fpBase, { grid: true, helper: 'clone' });
});
</script>
