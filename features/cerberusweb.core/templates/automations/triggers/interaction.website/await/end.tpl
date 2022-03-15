{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}" nonce="{$session->nonce}">
{
    var $script = document.querySelector('#{$script_uid}');
    var $popup = $script.closest('.cerb-interaction-popup');

    var evt = $$.createEvent('cerb-interaction-event--end', {
        eventData: {$event_data_json nofilter}
    });
    
    $popup.dispatchEvent(evt);
}
</script>