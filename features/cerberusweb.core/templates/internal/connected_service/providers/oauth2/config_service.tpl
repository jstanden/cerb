{$fieldset_id = uniqid()}
<fieldset class="peek black" id="{$fieldset_id}">
	<b>Grant Type:</b><br>
	{*$grant_types = ["authorization_code" => "Authorization Code", "implicit" => "Implicit", "resource_credentials" => "Resource Owner Password Credentials", "client_credentials" => "Client Credentials"]*}
	{$grant_types = ["authorization_code" => "Authorization Code"]}
	<select name="params[grant_type]">
		{foreach from=$grant_types item=v key=k}
		<option value="{$k}" {if $k == $params.grant_type}checked="checked"{/if}>{$v}</option>
		{/foreach}
	</select>
	<br>
	<br>
	
	<b>Client ID:</b><br>
	<input type="text" name="params[client_id]" value="{$params.client_id}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Client Secret:</b><br>
	<input type="text" name="params[client_secret]" value="{$params.client_secret}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Authorization URL:</b><br>
	<input type="text" name="params[authorization_url]" value="{$params.authorization_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Access Token URL:</b><br>
	<input type="text" name="params[access_token_url]" value="{$params.access_token_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Resource Owner URL:</b><br>
	<input type="text" name="params[resource_owner_url]" value="{$params.resource_owner_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Scope:</b><br>
	<input type="text" name="params[scope]" value="{$params.scope}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Approval Prompt:</b><br>
	<input type="text" name="params[approval_prompt]" value="{$params.approval_prompt}" style="width:100%;" size="50" spellcheck="false" placeholder="e.g. auto, force"><br>
	<br>
</fieldset>