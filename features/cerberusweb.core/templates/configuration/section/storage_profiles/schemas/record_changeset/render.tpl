{if is_numeric($active_storage_profile)}
    {$active_storage_label = $storage_profiles.$active_storage_profile->name}
{else}
    {$active_storage_label = $storage_engines.$active_storage_profile->name}
{/if}
Store content in <b>{$active_storage_label}</b>.<br>