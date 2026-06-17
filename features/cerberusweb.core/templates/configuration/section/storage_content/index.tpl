<style nonce="{DevblocksPlatform::getRequestNonce()}">
{* Storage-specific layout: the lifecycle grid (wraps cerb-ui tiles) and the per-store action rows *}
{* Fixed side columns keep the connector band aligned across cards regardless of store-name length *}
.cerb-storage-lifecycle { display:grid; grid-template-columns:minmax(10em,15em) minmax(80px,1fr) minmax(10em,15em); align-items:center; gap:0.8em; margin-bottom:0.9em; }

.cerb-storage-stores { display:flex; flex-direction:column; gap:0.45em; margin-top:0.75em; }
.cerb-storage-store { display:flex; align-items:center; gap:0.6em; }
.cerb-storage-store--role { flex:0 0 1.1em; display:flex; justify-content:center; }
.cerb-storage-store--swatch { width:11px; height:11px; border-radius:3px; flex-shrink:0; }
.cerb-storage-store--name { font-weight:600; flex:0 0 160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cerb-storage-store--metric { color:var(--cerb-color-background-contrast-150); flex:0 0 120px; white-space:nowrap; }
.cerb-storage-store--bar { flex:1 1 auto; height:8px; border-radius:4px; overflow:hidden; background:var(--cerb-color-background-contrast-240); }
.cerb-storage-store--bar > span { display:block; height:100%; border-radius:4px; transition:width 0.3s ease; }
.cerb-storage-store--action-slot { flex:0 0 130px; display:flex; align-items:center; justify-content:flex-end; min-height:2.4em; }
.cerb-storage-store--action { cursor:pointer; color:var(--cerb-color-link); white-space:nowrap; }
{* Objects/Size toggle: each .cerb-storage-metric-swap (card --summary + every store row) shows objects or size
   per the container's data-storage-metric. Objects is the default so there's no flash before JS sets the attr. *}
.cerb-storage-metric-swap [data-metric="size"] { display:none; }
[data-storage-metric="size"] .cerb-storage-metric-swap [data-metric="objects"] { display:none; }
[data-storage-metric="size"] .cerb-storage-metric-swap [data-metric="size"] { display:inline; }
</style>

<div class="cerb-ui-page cerb-ui-page--max-width">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">{'common.storage'|devblocks_translate|capitalize}</div>
			<div class="cerb-ui-header--subtitle">Per-schema storage profiles, lifecycle, and object distribution</div>
		</div>
		<div class="cerb-ui-header--right">
			<div class="cerb-ui-chip" title="Entire MySQL database — all records, not just storage objects">
				<div class="cerb-ui-chip--head">Database</div>
				<div><div class="cerb-ui-chip--label">Data</div><div class="cerb-ui-chip--value">{$total_db_data|devblocks_prettybytes:1}</div></div>
				<div><div class="cerb-ui-chip--label">Indexes</div><div class="cerb-ui-chip--value">{$total_db_indexes|devblocks_prettybytes:1}</div></div>
				<div><div class="cerb-ui-chip--label">DB Disk</div><div class="cerb-ui-chip--value">{$total_db_size|devblocks_prettybytes:1}</div></div>
			</div>
			<div class="cerb-ui-switcher" id="cerb-storage-toggle-cerbui">
				<button type="button" data-value="objects" class="cerb-ui-switcher--active">Objects</button>
				<button type="button" data-value="size">Size</button>
			</div>
		</div>
	</div>

	{if !empty($storage_distribution)}
		<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-border-3">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-database" style="color:var(--cerb-color-background-contrast-160);"></span> Storage distribution</div>
				<div class="cerb-ui-header--summary"><b>{$storage_total_count}</b> objects across <b>{$storage_distribution|count}</b> store{if ($storage_distribution|count) != 1}s{/if}</div>
			</div>

			{* Each segment carries both metrics + a store-identity color key; CerbUI.Distbar(legend:true) sizes them + builds a matching legend below *}
			<div class="cerb-ui-distbar" id="cerb-storage-distbar">
				{foreach from=$storage_distribution item=d key=dk}
					{if $d.storage_profile_id}
						{$_lp = $storage_profiles[$d.storage_profile_id]}
						{$d_name = ($_lp) ? $_lp->name : $d.storage_extension}
					{else}
						{$_le = $storage_engines[$d.storage_extension]}
						{$d_name = ($_le) ? $_le->name : $d.storage_extension}
					{/if}
					<span data-color-key="{$d.storage_extension}:{$d.storage_profile_id}" data-label="{$d_name}" data-value-objects="{$d.count}" data-value-size="{$d.bytes}" data-text-size="{$d.bytes|devblocks_prettybytes}"></span>
				{/foreach}
			</div>
		</div>
	{/if}

	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--label">Record schemas</div>
	</div>

	<div data-cerb-schemas-container>
	{foreach from=$storage_schemas item=schema key=schema_id}
		<div data-cerb-storage-schema="{$schema_id}">
		{include file="devblocks:cerberusweb.core::configuration/section/storage_content/rule.tpl"}
		</div>
	{/foreach}
	</div>


</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const STORE_KEY = 'cerb.storage.metric';
	let metric = window.localStorage ? (localStorage.getItem(STORE_KEY) || 'objects') : 'objects';
	if(metric !== 'size') metric = 'objects';

	// One shared color scale keyed by store identity (data-color-key) so the summary bar, every card bar,
	// the store-row swatches, and the lifecycle tile icons give the same store the same color
	const scale = (window.CerbUI && CerbUI.colorScale) ? CerbUI.colorScale() : null;

	// Share this scale with the edit/migrate popups so a store keeps the same color there as here
	window.cerbStorageColorScale = scale;

	// Top "Storage distribution" panel: a Distbar with a matching legend. Built first so it primes the
	// scale in summary order.
	let topBar = null;
	if(window.CerbUI && CerbUI.Distbar) {
		const distEl = document.getElementById('cerb-storage-distbar');
		if(distEl) topBar = new CerbUI.Distbar(distEl, { key: metric, legend: true, scale: scale });
	}

	// Size each store row's bar as value / card-total for the active metric; color via the shared scale
	function sizeStoreBars(card) {
		const attr = CerbUI.valueAttr(metric);
		const fills = Array.from(card.querySelectorAll('.cerb-storage-store--bar > span'));
		const sum = fills.reduce((a, s) => a + (parseFloat(s.dataset[attr]) || 0), 0);
		fills.forEach(function(s) {
			const v = parseFloat(s.dataset[attr]) || 0;
			s.style.width = (sum > 0 ? v / sum * 100 : 0) + '%';
			if(scale) s.style.backgroundColor = scale.color(s.dataset.colorKey);
		});
	}

	// Size the store-row bars + color the store swatches and lifecycle tile icons within a card scope (shared scale)
	function initCards(root) {
		if(!(window.CerbUI && CerbUI.valueAttr)) return;
		root.querySelectorAll('[data-cerb-cerbui-card]').forEach(function(card) {
			sizeStoreBars(card);
			if(scale) {
				card.querySelectorAll('[data-color-key]').forEach(function(el) {
					if(el.classList.contains('cerb-storage-store--swatch') || el.classList.contains('cerb-ui-tile--icon'))
						el.style.backgroundColor = scale.color(el.dataset.colorKey);
				});
			}
		});
	}

	// Reflect the active metric on the schemas container — CSS shows objects-or-size per store row from it
	const schemasContainer = document.querySelector('[data-cerb-schemas-container]');
	function applyMetricAttr() {
		if(schemasContainer) schemasContainer.setAttribute('data-storage-metric', metric);
	}
	applyMetricAttr();

	initCards(document);

	// Re-init a card after its AJAX refresh (edit/migrate); reuse the same scale
	window.cerbStorageInitCard = function(scopeEl) {
		const node = (scopeEl && scopeEl.jquery) ? scopeEl.get(0) : scopeEl;
		initCards(node || document);
	};

	// Objects/Size toggle drives the top Distbar, the per-row metric text (via the container attr), and the bars
	if(window.CerbUI && CerbUI.Switcher) {
		new CerbUI.Switcher(document.getElementById('cerb-storage-toggle-cerbui'), {
			storageKey: STORE_KEY,
			onSelect: function(value) {
				metric = value;
				applyMetricAttr();
				if(topBar) topBar.setKey(value);
				document.querySelectorAll('[data-cerb-cerbui-card]').forEach(sizeStoreBars);
			}
		});
	}

	// Action delegation (edit / migrate / in-progress monitor) on the schema cards
	$('[data-cerb-schemas-container]').on('click', function(e) {
		let $target = $(e.target);

		let $edit = $target.closest('[data-cerb-schema-id]');
		if($edit.length) {
			e.stopPropagation();
			let schema_id = $edit.attr('data-cerb-schema-id');
			genericAjaxPopup('peek','c=config&a=invoke&module=storage_content&action=showStorageSchemaPeek&ext_id=' + encodeURIComponent(schema_id), null, false);
			return;
		}

		let $migrate = $target.closest('[data-cerb-migrate]');
		if($migrate.length) {
			e.stopPropagation();
			genericAjaxPopup('peek','c=config&a=invoke&module=storage_content&action=showMigratePopup'
				+ '&schema_id=' + encodeURIComponent($migrate.attr('data-schema-id'))
				+ '&src_extension=' + encodeURIComponent($migrate.attr('data-src-extension'))
				+ '&src_profile_id=' + encodeURIComponent($migrate.attr('data-src-profile-id')), null, false);
			return;
		}

		let $monitor = $target.closest('[data-cerb-migrate-monitor]');
		if($monitor.length) {
			e.stopPropagation();
			let $trigger = $('<a/>')
				.attr('data-context','cerb.contexts.queue.job')
				.attr('data-context-id', String($monitor.attr('data-job-id')))
				.css('display','none')
				.appendTo('body');
			$trigger
				.cerbPeekTrigger({ width: '600' })
				.on('cerb-peek-closed', function(ev) {
					ev.stopPropagation();
					$trigger.remove();
				})
				.trigger('click');
			return;
		}
	});
});
</script>
