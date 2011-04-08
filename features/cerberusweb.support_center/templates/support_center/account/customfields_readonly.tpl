{if $field->type=='S'}
	{$values.{$field->id}}
{elseif $field->type=='U'}
	{$url = $values.{$field->id}}
	<a href="{$url}" target="_blank">{$url}</a>
{elseif $field->type=='N'}
	{$values.{$field->id}}
{elseif $field->type=='T'}
	{$values.{$field->id}|escape|nl2br nofilter}
{elseif $field->type=='D'}
	{$values.{$field->id}}
{elseif $field->type=='W'}
	{if empty($workers)}
		{$workers = DAO_Worker::getAllActive()}
	{/if}
	{$worker = $workers.{$values.{$field->id}}}
	{if !empty($worker)}
		{$worker->getName()}
	{/if}
{elseif $field->type=='E'}
	{$values.{$field->id}|devblocks_date}
{elseif $field->type=='X'}
	{if is_array($values.{$field->id})}
	{foreach from=$values.{$field->id} item=row name=rows}
	{$row}<br>
	{/foreach}
	{/if}
{elseif $field->type=='C'}
	{if $values.{$field->id}}
		{$translate->_('common.yes')|capitalize}
	{else}
		{$translate->_('common.no')|capitalize}
	{/if}
{/if}
