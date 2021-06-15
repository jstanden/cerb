<div>
    {include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
    <h4 style="display:inline-block;">{$wait_message}</h4>
    <input type="hidden" name="prompts[duration]" value="done">
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
    setTimeout(
        function() {
            var $script = $('#{$script_uid}');
            var $form = $script.closest('form');
            
            var evt = $.Event('cerb-form-builder-submit');
            $form.triggerHandler(evt);
        },
        {$wait_ms}
    );
});
</script>