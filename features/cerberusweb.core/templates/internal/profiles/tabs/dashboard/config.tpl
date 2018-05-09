<div id="tab{$tab->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Column width: <small>(in pixels)</small></legend>
		
		{$column_width = $tab->extension_params.column_width|default:500}
		
		<textarea name="params[column_width]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$column_width}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#tab{$tab->id}Config');
	
	var $textarea = $frm.find('.placeholders');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
});
</script>