{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
    $(function() {
        var $script = $('#{$script_uid}');
        var $form = $script.closest('form');

        var event_data = {
            eventData: { }
        };
        
        {if $event_data && is_array($event_data)}
        event_data.eventData = {$event_data|json_encode nofilter};
        {/if}

        var evt = $.Event('cerb-form-builder-end', event_data);
        $form.triggerHandler(evt);
    });
</script>