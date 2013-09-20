{$has_variables = false}
{foreach from=$macro_params item=var}
	{if empty($var.is_private)}{$has_variables = true}{/if}
{/foreach}

{if $has_variables}
<div class="block" style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$macro_params variable_values=$params field_name=$namePrefix with_placeholders=true}
</div>
{/if}