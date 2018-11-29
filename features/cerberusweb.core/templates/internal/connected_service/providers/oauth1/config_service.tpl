{$fieldset_id = uniqid()}
<fieldset class="peek black" id="{$fieldset_id}">
	<b>Client ID:</b><br>
	<input type="text" name="params[client_id]" value="{$params.client_id}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Client Secret:</b><br>
	<input type="text" name="params[client_secret]" value="{$params.client_secret}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Request Token URL:</b><br>
	<input type="text" name="params[request_token_url]" value="{$params.request_token_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Authentication URL:</b><br>
	<input type="text" name="params[authentication_url]" value="{$params.authentication_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Access Token URL:</b><br>
	<input type="text" name="params[access_token_url]" value="{$params.access_token_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Signature Method:</b><br>
	{$methods = ['HMAC-SHA1','PLAINTEXT']}
	<select name="params[signature_method]">
		{foreach from=$methods item=method}
		<option value="{$method}" {if $method==$params.signature_method}selected="selected"{/if}>{$method}</option>
		{/foreach}
	</select>
	<br>
</fieldset>
