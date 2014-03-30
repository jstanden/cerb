Objects are cached in <b>Redis</b> at <b>{$cacher_config.host}:{$cacher_config.port}</b>{*
*}{if $cacher_config.database}, in database <b>{$cacher_config.database}</b>{/if}{*
*}{if $cacher_config.key_prefix}, and keys are namespaced with <b>{$cacher_config.key_prefix}</b>{/if}