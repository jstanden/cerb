<h2>Database Schema</h2>

<label id="frmSetupSchemaTesterOptions">
    <input type="checkbox" name="only_differences" value="1">
    Only show differences
</label>

{if 'fieldsets' == $layout.style}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
{elseif in_array($layout.style, ['columns','grid'])}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
{else}
    {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $only_differences = $('#frmSetupSchemaTesterOptions input[name=only_differences]');
    let $sheet = $only_differences.parent().next('div').find('table').first();

    $only_differences.on('change', function(e) {
        e.stopPropagation();

        if($only_differences.is(':checked')) {
            $sheet.find('tbody').each(
                function() {
                    if(0 === $(this).find('.cerb-icon-alert, .cerb-icon-plus').length)
                        $(this).hide();
                }
            );
        } else {
            $sheet.find('tbody').show();
        }
    });
});
</script>