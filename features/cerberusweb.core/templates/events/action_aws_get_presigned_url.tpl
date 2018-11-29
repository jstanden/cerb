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

<b>Expires:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[expires_secs]" size="7" value="{$params.expires_secs}" placeholder="300"> seconds
</div>

<b>Authentication:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[auth_connected_account_id]">
		<option value=""></option>
		{foreach from=$aws_accounts item=account}
		<option value="{$account->id}" {if $params.auth_connected_account_id == $account->id}selected="selected"{/if}>{$account->name}</option>
		{/foreach}
	</select>
</div>

<b>Save URL to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_presigned_url"}" required="required" spellcheck="false" size="32" placeholder="e.g. _presigned_url">&#125;&#125;
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('select.cerb-httprequest-verb').change(function() {
		var $container = $(this).closest('fieldset');
		var $div_httpbody = $container.find('div.cerb-httprequest-body');
		var val = $(this).val();
		
		if(val == 'post' || val == 'put')
			$div_httpbody.show();
		else
			$div_httpbody.fadeOut();
	});
});
</script>
