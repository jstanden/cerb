{$col = explode('_',$action_key)}
{$cf_id = $col[1]}

{if isset($custom_fields.$cf_id)}
	Set 
	{assign var=cfield value=$custom_fields.$cf_id}
	{assign var=cfield_context value=$cfield->context}
	{$context_manifests.$cfield_context->name}:{$custom_fields.$cf_id->name} 
	 = 
	{if isset($action.value) && is_array($action.value)}
		{foreach from=$action.value item=i name=vals}
		<b>{$i}</b>{if !$smarty.foreach.vals.last} and {/if}
		{/foreach}
	{else}
		{if 'W'==$cfield->type}
			{assign var=worker_id value=$action.value}
			{if empty($workers)}
				{$workers = DAO_Worker::getAllActive()}
			{/if}
			{if isset($workers.$worker_id)}
				<b>{$workers.$worker_id->getName()}</b>
			{/if}
		{else}
			<b>{$action.value}</b>
		{/if}
	{/if}
	<br>
{/if}
