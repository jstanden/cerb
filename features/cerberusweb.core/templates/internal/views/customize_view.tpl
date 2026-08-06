<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="saveCustomize">
<input type="hidden" name="id" value="{$view->id}">
<div class="block" style="margin:5px;">
<h3 style="margin-bottom:10px;color:inherit;">{'common.customize'|devblocks_translate|capitalize}</h3>

{* Page-local layout composing the cerb-ui components (no flex utilities yet) *}
<style nonce="{DevblocksPlatform::getRequestNonce()}">
.cerb-customize-cols { flex:1 1 0; min-width:0; max-width:480px; display:flex; flex-direction:column; gap:5px; }
.cerb-customize-cols .cerb-ui-tile { cursor:grab; padding-right:1.8em; }
/* relative for the absolute remove button — but NOT while it's the drag helper (that needs position:fixed) */
.cerb-customize-cols .cerb-ui-tile:not(.cerb-ui-sortable--helper) { position:relative; }
.cerb-customize-cols .cerb-ui-tile:active { cursor:grabbing; }
.cerb-customize-grip { opacity:0.45; flex-shrink:0; }
.cerb-customize-remove { position:absolute; top:3px; right:5px; appearance:none; border:0; background:none; box-shadow:none; padding:0; margin:0; height:auto; min-height:0; cursor:pointer; opacity:0; color:var(--cerb-color-background-contrast-150); font-size:1.4em; line-height:1; }
/* beat the global BUTTON:hover gradient/chrome so the X stays flat (no raised pill) */
.cerb-customize-remove:hover { background:none; box-shadow:none; }
.cerb-customize-cols .cerb-ui-tile:hover .cerb-customize-remove { opacity:0.85; }
.cerb-customize-cols .cerb-ui-tile:hover .cerb-customize-remove:hover { opacity:1; }
.cerb-customize-cols:empty::before { content:attr(data-empty); display:block; padding:8px; opacity:0.6; font-style:italic; }

/* Right-hand actions: the "+ Add column" trigger + Reset, stacked so they stay put as the list grows */
.cerb-customize-cols--actions { display:flex; flex-direction:row; gap:6px; flex-shrink:0; }
/* Picked rows in the floating menu: grayed + a green check, kept in their natural place (not re-sorted) */
.cerb-customize-menu--item.is-picked { opacity:0.5; }
.cerb-customize-menu--item.is-picked .cerb-customize-menu--check { visibility:visible; }
.cerb-customize-menu--icon { margin-right:0.5em; color:var(--cerb-customize-icon-color, currentColor); }
.cerb-customize-menu--check { visibility:hidden; margin-left:0.5em; flex-shrink:0; color:var(--cerb-color-tag-green); }
/* On the active/hover row the palette colors can clash with the highlight — match the text color instead */
.cerb-ui-menu--item-active .cerb-customize-menu--icon,
.cerb-ui-menu--item-active .cerb-customize-menu--check { color:inherit; }
</style>

{* Custom Views *}
{$is_custom = $view->isCustom()}

{* Trigger Views *}
{if substr($view->id,0,9)=="_trigger_"}
	{$is_trigger = true}
{else}
	{$is_trigger = false}
{/if}

{if $is_custom}
{$workspace_list = $view->getCustomWorklistModel()}
{/if}

{if $is_custom || $is_trigger}
<fieldset class="peek peek-noborder black">
	<legend>{'common.title'|devblocks_translate|capitalize}</legend>
	
	<input type="text" name="title" value="{$view->name}" size="64" autocomplete="off"><br>
</fieldset>
{/if}

{if $is_custom}
<fieldset class="peek peek-noborder black" style="margin-bottom:0;">
	<legend>Restrict the worklist results using this quick search:</legend>
	
	<div id="viewCustomReqQuickSearch{$view->id}" style="margin:5px 0px 0px 0px;">
		<div class="cerb-ui-searchquery" id="viewCustomReqQuery{$view->id}">
			<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
			<div class="cerb-ui-searchquery--field">
				<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
				<textarea name="params_required_query" class="cerb-ui-searchquery--input" rows="1">{$workspace_list->params_required_query}</textarea>
				<span class="cerb-ui-searchquery--caret-anchor"></span>
			</div>
			<div class="cerb-ui-searchquery--right">
				<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
			</div>
		</div>
	</div>
