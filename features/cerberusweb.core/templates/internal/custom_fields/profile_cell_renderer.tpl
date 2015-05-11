{$cf_id = str_replace('cf_', '', $k)}
<b>{$v.label|capitalize}:</b>
{if $v.type == Model_CustomField::TYPE_CHECKBOX}
	{if $v.value}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}
{elseif $v.type == Model_CustomField::TYPE_DATE}
	<abbr title="{$v.value|devblocks_date}">{$v.value|devblocks_prettytime}</abbr>
{elseif $v.type == Model_CustomField::TYPE_SINGLE_LINE}
	{$v.value|escape|devblocks_hyperlinks nofilter}
{elseif $v.type == Model_CustomField::TYPE_MULTI_LINE}
	{if $v.value}
		{if count($v.value) > 128 || false != strpos($v.value,"\n")}
		<span>
			{$v.value|truncate:128} [<a href="javascript:;" onclick="$(this).parent().next('div').fadeIn().end().hide();">expand</a>]
		</span>
		<div style="display:none;">
			{$v.value|escape|devblocks_hyperlinks|nl2br nofilter}
			<br>
			[<a href="javascript:;" onclick="$(this).parent().hide().prev('span').fadeIn();">collapse</a>]
		</div>
		{else}
			{$v.value}
		{/if}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_URL}
	<a href="{$v.value}" target="_blank">{$v.value}</a>
{elseif $v.type == Model_CustomField::TYPE_WORKER}
	{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{if isset($workers.{$v.value})}
		{$workers.{$v.value}->getName()}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_MULTI_CHECKBOX}
	{$v.value|implode:', '}
{elseif $v.type == Model_CustomField::TYPE_LINK}
	{$link_context_ext = Extension_DevblocksContext::get($v.params.context)}
	
	{if $link_context_ext && $v.value}
		{$link_meta = $link_context_ext->getMeta($v.value)}
		
		{if $link_meta}
			{if $link_meta.permalink}
				<a href="{$link_meta.permalink}">{$link_meta.name}</a>
			{else}
				{$link_meta.name}
			{/if}
		{/if}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_FILE}
	{$file_id = $v.value}
	{$file = DAO_Attachment::get($file_id)}
	<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" target="_blank">{$file->display_name}</a> ({$file->storage_size|devblocks_prettybytes})
{elseif $v.type == Model_CustomField::TYPE_FILES}
	{foreach from=$v.value item=file_id name=files}
		{$file = DAO_Attachment::get($file_id)}
		<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" target="_blank">{$file->display_name}</a> ({$file->storage_size|devblocks_prettybytes}){if !$smarty.foreach.files.last}, {/if}
	{/foreach}
{else}
	{$v.value}
{/if}
