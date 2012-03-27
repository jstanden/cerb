{if !$checkbox_name}{$checkbox_name = '[worker_id][]'}{/if}
{foreach from=$values_to_contexts item=var_data key=var_key}
	{if $var_data.context == "{CerberusContexts::CONTEXT_WORKER}"}
		<li><label><input type="checkbox" name="{$namePrefix}{$checkbox_name}" value="{$var_key}" {if in_array($var_key, $param_value)}checked="checked"{/if}> {$var_data.label}</label></li>
	{/if}
{/foreach}
