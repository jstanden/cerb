<style nonce="{DevblocksPlatform::getRequestNonce()}">
#formStorageMigrate .cerb-storage-tile { cursor:pointer; user-select:none; }
#formStorageMigrate .cerb-storage-tile .cerb-ui-tile--caret { margin-left:0.4em; color:var(--cerb-color-background-contrast-160); font-size:0.85em; }
#formStorageMigrate .cerb-storage-tile--locked { cursor:default; }
</style>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formStorageMigrate" name="formStorageMigrate">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="storage_content">
<input type="hidden" name="action" value="startMigration">
<input type="hidden" name="schema_id" value="{$schema->manifest->id}">
<input type="hidden" name="src_extension" value="{$src_extension}">
<input type="hidden" name="src_profile_id" value="{$src_profile_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !empty($destinations)}
	{$dst0 = $destinations[0]}
	{* The menu writes the chosen destination value here; the form submit serializes it as `dst` *}
	<input type="hidden" name="dst" value="{$default_dst}">

	{* Tile icon backgrounds are colored client-side by the storage page's shared CerbUI color scale *}
	<div class="cerb-storage-lifecycle">
		<div class="cerb-ui-tile cerb-storage-tile cerb-storage-tile--locked" id="tileSrc">
			<span class="cerb-ui-tile--icon" data-color-key="{$source.color_key}"><span class="cerb-icons {$source.icon}"></span></span>
			<div class="cerb-ui-tile--text">
				<div class="cerb-ui-tile--kind">from</div>
				<div class="cerb-ui-tile--name">{$source.name}</div>
			</div>
			<span class="cerb-icons cerb-icon-lock cerb-ui-tile--caret"></span>
		</div>

		<div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right cerb-ui-separator--dashed cerb-storage-move">
			move <b>{$src_stats.count}</b> object{if $src_stats.count != 1}s{/if} (<b>{$src_stats.bytes|devblocks_prettybytes}</b>)
		</div>

		<div class="cerb-ui-tile cerb-storage-tile" id="tileDst" data-value="{$default_dst}">
			<span class="cerb-ui-tile--icon" data-color-key="{$dst0.color_key}"><span class="cerb-icons {$dst0.icon}"></span></span>
			<div class="cerb-ui-tile--text">
				<div class="cerb-ui-tile--kind">to</div>
				<div class="cerb-ui-tile--name">{$dst0.label}</div>
			</div>
			<span class="cerb-icons cerb-icon-chevron-down cerb-ui-tile--caret"></span>
		</div>
	</div>

	{* Menu source (parsed once by CerbUI.Menu); icons injected from data-* via onRenderItem *}
	<ul id="menuDst" hidden>
		{foreach from=$destinations item=dest}
			<li data-value="{$dest.value}" data-name="{$dest.label}" data-icon="{$dest.icon}" data-color-key="{$dest.color_key}">{$dest.label}</li>
		{/foreach}
	</ul>

	<br>
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> Start migration</button>
{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">No destinations</div>
					<div class="cerb-ui-header--subtitle">
						There are no available storage destinations to migrate to.
						Please create a new storage profile first.
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#formStorageMigrate');
	const $popup = genericAjaxPopupFind($frm);
	const schemaId = '{$schema->manifest->id}';

	// Reuse the storage page's shared scale so tile colors match the card behind this popup
	const scale = window.cerbStorageColorScale || ((window.CerbUI && CerbUI.colorScale) ? CerbUI.colorScale() : null);

	const tileSrc = document.getElementById('tileSrc');
	const tileDst = document.getElementById('tileDst');
	const dstInput = $frm[0].querySelector('input[name=dst]');

	// Color a tile's icon square by its store-identity key (shared CerbUI scale)
	function colorTile(tile) {
		if(!scale || !tile) return;
		const ico = tile.querySelector('.cerb-ui-tile--icon');
		const key = ico ? ico.getAttribute('data-color-key') : null;
		if(ico && key) ico.style.backgroundColor = scale.color(key);
	}
	colorTile(tileSrc);
	colorTile(tileDst);

	// Swap a tile's glyph / name / color from a chosen store's data
	function applyTile(tile, d) {
		const ico = tile.querySelector('.cerb-ui-tile--icon');
		const glyph = ico ? ico.querySelector('.cerb-icons') : null;
		const nameEl = tile.querySelector('.cerb-ui-tile--name');
		if(nameEl) nameEl.textContent = d.name;
		if(glyph) glyph.className = 'cerb-icons ' + (d.icon || 'cerb-icon-database');
		if(ico && d.colorKey) {
			ico.setAttribute('data-color-key', d.colorKey);
			ico.style.backgroundColor = scale ? scale.color(d.colorKey) : '';
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

	// Destination tile menu (the source is fixed and already excluded server-side)
	const menuDstUl = document.getElementById('menuDst');
	if(menuDstUl && tileDst && window.CerbUI && CerbUI.Menu) {
		const dstMenu = new CerbUI.Menu(menuDstUl, {
			fixed: true,
			onRenderItem: renderItem,
			onSelect: function(li, src) {
				if(dstInput) dstInput.value = src.dataset.value;
				tileDst.dataset.value = src.dataset.value;
				applyTile(tileDst, { name: src.dataset.name, icon: src.dataset.icon, colorKey: src.dataset.colorKey });
				dstMenu.close();
			}
		});
		tileDst.addEventListener('click', function() {
			dstMenu.isOpen() ? dstMenu.close() : dstMenu.open(tileDst);
		});
	}

	$popup.one('popup_open', function() {
		$(this).dialog('option', 'title', 'Migrate Storage');

		$frm.find('button.submit').on('click', function(e) {
			e.stopPropagation();

			const funcStartMigration = function() {
				const $btn = $(this).prop('disabled', true);

				genericAjaxPost('formStorageMigrate', null, null, function(json) {
					if(!json || typeof json !== 'object' || json.status !== 'ok' || !json.job_id) {
						$btn.prop('disabled', false);
						if(json && json.error)
							Devblocks.createAlertError(json.error);
						return;
					}

					$popup.dialog('close');

					// Open the queue job monitor popup
					const $trigger = $('<a/>')
							.attr('data-context', 'cerb.contexts.queue.job')
							.attr('data-context-id', String(json.job_id))
							.css('display', 'none')
							.appendTo('body');

					$trigger
							.cerbPeekTrigger({ width: '600' })
							.on('cerb-peek-closed', function(ev) {
								ev.stopPropagation();
								$trigger.remove();

								// Refresh this schema's block so the "migrating..." indicator updates,
								// then re-apply the current Objects/Size metric to the new card
								const $schema = $('[data-cerb-storage-schema="' + schemaId + '"]');
								if($schema.length)
									genericAjaxGet($schema, 'c=config&a=invoke&module=storage_content&action=showStorageSchema&ext_id=' + encodeURIComponent(schemaId), function() {
										if(window.cerbStorageInitCard) window.cerbStorageInitCard($schema);
									});
							})
							.trigger('click');
				});
			};

			CerbUI.Confirm.open({
				title: 'Are you sure?',
				body: 'This will start a migration of {$src_stats.count} storage objects.',
				onConfirm: funcStartMigration,
			});
		});
	});
});
</script>
