<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWorkerSettings">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'preferences.account.settings'|devblocks_translate|capitalize}</legend>
	
	<div style="margin-bottom:5px;">
		<table cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td style="padding-right:5px;">
					<b>{'common.name.first'|devblocks_translate|capitalize}</b>:<br>
					<input type="text" name="first_name" size="20" value="{$worker->first_name}" placeholder="Kina"><br>
				</td>
				<td>
					<b>{'common.name.last'|devblocks_translate|capitalize}</b>:<br>
					<input type="text" name="last_name" size="35" value="{$worker->last_name}" placeholder="Halpue"><br>
				</td>
			</tr>
		</table>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.title'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="title" size="64" value="{$worker->title}" placeholder="e.g. Customer Service Manager"><br>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.gender'|devblocks_translate|capitalize}</b>:<br>
		<label><input type="radio" name="gender" value="M" {if $worker->gender == 'M'}checked="checked"{/if}> <span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);"></span> {'common.gender.male'|devblocks_translate|capitalize}</label>
		 &nbsp; 
		 &nbsp; 
		<label><input type="radio" name="gender" value="F" {if $worker->gender == 'F'}checked="checked"{/if}> <span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);"></span> {'common.gender.female'|devblocks_translate|capitalize}</label>
		 &nbsp; 
		 &nbsp; 
		<label><input type="radio" name="gender" value="" {if empty($worker->gender)}checked="checked"{/if}>  Not specified</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.location'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="location" size="64" value="{$worker->location}" placeholder="e.g. Los Angeles, CA USA"><br>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.phone'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="phone" size="64" value="{$worker->phone}" placeholder="">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.mobile'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="mobile" size="64" value="{$worker->mobile}" placeholder="">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.dob'|devblocks_translate|capitalize}</b>: <i>(YYYY-MM-DD)</i><br>
		<input type="text" name="dob" value="{if $worker->dob}{$worker->dob}{/if}" size="32" autocomplete="off" spellcheck="false" placeholder="1970-01-15">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'worker.at_mention_name'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="at_mention_name" value="{$worker->at_mention_name}" size="32" autocomplete="off" spellcheck="false" placeholder="UserName">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.photo'|devblocks_translate|capitalize}</b>:<br>
		<div style="float:left;margin-right:5px;">
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:100px;width:100px;border-radius:5px;border:1px solid rgb(235,235,235);">
		</div>
		<div style="float:left;">
			<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{'common.edit'|devblocks_translate|capitalize}</button>
			<input type="hidden" name="avatar_image">
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.localization'|devblocks_translate|capitalize}</legend>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.timezone'|devblocks_translate|capitalize}</b> {if !empty($server_timezone)}({'preferences.account.current'|devblocks_translate} {$server_timezone}){/if}<br>
		<select name="timezone">
			{foreach from=$timezones item=tz}
				<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
			{/foreach}
		</select>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'preferences.account.timeformat'|devblocks_translate|capitalize}</b><br>
		<select name="time_format">
			{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
			{foreach from=$timeformats item=timeformat}
				<option value="{$timeformat}" {if $prefs.time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
			{/foreach}
		</select>
	</div>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.language'|devblocks_translate|capitalize}</b> {if !empty($selected_language) && isset($langs.$selected_language)}({'preferences.account.current'|devblocks_translate} {$langs.$selected_language}){/if}<br>
		<select name="lang_code">
			{foreach from=$langs key=lang_code item=lang_name}
				<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
			{/foreach}
		</select>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.availability'|devblocks_translate|capitalize}</legend>
	
	<b>{'preferences.account.availability.calendar_id'|devblocks_translate}</b><br>
	
	<div style="margin-left:10px;">
		<select name="availability_calendar_id">
			<option value="">- always unavailable -</option>
			{foreach from=$calendars item=calendar}
			{if $calendar->owner_context == CerberusContexts::CONTEXT_WORKER && $calendar->owner_context_id == $active_worker->id}
			<option value="{$calendar->id}" {if $calendar->id==$active_worker->calendar_id}selected="selected"{/if}>{$calendar->name}</option>
			{/if}
			{/foreach}
		</select>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.ui'|devblocks_translate|capitalize}</legend>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.assist'|devblocks_translate|capitalize}</b><br>
		<label><input type="checkbox" name="assist_mode" value="1" {if $prefs.assist_mode eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
	</div>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.keyboard.shortcuts'|devblocks_translate|capitalize}</b><br>
		<label><input type="checkbox" name="keyboard_shortcuts" value="1" {if $prefs.keyboard_shortcuts eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.mail'|devblocks_translate|capitalize}</legend>
	
	<b>{'preferences.account.mail.display'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="checkbox" name="mail_disable_html_display" value="1" {if $prefs.mail_disable_html_display}checked{/if}> {'preferences.account.mail.display.disable_html'|devblocks_translate}</label><br>
		<label><input type="checkbox" name="mail_always_show_all" value="1" {if $prefs.mail_always_show_all}checked{/if}> {'preferences.account.mail.readall'|devblocks_translate}</label><br>
		<label><input type="checkbox" name="mail_display_inline_log" value="1" {if $prefs.mail_display_inline_log}checked{/if}> {'preferences.account.mail.display.inline_log'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.reply_button'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_reply_button" value="0" {if empty($prefs.mail_reply_button)}checked="checked"{/if}> {'display.reply.quote'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_reply_button" value="2" {if 2==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.only_these_recipients'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_reply_button" value="1" {if 1==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.no_quote'|devblocks_translate}</label><br>
	</div>

	<b>{'preferences.account.mail.reply_textbox_size'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="checkbox" name="mail_reply_html" value="1" {if $prefs.mail_reply_html}checked{/if}> {'preferences.account.mail.reply.html'|devblocks_translate}</label><br>
		
		{'preferences.account.mail.reply_textbox_size.pixels'|devblocks_translate} <input type="text" name="mail_reply_textbox_size_px" size="4" maxlength=4" value="{$prefs.mail_reply_textbox_size_px|default:'500'}" onfocus="$(this).prev().find('input:radio').click();"> pixels<br>
		<div style="margin:0px 0px 10px 10px;">
			<label><input type="checkbox" name="mail_reply_textbox_size_auto" value="1" {if !empty($prefs.mail_reply_textbox_size_auto)}checked{/if}> {'preferences.account.mail.reply_textbox_size.auto'|devblocks_translate}</label><br>
		</div>
	</div>

	<b>{'preferences.account.mail.signature'|devblocks_translate}</b>
	<div style="margin:0px 0px 10px 10px;">
		<label><input type="radio" name="mail_signature_pos" value="0" {if empty($prefs.mail_signature_pos)}checked="checked"{/if}> {'preferences.account.mail.signature.none'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_signature_pos" value="3" {if 3==$prefs.mail_signature_pos}checked="checked"{/if}> {'preferences.account.mail.signature.above'|devblocks_translate}</label><br>
		<label><input type="radio" name="mail_signature_pos" value="1" {if 1==$prefs.mail_signature_pos}checked="checked"{/if}> {'preferences.account.mail.signature.above.cut'|devblocks_translate}</label><br>
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
	<legend>{'common.email_addresses'|devblocks_translate|capitalize}</legend>

	{'preferences.account.email.associated'|devblocks_translate}<br>

	<ul id="listWorkerEmailAddresses" style="padding:0px;margin:5px 0px 0px 10px;list-style:none;">
		{foreach from=$addresses item=address}
		<li style="padding-bottom:10px;">
			<input type="hidden" name="worker_email_ids[]" value="{$address->address_id}">

			{if $address->address_id == $active_worker->email_id}
			<button type="button"><span class="glyphicons glyphicons-circle-ok" style="font-size:16px;color:rgb(80,80,80);"></span></button>
			{else}
			<button type="button" onclick="if(confirm('Are you sure you want to delete this email address?')) { $(this).closest('li').remove(); }" class="delete"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></button>
			{/if}

			<b>{$address->getEmailAsString()}</b>

			{if $address->is_confirmed}
				{if $address->address_id == $active_worker->email_id}
				(Primary)
				{/if}
			{else}
			<i>(Pending Verification:</i> <a href="javascript:;" style="font-style:italic;" onclick="document.resendConfirmationForm.email.value='{$address->getEmailAsString()}';document.resendConfirmationForm.submit();">{'preferences.account.email.address.resend.confirm'|devblocks_translate}</a>)
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

<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
</form>

<form action="{devblocks_url}{/devblocks_url}" name="resendConfirmationForm" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="resendConfirmation">
<input type="hidden" name="email" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
</form>

<script type="text/javascript">
$(function() {
	var $form = $('#frmWorkerSettings');
	
	// Avatar chooser
	var $avatar_chooser = $form.find('button.cerb-avatar-chooser');
	var $avatar_image = $avatar_chooser.parent().parent().find('img.cerb-avatar');
	ajax.chooserAvatar($avatar_chooser, $avatar_image);
});
</script>