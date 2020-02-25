<h2>Avatars</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupAvatars" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="avatars">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>
	
	<b>Default picture on gendered contact records:</b>
	<div style="margin:5px 0px 5px 10px;">
		{$avatar_default_style_contact = $settings->get('cerberusweb.core','avatar_default_style_contact',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT)}
		<label><input type="radio" name="avatar_default_style_contact" value="monograms" {if in_array($avatar_default_style_contact,['monograms',''])}checked="checked"{/if}> Monograms</label>
		<label><input type="radio" name="avatar_default_style_contact" value="silhouettes" {if $avatar_default_style_contact == 'silhouettes'}checked="checked"{/if}> Silhouettes</label>
	</div>
	
	<b>Default picture on gendered worker records:</b>
	<div style="margin:5px 0px 5px 10px;">
		{$avatar_default_style_worker = $settings->get('cerberusweb.core','avatar_default_style_worker',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER)}
		<label><input type="radio" name="avatar_default_style_worker" value="monograms" {if in_array($avatar_default_style_worker,['monograms',''])}checked="checked"{/if}> Monograms</label>
		<label><input type="radio" name="avatar_default_style_worker" value="silhouettes" {if $avatar_default_style_worker == 'silhouettes'}checked="checked"{/if}> Silhouettes</label>
	</div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupAvatars');
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.saveAjaxForm($frm);
		})
	;
});
</script>
