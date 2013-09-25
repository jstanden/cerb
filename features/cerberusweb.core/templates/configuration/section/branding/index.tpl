<h2>Branding</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBranding" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="branding">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>Settings</legend>
	
	<b>Logo URL:</b> (leave blank for default)<br>
	<input type="text" name="logo" value="{$settings->get('cerberusweb.core','helpdesk_logo_url')}" size="64"><br>
	<br>
	
	<b>Favicon URL:</b> (leave blank for default)<br>
	<input type="text" name="favicon" value="{$settings->get('cerberusweb.core','helpdesk_favicon_url')}" size="64"><br>
	<br>

	<b>Helpdesk Title:</b><br>
	<input type="text" name="title" value="{$settings->get('cerberusweb.core','helpdesk_title')}" size="64"><br>
	<br>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$('#frmSetupBranding BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupBranding','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupBranding div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupBranding div.status','Your changes have been saved.');
				}
			});
		})
	;
</script>
