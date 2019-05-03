<fieldset id="pageConfig{$model->id}" class="peek">
<legend>Display this content: <small>(Markdown)</small></legend>

<textarea name="params[content]" class="placeholders cerb-code-editor" data-editor-mode="ace/mode/markdown">{$model->params.content}</textarea>

</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#pageConfig{$model->id}');
	
	$fieldset.find('textarea.cerb-code-editor')
		.cerbCodeEditor()
		;
});
</script>
