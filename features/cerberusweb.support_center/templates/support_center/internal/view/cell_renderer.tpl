{$col = explode('_',$column)}
{$col_id = $col[1]}
{$col = $custom_fields[$col_id]}

{if is_a($col, 'Model_CustomField')}
{if $col->type==Model_CustomField::TYPE_SINGLE_LINE}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_URL}
	<td data-column="{$column}">{if !empty($result.$column)}<a href="{$result.$column}" target="_blank" rel="noopener noreferrer">{$result.$column}</a>{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_CURRENCY}
	<td data-column="{$column}">
		{$currency_id = $col->params.currency_id}
		{$currency = $currencies.$currency_id}
		{if $currency}
			{if $result.$column}
				{$currency->symbol}
				{$result.$column|devblocks_decimal:$currency->decimal_at}
				{$currency->code}
			{/if}
		{else}
			{$result.$column|devblocks_decimal:2}
		{/if}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DECIMAL}
	<td data-column="{$column}">
		{$decimal_at = $col->params.decimal_at}
		{$result.$column|devblocks_decimal:$decimal_at}
	</td>
{elseif $col->type==Model_CustomField::TYPE_NUMBER}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_LINE}
	<td data-column="{$column}" title="{$result.$column}">{$result.$column|truncate:32}</td>
{elseif $col->type==Model_CustomField::TYPE_DROPDOWN}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_LIST}
	<td data-column="{$column}">
		{$opts = $result.$column|explode:','}
		{$opts|array_keys}
		{foreach from=$opts item=opt name=opts}
			<span>{$opt}</span>{if !$smarty.foreach.opts.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
	<td data-column="{$column}">
		{$opts = $result.$column|explode:','|sort}
		{$opts|array_keys}
		{foreach from=$opts item=opt name=opts}
			<span>{$opt}</span>{if !$smarty.foreach.opts.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DATE}
	<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type==Model_CustomField::TYPE_CHECKBOX}
	<td data-column="{$column}">{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_LINK}
	<td data-column="{$column}"></td>
{elseif $col->type==Model_CustomField::TYPE_FILE}
	<td data-column="{$column}">
		{*
		{$file_id = $result.$column}
		{$file = DAO_Attachment::get($file_id)}
		<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$file->storage_sha1hash}&name={$file->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$file->name}</a>
		*}
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILES}
	<td data-column="{$column}">
		{*
		{$file_ids = DevblocksPlatform::parseCrlfString($result.$column)}

		{foreach from=$file_ids item=file_id name=files}
			{$file = DAO_Attachment::get($file_id)}
			<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$file->storage_sha1hash}&name={$file->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$file->name}</a>
		{/foreach}
		*}
	</td>
{elseif $col->type==Model_CustomField::TYPE_WORKER}
	<td data-column="{$column}">
	{assign var=worker_id value=$result.$column}
	{if !empty($worker_id) && isset($workers.$worker_id)}
		{$workers.$worker_id->getName()}
	{/if}
	</td>
{else}
	<td data-column="{$column}"></td>
{/if}
{/if}