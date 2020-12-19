<form action="{devblocks_url}{/devblocks_url}" id="portalConfig{$model->id}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$model->id}">

<fieldset class="peek">
	<legend>Use this identity pool:</legend>
	
	<button type="button" class="chooser-abstract" data-field-name="params[identity_pool_id]" data-context="{CerberusContexts::CONTEXT_IDENTITY_POOL}" data-single="true" data-query="" data-query-required=""><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{if $identity_pool}
			<li><input type="hidden" name="params[identity_pool]" value="{$identity_pool->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_IDENTITY_POOL}" data-context-id="{$identity_pool->id}">{$identity_pool->name}</a></li>
		{/if}
	</ul>
</fieldset>

<fieldset class="peek">
	<legend>Custom Stylesheet (CSS)</legend>
	
	<textarea name="params[user_stylesheet]" class="cerb-textarea-code-editor" data-editor-mode="ace/mode/css">{$params.user_stylesheet}</textarea>
</fieldset>

<button type="button" class="cerb-button-save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#portalConfig{$model->id}');
	
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
	$frm.find('.chooser-abstract').cerbChooserTrigger();
	
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