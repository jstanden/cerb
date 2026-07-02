{$rl_uniqid = uniqid('values_')}
<div id="{$rl_uniqid}" class="cerb-u-flex cerb-u-flex-column cerb-u-items-start cerb-u-gap-1">
    {foreach from=$target_dicts item=target_dict}
        {$target_ext = Extension_DevblocksContext::get($target_dict->_context, true)}
        <span class="cerb-ui-pill" style="white-space:normal;word-break:break-word;">
            {if $target_dict->_image_url}
                <img src="{$target_dict->_image_url}" style="height:16px;width:16px;border-radius:16px;">
            {/if}
            {if is_a($target_ext, 'Extension_DevblocksContext') && $target_ext->hasOption('cards')}
                <a class="cerb-peek-trigger" data-context="{$target_dict->_context}" data-context-id="{$target_dict->id}">{$target_dict->_label|truncate:64}</a>
            {else}
                {$target_dict->_label|truncate:64}
            {/if}
        </span>
    {/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $container = $('#{$rl_uniqid}');
    $container.find('a.cerb-peek-trigger').cerbPeekTrigger();
});
</script>
