Objects are cached in <b>Memcached</b> at <b>{$cacher_config.host}:{$cacher_config.port}</b>{*
*}{if $cacher_config.key_prefix}, and keys are namespaced with <b>{$cacher_config.key_prefix}</b>{/if}