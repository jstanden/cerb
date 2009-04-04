{assign var=col value=$column|explode:'_'}
{assign var=col_id value=$col.1}
{assign var=col value=$custom_fields.$col_id}

{if $col->type=='S'}
	<td>{$result.$column}</td>
{elseif $col->type=='U'}
	<td>{if !empty($result.$column)}<a href="{$result.$column}" target="_blank">{$result.$column}</a>{/if}</td>
{elseif $col->type=='N'}
	<td>{$result.$column}</td>
{elseif $col->type=='T'}
	<td title="{$result.$column|escape}">{$result.$column|truncate:32}</td>
{elseif $col->type=='D'}
	<td>{$result.$column}</td>
{elseif $col->type=='M'}
	<td>{$result.$column}</td>
{elseif $col->type=='X'}
	<td>{$result.$column}</td>
{elseif $col->type=='E'}
	<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type=='C'}
	<td>{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{elseif $col->type=='F'}
	<td>{$result.$column}</td>
{elseif $col->type=='W'}
	<td>
	{assign var=worker_id value=$result.$column}
	{if empty($workers) && !empty($worker_id)}
		{php}$this->assign('workers',DAO_Worker::getAllActive());{/php}
	{/if}
	{if !empty($worker_id) && isset($workers.$worker_id)}
		{$workers.$worker_id->getName()}
	{/if}
	</td>
{/if}
