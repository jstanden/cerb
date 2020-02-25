{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="mail">

<b>{'preferences.account.mail.display'|devblocks_translate}</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="checkbox" name="mail_disable_html_display" value="1" {if $prefs.mail_disable_html_display}checked{/if}> {'preferences.account.mail.display.disable_html'|devblocks_translate}</label><br>
	<label><input type="checkbox" name="mail_always_read_all" value="1" {if $prefs.mail_always_read_all}checked{/if}> {'preferences.account.mail.readall'|devblocks_translate}</label><br>
</div>

<b>{'preferences.account.mail.reply_button'|devblocks_translate}</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="radio" name="mail_reply_button" value="0" {if empty($prefs.mail_reply_button)}checked="checked"{/if}> {'display.reply.quote'|devblocks_translate}</label><br>
	<label><input type="radio" name="mail_reply_button" value="2" {if 2==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.only_these_recipients'|devblocks_translate}</label><br>
	<label><input type="radio" name="mail_reply_button" value="1" {if 1==$prefs.mail_reply_button}checked="checked"{/if}> {'display.reply.no_quote'|devblocks_translate}</label><br>
</div>

<b>{'preferences.account.mail.reply_format'|devblocks_translate}</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="radio" name="mail_reply_format" value="" {if empty($prefs.mail_reply_format)}checked="checked"{/if}> {'preferences.account.mail.reply_format.popup'|devblocks_translate}</label><br>
	<label><input type="radio" name="mail_reply_format" value="inline" {if 'inline'==$prefs.mail_reply_format}checked="checked"{/if}> {'preferences.account.mail.reply_format.inline'|devblocks_translate}</label><br>
</div>

<b>{'preferences.account.mail.reply_textbox_size'|devblocks_translate}</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="checkbox" name="mail_reply_html" value="1" {if $prefs.mail_reply_html}checked{/if}> {'preferences.account.mail.reply.html'|devblocks_translate}</label><br>
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

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function() {
			Devblocks.saveAjaxTabForm($frm);
		});
	});
});
</script>
