<fieldset class="peek black">
	<div>
		<b>Base URL:</b><br>
		<input type="text" name="params[api_base_url]" value="{$params.api_base_url}" size="50" style="width:100%;" spellcheck="false"><br>
	</div>
	
	<div>
		<b>API Key Name:</b><br>
		<input type="text" name="params[api_key_name]" value="{$params.api_key_name}" size="50" style="width:100%;" placeholder="e.g. apikey" spellcheck="false"><br>
	</div>
	
	<div>
		<b>API Key Location:</b><br>
		<label>
			<input type="radio" name="params[api_key_location]" value="url" {if !$params.api_key_location || 'url' == $params.api_key_location}checked="checked"{/if}>
			URL Parameter
		</label>
		<label>
			<input type="radio" name="params[api_key_location]" value="header" {if 'header' == $params.api_key_location}checked="checked"{/if}>
			HTTP Header
		</label>
	</div>
</fieldset>