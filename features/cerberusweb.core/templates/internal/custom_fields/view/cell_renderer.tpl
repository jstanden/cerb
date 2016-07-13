{if empty($custom_fields)}{$custom_fields = DAO_CustomField::getAll()}{/if}
{$col = explode('_',$column)}
{$col_id = $col[1]}
{$col = $custom_fields[$col_id]}

{if $col->type==Model_CustomField::TYPE_SINGLE_LINE}
	<td data-column="{$column}">{$result.$column|escape|devblocks_hyperlinks nofilter}</td>
{elseif $col->type==Model_CustomField::TYPE_URL}
	<td data-column="{$column}">{$result.$column|escape|devblocks_hyperlinks nofilter}</td>
{elseif $col->type==Model_CustomField::TYPE_NUMBER}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_LINE}
	<td data-column="{$column}" title="{$result.$column}">{$result.$column|escape|devblocks_hyperlinks nofilter}</td>
{elseif $col->type==Model_CustomField::TYPE_DROPDOWN}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
	<td data-column="{$column}">
		{$opts = DevblocksPlatform::parseCrlfString($result.$column)}
		{$null = sort($opts)}
		{foreach from=$opts item=opt name=opts}
			<span>{$opt}</span>{if !$smarty.foreach.opts.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DATE}
	<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type==Model_CustomField::TYPE_CHECKBOX}
	<td data-column="{$column}">{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_LINK}
	<td data-column="{$column}">
		{if $col->params.context && $result.$column}
			{$link_ctx = Extension_DevblocksContext::get($col->params.context)}
			{if $link_ctx}
				{$link_ctx_meta = $link_ctx->getMeta($result.$column)}
				{if $link_ctx_meta}
					{if $link_ctx_meta.permalink}
						<a href="{$link_ctx_meta.permalink}">{$link_ctx_meta.name}</a>
					{else}
						{$link_ctx_meta.name}
					{/if}
				{/if}
			{/if}
		{/if}
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILE}
	<td data-column="{$column}">
		{$file_id = $result.$column}
		{$file = DAO_Attachment::get($file_id)}
		<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" title="{$file->display_name} ({$file->storage_size|devblocks_prettybytes})" target="_blank">{$file->display_name}</a>
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILES}
	<td data-column="{$column}">
		{$file_ids = DevblocksPlatform::parseCrlfString($result.$column)}

		{foreach from=$file_ids item=file_id name=files}
			{$file = DAO_Attachment::get($file_id)}
			<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" title="{$file->display_name} ({$file->storage_size|devblocks_prettybytes})" target="_blank">{$file->display_name}</a>{if !$smarty.foreach.files.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_WORKER}
	<td data-column="{$column}">
	{assign var=worker_id value=$result.$column}
	{if empty($workers) && !empty($worker_id)}
		{$workers = DAO_Worker::getAll()}
	{/if}
	{if !empty($worker_id) && isset($workers.$worker_id)}
		<a href="{devblocks_url}c=profiles&what=worker&id={$worker_id}{/devblocks_url}" target="_blank">{$workers.$worker_id->getName()}</a>
	{/if}
	</td>
{else}
	<td data-column="{$column}"></td>
{/if}
