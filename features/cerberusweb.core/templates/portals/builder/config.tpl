<form action="{devblocks_url}{/devblocks_url}" id="portalConfig{$model->id}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$model->id}">

<fieldset class="peek">
	<legend>Portal Configuration (KATA)</legend>
	<textarea name="params[portal_kata]" data-editor-mode="ace/mode/cerb_kata">{$params.portal_kata}</textarea>
</fieldset>

<button type="button" class="cerb-button-save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#portalConfig{$model->id}');
	
	$frm.find('button.cerb-button-save')
		.on('click', function(e) {
			e.stopPropagation();
			
			genericAjaxPost($frm, '', null, function(json) {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else {
					Devblocks.createAlert('Saved!', 'note');
				}
			});
		})
		;
	
	$frm.find('textarea.cerb-textarea-code-editor').cerbCodeEditor();

	// Editors
	$frm.find('textarea[name="params[portal_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaPortal
		})
		.nextAll('pre.ace_editor')
	;
});
</script>