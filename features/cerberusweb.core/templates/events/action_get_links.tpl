{if is_array($values_to_contexts)}
<b>Get links on:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{if $smarty.foreach.context_data.first && empty($params.on)}{$params.on = $val_key}{/if}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on==$val_key}{$selected_context = $context_data.context}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Of type:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[links_context]">
	{foreach from=$context_exts item=context_ext key=context_ext_id}
	<option value="{$context_ext_id}" {if $params.links_context == $context_ext_id}selected="selected"{/if}>{$context_ext->name}</option>
	{/foreach}
	</select>
</div>

<b>Save results to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

{*
	{foreach from=$trigger->variables item=var key=var_key}
	{if substr($var.type,0,4) == 'ctx_'}
		{$ctx_ext_id = substr($var.type, 4)}
		{if $context_exts.$ctx_ext_id}
		<option value="{$var_key}">(variable) {$var.label} ({$context_exts.$ctx_ext_id->name})</option>
		{/if}
	{/if}
	{/foreach}
*}