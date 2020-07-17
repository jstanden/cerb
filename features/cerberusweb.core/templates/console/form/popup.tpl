{include file="devblocks:cerberusweb.core::console/form/panel.tpl"}

{$script_uid = uniqid('script')}
<script id="{$script_uid}" type="text/javascript">
$(function() {
    var $popup = genericAjaxPopupFind('#{$script_uid}');

    $popup.one('popup_open',function() {
        $popup.dialog('option','title', "{$bot_name|escape:'javascript' nofilter}");

        {if $bot_image_url}
        $popup.closest('.ui-dialog').find('.ui-dialog-title')
            .prepend(
                $('<img/>')
                    .addClass('cerb-avatar')
                    .css('width', '24px')
                    .css('height', '24px')
                    .css('margin-right', '5px')
                    .attr('src', '{$bot_image_url|escape:'javascript' nofilter}')
            )
        ;
        {/if}

        $popup.closest('.ui-dialog').find('.ui-dialog-titlebar-close')
            .attr('tabindex', '-1')
        ;
    });
});
</script>
