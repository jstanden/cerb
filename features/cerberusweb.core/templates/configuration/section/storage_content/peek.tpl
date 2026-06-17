{$is_archivable = $lifecycle.is_archivable|default:false}
{$active_value = $lifecycle.active|default:'devblocks.storage.engine.database'}
{$archive_value = $lifecycle.archive|default:$active_value}
{$archive_after_days = $lifecycle.archive_after_days|default:1}

{* Archive is "None" (no separate store, no migration) when not archivable, unset, or pointed at the active store *}
{$archive_is_none = (!$is_archivable || $archive_value == '' || $archive_value == $active_value)}

{* Resolve the ACTIVE store -> engine id, display name, icon glyph, color key (engine:profile_id) *}
{if is_numeric($active_value)}
	{$_ap = $storage_profiles[$active_value]}
	{$active_engine_id = $_ap->extension_id}
	{$active_name = $_ap->name}
	{$active_pid = $active_value}
{else}
	{$active_engine_id = $active_value}
	{$_ae = $storage_engines[$active_value]}
	{$active_name = $_ae->name}
	{$active_pid = 0}
{/if}
{$active_icon = $storage_engine_styles[$active_engine_id].icon|default:'cerb-icon-database'}
{$active_color_key = "`$active_engine_id`:`$active_pid`"}

{* Resolve the ARCHIVE store the same way (only when a real one is selected) *}
{if !$archive_is_none}
	{if is_numeric($archive_value)}
		{$_rp = $storage_profiles[$archive_value]}
		{$archive_engine_id = $_rp->extension_id}
		{$archive_name = $_rp->name}
		{$archive_pid = $archive_value}
	{else}
		{$archive_engine_id = $archive_value}
		{$_re = $storage_engines[$archive_value]}
		{$archive_name = $_re->name}
		{$archive_pid = 0}
	{/if}
	{$archive_icon = $storage_engine_styles[$archive_engine_id].icon|default:'cerb-icon-database'}
	{$archive_color_key = "`$archive_engine_id`:`$archive_pid`"}
{/if}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
#frmStorageSchemaPeek .cerb-storage-tile { cursor:pointer; user-select:none; }
#frmStorageSchemaPeek .cerb-storage-tile .cerb-ui-tile--caret { margin-left:0.4em; color:var(--cerb-color-background-contrast-160); font-size:0.85em; }
#frmStorageSchemaPeek .cerb-storage-tile--muted { border-style:dashed; opacity:0.75; }
#frmStorageSchemaPeek .cerb-storage-tile--muted .cerb-ui-tile--icon { background:var(--cerb-color-background-contrast-210) !important; color:var(--cerb-color-background-contrast-150); }
#frmStorageSchemaPeek .cerb-storage-days { font-size:0.95em; }
#frmStorageSchemaPeek .cerb-storage-days input { width:3.5em; text-align:center; }
</style>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmStorageSchemaPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="storage_content">
<input type="hidden" name="action" value="saveStorageSchemaPeek">
<input type="hidden" name="ext_id" value="{$schema->manifest->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{* The save path reads these three; archive == active means "None" (no migration) *}
<input type="hidden" name="active_storage_profile" value="{$active_value}">
{if $is_archivable}
	<input type="hidden" name="archive_storage_profile" value="{if $archive_is_none}{$active_value}{else}{$archive_value}{/if}">
{/if}

