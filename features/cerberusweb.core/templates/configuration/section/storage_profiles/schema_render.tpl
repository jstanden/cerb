{if is_numeric($active_storage_profile)}
	{$active_storage_label = $storage_profiles.$active_storage_profile->name}
{else}
	{$active_storage_label = $storage_engines.$active_storage_profile->name}
{/if}
{* When active and archive are the same place no migration happens, so render it as local-only *}
{$show_archive = $is_archivable && ($active_storage_profile != $archive_storage_profile)}
Store {if $show_archive}active {/if}content in <b>{$active_storage_label}</b>.<br>
{if $show_archive}
	{if is_numeric($archive_storage_profile)}
		{$archive_storage_label = $storage_profiles.$archive_storage_profile->name}
	{else}
		{$archive_storage_label = $storage_engines.$archive_storage_profile->name}
	{/if}
	Archive inactive content after <b>{$archive_after_days}</b> days to <b>{$archive_storage_label}</b>.<br>
{/if}
