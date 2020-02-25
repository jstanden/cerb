<h2>Branding</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBranding" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="branding">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{'common.logo'|devblocks_translate|capitalize}</legend>
	
	{$logo_updated_at = $settings->get('cerberusweb.core','ui_user_logo_updated_at')}
	
	<div style="margin:5px;">
		<img class="img-logo" src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{/devblocks_url}?v={$logo_updated_at}" style="max-width:90vw;">
	</div>
	<input type="hidden" name="logo_id" value="">
	<button type="button" class="button-file-upload" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-edit"></span></button>
	<button type="button" class="button-file-remove" title="{'common.remove'|devblocks_translate|capitalize}" {if !$logo_updated_at}style="display:none;{/if}"><span class="glyphicons glyphicons-circle-remove"></span></button>
</fieldset>

<fieldset>
	<legend>Settings</legend>
	
	<b>Browser Title:</b><br>
	<input type="text" name="title" value="{$settings->get('cerberusweb.core','helpdesk_title')}" size="64"><br>
	<br>

	<b>Favicon URL:</b> (leave blank for default)<br>
	<input type="text" name="favicon" value="{$settings->get('cerberusweb.core','helpdesk_favicon_url')}" size="64"><br>
</fieldset>

<fieldset>
	<legend>Custom Stylesheet</legend>
	
	<textarea name="user_stylesheet" class="cerb-editor" data-editor-mode="ace/mode/css">{$settings->get('cerberusweb.core','ui_user_stylesheet')}</textarea>
</fieldset>

<div class="cerb-buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupBranding');
	var $logo_img = $frm.find('img.img-logo');
	var $logo_id = $frm.find('input:hidden[name=logo_id]');
	var $button_logo_remove = $frm.find('button.button-file-remove');
	
	$frm.find('button.button-file-upload')
		.on('click', function() {
			var $chooser = genericAjaxPopup('chooser','c=internal&a=invoke&module=records&action=chooserOpenFile&single=1',null,true,'750');
			
			$chooser.one('chooser_save', function(event) {
				var file_id = event.values[0];
				$logo_img.attr('src', DevblocksWebPath + 'files/' + file_id + '/logo');
				$logo_id.val(file_id);
				$button_logo_remove.fadeIn();
			});
		});
	
	$button_logo_remove
		.on('click', function() {
			$logo_img.attr('src', DevblocksWebPath + 'resource/cerberusweb.core/images/wgm/cerb_logo.png');
			$logo_id.val('delete');
			$button_logo_remove.hide();
		});
	
	$frm.find('button.submit')
		.click(function(e) {
			Devblocks.saveAjaxForm($frm);
		})
		;
	
	$frm.find('textarea.cerb-editor')
		.cerbCodeEditor()
		;
});
</script>