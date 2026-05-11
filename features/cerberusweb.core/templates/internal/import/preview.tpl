<div>
    {foreach from=$preview item=record}
    <div>
        {if $record.object_id}
            <h3>Update <a data-context="{$record_ext->id}" data-context-id="{$record.object_id}">{$record_ext->manifest->params.alias} #{$record.object_id}</a></h3>
        {else}
            <h3>New {$record_ext->manifest->params.alias}</h3>
        {/if}
        {foreach from=$record.values item=v key=k}
        {if !is_null($v)}
            <b>{$keys[$k].label}:</b>
            <div style="padding:0.5em;">{$v|truncate:512}</div>
        {/if}
        {/foreach}
        <hr style="color:var(--cerb-color-fieldset-border);">
    </div>
    {/foreach}
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    const $script = $('#{$script_uid}');
    const $div = $script.prev('div');
    $div.find('a[data-context-id]').cerbPeekTrigger();
});
</script>