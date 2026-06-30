<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{'common.authentication'|devblocks_translate|capitalize}</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupAuth" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="auth">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Single Sign-on (SSO)</div>
	</div>

	{if $sso_services_available}
		Allow workers to authenticate using their identity at these trusted connected services:

		<div class="cerb-sortable" style="margin:8px 0 0 0;">
			{foreach from=$sso_services_available item=sso_service}
			<div class="cerb-sort-item cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<span class="cerb-icons cerb-icon-menu-hamburger" style="cursor:move;color:var(--cerb-color-background-contrast-170);"></span>

				<label class="cerb-ui-toggle"><input type="checkbox" name="params[auth_sso_service_ids][]" value="{$sso_service->id}" {if isset($sso_services_enabled[$sso_service->id])}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>

				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=connected_service&context_id={$sso_service->id}{/devblocks_url}?v={$sso_service->updated_at}">
				<a class="cerb-peek-trigger no-underline" data-context="{$connected_service_context}" data-context-id="{$sso_service->id}"><b>{$sso_service->name}</b></a>
			</div>
			{/foreach}
		</div>
	{else}
		<div style="color:var(--cerb-color-background-contrast-150);">You don't have any SSO-enabled connected services configured.</div>
	{/if}
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.auth.mfa'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle"><input id="authMfaAllowRemember" type="checkbox" name="params[auth_mfa_allow_remember]" value="1" {if $params.auth_mfa_allow_remember}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
		<label for="authMfaAllowRemember">Allow users to remember multi-factor authentication on trusted devices.</label>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin:8px 0 0 50px;">
		<span>Trusted devices must re-authenticate after</span>
		<input type="text" name="params[auth_mfa_remember_days]" value="{$params.auth_mfa_remember_days}" size="3" maxlength="2" placeholder="7" style="width:3em;">
		<span>days</span>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">When creating a new worker account</div>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle"><input id="authNewWorkerDisablePassword" type="checkbox" name="params[auth_new_worker_disable_password]" value="1" {if $params.auth_new_worker_disable_password}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
		<label for="authNewWorkerDisablePassword">Disable password-based authentication</label>
	</div>
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin-top:6px;">
		<label class="cerb-ui-toggle"><input id="authNewWorkerRequireMfa" type="checkbox" name="params[auth_new_worker_require_mfa]" value="1" {if $params.auth_new_worker_require_mfa}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
		<label for="authNewWorkerRequireMfa">Require multi-factor authentication</label>
	</div>
</div>

<div>
	<button type="button" id="btnSaveAuth" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupAuth');

	Devblocks.formDisableSubmit($frm);

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	if(window.CerbUI && CerbUI.Toggle) {
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });
	}

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($frm.find('.cerb-sortable').get(0), {
			tolerance: 'pointer',
			helper: 'clone',
			handle: '.cerb-icon-menu-hamburger',
			items: '.cerb-sort-item'
		});

	$frm.find('#btnSaveAuth')
		.click(function(e) {
			e.stopPropagation();
			Devblocks.saveAjaxForm($frm);
		})
	;
});
</script>
