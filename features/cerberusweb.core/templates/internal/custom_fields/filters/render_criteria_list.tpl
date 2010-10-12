{$col = explode('_',$crit_key)}
{$cf_id = $col[1]}

{if isset($custom_fields.$cf_id)}
	{assign var=cfield value=$custom_fields.$cf_id}
	{assign var=crit_oper value=$crit.oper}
	{assign var=cfield_context value=$cfield->context}
	{$context_manifests.$cfield_context->name}:{$custom_fields.$cf_id->name} 
	{if 'E'==$cfield->type}
		<i>between</i> <b>{$crit.from}</b> <i>and</i> <b>{$crit.to}</b>
	{elseif 'W'==$cfield->type}
		{if empty($workers)}
			{$workers = DAO_Worker::getAllActive()}
		{/if}
		= 
		{foreach from=$crit.value item=worker_id name=workers}
			{if isset($workers.$worker_id)}
				<b>{$workers.$worker_id->getName()}</b>
				{if !$smarty.foreach.workers.last} or {/if}
			{/if}
		{/foreach}
	{elseif isset($crit.value) && is_array($crit.value)}
		 = 
		{foreach from=$crit.value item=i name=vals}
		<b>{$i}</b>{if !$smarty.foreach.vals.last} or {/if}
		{/foreach}
	{else}
		{if !empty($crit_oper)}{$crit_oper}{else}={/if}
		<b>{$crit.value}</b>
	{/if}
	<br>
{/if}
