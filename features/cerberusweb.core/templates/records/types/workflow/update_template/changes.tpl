{if $summary}
{$uniqid = uniqid('wfchanges')}
<div id="{$uniqid}">
    <div class="cerb-u-flex cerb-u-items-center cerb-u-mb-2">
        <div class="cerb-u-text-muted cerb-u-fs-n1 cerb-u-text-uppercase">Changes</div>
        <div class="cerb-code-editor-toolbar" style="margin-left:auto;">
            <button type="button" data-cerb-toolbar-button-prev-change title="Previous change"><span class="cerb-icons cerb-icon-step-backward"></span></button>
            <button type="button" data-cerb-toolbar-button-next-change title="Next change"><span class="cerb-icons cerb-icon-step-forward"></span></button>
        </div>
    </div>

    <div class="cerb-u-flex cerb-u-gap-3">
        <div class="cerb-x-summary" style="flex:0 0 240px;display:flex;flex-direction:column;gap:0.15em;max-height:460px;overflow:auto;">
            {foreach from=$summary item=change}
            <div class="cerb-x-jump cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-bgg-hover" data-line-left="{$change.line_left}" data-line-right="{$change.line_right}"{if $change.is_config} data-config-change="1" data-config-old="{$change.old_value}" data-config-new="{$change.new_value}"{/if} title="{$change.action|capitalize}" style="cursor:pointer;padding:0.35em 0.5em;border-radius:0.35em;">
                <span class="cerb-icons cerb-icon-{$change.action_icon}" style="color:var(--cerb-color-tag-{$change.action_color});"></span>
                <span class="cerb-icons cerb-icon-{$change.type_icon} cerb-u-text-muted"></span>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{$change.name}</span>
            </div>
            {/foreach}
        </div>
        <div style="flex:1 1 0;min-width:0;">
            <div class="cerb-x-diff"></div>
            <div class="cerb-x-config-detail" style="display:none;">
                <div class="cerb-u-text-muted cerb-u-fs-n1 cerb-u-text-uppercase cerb-u-mb-2">Configuration value changed</div>
                <div class="cerb-u-flex cerb-u-gap-3">
                    <div class="cerb-ui-tile cerb-ui-tile--block" style="flex:1;align-items:flex-start;">
                        <div class="cerb-ui-tile--text">
                            <div class="cerb-ui-tile--kind">Before</div>
                            <div class="cerb-ui-tile--name cerb-x-config-old" style="white-space:pre-wrap;word-break:break-word;"></div>
                        </div>
                    </div>
                    <div class="cerb-ui-tile cerb-ui-tile--block" style="flex:1;align-items:flex-start;">
                        <div class="cerb-ui-tile--text">
                            <div class="cerb-ui-tile--kind">After</div>
                            <div class="cerb-ui-tile--name cerb-x-config-new" style="white-space:pre-wrap;word-break:break-word;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
    const $root = $('#{$uniqid}');

    if(!(window.CerbUI && CerbUI.DiffViewer))
        return;

    const viewer = new CerbUI.DiffViewer($root.find('.cerb-x-diff')[0], {
        left: {$left_doc|json_encode nofilter},
        right: {$right_doc|json_encode nofilter},
        lines: 22
    });

    let diff_step = 0;

    const onStepToDiff = function() {
        const delta = $(this).is('[data-cerb-toolbar-button-prev-change]') ? -1 : 1;
        const count = viewer.getDiffs().length;

        if(!count)
            return;

        diff_step += delta;

        if(diff_step < 0)
            diff_step = count - 1;
        else if(diff_step > count - 1)
            diff_step = 0;

        viewer.scrollToDiff(diff_step);
    };

    $root.find('[data-cerb-toolbar-button-prev-change]').on('click', onStepToDiff);
    $root.find('[data-cerb-toolbar-button-next-change]').on('click', onStepToDiff);

    const $diff = $root.find('.cerb-x-diff');
    const $detail = $root.find('.cerb-x-config-detail');

    const setConfigValue = function($el, value) {
        if(value === '')
            $el.text('(none)').addClass('cerb-u-text-muted');
        else
            $el.text(value).removeClass('cerb-u-text-muted');
    };

    $root.find('.cerb-x-jump').on('click', function(e) {
        e.preventDefault();

        const $row = $(this);

        $root.find('.cerb-x-jump').removeClass('cerb-u-bgg-2');
        $row.addClass('cerb-u-bgg-2');

        // Config values live in a separate document (config_kata), not the diffed template — show their
        // before/after in a detail panel instead of the (empty) diff panes.
        if($row.is('[data-config-change]')) {
            setConfigValue($detail.find('.cerb-x-config-old'), $row.attr('data-config-old') || '');
            setConfigValue($detail.find('.cerb-x-config-new'), $row.attr('data-config-new') || '');

            $diff.hide();
            $detail.show();
            return;
        }

        $detail.hide();
        $diff.show();

        const ll = parseInt($row.attr('data-line-left'), 10) || 0;
        const lr = parseInt($row.attr('data-line-right'), 10) || 0;

        // Scroll the pane that actually contains this resource (right = new template for a create/update,
        // left = old template for a deletion); the panes are scroll-synced, so the other one follows.
        if(lr > 0)
            viewer.right.scrollToLine(Math.max(0, lr - 3));
        else
            viewer.left.scrollToLine(Math.max(0, ll - 3));
    });
})();
</script>
{else}
    (no changes)
{/if}