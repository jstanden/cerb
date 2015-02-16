{$uniqid = uniqid()}
<fieldset id="{$uniqid}">

<legend>SMTP</legend>

This mail transport delivers mail to an <a href="http://en.wikipedia.org/wiki/Simple_Mail_Transfer_Protocol" target="_blank">SMTP</a> server.
<br>
<br>

<b>Host:</b><br>
<input type="text" name="params[{$extension->id}][host]" value="{$model->params.host|default:'localhost'}" size="64">
<i>(e.g. localhost)</i>
<br>
<br>

<b>Port:</b><br>
<input type="text" name="params[{$extension->id}][port]" value="{$model->params.port|default:25}" size="5">
<i>(Usually 587 for TLS/SSL; 25 for legacy environments)</i>
<br>
<br>

<b>Encryption:</b> (optional)<br>
<label><input type="radio" name="params[{$extension->id}][encryption]" value="None" {if empty($model->params.encryption) || $model->params.encryption == 'None'}checked{/if}>None</label>&nbsp;&nbsp;&nbsp;
<label><input type="radio" name="params[{$extension->id}][encryption]" value="TLS" {if $model->params.encryption == 'TLS'}checked{/if}>TLS</label>&nbsp;&nbsp;&nbsp;
<label><input type="radio" name="params[{$extension->id}][encryption]" value="SSL" {if $model->params.encryption == 'SSL'}checked{/if}>SSL</label><br>
<br>

<b>Authentication:</b> (optional)<br>
<label><input type="checkbox" name="params[{$extension->id}][auth_enabled]" value="1" class="peek-smtp-auth" {if $model->params.auth_enabled}checked{/if}> Enabled</label><br>
<br>

<div class="peek-smtp-encryption" style="margin-left:15px;display:{if $model->params.auth_enabled}block{else}none{/if};">
	<b>Username:</b><br>
	<input type="text" name="params[{$extension->id}][auth_user]" value="{$model->params.auth_user}" size="45" style="width:95%;"><br>
	<br>
	
	<b>Password:</b><br>
	<input type="text" name="params[{$extension->id}][auth_pass]" value="{$model->params.auth_pass}" size="45" style="width:95%;"><br>
	<br>
</div>

<b>Timeout:</b><br>
<input type="text" name="params[{$extension->id}][timeout]" value="{$model->params.timeout|default:30}" size="4">
seconds
<br>
<br>

<b>Maximum Deliveries Per SMTP Connection:</b><br>
<input type="text" name="params[{$extension->id}][max_sends]" value="{$model->max_sends|default:20}" size="5">
<i>(tuning this depends on your mail server; default is 20)</i>
<br>
<br>

<div class="status"></div>

</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$uniqid}');
	$fieldset.find('input:checkbox.peek-smtp-auth').click(function() {
		if($(this).is(':checked')) {
			$fieldset.find('div.peek-smtp-encryption')
				.fadeIn()
				;
		} else {
			$fieldset.find('div.peek-smtp-encryption')
				.fadeOut()
				.find('input:text')
				.val('')
				;
		}
	});
});
</script>