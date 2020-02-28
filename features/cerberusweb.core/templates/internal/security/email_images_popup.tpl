{$popup_id = uniqid('popup')}
{$sender = $message->getSender()}

<div id="{$popup_id}">
    <form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
        <input type="hidden" name="c" value="profiles">
        <input type="hidden" name="a" value="invoke">
        <input type="hidden" name="module" value="message">
        <input type="hidden" name="action" value="saveImagesPopup">
        <input type="hidden" name="id" value="{$message->id}">
        <input type="hidden" name="sender_id" value="{$sender->id}">

        <div style="margin-bottom:10px;">
            <button type="button" data-cerb-button="show-images"><span class="glyphicons glyphicons-picture"></span> Display images</button>

            {if !$sender->is_trusted}
                <button type="button" data-cerb-button="trust"><span class="glyphicons glyphicons-circle-plus"></span> Always show images from this sender</button>
            {else}
                <button type="button" data-cerb-button="untrust"><span class="glyphicons glyphicons-circle-remove"></span> Stop showing images from this sender</button>
            {/if}

            {*<button type="button" data-cerb-button="refresh"><span class="glyphicons glyphicons-refresh"></span> Refresh</button>*}
        </div>

        <div>
            <b>Sender:</b> <a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;word-break:break-all;" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$sender->id}">{$sender->email}</a>
        </div>

        {if $filtering_results.urls.blockedImage}
        <h3><span class="glyphicons glyphicons-ban" style="color:rgb(180,0,0);"></span> These external images are blocked by rules:</h3>

        <div data-list="deny" style="margin-left:15px;">
        {foreach from=$filtering_results.urls.blockedImage item=urls key=host}
            <div>
                <b>{$host}</b>
                <ul style="margin:0;padding:0 0 0 2em;list-style:circle;">
                    {foreach from=$urls item=url key=k}
                    <li style="word-break:break-all;padding:0 0 3px 0;">
                        {$url}
                    </li>
                    {/foreach}
                </ul>
            </div>
        {/foreach}
        </div>
        {/if}

        {if $filtering_results.urls.proxiedImage}
        <h3><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> These external images are allowed:</h3>

        <div data-list="allow" style="margin-left:15px;">
        {foreach from=$filtering_results.urls.proxiedImage item=urls key=host}
            <div>
                <b>{$host}</b>
                <ul style="margin:0;padding:0 0 0 2em;list-style:circle;">
                    {foreach from=$urls item=url key=k}
                    <li style="word-break:break-all;padding:0 0 3px 0;">
                        {$url}
                    </li>
                    {/foreach}
                </ul>
            </div>
        {/foreach}
        </div>
        {/if}
    </form>
</div>

<script type="text/javascript">
$(function() {
    var $popup = genericAjaxPopupFind('#{$popup_id}');
    var $layer = $popup.attr('data-layer');

    $popup.one('popup_open',function() {
        $popup.dialog('option','title', 'External Images');

        $popup.find('.cerb-peek-trigger').cerbPeekTrigger();

        var $frm = $popup.find('form');

        $popup.find('[data-cerb-button=show-images]').on('click', function(e) {
            e.stopPropagation();

            var evt = $.Event('cerb-message--show-images');
            $popup.triggerHandler(evt);

            genericAjaxPopupClose($popup);
        });

        {*
        $popup.find('[data-cerb-button=refresh]').on('click', function(e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'message');
            formData.set('action', 'renderImagesPopup');
            formData.set('id', '{$message->id}');
            formData.set('type', 'images');

            genericAjaxPopup($layer, formData, 'reuse');
        });
        *}

        $popup.find('[data-cerb-button=trust]').on('click', function(e) {
            e.stopPropagation();

            var formData = new FormData($frm[0]);
            formData.set('is_trusted', '1');

            genericAjaxPopupPostCloseReloadView($layer, formData, null, false);
        });

        $popup.find('[data-cerb-button=untrust]').on('click', function(e) {
            e.stopPropagation();

            var formData = new FormData($frm[0]);
            formData.set('is_trusted', '0');

            genericAjaxPopupPostCloseReloadView($layer, formData, null, false);
        });
    });
});
</script>