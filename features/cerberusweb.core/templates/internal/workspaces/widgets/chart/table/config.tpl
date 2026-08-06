<div id="widget{$widget->id}Config" class="cerb-ui-form" style="margin-top:10px;">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">Run this data query:</div>
			<div class="cerb-ui-header--right">
				{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
			</div>
		</div>

		<textarea id="widget{$widget->id}DataQuery" class="placeholders" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->params.data_query}</textarea>

		<div class="cerb-ui-form--field cerb-u-mt-2">
			<label class="cerb-ui-form--label">Cache</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
				<span class="cerb-u-text-muted">query results for</span>
				<input type="text" size="5" maxlength="6" name="params[cache_secs]" placeholder="e.g. 300" value="{$widget->params.cache_secs}" style="width:6em;flex:0 0 auto;">
				<span class="cerb-u-text-muted">seconds</span>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	const dq = new CerbUI.DataQuery($config.find('#widget{$widget->id}DataQuery')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true,
	});
});
</script>