<select name="{$namePrefix}[value]">
	<option value="" {if empty($params.value)}selected="selected"{/if}></option>
	{foreach from=$options item=option}
	<option value="{$option}" {if $params.value==$option}selected="selected"{/if}>{$option}</option>
	{/foreach}
	{foreach from=$trigger->variables item=var key=var_key}
		{if $var.type == Model_CustomField::TYPE_SINGLE_LINE}
			{capture assign=value}{ldelim}{ldelim}{$var_key}{rdelim}{rdelim}{/capture}
			<option value="{$value}" {if $params.value==$value}selected="selected"{/if}>(variable) {$var.label}</option>
		{/if}
	{/foreach}
</select>
