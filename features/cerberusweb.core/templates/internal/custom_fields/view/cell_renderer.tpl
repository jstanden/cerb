{if empty($custom_fields)}{$custom_fields = DAO_CustomField::getAll()}{/if}
{$col = explode('_',$column)}
{$col_id = $col[1]}
{$col = $custom_fields[$col_id]}

{if is_a($col, 'Model_CustomField')}
{if $col->type==Model_CustomField::TYPE_SINGLE_LINE}
	<td data-column="{$column}">{$result.$column|escape|devblocks_hyperlinks nofilter}</td>
{elseif $col->type==Model_CustomField::TYPE_URL}
	<td data-column="{$column}">{$result.$column|escape|devblocks_hyperlinks nofilter}</td>
{elseif $col->type==Model_CustomField::TYPE_NUMBER}
	<td data-column="{$column}">{$result.$column}</td>
{elseif $col->type==Model_CustomField::TYPE_CURRENCY}
	<td data-column="{$column}">
		{$currency = DAO_Currency::get($col->params.currency_id)}
		{$currency->symbol}
		{DevblocksPlatform::strFormatDecimal($result.$column, $currency->decimal_at)}
		{$currency->code}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DECIMAL}
	<td data-column="{$column}">
		{$decimal_at = $col->params.decimal_at}
		{DevblocksPlatform::strFormatDecimal($result.$column, $decimal_at)}
	</td>
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
{elseif $col->type==Model_CustomField::TYPE_LIST}
	<td data-column="{$column}">
		{$opts = DevblocksPlatform::parseCrlfString($result.$column)}
		{foreach from=$opts item=opt name=opts}
			<span>{$opt}</span>{if !$smarty.foreach.opts.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_DATE}
	<td data-column="{$column}" data-timestamp="{$result.$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
{elseif $col->type==Model_CustomField::TYPE_CHECKBOX}
	<td data-column="{$column}">{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
{elseif $col->type==Model_CustomField::TYPE_LINK}
	<td data-column="{$column}">
		{if $col->params.context && $result.$column}
			{$link_ctx = Extension_DevblocksContext::get($col->params.context)}
			{if $link_ctx}
				{$link_ctx_meta = $link_ctx->getMeta($result.$column)}
				{if $link_ctx_meta}
					{if $link_ctx->hasOption('cards')}
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{$col->params.context}" data-context-id="{$result.$column}" data-permalink="{$link_ctx_meta.permalink}">{$link_ctx_meta.name}</a>
					{elseif $link_ctx_meta.permalink}
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
		<a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" title="{$file->name} ({$file->storage_size|devblocks_prettybytes})" target="_blank" rel="noopener">{$file->name}</a>
	</td>
{elseif $col->type==Model_CustomField::TYPE_FILES}
	<td data-column="{$column}">
		{$file_ids = DevblocksPlatform::parseCrlfString($result.$column)}

		{foreach from=$file_ids item=file_id name=files}
			{$file = DAO_Attachment::get($file_id)}
			<a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" title="{$file->name} ({$file->storage_size|devblocks_prettybytes})" target="_blank" rel="noopener">{$file->name}</a>{if !$smarty.foreach.files.last}, {/if}
		{/foreach}
	</td>
{elseif $col->type==Model_CustomField::TYPE_WORKER}
	<td data-column="{$column}">
	{assign var=worker_id value=$result.$column}
	{if empty($workers) && !empty($worker_id)}
		{$workers = DAO_Worker::getAll()}
	{/if}
	{if !empty($worker_id) && isset($workers.$worker_id)}
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker_id}">{$workers.$worker_id->getName()}</a>
	{/if}
	</td>
{else}
	{if $col && method_exists($col, 'getTypeExtension')}
		{$field_ext = $col->getTypeExtension()}
		{if $field_ext}
			<td data-column="{$column}">
				{$field_ext->renderValue($col, $result.$column)}
			</td>
		{else}
			<td data-column="{$column}"></td>
		{/if}
	{else}
		<td data-column="{$column}"></td>
	{/if}
{/if}
{/if}