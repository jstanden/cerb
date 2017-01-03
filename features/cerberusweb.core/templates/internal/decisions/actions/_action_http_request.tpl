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

<b>Authentication:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[auth]" value="" {if !$params.auth}checked="checked"{/if}> {'common.none'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[auth]" value="connected_account" {if 'connected_account' == $params.auth}checked="checked"{/if}> {'common.connected_account'|devblocks_translate|capitalize}</label>
</div>

<div class="cerb-httprequest-connected-account" style="margin-left:20px;{if 'connected_account' != $params.auth}display:none;{/if}">
	<b>{'common.connected_account'|devblocks_translate|capitalize}:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<select name="{$namePrefix}[auth_connected_account_id]">
			<option value=""></option>
			{foreach from=$connected_accounts item=account}
			<option value="{$account->id}" {if $params.auth_connected_account_id == $account->id}selected="selected"{/if}>{$account->name}</option>
			{/foreach}
		</select>
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
	<div>
	(with properties: <tt>.content_type</tt> &nbsp; <tt>.body</tt> &nbsp; <tt>.info.http_code</tt> &nbsp; <tt>.error</tt>)
	</div>
</div>

<script type="text/javascript">
$(function() {
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
	
	$action.find('input[name="{$namePrefix}[auth]"]').change(function() {
		var $container = $(this).closest('fieldset');
		var $div_account = $container.find('div.cerb-httprequest-connected-account');
		var val = $(this).val();
		
		if(val == 'connected_account')
			$div_account.show();
		else
			$div_account.fadeOut();
	});
});
</script>
