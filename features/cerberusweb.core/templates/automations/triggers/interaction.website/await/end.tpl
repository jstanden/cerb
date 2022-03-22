{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}" nonce="{$session->nonce}">
{
    let $script = document.querySelector('#{$script_uid}');
    let $popup = $script.closest('.cerb-interaction-popup');

    let evt = $$.createEvent('cerb-interaction-event--end', {
        eventData: {$event_data_json nofilter}
    });
    
    $popup.dispatchEvent(evt);
}
</script>