</fieldset>
{/if}

<fieldset class="peek peek-noborder black">
	<legend>
		{'dashboard.columns'|devblocks_translate|capitalize} <small>(<a href="javascript:;" data-cerb-link-clear>{'common.clear'|devblocks_translate|lower}</a>)</small>
	</legend>

	<div class="cerb-customize-layout cerb-u-flex cerb-u-gap-3 cerb-u-items-start">
		{* Selected columns: a CerbUI.Sortable list of tiles (built by JS from the metadata below) *}
		<div class="cerb-customize-cols" id="customizeCols{$view->id}" data-empty="No columns selected yet."></div>

		{* Actions sit to the right so the trigger (and its floating menu) stay put as the list grows *}
		<div class="cerb-customize-cols--actions">
			<button type="button" class="cerb-ui-button" data-cerb-add-column><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize} {'dashboard.columns'|devblocks_translate|lower}</button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-reset-columns><span class="cerb-icons cerb-icon-refresh"></span> {'common.reset'|devblocks_translate|capitalize}</button>
		</div>

		{* Source tree for the "+ Add column" CerbUI.Menu (re-populated by JS on each open) *}
		<ul class="cerb-customize-menu" id="customizeColsMenu{$view->id}" hidden></ul>
	</div>
</fieldset>

<fieldset class="peek peek-noborder black">
	<legend>{'common.options'|devblocks_translate|capitalize}</legend>
	
	<div>
		{'dashboard.num_rows'|devblocks_translate}: <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}">
	</div>
	
	{if $is_custom}
	<div>
		{'common.color'|devblocks_translate|capitalize}: 
		<input type="text" name="view_options[header_color]" value="{$workspace_list->options.header_color|default:'#6A87DB'}" class="color-picker">
	</div>
	<div style="margin-top:1em;">
		<label><input type="checkbox" name="view_options[disable_sorting]" value="1" {if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}checked="checked"{/if}> Prevent workers from changing the sort column</label>
	</div>
	{/if}
	
	<div>
		{$view->renderCustomizeOptions($is_custom)}
	</div>
</fieldset>

