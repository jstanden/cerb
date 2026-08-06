{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="pages">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">Show these pages in the navigation bar</div></div>
	<div id="pp-{$form_id}"></div>
</div>

<span id="pp-inputs-{$form_id}"></span>
</form>

<script type="application/json" id="pp-data-{$form_id}">{$pages_json nofilter}</script>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
$(function() {
	const formId = '{/literal}{$form_id}{literal}';
	const $frm = $('#' + formId);

	Devblocks.formDisableSubmit($frm);

	if(!(window.CerbUI && CerbUI.PriorityPicker))
		return;

	// Per-page appearance: a document icon tinted by a stable per-id color (same hashing as record avatars)
	const decorate = function(it) {
		it.icon = 'file-document';
		if(CerbUI.chooserCore)
			it.color = CerbUI.chooserCore.monogramColor('workspace_page:' + it.id);
		return it;
	};

	let items = [];
	try { items = JSON.parse(document.getElementById('pp-data-' + formId).textContent || '[]').map(decorate); } catch(e) {}

	const $inputs = $('#pp-inputs-' + formId);
	let pp = null;

	pp = new CerbUI.PriorityPicker(document.getElementById('pp-' + formId), {
		items: items,
		icon: 'collection',
		headerLabel: 'Pages',
		emptyText: 'No pages',
		outsideIgnore: '.cerb-ui-chooser--panel', // the inline adder's dropdown attaches to <body>
		// Inline "add a page" chooser at the bottom of the popover — no second popup, never closes the picker.
		// Each pick appends a new (selected) page row for sorting/toggling.
		panelFooter: function(picker) {
			const wrap = document.createElement('div');

			if(!CerbUI.RecordChooser)
				return wrap;

			const host = document.createElement('div');
			wrap.appendChild(host);

			const rc = new CerbUI.RecordChooser(host, {
				context: 'workspace_page',
				searchButton: false, // autocomplete only — the full-popup dialog would close the picker
				searchPlaceholder: 'Add a page…',
				emptyIcon: 'collection',
				exclude: function() { return picker.getItems().map(function(it) { return it.id; }); },
				onSelect: function(item) {
					picker.setItems(picker.getItems().concat([decorate({
						id: String(item.id),
						label: item.label,
						selected: true
					})]), { preserveSelection: true });
					rc.clear(false);
					rc.openAutocomplete(); // keep the menu open to add the next page
				},
				onResults: function(results, term) {
					// Nothing left to add (the empty query returns no pages) → hide the adder until a save frees some up
					if(!term && results.length === 0 && wrap.parentNode)
						wrap.parentNode.style.display = 'none';
				}
			});

			wrap.cerbCleanup = function() { rc.destroy(); };
			return wrap;
		},
		onChange: function(state) {
			$inputs.empty();
			state.selected.forEach(function(id) {
				$('<input>', { type: 'hidden', name: 'pages[]', value: id }).appendTo($inputs);
			});
			Devblocks.saveAjaxTabForm($frm);
		}
	});
});
{/literal}
</script>
