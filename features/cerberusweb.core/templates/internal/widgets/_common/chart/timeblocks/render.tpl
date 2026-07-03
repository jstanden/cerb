<div id="widget{$widget->id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        try {
            const $widget = $('#widget{$widget->id}');
            const data = {$data nofilter};

            new CerbUI.Timeblocks($widget[0], { data: data });

        } catch(e) {
            console.error(e);
        }
    });
</script>
