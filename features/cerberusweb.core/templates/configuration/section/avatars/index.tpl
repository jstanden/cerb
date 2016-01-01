<h2>Avatars</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupAvatars" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="avatars">
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
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$('#frmSetupAvatars BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupAvatars','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupAvatars div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupAvatars div.status','Your changes have been saved.');
				}
			});
		})
	;
</script>
