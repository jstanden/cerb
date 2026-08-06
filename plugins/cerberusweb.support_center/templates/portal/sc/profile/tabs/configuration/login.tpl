{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="login">

<div class="cerb-u-mb-3">
	<b>Authenticate logins using these methods:</b>
</div>

{foreach from=$login_extensions item=ext}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="login_extensions[]" value="{$ext->id}" {if isset($login_extensions_enabled.{$ext->id})}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<div class="cerb-ui-header--title-sm">{$ext->manifest->name}</div>
		</div>
	</div>

	<div class="cerb-ui-login-ext-config" {if !isset($login_extensions_enabled.{$ext->id})}style="display:none;"{/if}>
		{$ext->renderConfigForm($portal)}
	</div>
</div>
{/foreach}

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	// Enhance every toggle (extension enable/disable + each extension's own config toggles).
	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	// The editor lives in a cerb-ui-panel now, so reveal/hide the extension config from the panel (not closest('fieldset')).
	$frm.find('input[name="login_extensions[]"]').on('change', function(e) {
		e.stopPropagation();
		$(this).closest('.cerb-ui-panel').find('.cerb-ui-login-ext-config').toggle(this.checked);
	});

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	$frm.find('button.save').on('click', function(e) {
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
