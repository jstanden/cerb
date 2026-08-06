{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="history">

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-section>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Worklist columns <small class="cerb-u-text-muted cerb-u-fw-400">leave blank for default &middot; drag to reorder</small></div>
		<div class="cerb-ui-header--right">
			<span class="cerb-u-text-muted cerb-u-fs-n1" data-cerb-count></span>
			<button type="button" class="cerb-ui-selectall" data-cerb-toggleall title="Select all"><span class="cerb-icons cerb-icon-checked"></span></button>
		</div>
	</div>

	<div class="cerb-ui-tile-grid" id="historyColsGrid_{$form_id}">
		{foreach from=$history_columns item=column key=token}
		{$selected = in_array($token, $history_params.columns)}
		<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell{if $selected} is-selected{/if}" data-token="{$token}">
			<input type="checkbox" class="cerb-history-col--cb" name="history_columns[]" value="{$token}" {if $selected}checked="checked"{/if}>
			<span class="cerb-ui-tile--name">{$column->db_label|capitalize}</span>
		</label>
		{/foreach}
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	// Section tracks its own count + select-all/clear toggle; each tile dims until checked.
	const section = $frm.find('[data-cerb-section]')[0];
	if(section) {
		const countEl = section.querySelector('[data-cerb-count]');
		const toggleAll = section.querySelector('[data-cerb-toggleall]');
		const boxes = function() { return section.querySelectorAll('input.cerb-history-col--cb'); };

		const update = function() {
			const all = boxes();
			let n = 0;
			all.forEach(function(cb) {
				const cell = cb.closest('.cerb-ui-tile-grid--cell');
				if(cell) cell.classList.toggle('is-selected', cb.checked);
				if(cb.checked) n++;
			});
			if(countEl) countEl.textContent = n + ' / ' + all.length;
			if(toggleAll) {
				const allSel = all.length && n === all.length;
				const icon = toggleAll.querySelector('.cerb-icons');
				if(icon) icon.className = 'cerb-icons ' + (allSel ? 'cerb-icon-unchecked' : 'cerb-icon-checked');
				toggleAll.title = allSel ? 'Clear all' : 'Select all';
			}
		};

		section.addEventListener('change', function(e) {
			if(e.target.matches('input.cerb-history-col--cb')) update();
		});

		if(toggleAll) toggleAll.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			const all = boxes();
			const target = !Array.from(all).every(function(cb) { return cb.checked; });
			all.forEach(function(cb) { cb.checked = target; });
			update();
		});

		update();
	}

	// Columns are reorderable (drag the whole tile); DOM order = saved/render order. grid:true gives
	// row-aware 2D insertion, helper:'clone' drags a copy on <body> (position:fixed unaffected by the
	// popup's transformed ancestors). Sortable ignores checkbox pointer-downs so a plain click still toggles.
	const grid = document.getElementById('historyColsGrid_{$form_id}');
	if(grid && window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable(grid, { grid: true, helper: 'clone' });

	$frm.find('button.save').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});
});
</script>
