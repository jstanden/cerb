{$popup_id = uniqid('popup')}
<div id="{$popup_id}">
    <form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
        <input type="hidden" name="c" value="profiles">
        <input type="hidden" name="a" value="invoke">
        <input type="hidden" name="module" value="message">
        <input type="hidden" name="action" value="">
        <input type="hidden" name="id" value="{$message->id}">

        {if $filtering_results.urls.blockedLink}
            <h3><span class="glyphicons glyphicons-ban" style="color:rgb(180,0,0);"></span> These external links are blocked by rules:</h3>

            <table style="margin-top:10px;">
                {foreach from=$filtering_results.urls.blockedLink item=urls key=host}
                    <tr>
                        <td>
                            <b>{$host}</b>
                            <ul style="margin:0;padding:0 0 0 2em;list-style:circle;">
                                {foreach from=$urls item=url key=k}
                                    <li style="word-break:break-all;padding:0 0 3px 0;">{$url}</li>
                                {/foreach}
                            </ul>
                        </td>
                    </tr>
                    </tr>
                {/foreach}
            </table>
        {/if}

        {if $filtering_results.urls.redirectedLink}
        <h3><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> These external links are allowed:</h3>

        <table style="margin-top:10px;">
            {foreach from=$filtering_results.urls.redirectedLink item=urls key=host}
                <tr>
                    <td>
                        <b>{$host}</b>
                        <ul style="margin:0;padding:0 0 0 2em;list-style:circle;">
                            {foreach from=$urls item=url key=k}
                            <li style="word-break:break-all;padding:0 0 3px 0;">{$url}</li>
                            {/foreach}
                        </ul>
                    </td>
                </tr>
            </tr>
            {/foreach}
        </table>
        {/if}
    </form>
</div>

<script type="text/javascript">
$(function() {
    var $popup = genericAjaxPopupFind('#{$popup_id}');

    $popup.one('popup_open',function() {
        $popup.dialog('option','title', 'External Links');
    });
});
</script>