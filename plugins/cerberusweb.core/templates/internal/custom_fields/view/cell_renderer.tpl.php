{assign var=col value=$column|explode:'_'}
{assign var=col_id value=$col.1}
{assign var=col value=$custom_fields.$col_id}

{if $col->type=='S'}
	<td>{$result.$column}</td>
{elseif $col->type=='N'}
	<td>{$result.$column}</td>
{elseif $col->type=='T'}
	<td title="{$result.$column|escape}">{$result.$column|truncate:32}</td>
{elseif $col->type=='D'}
	<td>{$result.$column}</td>
{elseif $col->type=='E'}
	<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type=='C'}
	<td>{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{/if}
