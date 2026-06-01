<fieldset style="margin-top:10px;position:relative;">
    <span data-cerb-link="remove_fieldset" class="cerb-icons cerb-icon-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);"></span>
    <legend>{'common.preview'|devblocks_translate|capitalize}</legend>
    {include file="devblocks:cerberusweb.core::internal/snippets/prompts.tpl"}
</fieldset>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    let $script = $('#{$script_uid}');
    $script.prev('fieldset').find('[data-cerb-link=remove_fieldset]').on('click', function(e) {
        e.stopPropagation();
        $(this).closest('fieldset').remove();
    });
});
</script>