{$engine = $schema->getEngine()}

<fieldset>
	<legend>
		{$schema->manifest->name} 
		(<a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=invoke&module=search&action=showSearchSchemaPeek&ext_id={$schema->manifest->id}', null, false);">{'common.edit'|devblocks_translate|lower}</a>)
	</legend>
	
	{if $engine}
	{$schema_meta = $schema->getIndexMeta()}
	<div>
		{if $schema_meta.count}
		{if $schema_meta.is_count_approximate}~{/if}
		<b>{$schema_meta.count|number_format}</b> records indexed in <b>{$engine->manifest->name}</b>.
		{else}
		Records indexed in <b>{$engine->manifest->name}</b>.
		{/if}
	</div>
	{/if}
	
</fieldset>
