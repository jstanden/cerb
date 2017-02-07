<b>{'common.url'|devblocks_translate|upper}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	{$verbs = [get,post,put,delete]}
	<select name="{$namePrefix}[http_verb]" class="cerb-httprequest-verb">
		{foreach from=$verbs item=verb}
		<option value="{$verb}" {if $params.http_verb == $verb}selected="selected"{/if}>{$verb|upper}</option>
		{/foreach}
	</select>
	<br>
	
	<input type="text" name="{$namePrefix}[http_url]" value="{$params.http_url|default:""}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. http://example.com/api/request.json">
</div>

<b>HTTPS client certificate file path (Optional):</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[http_cert]" value="{$params.http_cert|default:""}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. /path/to/https/cert.pem">
</div>

<div class="cerb-httprequest-headers">
	<b>Request headers:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<textarea rows="3" cols="60" name="{$namePrefix}[http_headers]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.http_headers}</textarea>
	</div>
</div>

<div class="cerb-httprequest-body" style="{if !in_array($params.http_verb,[post,put])}display:none;{/if}">
	<b>Request body:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<textarea rows="3" cols="60" name="{$namePrefix}[http_body]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.http_body}</textarea>
	</div>
</div>

<b>Options:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="checkbox" name="{$namePrefix}[options][ignore_ssl_validation]" value="1" {if $params.options.ignore_ssl_validation}checked="checked"{/if}> Ignore SSL certificate validation (e.g. self-signed)</label>
	<br>
	<label><input type="checkbox" name="{$namePrefix}[options][raw_response_body]" value="1" {if $params.options.raw_response_body}checked="checked"{/if}> Don't attempt to auto-convert the response body (e.g. JSON decode)</label>
	<br>
</div>

<b>Also execute HTTP request in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save response to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_http_response"}" required="required" spellcheck="false" size="32" placeholder="e.g. _http_response">&#125;&#125;
</div>

<script type="text/javascript">
var $action = $('fieldset#{$namePrefix}');
$action.find('textarea').autosize();

$action.find('select.cerb-httprequest-verb').change(function() {
	var $container = $(this).closest('fieldset');
	var $div_httpbody = $container.find('div.cerb-httprequest-body');
	var val = $(this).val();
	
	if(val == 'post' || val == 'put')
		$div_httpbody.show().find('textarea').autosize();
	else
		$div_httpbody.fadeOut();
});
</script>
