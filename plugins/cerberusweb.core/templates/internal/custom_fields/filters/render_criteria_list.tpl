{* [TODO] Custom Field Types *}
{assign var=col value=$crit_key|explode:'_'}
{assign var=cf_id value=$col.1}

{if isset($custom_fields.$cf_id)}
	{assign var=cfield value=$custom_fields.$cf_id}
	{assign var=crit_oper value=$crit.oper}
	{assign var=cfield_source value=$cfield->source_extension}
	{$source_manifests.$cfield_source->name}:{$custom_fields.$cf_id->name} 
	{if 'E'==$cfield->type}
		<i>between</i> <b>{$crit.from}</b> <i>and</i> <b>{$crit.to}</b>
	{elseif 'W'==$cfield->type}
		{if empty($workers)}
			{php}$this->assign('workers', DAO_Worker::getAllActive());{/php}
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
