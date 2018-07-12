<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>Run this data query:</legend>
		
		<textarea name="params[data_query]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.data_query}</textarea>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Chart options:</legend>
		
		<div>
			The <b>x-axis labels</b> are in the key:
		</div>
		
		<div>
			<textarea name="params[xaxis_key]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.xaxis_key|default:'label'}</textarea>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	$config.find('textarea.placeholders').cerbCodeEditor();
});
</script>