{$div_id = uniqid()}
<div class="cerb-ui-form" id="ds{$div_id}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<span>Data query</span>
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
		</label>
		<textarea id="dq{$div_id}" class="placeholders" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->params.data_query}</textarea>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $div = $('#ds{$div_id}');

	const dq = new CerbUI.DataQuery($div.find('#dq{$div_id}')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true,
	});
});
</script>
