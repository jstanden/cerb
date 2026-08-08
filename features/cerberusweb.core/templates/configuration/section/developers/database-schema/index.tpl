<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">Database Schema</div>
        <div class="cerb-ui-header--subtitle">Verify database schema integrity</div>
    </div>
    <div class="cerb-ui-header--right">
        <button type="button" class="cerb-ui-button" data-cerb-schema-kata>
            <span class="cerb-icons cerb-icon-database"></span> Schema KATA
        </button>
    </div>
</div>

<div id="frmSetupSchemaTesterOptions" class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
    <label class="cerb-ui-toggle" data-cerb-only-differences>
        <input type="checkbox" name="only_differences" value="1">
        <span class="cerb-ui-toggle--slider"></span>
    </label>
    <span>Only show differences</span>

    <label class="cerb-ui-toggle cerb-u-ml-2" data-cerb-ignore-collation>
        <input type="checkbox" name="ignore_collation" value="1">
        <span class="cerb-ui-toggle--slider"></span>
    </label>
    <span title="Collation drift is expected everywhere while the schema migrates off utf8mb3">Ignore collation</span>
</div>

{if 'fieldsets' == $layout.style}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
{elseif in_array($layout.style, ['columns','grid'])}
    {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
{else}
    {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $options = $('#frmSetupSchemaTesterOptions');
    const $sheet = $options.nextAll('div').find('table.cerb-sheet').first();

    // Only the <th> carries data-column-key, so the collation cell is found by column position
    const collation_idx = $sheet.find('thead th[data-column-key="collation"]').index();

    // A row is worth showing when it's an added table, or when it has a drift marker somewhere that
    // isn't the collation cell (that exclusion only applies while `ignore_collation` is on)
    const isInteresting = function($row, ignore_collation) {
        if($row.find('.cerb-icon-plus').length)
            return true;

        const $alerts = $row.find('.cerb-icon-alert');

        if(!$alerts.length)
            return false;

        if(!ignore_collation || collation_idx < 0)
            return true;

        return $alerts.filter(function() {
            return $(this).closest('td').index() !== collation_idx;
        }).length > 0;
    };

    const apply = function() {
        const only_differences = toggle_differences.getValue();
        const ignore_collation = toggle_collation.getValue();

        $sheet.find('tbody').each(function() {
            const $row = $(this);
            $row.toggle(!only_differences || isInteresting($row, ignore_collation));
        });

        // With every row shown there's nothing for the collation filter to narrow
        toggle_collation.setDisabled(!only_differences);
    };

    const toggle_differences = new CerbUI.Toggle($options.find('[data-cerb-only-differences]')[0], {
        onChange: apply
    });

    const toggle_collation = new CerbUI.Toggle($options.find('[data-cerb-ignore-collation]')[0], {
        onChange: apply
    });

    apply();

    $('[data-cerb-schema-kata]').on('click', function() {
        genericAjaxPopup('schemaKata', 'c=config&a=invoke&module=database_schema&action=schemaKataPopup', null, false, '75%');
    });
});
</script>
