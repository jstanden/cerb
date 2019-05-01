<b>Run this data query:</b> 
{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}

<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[data_query]" class="cerb-code-editor" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$params.data_query}</textarea>
</div>

<b>Sheet schema:</b> (YAML)

<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[sheet_yaml]" class="cerb-code-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$params.sheet_yaml}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('textarea.cerb-code-editor')
		.cerbCodeEditor()
		;
});
</script>
