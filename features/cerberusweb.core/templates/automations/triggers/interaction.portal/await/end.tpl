{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$$.ready(function() {
    var $script = document.querySelector('#{$script_uid}');
    var $panel = $script.closest('.cerb-interaction-panel');

    var evt = $$.createEvent('cerb-interaction-event--end', {
        eventData: {$event_data_json nofilter}
    });

    $panel.dispatchEvent(evt);
});
</script>