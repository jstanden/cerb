<h2>Outgoing Mail</h2>

<form action="index.php" method="POST" id="frm-cerb-installer-mail-transport">
<input type="hidden" name="step" value="{$smarty.const.STEP_OUTGOING_MAIL}">
<input type="hidden" name="form_submit" value="1">

<h3>Default Mail Transport</h3>

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