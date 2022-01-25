{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="login">

<div style="margin-bottom:10px;">
	<b>Authenticate logins using these methods:</b>
</div>

{foreach from=$login_extensions item=ext}
<fieldset class="black peek" style="background:none;">
	<legend><label><input type="checkbox" name="login_extensions[]" value="{$ext->id}" {if isset($login_extensions_enabled.{$ext->id})}checked="checked"{/if} onclick="$(this).closest('fieldset').find('> div').toggle();"> {$ext->manifest->name}</label></legend>
	
	<div style="margin-left:25px;{if isset($login_extensions_enabled.{$ext->id})}display:block;{else}display:none;{/if}">
		{$ext->renderConfigForm($portal)}
	</div>
</fieldset>
{/foreach}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
		
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
	
	$frm.find('.cerb-chooser-trigger')
		.cerbChooserTrigger()
	;
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});
});
</script>