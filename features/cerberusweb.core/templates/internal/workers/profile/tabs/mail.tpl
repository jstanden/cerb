{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="mail">

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

<div class="status"></div>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $status = $frm.find('div.status');
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
				}
			}
		});
	});
});
</script>
