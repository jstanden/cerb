{$is_downloadable = Context_Attachment::isDownloadableByActor($dict, $active_worker)}

<div style="margin-top:5px;">
    {if $is_downloadable}
        <button type="button" class="cerb-peek-download"><span class="glyphicons glyphicons-cloud-download"></span> {'common.download'|devblocks_translate|capitalize}</button>
    {/if}

    {if $context_counts}
        {foreach from=$context_counts item=count key=context_ext_id}
            {$context = $contexts.$context_ext_id}
            {if $context}
                <button type="button" class="cerb-search-trigger" data-context="{$context_ext_id}" data-query="attachments:(id:{$dict->id})"><div class="badge-count">{$count|default:0}</div> {$context->name}</button>
            {/if}
        {/foreach}
    {/if}
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
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/pgp-signature', 'multipart/encrypted', 'multipart/signed' ])}
            {if $dict->size < 1000000}
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/xhtml+xml', 'text/html' ])}
            {if $dict->size < 1000000}
                <iframe sandbox="allow-same-origin" src="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" style="width:100%; height:300px;border:1px solid rgb(200,200,200);"></iframe>
            {/if}
        {elseif in_array($dict->mime_type, [ 'application/pdf' ])}
            {if $dict->size < 5000000}
                <object data="{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}" width="100%" height="350"></object>
            {/if}
        {/if}
    </div>
{/if}

<script type="text/javascript">
$(function() {
    var $widget = $('#cardWidget{$widget->getUniqueId($dict->id)}');

    // Download button
    {if $is_downloadable}
    $widget.find('button.cerb-peek-download')
        .on('click', function(e) {
            window.open('{devblocks_url}c=files&id={$dict->id}&name={$dict->_label|devblocks_permalink}{/devblocks_url}?download=');
        });
    {/if}

    // Search
    $widget.find('.cerb-search-trigger').cerbSearchTrigger();
});
</script>