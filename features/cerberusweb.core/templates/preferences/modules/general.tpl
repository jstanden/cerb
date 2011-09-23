<form action="{devblocks_url}{/devblocks_url}" onsubmit="pwsMatch=(this.change_pass.value==this.change_pass_verify.value);if(!pwsMatch)document.getElementById('preferences_error').innerHTML='The passwords entered do not match.  Try again.';return pwsMatch;" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<fieldset>
	<legend>{$translate->_('preferences.account.settings')|capitalize}</legend>

	<b>{$translate->_('preferences.account.timezone')|capitalize}</b> {if !empty($server_timezone)}({$translate->_('preferences.account.current')} {$server_timezone}){/if}<br>
	<select name="timezone">
		{foreach from=$timezones item=tz}
			<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
		{/foreach}
	</select><br>
	<br>

	<b>{$translate->_('preferences.account.language')|capitalize}</b> {if !empty($selected_language) && isset($langs.$selected_language)}({$translate->_('preferences.account.current')} {$langs.$selected_language}){/if}<br>
	<select name="lang_code">
		{foreach from=$langs key=lang_code item=lang_name}
			<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
		{/foreach}
	</select><br>
	<br>

	<b>{$translate->_('preferences.account.assist')|capitalize}</b><br>
	<label><input type="checkbox" name="assist_mode" value="1" {if $prefs.assist_mode eq 1}checked{/if}> {$translate->_('common.enabled')|capitalize}</label><br>
	<br>

	<b>{$translate->_('preferences.account.keyboard.shortcuts')|capitalize}</b><br>
	<label><input type="checkbox" name="keyboard_shortcuts" value="1" {if $prefs.keyboard_shortcuts eq 1}checked{/if}> {$translate->_('common.enabled')|capitalize}</label><br>
	
</fieldset>

<fieldset>
	<legend>{'common.mail'|devblocks_translate|capitalize}</legend>
	
	<b>{$translate->_('common.options')|capitalize}:</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="checkbox" name="mail_always_show_all" value="1" {if $prefs.mail_always_show_all}checked{/if}> {$translate->_('preferences.account.mail.readall')}</label><br>
	</div>

	<b>{'preferences.account.mail.reply_button'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_reply_button" value="0" {if empty($prefs.mail_reply_button)}checked="checked"{/if}> {'display.reply.quote'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_reply_button" value="1" {if 1==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.no_quote'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.signature'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_signature_pos" value="0" {if empty($prefs.mail_signature_pos)}checked="checked"{/if}> {'preferences.account.mail.signature.none'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_signature_pos" value="1" {if 1==$prefs.mail_signature_pos}checked="checked"{/if}> {'preferences.account.mail.signature.above'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_signature_pos" value="2" {if 2==$prefs.mail_signature_pos}checked="checked"{/if}> {'preferences.account.mail.signature.below'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.status.compose'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_status_compose" value="open" {if 'open'==$prefs.mail_status_compose}checked="checked"{/if}> {'status.open'|devblocks_translate}</label>
		<label><input type="radio" name="mail_status_compose" value="waiting" {if empty($prefs.mail_status_compose) || 'waiting'==$prefs.mail_status_compose}checked="checked"{/if}> {'status.waiting'|devblocks_translate}</label>
		<label><input type="radio" name="mail_status_compose" value="closed" {if 'closed'==$prefs.mail_status_compose}checked="checked"{/if}> {'status.closed'|devblocks_translate}</label>
	</div>

	<b>{'preferences.account.mail.status.reply'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_status_reply" value="open" {if 'open'==$prefs.mail_status_reply}checked="checked"{/if}> {'status.open'|devblocks_translate}</label>
		<label><input type="radio" name="mail_status_reply" value="waiting" {if empty($prefs.mail_status_reply) || 'waiting'==$prefs.mail_status_reply}checked="checked"{/if}> {'status.waiting'|devblocks_translate}</label>
		<label><input type="radio" name="mail_status_reply" value="closed" {if 'closed'==$prefs.mail_status_reply}checked="checked"{/if}> {'status.closed'|devblocks_translate}</label>
	</div>
</fieldset>

<fieldset>
	<legend>{$translate->_('preferences.account.email')|capitalize}</legend>

	{$translate->_('preferences.account.email.associated')}<br>

	<ul id="listWorkerEmailAddresses" style="padding:0px;margin:5px 0px 0px 10px;list-style:none;">
		{foreach from=$addresses item=address}
		<li style="padding-bottom:10px;">
			<input type="hidden" name="worker_emails[]" value="{$address->address}">

			{if $address->address==$active_worker->email}
			<button type="button"><span class="cerb-sprite2 sprite-tick-circle-frame-gray"></span></button>
			{else}
			<button type="button" onclick="if(confirm('Are you sure you want to delete this email address?')) { $(this).closest('li').remove(); }" class="delete"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></button>
			{/if}

			<b>{$address->address}</b>

			{if $address->is_confirmed}
				{if $address->address==$active_worker->email}
				(Primary)
				{/if}
			{else}
			<i>(Pending Verification:</i> <a href="javascript:;" style="font-style:italic;" onclick="document.resendConfirmationForm.email.value='{$address->address}';document.resendConfirmationForm.submit();">{$translate->_('preferences.account.email.address.resend.confirm')}</a>)
			{/if}
		</li>
		{/foreach}
		<li>
			{$translate->_('preferences.account.email.address.add')}<br>
			<div style="padding:5px;">
				<input type="text" name="new_email" size="45" value="" class="input_email">
			</div>
		</li>
	</ul>
</fieldset>

<fieldset>
	<legend>{$translate->_('preferences.account.password.change')|capitalize}</legend>

	<table cellspacing="1" cellpadding="0" border="0">
		<tr>
			<td>{$translate->_('preferences.account.password.new')|capitalize}</td>
			<td><input type="password" name="change_pass" value=""></td>
		</tr>
		<tr>
			<td>{$translate->_('preferences.account.password.verify')|capitalize}</td>
			<td><input type="password" name="change_pass_verify"=""></td>
		</tr>
	</table>

	<div id="preferences_error" style="color:red;font-weight:bold;"></div>
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
</form>

<form action="{devblocks_url}{/devblocks_url}" name="resendConfirmationForm" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="resendConfirmation">
<input type="hidden" name="email" value="">
</form>
