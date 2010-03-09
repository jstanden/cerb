<div class="block">
	<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showStorageSchemaPeek&ext_id={$schema->manifest->id}', null, true);"><h3>{$schema->manifest->name|escape}</h3></a>
	{$schema_stats = $schema->getStats()}
	{if !empty($schema_stats)}
		{foreach from=$schema_stats item=stats key=extension_id}
			{if isset($storage_engines.$extension_id) && isset($stats.count) && isset($stats.bytes)}
			<b>{$storage_engines.$extension_id->name}:</b> {$stats.count} objects ({$stats.bytes|devblocks_prettybytes})<br>
			{/if}
		{/foreach}
	{/if}
	
	{$schema->render()}
</div>
<br>