{if $is_custom}
	{if $workspace_list}
		{$view_params = $workspace_list->getParamsRequired()}
		{if $view_params}
		<fieldset class="peek peek-noborder black" style="margin-bottom:0;">
			<legend>(Deprecated) Require these filters on the worklist:</legend>
			
			<div id="viewCustomReqFilters{$view->id}" style="margin:5px 0px 0px 10px;">
			{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl" is_custom=true}
			</div>
		</fieldset>
		{/if}
	{/if}
{/if}

<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<button type="button" class="cancel cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>

</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $container = $('#customize{$view->id}');
	const viewId = {$view->id|json_encode nofilter};

	Devblocks.formDisableSubmit($container);

	$container.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#4065f1','#6A87DB','#CF2C1D','#FEAF03','#57970A','#852EE0','#ADADAD','#34434E']
		});
	});

	{if $is_custom}
	const reqQueryEl = document.getElementById('viewCustomReqQuery' + viewId);
	if(reqQueryEl && window.CerbUI && CerbUI.SearchQuery) {
		const reqSq = new CerbUI.SearchQuery(reqQueryEl, {
			onAutocomplete: CerbUI.SearchQuery.queryFieldSource("{$workspace_list->context|default:''}"),
			context: "{$workspace_list->context|default:''}",
		});
		const reqAcBtn = reqQueryEl.querySelector('[data-action=autocomplete]');
		if(reqAcBtn) reqAcBtn.addEventListener('click', () => reqSq.openAutocomplete());
	}
	{/if}

	// === Columns picker (CerbUI.Sortable list of tiles + CerbUI.Menu of available columns) ===
	const colsMeta = {$columns_meta_json nofilter};
	const colsDefaults = {$default_columns_json nofilter};
	const initialSelected = {$selected_tokens_json nofilter};

	const listEl = document.getElementById('customizeCols' + viewId);
	const menuUl = document.getElementById('customizeColsMenu' + viewId);
	const addBtn = $container.find('[data-cerb-add-column]')[0];
	const resetBtn = $container.find('[data-cerb-reset-columns]')[0];
	const selectedTokens = new Set();
	let colMenu = null;
	let sortable = null;

	const escSel = function(t) { return (window.CSS && CSS.escape) ? CSS.escape(t) : t; };

	// Reflect a token's picked state on its rendered menu row(s), if currently in the DOM
	const syncMenuItem = function(token) {
		const picked = selectedTokens.has(token);
		document.querySelectorAll('.cerb-customize-menu--item[data-token="' + escSel(token) + '"]')
			.forEach(function(li) { li.classList.toggle('is-picked', picked); });
	};

	const refreshAllMenuItems = function() {
		document.querySelectorAll('.cerb-customize-menu--item[data-token]')
			.forEach(function(li) { li.classList.toggle('is-picked', selectedTokens.has(li.dataset.token)); });
	};

	// Build a draggable tile (with its hidden columns[] input) for a column token
	const makeTile = function(token) {
		const meta = colsMeta[token];
		if(!meta) return null;

		const tile = document.createElement('div');
		tile.className = 'cerb-ui-tile cerb-ui-tile--block';
		tile.dataset.token = token;

		const grip = document.createElement('span');
		grip.className = 'cerb-customize-grip cerb-icons cerb-icon-menu-hamburger';
		tile.appendChild(grip);

		const icon = document.createElement('span');
		icon.className = 'cerb-ui-tile--icon';
		icon.style.background = 'var(--cerb-color-tag-' + meta.color + ')';
		const glyph = document.createElement('span');
		glyph.className = 'cerb-icons cerb-icon-' + meta.icon;
		icon.appendChild(glyph);
		tile.appendChild(icon);

		const text = document.createElement('div');
		text.className = 'cerb-ui-tile--text cerb-u-flex-1';
		if(meta.eyebrow) {
			const kind = document.createElement('div');
			kind.className = 'cerb-ui-tile--kind';
			kind.textContent = meta.eyebrow;
			text.appendChild(kind);
		}
		const name = document.createElement('div');
		name.className = 'cerb-ui-tile--name';
		name.textContent = meta.label;
		text.appendChild(name);
		tile.appendChild(text);

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'cerb-customize-remove';
		remove.title = 'Remove column';
		// Icon goes in a child span so the cerb-icons mask keeps its background-color (currentColor);
		// putting it on the button would be killed by the button's own background:none.
		const removeIcon = document.createElement('span');
		removeIcon.className = 'cerb-icons cerb-icon-circle-remove';
		remove.appendChild(removeIcon);
		remove.addEventListener('click', function(e) {
			e.stopPropagation();
			removeColumn(token);
		});
		tile.appendChild(remove);

		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'columns[]';
		input.value = token;
		tile.appendChild(input);

		return tile;
	};

	const addColumn = function(token) {
		if(selectedTokens.has(token) || !colsMeta[token])
			return;
		const tile = makeTile(token);
		if(!tile) return;
		listEl.appendChild(tile);
		selectedTokens.add(token);
		if(sortable && sortable.refresh) sortable.refresh();
		syncMenuItem(token);
	};

	const removeColumn = function(token) {
		const tile = listEl.querySelector('[data-token="' + escSel(token) + '"]');
		if(tile) tile.remove();
		selectedTokens.delete(token);
		if(sortable && sortable.refresh) sortable.refresh();
		syncMenuItem(token);
	};

	const setColumns = function(tokens) {
		listEl.replaceChildren();
		selectedTokens.clear();
		tokens.forEach(addColumn);
		refreshAllMenuItems();
	};

	// Rebuild the "+ Add column" floating menu: all columns, grouped by fieldset, in Cerb's natural
	// order (not re-sorted by selection). Clicking a row toggles it; picked rows are grayed + checked
	// and stay in place. closeOnSelect keeps the menu (and submenu) open to toggle several in a row.
	const buildMenu = function() {
		if(colMenu) { colMenu.destroy(); colMenu = null; }

		// Standard fields and custom fields without a fieldset stay together in one flat list;
		// only real custom fieldsets (eyebrow is set) become submenus.
		const flatItems = [];
		const fieldsetGroups = new Map();
		Object.keys(colsMeta).forEach(function(token) {
			const meta = colsMeta[token];
			if(meta.eyebrow) {
				if(!fieldsetGroups.has(meta.eyebrow)) fieldsetGroups.set(meta.eyebrow, []);
				fieldsetGroups.get(meta.eyebrow).push(meta);
			} else {
				flatItems.push(meta);
			}
		});

		const makeLeaf = function(meta) {
			const li = document.createElement('li');
			li.dataset.token = meta.token;
			li.dataset.icon = meta.icon;
			li.dataset.color = meta.color;
			li.textContent = meta.label;
			return li;
		};

		menuUl.replaceChildren();

		// Base fields render flat at the top level
		flatItems.forEach(function(meta) { menuUl.appendChild(makeLeaf(meta)); });

		// Each custom fieldset gets its own submenu
		fieldsetGroups.forEach(function(items, groupName) {
			const parentLi = document.createElement('li');
			parentLi.textContent = groupName;
			const subUl = document.createElement('ul');
			items.forEach(function(meta) { subUl.appendChild(makeLeaf(meta)); });
			parentLi.appendChild(subUl);
			menuUl.appendChild(parentLi);
		});

		colMenu = new CerbUI.Menu(menuUl, {
			filter: true,
			closeOnSelect: false, // stay open (incl. the submenu) so several can be toggled in a row
			onSelect: function(li, src) {
				const token = src.dataset.token;
				if(!token) return;
				if(selectedTokens.has(token)) removeColumn(token); else addColumn(token);
			},
			onRenderItem: function(li, src) {
				const token = src.dataset.token;
				if(!token) return; // group headers have no token
				li.classList.add('cerb-customize-menu--item');
				li.dataset.token = token;

				// Leading type icon. Set the palette color via a custom property (not inline `color`)
				// so the active/hover row can override it to the text color via CSS.
				const ico = document.createElement('span');
				ico.className = 'cerb-customize-menu--icon cerb-icons cerb-icon-' + src.dataset.icon;
				if(src.dataset.color)
					ico.style.setProperty('--cerb-customize-icon-color', 'var(--cerb-color-tag-' + src.dataset.color + ')');
				li.insertBefore(ico, li.firstChild);

				// Trailing check (shown via CSS when the row is picked)
				const check = document.createElement('span');
				check.className = 'cerb-customize-menu--check cerb-icons cerb-icon-checked';
				li.appendChild(check);

				li.classList.toggle('is-picked', selectedTokens.has(token));
			}
		});
	};

	// No handle: the whole tile is draggable (the grip icon is just a visual hint).
	// ghostOrigin: show the origin slot as a dimmed clone of the item being dragged.
	if(window.CerbUI && CerbUI.Sortable)
		sortable = new CerbUI.Sortable(listEl, { ghostOrigin: true });

	// Seed the list with the saved columns, in order
	setColumns(initialSelected);

	if(addBtn) addBtn.addEventListener('click', function(e) {
		e.stopPropagation();
		if(!(window.CerbUI && CerbUI.Menu)) return;
		if(colMenu && colMenu.isOpen()) { colMenu.close(); return; }
		buildMenu();
		colMenu.open(addBtn);
	});

	if(resetBtn) resetBtn.addEventListener('click', function(e) {
		e.stopPropagation();
		setColumns(colsDefaults);
	});

	$container.find('[data-cerb-link-clear]').on('click', function(e) {
		e.stopPropagation();
		setColumns([]);
	});

	$container.find('button.save').on('click', function(e) {
		e.stopPropagation();
		let formData = new FormData($container[0]);
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'saveCustomize');

		genericAjaxPost(formData, null, null, function() {
			genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');
		});
	});

	$container.find('button.cancel').on('click', function(e) {
		e.stopPropagation();
		toggleDiv('customize{$view->id}','none');
	});
});
</script>