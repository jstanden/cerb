{$uniqid = uniqid('sheetBuilder')}
<div data-cerb-sheet-builder-popup>
	<div id="{$uniqid}" class="cerb-sb-embedded"></div>

	<div class="cerb-sb--apply-bar">
		<button type="button" class="cerb-ui-button" data-cerb-apply><span class="cerb-icons cerb-icon-check"></span> Apply to form</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-cancel>Cancel</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var el = document.getElementById('{$uniqid}');
	var $popup = $(el).closest('[data-cerb-sheet-builder-popup]');

	Devblocks.loadResources({
		'js': [
			'/resource/cerberusweb.core/js/cerb-ui/sheet-builder.js?v={$smarty.const.APP_BUILD}'
		]
	}, function() {
		if(!(window.CerbUI && CerbUI.SheetBuilder)) {
			console.error('CerbUI.SheetBuilder failed to load');
			return;
		}

		var sb = new CerbUI.SheetBuilder(el, {
			columnSchema: {$column_schema_json nofilter},
			layoutSchema: {$layout_schema_json nofilter},
			dataSourceSchema: {$datasource_schema_json nofilter},
			allowedColumnTypes: {$allowed_column_types_json nofilter},
			allowedDataSourceTypes: {$allowed_datasource_types_json nofilter},
			recordTypes: {$record_types_json nofilter},
			sheetDataAutomations: {$sheet_data_automations_json nofilter},
			initial: {$initial_json nofilter}
		});

		// Apply → hand the two element parts up to the opener (Form Builder), which writes them back + closes.
		$popup.find('[data-cerb-apply]').on('click', function(e) {
			e.stopPropagation();
			$popup.trigger('cerb-sheet-builder-apply', [sb.getParts()]);
		});

		$popup.find('[data-cerb-cancel]').on('click', function(e) {
			e.stopPropagation();
			if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog.from) {
				var d = CerbUI.Dialog.from(el);
				if(d) d.close();
			}
		});
	});
});
</script>
