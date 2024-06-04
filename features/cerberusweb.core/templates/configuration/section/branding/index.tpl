{$logo_updated_at = time()}

<h2>Branding</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBranding">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="branding">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div style="display:flex;">
	<fieldset style="flex:1 1 50%;">
		<legend>{'common.logo'|devblocks_translate|capitalize} (light)</legend>
		
		<div style="margin:5px;background-color:white;">
			<img class="img-logo" src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{/devblocks_url}?v={$logo_updated_at}" style="max-width:45vw;height:80px;margin:10px;">
		</div>
		
		<button type="button" class="button-file-upload" data-context="resource" data-context-id="ui.logo" data-edit="type:cerb.resource.image description:&quot;The logo displayed in the top left of the UI&quot;" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-edit"></span></button>
	</fieldset>
	
	<fieldset style="flex:1 1 50%;">
		<legend>{'common.logo'|devblocks_translate|capitalize} (dark)</legend>

		<div style="margin:5px;background-color:rgb(32,32,32);">
			<img class="img-logo-dark" src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo-dark{/devblocks_url}?v={$logo_updated_at}" style="max-width:45vw;height:80px;margin:10px;">
		</div>
		
		<button type="button" class="button-file-upload" data-context="resource" data-context-id="ui.logo.dark" data-edit="type:cerb.resource.image description:&quot;The dark variation of the logo displayed in the top left of the UI&quot;" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-edit"></span></button>
	</fieldset>
</div>

<fieldset>
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>
	
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
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupBranding');

	Devblocks.formDisableSubmit($frm);
	
	$frm.find('button.button-file-upload')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted cerb-peek-aborted', function(e) {
			e.stopPropagation();

			var $logo = $('#cerb-logo');
			var $img = $frm.find('img.img-logo');
			var $img_dark = $frm.find('img.img-logo-dark');
			var now = new Date().getTime();

			$img.attr('src', '{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{/devblocks_url}?v=' + now);
			$img_dark.attr('src', '{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo-dark{/devblocks_url}?v=' + now);

			{if $pref_dark_mode}
			$logo.css('background-image', 'url(' + $img_dark.attr('src') + ')');
			{else}
			$logo.css('background-image', 'url(' + $img.attr('src') + ')');
			{/if}
		})
	;
	
	$frm.find('button.submit')
		.click(function() {
			Devblocks.saveAjaxForm($frm);
		})
		;
	
	$frm.find('textarea.cerb-editor')
		.cerbCodeEditor()
		;
});
</script>