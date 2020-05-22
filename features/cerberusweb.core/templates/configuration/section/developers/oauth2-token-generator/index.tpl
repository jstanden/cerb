<h2>OAuth2 Token Generator</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupOAuth2TokenGenerator" onsubmit="return false;">
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
				<button type="button" class="chooser-abstract" data-field-name="oauth_app_id" data-context="{Context_OAuthApp::ID}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
				
				{$oauth_app = null}
				
				<ul class="bubbles chooser-container">
					{if $oauth_app}
						<li><input type="hidden" name="oauth_app_id" value="{$oauth_app->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_OAuthApp::ID}" data-context-id="{$oauth_app->id}">{$oauth_app->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.worker'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%" valign="top">
				<button type="button" class="chooser-abstract" data-field-name="worker_id" data-context="{Context_Worker::ID}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$worker = null}
				
				<ul class="bubbles chooser-container">
					{if $worker}
						<li><input type="hidden" name="worker_id" value="{$worker->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_Worker::ID}" data-context-id="{$worker->id}">{$worker->getName()}</a></li>
					{/if}
				</ul>
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
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.create'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="status" style="margin-top:10px;"></div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupOAuth2TokenGenerator');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = $('<span class="cerb-ajax-spinner"/>');
	
	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
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
