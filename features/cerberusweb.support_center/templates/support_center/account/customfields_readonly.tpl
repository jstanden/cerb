{if $field->type==Model_CustomField::TYPE_SINGLE_LINE}
	{$values.{$field->id}}
{elseif $field->type==Model_CustomField::TYPE_URL}
	{$url = $values.{$field->id}}
	<a href="{$url}" target="_blank">{$url}</a>
{elseif $field->type==Model_CustomField::TYPE_NUMBER}
	{$values.{$field->id}}
{elseif $field->type==Model_CustomField::TYPE_MULTI_LINE}
	{$values.{$field->id}|escape|nl2br nofilter}
{elseif $field->type==Model_CustomField::TYPE_DROPDOWN}
	{$values.{$field->id}}
{elseif $field->type==Model_CustomField::TYPE_WORKER}
	{if empty($workers)}
		{$workers = DAO_Worker::getAllActive()}
	{/if}
	{$worker = $workers.{$values.{$field->id}}}
	{if !empty($worker)}
		{$worker->getName()}
	{/if}
{elseif $field->type==Model_CustomField::TYPE_DATE}
	{$values.{$field->id}|devblocks_date}
{elseif $field->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
	{if is_array($values.{$field->id})}
	{foreach from=$values.{$field->id} item=row name=rows}
	{$row}<br>
	{/foreach}
	{/if}
{elseif $field->type==Model_CustomField::TYPE_CHECKBOX}
	{if $values.{$field->id}}
		{'common.yes'|devblocks_translate|capitalize}
	{else}
		{'common.no'|devblocks_translate|capitalize}
	{/if}
{/if}
