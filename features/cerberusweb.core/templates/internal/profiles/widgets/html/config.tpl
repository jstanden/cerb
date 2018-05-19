<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Editor" class="peek">
		<legend>Render this template:</legend>
		
		<textarea name="params[template]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.template}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Editor');
	var $textarea = $fieldset.find('textarea[name="params[template]"]');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
});
</script>