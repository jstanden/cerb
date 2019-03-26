<fieldset class="peek black">
	<div>
		<b>Base URL:</b><br>
		<input type="text" name="params[base_url]" value="{$params.base_url}" size="50" style="width:100%;" spellcheck="false"><br>
	</div>
	
	<div>
		<b>Also allow these URL prefixes:</b> (one per line)<br>
		<textarea name="params[url_whitelist]" rows="5" cols="45" style="width:100%;">{$params.url_whitelist}</textarea>
	</div>
</fieldset>