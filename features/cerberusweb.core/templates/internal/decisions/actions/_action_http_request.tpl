<b>API URL:</b>
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

<div class="cerb-httprequest-body" style="{if !in_array($params.http_verb,[post,put])}display:none;{/if}">
	<b>Request body:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<textarea rows="3" cols="60" name="{$namePrefix}[http_body]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.http_body}</textarea>
	</div>
</div>

<b>Save response to a variable named:</b><br>
<div style="margin-left:15px;margin-bottom:5px;">
	<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_http_response"}" size="45" style="width:100%;" placeholder="e.g. _http_response">
</div>

<script type="text/javascript">
var $action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();

$action.find('select.cerb-httprequest-verb').change(function() {
	var $container = $(this).closest('fieldset');
	var $div_httpbody = $container.find('div.cerb-httprequest-body');
	var val = $(this).val();
	
	if(val == 'post' || val == 'put')
		$div_httpbody.show().find('textarea').elastic();
	else
		$div_httpbody.fadeOut();
});
</script>