{* Tile icon backgrounds are colored client-side by a shared CerbUI color scale (keyed by store identity) *}
<div class="cerb-storage-lifecycle">
	<div class="cerb-ui-tile cerb-storage-tile" id="tileActive" data-value="{$active_value}">
		<span class="cerb-ui-tile--icon" data-color-key="{$active_color_key}"><span class="cerb-icons {$active_icon}"></span></span>
		<div class="cerb-ui-tile--text">
			<div class="cerb-ui-tile--kind">active</div>
			<div class="cerb-ui-tile--name">{$active_name}</div>
		</div>
		<span class="cerb-icons cerb-icon-chevron-down cerb-ui-tile--caret"></span>
	</div>

	{if $is_archivable}
		<div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right cerb-ui-separator--dashed cerb-storage-days" id="daysCell"{if $archive_is_none} hidden{/if}>
			after <input type="number" min="0" name="archive_after_days" value="{$archive_after_days}"> days of inactivity
		</div>

		<div class="cerb-ui-tile cerb-storage-tile{if $archive_is_none} cerb-storage-tile--muted{/if}" id="tileArchive" data-value="{if $archive_is_none}{else}{$archive_value}{/if}">
			<span class="cerb-ui-tile--icon"{if !$archive_is_none} data-color-key="{$archive_color_key}"{/if}><span class="cerb-icons {if $archive_is_none}cerb-icon-ban{else}{$archive_icon}{/if}"></span></span>
			<div class="cerb-ui-tile--text">
				<div class="cerb-ui-tile--kind">archive</div>
				<div class="cerb-ui-tile--name">{if $archive_is_none}None{else}{$archive_name}{/if}</div>
			</div>
			<span class="cerb-icons cerb-icon-chevron-down cerb-ui-tile--caret"></span>
		</div>
	{/if}
</div>

{* Menu sources (parsed once by CerbUI.Menu); icons injected from data-* via onRenderItem *}
<ul id="menuActive" hidden>
	{foreach from=$local_engine_ids item=eng_id}
		{$_e = $storage_engines[$eng_id]}
		{if $_e}<li data-value="{$eng_id}" data-name="{$_e->name}" data-icon="{$storage_engine_styles[$eng_id].icon|default:'cerb-icon-database'}" data-color-key="{$eng_id}:0">{$_e->name}</li>{/if}
	{/foreach}
</ul>

