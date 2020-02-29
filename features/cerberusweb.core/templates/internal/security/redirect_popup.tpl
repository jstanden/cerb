{$popup_id = uniqid('popup')}
<div id="{$popup_id}">
    <p>
        Please review your destination before clicking on the link:
    </p>

    <table cellpadding="5">
        {if $url_parts.host}
        <tr>
            <td valign="top" style="font-size:125%;">
                <b>Host:</b>
            </td>
            <td style="font-size:125%;">
                {if $url_parts.scheme == 'https'}
                    <span class="glyphicons glyphicons-lock" title="SSL"></span>
                {elseif $url_parts.scheme = 'http'}
                    <span class="glyphicons glyphicons-unlock" title="No SSL"></span>
                {/if}
                {$url_parts.host}
            </td>
        </tr>
        {else}
            <tr>
                <td valign="top" style="font-size:125%;">
                    <b>Protocol:</b>
                </td>
                <td style="font-size:125%;">
                    {$url_parts.scheme}
                </td>
            </tr>
        {/if}
        <tr>
            <td valign="top" style="font-size:125%;">
                <b>Path:</b>
            </td>
            <td style="font-size:125%;word-break:break-all;">
                {$url_parts.path}
            </td>
        </tr>
        {if $query_parts}
        <tr>
            <td valign="top" style="font-size:125%;">
                <b>Query:</b>
            </td>
            <td style="font-size:125%;word-break:break-all;">
                <table>
                {foreach from=$query_parts item=qv key=qk}
                    <tr>
                        <td valign="top" nowrap="nowrap">
                            <b>{$qk}:</b>
                        </td>
                        <td>
                            {$qv}
                        </td>
                    </tr>
                {/foreach}
                </table>
            </td>
        </tr>
        {/if}
    </table>

    <p>
        <a href="{$url}" data-cerb-link target="_blank" rel="noopener noreferrer nofollow" style="font-size:150%;font-weight:bold;word-break:break-all;">{$url}</a>
    </p>
</div>

<script type="text/javascript">
$(function() {
    var $popup = genericAjaxPopupFind('#{$popup_id}');

    $popup.one('popup_open',function(event,ui) {
        $popup.dialog('option','title', 'You are being redirected to an external link:');

        $popup.find('a[data-cerb-link]')
            .on('click', function() {
                genericAjaxPopupClose($popup);
            })
            .focus()
        ;
    });
});
</script>