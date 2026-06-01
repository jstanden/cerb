<fieldset style="margin-top:10px;position:relative;">
    <span data-cerb-link="remove_parent" class="cerb-icons cerb-icon-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);"></span>
    <legend>{'common.preview'|devblocks_translate|capitalize}</legend>

    {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompts.tpl" prompts=$prompts}
</fieldset>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
   let $script = $('#{$script_uid}');
   let $fieldset = $script.prev('fieldset');

   $fieldset.find('[data-cerb-link=remove_parent]').on('click', Devblocks.onClickRemoveParent);
});
</script>