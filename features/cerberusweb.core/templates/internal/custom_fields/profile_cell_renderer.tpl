{$cf_id = str_replace('cf_', '', $k)}
<b>{$v.label|capitalize}:</b>
{if $v.type == Model_CustomField::TYPE_CHECKBOX}
	{if $v.value}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}
{elseif $v.type == Model_CustomField::TYPE_DATE}
	<abbr title="{$v.value|devblocks_date}">{$v.value|devblocks_prettytime}</abbr>
{elseif $v.type == Model_CustomField::TYPE_SINGLE_LINE}
	{$v.value|truncate:128}
{elseif $v.type == Model_CustomField::TYPE_URL}
	<a href="{$v.value}" target="_blank">{$v.value}</a>
{elseif $v.type == Model_CustomField::TYPE_WORKER}
	{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{if isset($workers.{$v.value})}
		{$workers.{$v.value}->getName()}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_MULTI_CHECKBOX}
	{$v.value|implode:', '}
{elseif $v.type == Model_CustomField::TYPE_FILE}
	{$file_id = $v.value}
	{$file = DAO_Attachment::get($file_id)}
	{$links = DAO_AttachmentLink::getByAttachmentId($file_id)}
	{foreach from=$links item=link}
		<a href="{devblocks_url}c=files&guid={$link->guid}&file={$file->display_name}{/devblocks_url}" title="{$file->display_name}" target="_blank">{$file->storage_size|devblocks_prettybytes}</a>
	{/foreach}
{elseif $v.type == Model_CustomField::TYPE_FILES}
	{foreach from=$v.value item=file_id name=files}
		{$file = DAO_Attachment::get($file_id)}
		{$links = DAO_AttachmentLink::getByAttachmentId($file_id)}
		{foreach from=$links item=link}
			<a href="{devblocks_url}c=files&guid={$link->guid}&file={$file->display_name}{/devblocks_url}" title="{$file->display_name}" target="_blank">{$file->storage_size|devblocks_prettybytes}</a>{if !$smarty.foreach.files.last}, {/if}
		{/foreach}
	{/foreach}
{else}
	{$v.value}
{/if}
