{$is_downloadable = $active_worker->hasPriv('core.display.actions.attachments.download') && Context_Attachment::isDownloadableByActor($dict, $active_worker)}

<div class="cerb-u-mt-2">
    <ul class="cerb-ui-toolbar" data-cerb-attachment-toolbar hidden>
        {if $is_downloadable}
        <li class="cerb-peek-download" data-icon="download">{'common.download'|devblocks_translate|capitalize}</li>
        {/if}

        {if $context_counts}
            {foreach from=$context_counts item=count key=context_ext_id}
                {$context = $contexts.$context_ext_id}
                {if $context}
                <li class="cerb-search-trigger" data-context="{$context_ext_id}" data-query="attachments:(id:{$dict->id})" data-icon="{$context->params.icon|default:'collection'}" data-badge="{$count|default:0|number_format}">{$context->name}</li>
                {/if}
            {/foreach}
        {/if}
    </ul>
</div>

{if $is_downloadable}
    <div style="margin:10px;">
        {if !$dict->mime_type}
            {* ... do nothing ... *}
        {elseif in_array($dict->mime_type, [ 'audio/ogg', 'audio/mpeg', 'audio/wav', 'audio/x-wav' ])}
            <audio controls width="100%">
                <source src="{devblocks_url full=true}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" type="{$dict->mime_type}">
                Your browser does not support HTML5 audio.
            </audio>
        {elseif in_array($dict->mime_type, [ 'video/mp4', 'video/mpeg', 'video/quicktime' ])}
            <video controls width="100%">
                <source src="{devblocks_url full=true}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" type="{$dict->mime_type}">
                Your browser does not support HTML5 video.
            </video>
        {elseif in_array($dict->mime_type, [ 'image/png', 'image/jpg', 'image/jpeg', 'image/gif' ])}
            <img src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="max-width:100%;border:1px solid rgb(200,200,200);">
        {elseif in_array($dict->mime_type, [ 'application/json', 'message/rfc822', 'text/css', 'text/csv', 'text/javascript', 'text/plain', 'text/xml' ])}
            {if $dict->size < 1000000}
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px; border:1px solid var(--cerb-color-background-contrast-200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/pgp-signature', 'multipart/encrypted', 'multipart/signed' ])}
            {if $dict->size < 1000000}
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px; border:1px solid var(--cerb-color-background-contrast-200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/xhtml+xml', 'text/html' ])}
            {if $dict->size < 1000000}
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px; border:1px solid var(--cerb-color-background-contrast-200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/pdf' ]) && $smarty.const.APP_SECURITY_CSP_OBJECT_SRC}
            {if $dict->size < 5000000}
                <object data="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" width="100%" height="350"></object>
            {/if}
        {/if}
    </div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $widget = $('#cardWidget{$widget->getUniqueId($dict->id)}');
    const $toolbar = $widget.find('ul[data-cerb-attachment-toolbar]');

    // The source <li>s stay wired to their original behaviors; CerbUI.Toolbar renders the
    // visible strip and clicks the matching source <li> on select.

    // Download
    {if $is_downloadable}
    $toolbar.find('li.cerb-peek-download')
        .on('click', function(e) {
            e.stopPropagation();
            const a = document.createElement('a');
            a.style.display = 'none';
            document.body.appendChild(a);
            a.href = '{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}?download=';
            a.click();
            a.remove();
        });
    {/if}

    // Search
    $toolbar.find('li.cerb-search-trigger').cerbSearchTrigger();

    // Toolbar strip: record-type icons + count badges
    if($toolbar.length && window.CerbUI && CerbUI.Toolbar) {
        new CerbUI.Toolbar($toolbar[0], {
            badgeStyle: 'pill',
            onSelect: function(item, sourceLi) {
                if(sourceLi)
                    $(sourceLi).trigger('click');
            }
        });
    }
});
</script>