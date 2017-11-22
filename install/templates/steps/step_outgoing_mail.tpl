<h2>Outgoing Mail</h2>

<form action="index.php" method="POST" id="frm-cerb-installer-mail-transport">
<input type="hidden" name="step" value="{$smarty.const.STEP_OUTGOING_MAIL}">
<input type="hidden" name="form_submit" value="1">

<h3>Default Sender</h3>

When a worker replies to messages from Cerb, this email address will be 
used as the sender by default.  This proxy protects your workers' direct email 
addresses and ensures that all replies are routed back to Cerb.  Each 
group may configure their own sender information (e.g., sales@yourcompany, 
support@yourcompany, marketing@yourcompany).<br>
<br>
The sender <b>absolutely must</b> be an email address that routes back into 
Cerb (e.g. by POP3) so that incoming replies to your messages are properly 
received.<br>
<br>

<b>What email address should be the default sender for outgoing email?</b><br>
<input type="text" name="default_reply_from" value="{$default_reply_from}" placeholder="support@example.com" size="64">
(e.g. support@example.com)
<br>
<br>

<b>Would you like to use a personalized sender name for outgoing email?</b> (optional)<br>
<input type="text" name="default_reply_personal" value="{$default_reply_personal}" placeholder="Example, Inc." size="64">
(e.g. "Acme Widgets Helpdesk")
<br>

<h3>Mail Transport</h3>

<b>Type:</b>

<div style="margin:5px 0px 0px 5px;">
	<div>
		<label>
			<input type="radio" name="extension_id" value="core.mail.transport.smtp" {if empty($extension_id) || $extension_id=="core.mail.transport.smtp"}checked="checked"{/if}> <b>SMTP</b>
			<div style="margin-left:15px;">This sends outgoing mail through a live SMTP server. Use this for production environments.</div>
		</label>
	</div>
	
	<div>
		<label>
			<input type="radio" name="extension_id" value="core.mail.transport.null" {if $extension_id=="core.mail.transport.null"}checked="checked"{/if}> <b>None</b>
			<div style="margin-left:15px;">This discards all outgoing mail without sending it.  This is often desirable in development, testing, or evaluation environments.</div>
		</label>
	</div>
</div>
<br>

<div id="cerb-installer-smtp-details" style="{if $extension_id=="core.mail.transport.null"}display:none;{/if}">
	<b>Host:</b><br>
	<input type="text" name="smtp_host" value="{$smtp_host|default:'localhost'}" size="45"><br>
	<br>
	
	<b>Port:</b><br>
	<input type="text" name="smtp_port" value="{$smtp_port|default:25}" size="5"><br>
	<br>
	
	<i>Auth User (optional):</i><br>
	<input type="text" name="smtp_auth_user" value="{$smtp_auth_user}"><br>
	<br>
	
	<i>Auth Password (optional):</i><br>
	<input type="text" name="smtp_auth_pass" value="{$smtp_auth_pass}"><br>
	<br>
	
	<i>Encryption (optional):</i><br>
	<input type="radio" name="smtp_enc" value="TLS" {if $smtp_enc == 'TLS'}checked{/if}>TLS<br>
	<input type="radio" name="smtp_enc" value="SSL" {if $smtp_enc == 'SSL'}checked{/if}>SSL<br>
	<input type="radio" name="smtp_enc" value="None" {if $smtp_enc == 'None'}checked{/if}>None<br>
	<br>
</div>

{if $error_display}
<div class="error">
	{$error_display}
</div>
{/if}

<button type="submit">Verify mail settings &raquo;</button>

</form>
<br>

<script type="text/javascript">
$(function() {
	var $frm = $('#frm-cerb-installer-mail-transport');
	var $div_smtp_details = $('#cerb-installer-smtp-details');
	
	$frm.find('input[name=extension_id]').change(function() {
		if($(this).val() == "core.mail.transport.smtp") {
			$div_smtp_details.fadeIn();
		} else {
			$div_smtp_details.fadeOut();
		}
	});
});
</script>