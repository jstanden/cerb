<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">OAuth2 Token Generator</div>
		<div class="cerb-ui-header--subtitle">Manually create worker-scoped OAuth tokens for testing and integration</div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupOAuth2TokenGenerator">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="oauth2_token_generator">
<input type="hidden" name="action" value="generateToken">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>
		Create API Token
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/api/authentication/"}
	</legend>
	
	<table>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>OAuth App:</b>
			</td>
			<td width="99%" valign="top">
				<div class="cerb-ui-record-chooser" id="oauthAppChooser"></div>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.worker'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%" valign="top">
				<div class="cerb-ui-record-chooser" id="workerChooser"></div>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.scopes'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%" valign="top">
				<input type="text" name="scopes" size="45" value="api" placeholder="e.g. api profile">
			</td>
		</tr>

		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.expires'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%" valign="top">
				<input type="text" name="expires_duration" size="4" value="1" placeholder="1">
				<select name="expires_term">
					{$terms = ['minutes','hours','days','weeks','months','years']}
					{foreach from=$terms item=term}
					<option value="{$term}" {if $term=='hours'}selected="selected"{/if}>{$term}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.create'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="status" style="margin-top:10px;"></div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupOAuth2TokenGenerator');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = Devblocks.getSpinner();

	Devblocks.formDisableSubmit($frm);
	if(window.CerbUI && CerbUI.RecordChooser) {
		new CerbUI.RecordChooser($frm.find('#oauthAppChooser')[0], { context: '{CerberusContexts::CONTEXT_OAUTH_APP}', name: 'oauth_app_id', emptyIcon: 'key', searchPlaceholder: 'OAuth App' });
		new CerbUI.RecordChooser($frm.find('#workerChooser')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'worker_id', emptyIcon: 'user', searchPlaceholder: "{'common.worker'|devblocks_translate|capitalize|escape:'javascript' nofilter}" });
	}
		;
	
	$button
		.click(function(e) {
			Devblocks.clearAlerts();
			
			$button.hide();
			$spinner.detach();
			$status.html('').append($spinner);
			
			genericAjaxPost('frmSetupOAuth2TokenGenerator','',null,function(json) {
				$button.fadeIn();
				$status.html('');
				
				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else if (json.html) {
					$status.html(json.html);
					
				} else {
					Devblocks.createAlertError("An unknown error occurred.");
				}
			});
		})
	;
});
</script>
