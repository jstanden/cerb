<fieldset class="peek black">
	<b>Access Key ID:</b><br>
	<input type="text" name="params[access_key]" value="{$params.access_key}" size="50" spellcheck="false"><br>
	<br>
	
	<b>Secret Access Key:</b><br>
	<input type="password" name="params[secret_key]" value="{$params.secret_key}" size="45" autocomplete="off" spellcheck="false"><br>
	<br>

	<label>
		<input type="checkbox" name="params[allow_non_aws_hosts]" value="1" {if $params.allow_non_aws_hosts}checked="checked"{/if}>
		Allow requests to non-AWS endpoints (default is <code>*.amazonaws.com</code> only)
	</label><br>
</fieldset>
