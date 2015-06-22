<b>URL</b> is 
<input type="text" name="params[url]" value="{$params.url}" size="64">
<br>

<b>Format</b> is 
<select name="params[url_format]">
	<option value="">(auto)</option>
	<option value="text/json" {if $params.url_format == 'text/json'}selected="selected"{/if}>JSON</option>
	<option value="text/xml" {if $params.url_format == 'text/xml'}selected="selected"{/if}>XML</option>
	<option value="text/csv" {if $params.url_format == 'text/csv'}selected="selected"{/if}>CSV</option>
	<option value="text/plain" {if $params.url_format == 'text/plain'}selected="selected"{/if}>Raw</option>
</select>
<br>

<b>Cache</b> for 
<input type="text" name="params[url_cache_mins]" value="{$params.url_cache_mins|number_format}" size="3" maxlength="3"> 
minute(s)
<br>