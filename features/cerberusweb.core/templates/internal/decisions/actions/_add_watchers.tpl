{if !empty($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}">{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Add these watchers:</b>

<div style="margin-left:10px;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>
