{* Unique root id per render — all lookups are scoped relative to it (no fixed/colliding ids). *}
{$cfg_uid = uniqid('dtbcfg_')}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
#{$cfg_uid} .dtb-cfg-intro { opacity: 0.7; font-weight: 400; margin-bottom: 0.75em; line-height: 1.4; }
#{$cfg_uid} .dtb-cfg-handle { flex: 0 0 14px; align-self: stretch; min-height: 20px; cursor: grab; color: var(--cerb-color-background-contrast-170);
	background-image: linear-gradient(currentColor, currentColor), linear-gradient(currentColor, currentColor);
	background-size: 12px 2px; background-repeat: no-repeat; background-position: center calc(50% - 3px), center calc(50% + 3px); }
#{$cfg_uid} .dtb-cfg-handle:active { cursor: grabbing; }
#{$cfg_uid} .dtb-cfg-remove { background: none; border: 0; padding: 2px 4px; margin: 0; cursor: pointer; color: var(--cerb-color-text); opacity: 0.5; }
#{$cfg_uid} .dtb-cfg-remove:hover { opacity: 1; }
</style>

<div id="{$cfg_uid}" style="min-width:460px;">
	<div class="dtb-cfg-intro">
		Choose which projects appear on this board, set each project's accent color, and drag to set the default order.
		Everyone viewing this board shares this set; each viewer toggles their own focus and order from it.
	</div>

	{* Sortable project rows — DOM order is the saved order. *}
	<div class="dtb-cfg-rows cerb-u-flex cerb-u-flex-column cerb-u-gap-2">
		{foreach from=$rows item=row}
			<div class="dtb-cfg-row cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-1 cerb-u-px-2 cerb-u-rounded-1 cerb-u-bgg-1" data-id="{$row.id}">
				<span class="dtb-cfg-handle" aria-hidden="true"></span>
				<input type="text" class="dtb-cfg-color" name="colors[]" value="{$row.color}">
				<span class="dtb-cfg-namebox cerb-u-flex cerb-u-flex-column cerb-u-flex-1">
					<a class="cerb-peek-trigger dtb-cfg-name no-underline cerb-u-truncate cerb-u-cursor-pointer" data-context="cerb.contexts.task.project" data-context-id="{$row.id}">{$row.label}</a>
					{if $row.owner}<span class="dtb-cfg-owner cerb-u-fs-n4 cerb-u-opacity-50 cerb-u-truncate">{$row.owner}</span>{/if}
				</span>
				<input type="hidden" name="project_ids[]" value="{$row.id}">
				<button type="button" class="dtb-cfg-remove" title="Remove" aria-label="Remove">
					<span class="cerb-icons cerb-icon-circle-remove"></span>
				</button>
			</div>
		{/foreach}
	</div>
	<div class="dtb-cfg-empty cerb-u-opacity-50 cerb-u-italic cerb-u-py-2"{if $rows} hidden{/if}>No projects yet — add one below.</div>

	{* Add control: a RecordChooser; picking a project appends a row at the bottom. *}
	<div class="dtb-cfg-add cerb-u-mt-3">
		<div class="dtb-cfg-chooser"></div>
	</div>

	<div class="dtb-cfg-footer cerb-u-flex cerb-u-justify-end cerb-u-gap-2 cerb-u-mt-3">
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-dtb-cfg="cancel">Cancel</button>
		<button type="button" class="cerb-ui-button" data-dtb-cfg="save">Save</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const panel = document.getElementById('{$cfg_uid}');
	if(!panel || !window.CerbUI) return;

	const tabId = '{$workspace_tab->id}';
	const rowsEl = panel.querySelector('.dtb-cfg-rows');
	const emptyEl = panel.querySelector('.dtb-cfg-empty');

	// Auto-seed palette (mirrors WorkspaceTab_DailyTaskBoard::PALETTE / CerbUI.palettes.category10)
	const PALETTE = [{foreach from=$palette item=c name=pal}{if !$smarty.foreach.pal.first}, {/if}'{$c}'{/foreach}];

	function rowIds() {
		return Array.prototype.map.call(rowsEl.querySelectorAll('.dtb-cfg-row'), function(r) { return r.getAttribute('data-id'); });
	}

	function syncEmpty() {
		if(emptyEl) emptyEl.hidden = rowsEl.querySelectorAll('.dtb-cfg-row').length > 0;
	}

	function bindColorPicker(input) {
		if(CerbUI.ColorPicker) new CerbUI.ColorPicker(input, { palette: PALETTE, showInput: false });
	}

	function bindRemove(btn) {
		btn.addEventListener('click', function() { btn.closest('.dtb-cfg-row').remove(); syncEmpty(); });
	}

	// Click a project name → open its peek (view mode) to disambiguate same-named projects.
	function bindPeek(anchor) {
		if(window.jQuery && jQuery.fn.cerbPeekTrigger) jQuery(anchor).cerbPeekTrigger();
	}

	// Wire the server-rendered rows
	Array.prototype.forEach.call(rowsEl.querySelectorAll('.dtb-cfg-color'), bindColorPicker);
	Array.prototype.forEach.call(rowsEl.querySelectorAll('.dtb-cfg-remove'), bindRemove);
	Array.prototype.forEach.call(rowsEl.querySelectorAll('.cerb-peek-trigger'), bindPeek);

	// Append a new row for a chosen project (dedupe by id)
	function addRow(item) {
		const id = String(item.id);
		if(!id || rowIds().indexOf(id) !== -1) return; // dedupe

		const color = PALETTE[rowsEl.querySelectorAll('.dtb-cfg-row').length % PALETTE.length];

		const row = document.createElement('div');
		row.className = 'dtb-cfg-row cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-1 cerb-u-px-2 cerb-u-rounded-1 cerb-u-bgg-1';
		row.setAttribute('data-id', id);

		const handle = document.createElement('span');
		handle.className = 'dtb-cfg-handle';
		handle.setAttribute('aria-hidden', 'true');
		row.appendChild(handle);

		const colorInput = document.createElement('input');
		colorInput.type = 'text';
		colorInput.className = 'dtb-cfg-color';
		colorInput.name = 'colors[]';
		colorInput.value = color;
		row.appendChild(colorInput);

		const namebox = document.createElement('span');
		namebox.className = 'dtb-cfg-namebox cerb-u-flex cerb-u-flex-column cerb-u-flex-1';
		const name = document.createElement('a');
		name.className = 'cerb-peek-trigger dtb-cfg-name no-underline cerb-u-truncate cerb-u-cursor-pointer';
		name.setAttribute('data-context', 'cerb.contexts.task.project');
		name.setAttribute('data-context-id', id);
		name.textContent = item.label || ('#' + id);
		namebox.appendChild(name);
		// The chooser autocomplete carries the owner label in `sublabel` (built from meta.owner).
		if(item.sublabel) {
			const owner = document.createElement('span');
			owner.className = 'dtb-cfg-owner cerb-u-fs-n4 cerb-u-opacity-50 cerb-u-truncate';
			owner.textContent = 'owned by ' + item.sublabel;
			namebox.appendChild(owner);
		}
		row.appendChild(namebox);

		const hid = document.createElement('input');
		hid.type = 'hidden';
		hid.name = 'project_ids[]';
		hid.value = id;
		row.appendChild(hid);

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'dtb-cfg-remove';
		remove.title = 'Remove';
		remove.setAttribute('aria-label', 'Remove');
		remove.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
		row.appendChild(remove);

		rowsEl.appendChild(row);
		bindColorPicker(colorInput);
		bindRemove(remove);
		bindPeek(name);
		syncEmpty();
	}

	// RecordChooser as an adder — no `name` so it never posts; the rows are authoritative.
	if(CerbUI.RecordChooser) {
		const rc = new CerbUI.RecordChooser(panel.querySelector('.dtb-cfg-chooser'), {
			context: 'task_project',
			multiple: true,
			searchPlaceholder: 'Add a project…',
			emptyIcon: 'collection',
			exclude: function() { return rowIds(); }, // hide projects already on the board from autocomplete
			onSelect: function(item) { addRow(item); rc.clear(); },
		});
	}

	// Drag-to-order
	if(CerbUI.Sortable) {
		new CerbUI.Sortable(rowsEl, { items: '> .dtb-cfg-row', handle: '.dtb-cfg-handle' });
	}

	function dialog() { return CerbUI.Dialog ? CerbUI.Dialog.from(panel) : null; }

	// Cancel
	const cancelBtn = panel.querySelector('[data-dtb-cfg="cancel"]');
	if(cancelBtn) cancelBtn.addEventListener('click', function() { const d = dialog(); if(d) d.close(); });

	// Save — post the rows (project_ids[] + parallel colors[] in DOM/drag order), then reload the tab.
	const saveBtn = panel.querySelector('[data-dtb-cfg="save"]');
	if(saveBtn) saveBtn.addEventListener('click', function() {
		const fd = new FormData();
		fd.append('c', 'pages');
		fd.append('a', 'invokeTab');
		fd.append('tab_id', tabId);
		fd.append('action', 'saveConfig');
		Array.prototype.forEach.call(rowsEl.querySelectorAll('.dtb-cfg-row'), function(row) {
			const colorInput = row.querySelector('.dtb-cfg-color');
			fd.append('project_ids[]', row.getAttribute('data-id'));
			fd.append('colors[]', colorInput ? colorInput.value : '');
		});

		saveBtn.disabled = true;
		genericAjaxPost(fd, '', '', function(json) {
			if(json && json.status === 'ok') {
				const d = dialog();
				if(d) { d.markClean(); d.close(); }
				// Reload just this board's tab so the new shared scope + colors take effect.
				const boardRoot = document.getElementById('dtb-' + tabId);
				const cerbTabs = boardRoot ? window.CerbUI?.Tabs?.fromPanel(boardRoot) : null;
				if(cerbTabs) { cerbTabs.refresh(); return; }
				if(window.location) window.location.reload();
			} else {
				saveBtn.disabled = false;
			}
		}, { dataType: 'json', error: function() { saveBtn.disabled = false; } });
	});
})();
</script>
