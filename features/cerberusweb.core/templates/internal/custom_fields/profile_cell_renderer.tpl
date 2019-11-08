{$cf_id = str_replace('cf_', '', $k)}
<div style="margin-bottom:5px;">
<div>
	<b style="font-size:.9em;">{$v.label|capitalize}</b>
</div>
{if $v.type == Model_CustomField::TYPE_CHECKBOX}
	{if $v.value}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}
{elseif $v.type == Model_CustomField::TYPE_DATE}
	<abbr title="{$v.value|devblocks_date}">{$v.value|devblocks_prettytime}</abbr>
{elseif $v.type == Model_CustomField::TYPE_CURRENCY}
	{$currency = DAO_Currency::get($v.params.currency_id)}
	{if $currency}
	{$currency->symbol} {DevblocksPlatform::strFormatDecimal($v.value, $currency->decimal_at)} {$currency->code}
	{else}
	{$v.value}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_DECIMAL}
	{$decimal_at = $v.params.decimal_at}
	{if $decimal_at}
	{DevblocksPlatform::strFormatDecimal($v.value, $decimal_at)}
	{else}
	{$v.value}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_SINGLE_LINE}
	{$v.value|escape|devblocks_hyperlinks nofilter}
{elseif $v.type == Model_CustomField::TYPE_MULTI_LINE}
	{if $v.value}
		{if strlen($v.value) > 128 || false != strpos($v.value,"\n")}
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
	<a href="{$v.value}" target="_blank" rel="noopener">{$v.value}</a>
{elseif $v.type == Model_CustomField::TYPE_WORKER}
	{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{if isset($workers.{$v.value})}
		<ul class="bubbles">
			<li>
				<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$v.value}">{$workers.{$v.value}->getName()}</a>
			</li>
		</ul>
	{/if}
{elseif $v.type == Model_CustomField::TYPE_LIST}
	{if is_array($v.value)}
		{$v.value|implode:', '}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_MULTI_CHECKBOX}
	{if is_array($v.value)}
		{$v.value|implode:', '}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_LINK}
	{$link_context_ext = Extension_DevblocksContext::get($v.params.context)}
	{if $link_context_ext}
		{$link_meta = $link_context_ext->getMeta($v.value)}
		{if $link_meta && ($link_context_ext->id == CerberusContexts::CONTEXT_APPLICATION || $v.value)}
			<ul class="bubbles">
			<li class="bubble-gray" style="white-space:normal;">
				{if $link_context_ext->id == CerberusContexts::CONTEXT_APPLICATION}
				<img src="{devblocks_url}c=avatars&context=app&context_id={$v.value}{/devblocks_url}?v={$link_meta.updated}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
				{else}
					{if $v.value}
						{if $link_context_ext->hasOption('avatars')}
						<img src="{devblocks_url}c=avatars&context={$link_context_ext->manifest->params.alias}&context_id={$v.value}{/devblocks_url}?v={$link_meta.updated}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
						{/if}
					{/if}
				{/if}
				
				{if $link_context_ext->hasOption('cards')}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$v.params.context}" data-context-id="{$v.value}">{$link_meta.name|truncate:64}</a>
				{else}
					{$link_meta.name|truncate:64}
				{/if}
			</li>
			</ul>
		{/if}
	{/if}
{elseif $v.type == Model_CustomField::TYPE_FILE}
	{$file_id = $v.value}
	{$file = DAO_Attachment::get($file_id)}
	{if $file}
	<ul class="bubbles">
		<li>
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$file->id}">{$file->name} ({$file->storage_size|devblocks_prettybytes})</a>
		</li>
	</ul>
	{/if}
{elseif $v.type == Model_CustomField::TYPE_FILES}
	{foreach from=$v.value item=file_id name=files}
		{$file = DAO_Attachment::get($file_id)}
		{if $file}
		<a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$file->name}</a> ({$file->storage_size|devblocks_prettybytes}){if !$smarty.foreach.files.last}, {/if}
		{/if}
	{/foreach}
{elseif $v.type == 'context'}
	{$display_ctx = Extension_DevblocksContext::get($v.value)}
	{if $display_ctx}
		{$display_ctx->manifest->name}
	{else}
		{$v.value}
	{/if}
{elseif $v.type == 'extension'}
	{$display_ext = DevblocksPlatform::getExtension($v.value, false)}
	{if $display_ext}
		{$display_ext->name}
	{else}
		{$v.value}
	{/if}
{elseif $v.type == 'percent'}
	{$v.value*100}%
{elseif $v.type == 'phone'}
	<a href="tel:{$dict->$k}">{$v.value}</a>
{elseif $v.type == 'slider'}
	{$min = $v.params.min}
	{$max = $v.params.max}
	{$mid = $v.params.mid}
	{$pos = (($v.value-$min)/($max-$min))*100}
	<div style="display:inline-block;margin-top:5px;width:100px;height:10px;background-color:rgb(220,220,220);border-radius:8px;">
		<div style="position:relative;margin-left:-5px;top:-1px;left:{$v.value}%;width:12px;height:12px;border-radius:12px;background-color:{if $v.value < $mid}rgb(0,200,0);{elseif $v.value > $mid}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
	</div>
{elseif $v.type == 'size_bytes'}
	{$v.value|devblocks_prettybytes}
{elseif $v.type == 'time_mins'}
	{{$v.value*60}|devblocks_prettysecs:2}
{elseif $v.type == 'time_secs'}
	{$v.value|devblocks_prettysecs:2}
{elseif $v.type == 'timezone'}
	{$v.value}
{else}
	{$field_ext = Extension_CustomField::get($v.type)}
	{$field = DAO_CustomField::get($v.id)}
	{if $field_ext}
		{$field_ext->renderValue($field, $v.value)}
	{else}
		{$v.value}
	{/if}
{/if}
</div>