<div class="block">
	<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showStorageSchemaPeek&ext_id={$schema->manifest->id}', null, false);"><h3>{$schema->manifest->name|escape}</h3></a>
	{$schema->render()}
	
	{$schema_stats = $schema->getStats()}
	{if !empty($schema_stats)}
		<br>
		{foreach from=$schema_stats item=stats key=stat_key}
			{$stat_keys = explode(':',$stat_key)}
			{$extension_id = $stat_keys.0}
			{$profile_id = $stat_keys.1}
			
			{if isset($storage_engines.$extension_id) && isset($stats.count) && isset($stats.bytes)}
				{if empty($profile_id)}
				<b>{$storage_engines.{$extension_id}->name}:</b>
				{else}
				<b>{$storage_profiles.{$profile_id}->name}:</b>
				{/if}
				 
				{$stats.count} objects ({$stats.bytes|devblocks_prettybytes})<br>
			{/if}
		{/foreach}
	{/if}
</div>
<br>
