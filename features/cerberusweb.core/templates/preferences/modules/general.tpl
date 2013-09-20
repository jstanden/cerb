<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<fieldset class="peek">
	<legend>{'preferences.account.settings'|devblocks_translate|capitalize}</legend>

	<b>{'preferences.account.timezone'|devblocks_translate|capitalize}</b> {if !empty($server_timezone)}({'preferences.account.current'|devblocks_translate} {$server_timezone}){/if}<br>
	<select name="timezone">
		{foreach from=$timezones item=tz}
			<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
		{/foreach}
	</select><br>
	<br>
	
	<b>{'preferences.account.timeformat'|devblocks_translate|capitalize}</b><br>
	<select name="time_format">
		{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
		{foreach from=$timeformats item=timeformat}
			<option value="{$timeformat}" {if $prefs.time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
		{/foreach}
	</select><br>
	<br>

	<b>{'preferences.account.language'|devblocks_translate|capitalize}</b> {if !empty($selected_language) && isset($langs.$selected_language)}({'preferences.account.current'|devblocks_translate} {$langs.$selected_language}){/if}<br>
	<select name="lang_code">
		{foreach from=$langs key=lang_code item=lang_name}
			<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
		{/foreach}
	</select><br>
	<br>

	<b>{'preferences.account.assist'|devblocks_translate|capitalize}</b><br>
	<label><input type="checkbox" name="assist_mode" value="1" {if $prefs.assist_mode eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label><br>
	<br>

	<b>{'preferences.account.keyboard.shortcuts'|devblocks_translate|capitalize}</b><br>
	<label><input type="checkbox" name="keyboard_shortcuts" value="1" {if $prefs.keyboard_shortcuts eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label><br>
</fieldset>

<fieldset class="peek">
	<legend>{'common.availability'|devblocks_translate|capitalize}</legend>
	
	<b>{'preferences.account.availability.calendar_id'|devblocks_translate}</b><br>
	
	<div style="margin-left:10px;">
		<select name="availability_calendar_id">
			<option value="">- always unavailable -</option>
			{foreach from=$calendars item=calendar}
			{if $calendar->owner_context == CerberusContexts::CONTEXT_WORKER && $calendar->owner_context_id == $active_worker->id}
			<option value="{$calendar->id}" {if $calendar->id==$prefs.availability_calendar_id}selected="selected"{/if}>{$calendar->name}</option>
			{/if}
			{/foreach}
		</select>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.mail'|devblocks_translate|capitalize}</legend>
	
	<b>{'common.options'|devblocks_translate|capitalize}:</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="checkbox" name="mail_always_show_all" value="1" {if $prefs.mail_always_show_all}checked{/if}> {'preferences.account.mail.readall'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.reply_button'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_reply_button" value="0" {if empty($prefs.mail_reply_button)}checked="checked"{/if}> {'display.reply.quote'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_reply_button" value="1" {if 1==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.no_quote'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.reply_textbox_size'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		{'preferences.account.mail.reply_textbox_size.pixels'|devblocks_translate} <input type="text" name="mail_reply_textbox_size_px" size="4" maxlength=4" value="{$prefs.mail_reply_textbox_size_px|default:'500'}" onfocus="$(this).prev().find('input:radio').click();"> pixels<br>
		<div style="margin:0px 0px 10px 10px;">
			<label><input type="checkbox" name="mail_reply_textbox_size_inelastic" value="1" {if !empty($prefs.mail_reply_textbox_size_inelastic)}checked{/if}> {'preferences.account.mail.reply_textbox_size.inelastic'|devblocks_translate}</label><br>
		</div>
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

<fieldset class="peek">
	<legend>{'preferences.account.email'|devblocks_translate|capitalize}</legend>

	{'preferences.account.email.associated'|devblocks_translate}<br>

	<ul id="listWorkerEmailAddresses" style="padding:0px;margin:5px 0px 0px 10px;list-style:none;">
		{foreach from=$addresses item=address}
		<li style="padding-bottom:10px;">
			<input type="hidden" name="worker_emails[]" value="{$address->address}">

			{if $address->address==$active_worker->email}
			<button type="button"><span class="cerb-sprite2 sprite-tick-circle-gray"></span></button>
			{else}
			<button type="button" onclick="if(confirm('Are you sure you want to delete this email address?')) { $(this).closest('li').remove(); }" class="delete"><span class="cerb-sprite2 sprite-minus-circle"></span></button>
			{/if}

			<b>{$address->address}</b>

			{if $address->is_confirmed}
				{if $address->address==$active_worker->email}
				(Primary)
				{/if}
			{else}
			<i>(Pending Verification:</i> <a href="javascript:;" style="font-style:italic;" onclick="document.resendConfirmationForm.email.value='{$address->address}';document.resendConfirmationForm.submit();">{'preferences.account.email.address.resend.confirm'|devblocks_translate}</a>)
			{/if}
		</li>
		{/foreach}
		<li>
			{'preferences.account.email.address.add'|devblocks_translate}<br>
			<div style="padding:5px;">
				<input type="text" name="new_email" size="45" value="" class="input_email">
			</div>
		</li>
	</ul>
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
</form>

<form action="{devblocks_url}{/devblocks_url}" name="resendConfirmationForm" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="resendConfirmation">
<input type="hidden" name="email" value="">
</form>
