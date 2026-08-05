{$uniqid = uniqid('sheetBuilder')}
<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Sheet Builder</div>
		<div class="cerb-ui-header--subtitle">Design a sheet schema visually against a sample dataset, then copy the generated KATA into a widget or an <code>await:form</code> sheet.</div>
	</div>
</div>

<div id="{$uniqid}" class="cerb-sb-standalone"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	new CerbUI.SheetBuilder(document.getElementById('{$uniqid}'), {
		columnSchema: {$column_schema_json nofilter},
		layoutSchema: {$layout_schema_json nofilter},
		dataSourceSchema: {$datasource_schema_json nofilter},
		allowedColumnTypes: {$allowed_column_types_json nofilter},
		allowedDataSourceTypes: {$allowed_datasource_types_json nofilter},
		recordTypes: {$record_types_json nofilter},
		sheetDataAutomations: {$sheet_data_automations_json nofilter}
	});
});
</script>
