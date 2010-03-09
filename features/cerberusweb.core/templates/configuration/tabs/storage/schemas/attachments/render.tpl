{if is_numeric($active_storage_profile)}
	{$active_storage_label = $storage_profiles.$active_storage_profile->name}
{else}
	{$active_storage_label = $storage_engines.$active_storage_profile->name}
{/if}
{if is_numeric($archive_storage_profile)}
	{$archive_storage_label = $storage_profiles.$archive_storage_profile->name}
{else}
	{$archive_storage_label = $storage_engines.$archive_storage_profile->name}
{/if}
Store active content in <b>{$active_storage_label}</b>.<br>
Archive inactive content after <b>{$archive_after_days}</b> days to <b>{$archive_storage_label}</b>.<br>