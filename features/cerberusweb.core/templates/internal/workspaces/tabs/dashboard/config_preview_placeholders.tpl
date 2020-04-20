<fieldset style="margin-top:10px;position:relative;">
    <span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;" onclick="$(this).closest('fieldset').remove();"></span>
    <legend>{'common.preview'|devblocks_translate|capitalize}</legend>

    {include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompts.tpl" prompts=$prompts}
</fieldset>