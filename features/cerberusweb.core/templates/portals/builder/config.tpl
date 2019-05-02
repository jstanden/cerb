<form action="{devblocks_url}{/devblocks_url}" id="portalConfig{$model->id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="community_portal">
<input type="hidden" name="action" value="handleProfileTabAction">
<input type="hidden" name="portal_id" value="{$model->id}">
<input type="hidden" name="tab_action" value="saveConfigurationJson">

<fieldset class="peek">
	<legend>Custom Stylesheet (CSS)</legend>
	
	<textarea name="params[user_stylesheet]" class="cerb-textarea-code-editor" data-editor-mode="ace/mode/css">{$params.user_stylesheet}</textarea>
</fieldset>

<fieldset class="peek">
	<legend>Footer (HTML)</legend>
	
	<textarea name="params[footer_html]" class="cerb-textarea-code-editor placeholders" data-editor-mode="ace/mode/twig">{$params.footer_html}</textarea>
</fieldset>

<button type="button" class="cerb-button-save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#portalConfig{$model->id}');
	
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
	$frm.find('.chooser-abstract').cerbChooserTrigger();
	
	$frm.sortable({
		items: 'li',
		helper: 'clone',
		opacity: 0.7,
		tolerance: 'pointer'
	});
	
	var $button_submit = $frm.find('button.cerb-button-save')
		.on('click', function(e) {
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
});
</script>