<h2>{'common.authentication'|devblocks_translate|capitalize}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupAuth" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="auth">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Single Sign-on (SSO)</legend>
	
	{if $sso_services_available}
		Allow workers to authenticate using their identity at these trusted connected services:
		
		<div class="cerb-sortable" style="margin:5px 0px 0px 10px;">
			{foreach from=$sso_services_available item=sso_service}
			<div class="cerb-sort-item">
				<span class="glyphicons glyphicons-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:2px;"></span>
				
				<label>
					<input type="checkbox" name="params[auth_sso_service_ids][]" value="{$sso_service->id}" {if array_key_exists($sso_service->id, $sso_services_enabled)}checked="checked"{/if}> 
				</label>
				
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-context-id="{$sso_service->id}"><b>{$sso_service->name}</b></a>
			</div>
			{/foreach}
		</div>
	{else}
		You don't have any SSO-enabled connected services configured.
	{/if}
</fieldset>

<fieldset>
	<legend>{'common.auth.mfa'|devblocks_translate|capitalize}</legend>
	
	<label>
		<input type="checkbox" name="params[auth_mfa_allow_remember]" value="1" {if $params.auth_mfa_allow_remember}checked="checked"{/if}> 
		Allow users to remember multi-factor authentication on trusted devices.
	</label>
		
	<div style="margin:0 0 0 25px;">
		Trusted devices must re-authenticate after 
		<input type="text" name="params[auth_mfa_remember_days]" value="{$params.auth_mfa_remember_days}" size="3" maxlength="2" placeholder="7"> 
		days
	</div>
</fieldset>

<div class="cerb-buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupAuth');
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$frm.find('.cerb-sortable')
		.sortable({
			tolerance: 'pointer',
			helper: 'clone',
			handle: '.glyphicons-menu-hamburger',
			items: '.cerb-sort-item',
			opacity: 0.7
		})
		;
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.saveAjaxForm($frm);
		})
		;
});
</script>