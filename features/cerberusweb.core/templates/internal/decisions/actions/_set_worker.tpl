<select name="{$namePrefix}[worker_id]">
	<option value="0" {if empty($params.worker_id)}selected="selected"{/if}>({'common.nobody'|devblocks_translate|lower})</option>
	{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}" {if $params.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
	{/foreach}
	{foreach from=$values_to_contexts item=context_data key=val_key}
		{if $context_data.context == CerberusContexts::CONTEXT_WORKER && !$context_data.is_multiple}
		<option value="{$val_key}" context="{$context_data.context}" {if $params.worker_id == $val_key}selected="selected"{/if}>{$context_data.label}</option>
		{/if}
	{/foreach}
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == 'W'}
			<option value="{$var_key}" {if $params.worker_id==$var_key}selected="selected"{/if}>(variable) {$var_data.label}</option>
		{/if}
	{/foreach}
</select>