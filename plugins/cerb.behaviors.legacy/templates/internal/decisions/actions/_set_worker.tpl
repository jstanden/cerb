<select name="{$namePrefix}[worker_id]">
	{foreach from=$worker_values item=v key=k}
	<option value="{$k}" {if $v.context}data-context="{$v.context}"{/if} {if $params.worker_id == $k}selected="selected"{/if}>{$v.name}</option>
	{/foreach}
</select>