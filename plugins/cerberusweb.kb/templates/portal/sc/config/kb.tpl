{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="kb">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'portal.sc.cfg.choose_kb_topics'|devblocks_translate}</div>
	</div>

	<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-4">
		{assign var=root_id value="0"}
		{foreach from=$tree_map.$root_id item=category key=category_id}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="category_ids[]" value="{$category_id}" id="kbTopic_{$form_id}_{$category_id}" {if isset($kb_roots.$category_id)}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="kbTopic_{$form_id}_{$category_id}">{$categories.$category_id->name}</label>
		</div>
		{/foreach}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Article list</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Articles per page <span class="cerb-ui-form--hint">by default</span></label>
			<div>
				{$opts = [5,10,15,20,25,50,100]}
				<select name="kb_view_numrows">
					{foreach from=$opts item=opt}
					<option value="{$opt}" {if $kb_view_numrows==$opt}selected="selected"{/if}>{$opt}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-section>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Worklist columns <small class="cerb-u-text-muted cerb-u-fw-400">leave blank for default &middot; drag to reorder</small></div>
		<div class="cerb-ui-header--right">
			<span class="cerb-u-text-muted cerb-u-fs-n1" data-cerb-count></span>
			<button type="button" class="cerb-ui-selectall" data-cerb-toggleall title="Select all"><span class="cerb-icons cerb-icon-checked"></span></button>
		</div>
	</div>

	<div class="cerb-ui-tile-grid" id="kbColsGrid_{$form_id}">
		{foreach from=$kb_columns item=column key=token}
		{$selected = in_array($token, $kb_params.columns)}
		<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell{if $selected} is-selected{/if}" data-token="{$token}">
			<input type="checkbox" class="cerb-kb-col--cb" name="kb_columns[]" value="{$token}" {if $selected}checked="checked"{/if}>
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

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	// Columns section tracks its own count + select-all/clear toggle; each tile dims until checked.
	const section = $frm.find('[data-cerb-section]')[0];
	if(section) {
		const countEl = section.querySelector('[data-cerb-count]');
		const toggleAll = section.querySelector('[data-cerb-toggleall]');
		const boxes = function() { return section.querySelectorAll('input.cerb-kb-col--cb'); };

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
			if(e.target.matches('input.cerb-kb-col--cb')) update();
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
	// row-aware 2D insertion, helper:'clone' drags a copy on <body>. Sortable ignores checkbox
	// pointer-downs so a plain click still toggles.
	const grid = document.getElementById('kbColsGrid_{$form_id}');
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
