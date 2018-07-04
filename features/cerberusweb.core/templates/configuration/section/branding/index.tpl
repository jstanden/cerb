<h2>Branding</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBranding" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="branding">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Settings</legend>
	
	<b>Favicon URL:</b> (leave blank for default)<br>
	<input type="text" name="favicon" value="{$settings->get('cerberusweb.core','helpdesk_favicon_url')}" size="64"><br>
	<br>

	<b>Browser Title:</b><br>
	<input type="text" name="title" value="{$settings->get('cerberusweb.core','helpdesk_title')}" size="64"><br>
</fieldset>

<fieldset>
	<legend>Custom Stylesheet</legend>
	
	<textarea name="user_stylesheet" class="cerb-editor" data-editor-mode="ace/mode/css">{$settings->get('cerberusweb.core','ui_user_stylesheet')}</textarea>
</fieldset>

<div class="cerb-buttons">
	<div class="status"></div>
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupBranding');
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupBranding','',null,function(json) {
				if(false == json || false == json.status) {
					Devblocks.showError('#frmSetupBranding div.status',json.error);
				} else {
					Devblocks.showSuccess('#frmSetupBranding div.status','Your changes have been saved.');
				}
			});
		})
		;
	
	$frm.find('textarea.cerb-editor')
		.cerbCodeEditor()
		;
});
</script>