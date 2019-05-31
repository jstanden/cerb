{$div_id = uniqid()}
<div id="{$div_id}">
	Run this <b>data query</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}:
	<div>
		<textarea name="params[data_query]" data-editor-mode="ace/mode/cerb_query" placeholder="" class="placeholders">{$widget->params.data_query}</textarea>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	
	$div.find('textarea')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		;
});
</script>