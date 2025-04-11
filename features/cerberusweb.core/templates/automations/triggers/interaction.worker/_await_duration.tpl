<div>
    <h4 style="display:inline-block;">{$wait_message}</h4>
    <input type="hidden" name="prompts[duration]" value="done">
    <br>
    {include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
    setTimeout(
        function() {
            const $script = $('#{$script_uid}');
            const $form = $script.closest('form');

            $form.find('svg.cerb-spinner').first().hide();

            const evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        },
        {$wait_ms}
    );
});
</script>