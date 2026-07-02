<b>{'common.connected_account'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<div class="cerb-ui-record-chooser" id="{$namePrefix}_account_chooser">
		{if $connected_account}
			<li data-context-id="{$connected_account->id}" data-label="{$connected_account->name}"></li>
		{/if}
	</div>
</div>

<b>API Path:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	{$verbs = [get,post,put]}
	<select name="{$namePrefix}[api_verb]" class="jira-api-verb">
		{foreach from=$verbs item=verb}
		<option value="{$verb}" {if $params.api_verb == $verb}selected="selected"{/if}>{$verb|upper}</option>
		{/foreach}
	</select>
	<br>
	
	<input type="text" name="{$namePrefix}[api_path]" value="{$params.api_path|default:"/rest/api/2/serverInfo"}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. /rest/api/2/serverInfo">
</div>

<div class="jira-api-json" style="{if !in_array($params.api_verb,[post,put])}display:none;{/if}">
	<b>Request JSON:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<textarea rows="3" cols="60" name="{$namePrefix}[json]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.json}</textarea>
	</div>
</div>

<b>Save response to a variable named:</b><br>
<div style="margin-left:15px;margin-bottom:5px;">
	<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_jira_response"}" size="45" style="width:100%;" placeholder="e.g. _jira_response">
</div>

<b>Send live API requests in simulator mode:</b><br>
<div style="margin-left:15px;margin-bottom:5px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if empty($params.run_in_simulator)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser($action.find('#{$namePrefix}_account_chooser')[0], { context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}', name: '{$namePrefix}[connected_account_id]', emptyIcon: 'key', query: 'service:jira' });
	
	$action.find('select.jira-api-verb').change(function() {
		var $div_json = $action.find('div.jira-api-json');
		var val = $(this).val();
		
		if(val == 'post' || val == 'put')
			$div_json.show();
		else
			$div_json.fadeOut();
	});
});
</script>
