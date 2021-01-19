<fieldset id="frmSetupMailFiltering" class="peek">
    <legend>{'common.automations'|devblocks_translate|capitalize}</legend>
    
    <button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_AUTOMATION_EVENT}" data-context-id="mail.filter" data-edit="true">
        <span class="glyphicons glyphicons-cogwheel"></span> {'common.configure'|devblocks_translate|capitalize}
    </button>
</fieldset>

<script type="text/javascript">
$(function() {
    var $frm = $('#frmSetupMailFiltering');
    $frm.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>