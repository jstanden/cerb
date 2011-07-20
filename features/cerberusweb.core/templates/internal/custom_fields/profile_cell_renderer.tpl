{$cf_id = str_replace('cf_', '', $k)}
<b>{$v.label|capitalize}:</b>
{if $v.type == 'C'}
	{if $v.value}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}
{elseif $v.type == 'E'}
	<abbr title="{$v.value|devblocks_date}">{$v.value|devblocks_prettytime}</abbr>
{elseif $v.type == 'T'}
	{$v.value|truncate:128}
{elseif $v.type == 'U'}
	<a href="{$v.value}" target="_blank">{$v.value}</a>
{elseif $v.type == 'W'}
	{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{if isset($workers.{$v.value})}
		{$workers.{$v.value}->getName()}
	{/if}
{elseif $v.type == 'X'}
	{$v.value|implode:', '}
{else}
	{$v.value}
{/if}
