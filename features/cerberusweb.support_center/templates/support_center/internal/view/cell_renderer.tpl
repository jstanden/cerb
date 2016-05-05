{$col = explode('_',$column)}
{$col_id = $col[1]}
{$col = $custom_fields[$col_id]}

{if $col->type==Model_CustomField::TYPE_SINGLE_LINE}
	<td>{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_URL}
	<td>{if !empty($result.$column)}<a href="{$result.$column}" target="_blank">{$result.$column}</a>{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_NUMBER}
	<td>{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_LINE}
	<td title="{$result.$column}">{$result.$column|truncate:32}</td>
{elseif $col->type==Model_CustomField::TYPE_DROPDOWN}
	<td>{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
	<td>
		{$opts = $result.$column|explode:','|sort}
		{$opts|array_keys}
		{foreach from=$opts item=opt name=opts}
			<span>{$opt}</span>{if !$smarty.foreach.opts.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DATE}
	<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type==Model_CustomField::TYPE_CHECKBOX}
	<td>{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_LINK}
	<td>
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILE}
	<td>
	{*
		{$file_id = $result.$column}
		{$file = DAO_Attachment::get($file_id)}
		<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" title="{$file->display_name}" target="_blank">{$file->storage_size|devblocks_prettybytes}</a>
	*}
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILES}
	<td>
	{*
		{$file_ids = DevblocksPlatform::parseCrlfString($result.$column)}

		{foreach from=$file_ids item=file_id name=files}
			{$file = DAO_Attachment::get($file_id)}
			<a href="{devblocks_url}c=files&guid={$file->storage_sha1hash}&file={$file->display_name|escape:'url'}{/devblocks_url}" title="{$file->display_name}" target="_blank">{$file->storage_size|devblocks_prettybytes}</a>{if !$smarty.foreach.files.last}, {/if}
		{/foreach}
	*}
	</td>
{elseif $col->type==Model_CustomField::TYPE_WORKER}
	<td>
	{assign var=worker_id value=$result.$column}
	{if !empty($worker_id) && isset($workers.$worker_id)}
		{$workers.$worker_id->getName()}
	{/if}
	</td>
{else}
	<td></td>
{/if}