{if $is_archivable}
	<ul id="menuArchive" hidden>
		<li data-value="" data-name="None">None</li>
		<li></li>
		{foreach from=$local_engine_ids item=eng_id}
			{$_e = $storage_engines[$eng_id]}
			{if $_e}<li data-value="{$eng_id}" data-name="{$_e->name}" data-icon="{$storage_engine_styles[$eng_id].icon|default:'cerb-icon-database'}" data-color-key="{$eng_id}:0">{$_e->name}</li>{/if}
		{/foreach}
		{if $storage_profiles}
			<li></li>
			{foreach from=$storage_profiles item=profile key=profile_id}
				<li data-value="{$profile_id}" data-name="{$profile->name}" data-icon="{$storage_engine_styles[$profile->extension_id].icon|default:'cerb-icon-database'}" data-color-key="{$profile->extension_id}:{$profile_id}">{$profile->name}</li>
			{/foreach}
		{/if}
	</ul>
{/if}

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmStorageSchemaPeek');
	const $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	const root = document.getElementById('frmStorageSchemaPeek');
	// Reuse the storage page's shared scale so colors match the card behind this popup (fall back to a fresh one)
	const scale = window.cerbStorageColorScale || ((window.CerbUI && CerbUI.colorScale) ? CerbUI.colorScale() : null);

	const activeInput = root.querySelector('input[name=active_storage_profile]');
	const archiveInput = root.querySelector('input[name=archive_storage_profile]');
	const tileActive = document.getElementById('tileActive');
	const tileArchive = document.getElementById('tileArchive');
	const daysCell = document.getElementById('daysCell');

	// Color a tile's icon square by its store-identity key (shared CerbUI scale)
	function colorTile(tile) {
		if(!scale || !tile) return;
		const ico = tile.querySelector('.cerb-ui-tile--icon');
		const key = ico ? ico.getAttribute('data-color-key') : null;
		if(ico && key) ico.style.backgroundColor = scale.color(key);
	}
	colorTile(tileActive);
	if(tileArchive && !tileArchive.classList.contains('cerb-storage-tile--muted')) colorTile(tileArchive);

	// Swap a tile's glyph / name / color from a chosen store's data
	function applyTile(tile, d) {
		const ico = tile.querySelector('.cerb-ui-tile--icon');
		const glyph = ico ? ico.querySelector('.cerb-icons') : null;
		const nameEl = tile.querySelector('.cerb-ui-tile--name');
		if(nameEl) nameEl.textContent = d.name;
		if(glyph) glyph.className = 'cerb-icons ' + (d.icon || 'cerb-icon-archive');
		if(ico) {
			if(d.colorKey) {
				ico.setAttribute('data-color-key', d.colorKey);
				ico.style.backgroundColor = scale ? scale.color(d.colorKey) : '';
			} else {
				ico.removeAttribute('data-color-key');
				ico.style.backgroundColor = '';
			}
		}
	}

	// Inject a colored glyph chip into each menu item from its source dataset
	function renderItem(li, src) {
		const icon = src.dataset.icon;
		if(!icon) return;
		const chip = document.createElement('span');
		chip.className = 'cerb-icons ' + icon;
		chip.style.marginRight = '0.5em';
		if(scale && src.dataset.colorKey) chip.style.color = scale.color(src.dataset.colorKey);
		li.insertBefore(chip, li.firstChild);
	}

	// Archive = None: muted tile, no days input, store the active value so saveConfig sees archive == active
	function setArchiveNone() {
		if(!tileArchive) return;
		tileArchive.dataset.value = '';
		tileArchive.classList.add('cerb-storage-tile--muted');
		applyTile(tileArchive, { name:'None', icon:'cerb-icon-ban', colorKey:'' });
		archiveInput.value = activeInput.value;
		if(daysCell) daysCell.hidden = true;
	}
	function setArchiveStore(d) {
		if(!tileArchive) return;
		tileArchive.dataset.value = d.value;
		tileArchive.classList.remove('cerb-storage-tile--muted');
		applyTile(tileArchive, d);
		archiveInput.value = d.value;
		if(daysCell) daysCell.hidden = false;
	}

	// Active tile menu — local engines only
	const activeMenu = new CerbUI.Menu(document.getElementById('menuActive'), {
		fixed: true,
		onRenderItem: renderItem,
		onSelect: function(li, src) {
			activeInput.value = src.dataset.value;
			applyTile(tileActive, { name: src.dataset.name, icon: src.dataset.icon, colorKey: src.dataset.colorKey });
			// Keep archive valid against the new active store
			if(tileArchive) {
				if((tileArchive.dataset.value || '') === '')
					archiveInput.value = activeInput.value; // re-sync a None tile
				else if(tileArchive.dataset.value === activeInput.value)
					setArchiveNone(); // archive collided with the new active store
			}
			activeMenu.close();
		}
	});
	tileActive.addEventListener('click', function() {
		activeMenu.isOpen() ? activeMenu.close() : activeMenu.open(tileActive);
	});

	// Archive tile menu — None + engines + profiles, rebuilt per open to exclude the current active store
	const archiveTemplate = document.getElementById('menuArchive');
	let archiveMenu = null;
	function openArchiveMenu() {
		if(archiveMenu) { archiveMenu.destroy(); archiveMenu = null; }
		const ul = archiveTemplate.cloneNode(true);
		ul.removeAttribute('id');
		const activeVal = activeInput.value;
		ul.querySelectorAll('li[data-value]').forEach(function(li) {
			if(li.getAttribute('data-value') === activeVal) li.remove();
		});
		archiveMenu = new CerbUI.Menu(ul, {
			fixed: true,
			onRenderItem: renderItem,
			onSelect: function(li, src) {
				const val = src.dataset.value || '';
				if(val === '')
					setArchiveNone();
				else
					setArchiveStore({ value: val, name: src.dataset.name, icon: src.dataset.icon, colorKey: src.dataset.colorKey });
				archiveMenu.close();
			}
		});
		archiveMenu.open(tileArchive);
	}
	if(tileArchive) {
		tileArchive.addEventListener('click', function() {
			(archiveMenu && archiveMenu.isOpen()) ? archiveMenu.close() : openArchiveMenu();
		});
	}

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{$schema->manifest->name|escape:'javascript' nofilter}");

		$popup.find('button.submit').click(function() {
			let $output = $('[data-cerb-storage-schema="{$schema->manifest->id}"]');
			genericAjaxPost('frmStorageSchemaPeek',$output,null,function() {
				// Re-init the refreshed card's distbar + scale-colored swatches/tiles
				if(window.cerbStorageInitCard) window.cerbStorageInitCard($output);
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>
