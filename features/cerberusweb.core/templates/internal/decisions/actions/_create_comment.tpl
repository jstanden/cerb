{if !empty($values_to_contexts)}
<b>Comment on:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<b>{'common.format'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<label><input type="radio" name="{$namePrefix}[format]" value="" {if 'markdown' != $params.format}checked="checked"{/if}> Plaintext</label>
	<label><input type="radio" name="{$namePrefix}[format]" value="markdown" {if 'markdown' == $params.format}checked="checked"{/if}> Markdown</label>
</div>

<div style="margin-top:5px;">
<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>
</div>

{if !empty($values_to_contexts)}
<b>Link to:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $textarea = $action.find('textarea').first();
	
	var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

	$textarea.atwho({
		at: '@',
		{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
		{literal}insertTpl: '@${at_mention}',{/literal}
		data: atwho_workers,
		searchKey: '_index',
		limit: 10
	});
});
</script>