<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions:</legend>
		
		<div>
			<textarea name="params[interactions_yaml]" class="cerb-code-editor placeholders" data-editor-mode="ace/mode/yaml" style="width:100%;">{$widget->extension_params.interactions_yaml}</textarea>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $editor = $config.find('.cerb-code-editor');
	
	$editor
		.cerbCodeEditor()
		;
	
	cerbAutocompleteSuggestions.getYamlFormInteractions(function(json) {
		$editor
			.cerbCodeEditorAutocompleteYaml({
				autocomplete_suggestions: json
			})
			;
	});
});
</script>