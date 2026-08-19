{$uniqid = uniqid('diff')}

{* The same revisions list (newest first) feeds both column menus. "Current" = the live working value the host
   editor seeds via cerb-diff-viewer-ready; each other entry is a saved changeset, loaded on demand. *}
{capture name=diff_rev_options}
    {foreach from=$changesets item=changeset}
        {$cs_worker = $changeset->getWorker()}
        <option value="{$changeset->id}"{if $cs_worker} data-avatar-url="{devblocks_url}c=avatars&context=worker&context_id={$cs_worker->id}{/devblocks_url}?v={$cs_worker->updated}"{/if}>{$changeset->created_at|devblocks_date}{if $cs_worker} - {$cs_worker->getName()}{/if}</option>
    {/foreach}
{/capture}

{if !$changesets}
<div class="cerb-ui-panel cerb-ui-panel--note" id="{$uniqid}">
    <div class="cerb-ui-header cerb-ui-header--center">
        <div class="cerb-ui-callout">
            <span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
            <div>
                <div class="cerb-ui-header--title-sm">No change history yet</div>
                <div class="cerb-ui-header--subtitle">This record has no saved revisions to compare.</div>
            </div>
        </div>
    </div>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
    const $popup = genericAjaxPopupFind($('#{$uniqid}'));
    $popup.dialog('option', 'title', '{'common.change_history'|devblocks_translate|capitalize}');
})();
</script>
{else}
<div style="display:flex;align-items:center;width:100%;margin-bottom:0.5em;">
    <div style="flex:1 1 0;min-width:0;display:flex;align-items:center;gap:0.5em;">
        <select class="cerb-diff-menu cerb-diff-menu-left">
            {if $show_current}<option value="current">Current</option>{/if}
            {$smarty.capture.diff_rev_options nofilter}
        </select>
        <div class="cerb-code-editor-toolbar">
            <button type="button" data-cerb-toolbar-button-restore title="Restore this version"><span class="cerb-icons cerb-icon-history"></span> Restore this version</button>
        </div>
    </div>
    <div style="flex:0 0 50px;"></div>
    <div style="flex:1 1 0;min-width:0;display:flex;align-items:center;gap:0.5em;">
        <select class="cerb-diff-menu cerb-diff-menu-right">
            {if $show_current}<option value="current">Current</option>{/if}
            {$smarty.capture.diff_rev_options nofilter}
        </select>
        {* Takes the step buttons' place when there's nothing to step through -- the note lands where the eye
           already goes looking for < and >, instead of sitting beside the menu where it's easy to miss. *}
        <span data-cerb-diff-identical class="cerb-u-text-muted" style="display:none;margin-left:auto;"><span class="cerb-icons cerb-icon-check"></span> Identical to the other panel</span>
        <div class="cerb-code-editor-toolbar" style="margin-left:auto;" data-cerb-diff-steps>
            <button type="button" data-cerb-toolbar-button-prev-change title="Previous change"><span class="cerb-icons cerb-icon-step-backward"></span></button>
            <button type="button" data-cerb-toolbar-button-next-change title="Next change"><span class="cerb-icons cerb-icon-step-forward"></span></button>
        </div>
    </div>
</div>

<div style="width:100%;">
    <div id="{$uniqid}"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);

    $popup.dialog('option', 'title', '{'common.change_history'|devblocks_translate|capitalize}');

    const record_key = '{$record_key}';

    const leftSel = $popup.find('select.cerb-diff-menu-left')[0];
    const rightSel = $popup.find('select.cerb-diff-menu-right')[0];

    // Editor mode (default): the right pane is the live "Current" value, seeded by the host via cerb-diff-viewer-ready;
    // the left pane defaults to the newest snapshot. No-current mode (e.g. bot behaviors, which persist on every edit
    // so "Current" is just the latest snapshot) drops "Current" and defaults to comparing the two latest snapshots.
    const showCurrent = {if $show_current}true{else}false{/if};
    const newestContent = {$left_content|json_encode nofilter};

    const viewer = new CerbUI.DiffViewer($div[0], {
        left: '',
        right: '',
        lines: 23,
    });

    // Cache of document text by menu value ('current' or a changeset id). Saves a round-trip when re-picking a
    // revision, and (in editor mode) holds the live "current" value so it can be shown on EITHER side.
    const contentCache = {};

    // Snapshot option values (everything except 'current'), newest first.
    const snapshotKeys = Array.prototype.map.call(leftSel.options, function(o) { return o.value; })
        .filter(function(v) { return v !== 'current'; });
    const newestKey = snapshotKeys.length ? snapshotKeys[0] : null;
    const secondKey = snapshotKeys.length > 1 ? snapshotKeys[1] : null;

    if(newestKey)
        contentCache[newestKey] = newestContent;

    // Renderer for a column's menu: prepends the worker avatar (on both the dropdown items and the selected
    // trigger), and — on dropdown items only — flags the one revision the OPPOSING column is currently showing
    // with the corresponding panel icon, so it's obvious what you're comparing against. Every row reserves the
    // same trailing slot (only the matching row paints an icon) so labels stay aligned. Menus rebuild on each
    // open, so the marker always reflects the live opposing selection.
    const makeRenderer = function(opposingSel, panelIcon) {
        return function(el, option) {
            const url = option.dataset.avatarUrl;
            if(url) {
                const img = document.createElement('img');
                img.className = 'cerb-avatar';
                img.src = url;
                el.insertBefore(img, el.firstChild);
            }

            if(el.classList.contains('cerb-ui-menu--item')) {
                const mark = document.createElement('span');
                mark.style.flexShrink = '0';
                mark.style.width = '1em';
                mark.style.marginLeft = '0.75em';
                if(option.value == opposingSel.value) {
                    mark.className = 'cerb-icons cerb-icon-' + panelIcon;
                    mark.title = 'Shown in the other panel';
                    mark.style.opacity = '0.8';
                }
                el.appendChild(mark);
            }
        };
    };

    // Fetch (and cache) a revision's content, then hand it to `cb`. 'current' is held in the cache; changesets
    // are loaded on demand via getChangesetJson.
    const loadContent = function(key, cb) {
        if(contentCache.hasOwnProperty(key)) {
            cb(contentCache[key]);
            return;
        }

        let formData = new FormData();
        formData.set('c', 'internal');
        formData.set('a', 'invoke');
        formData.set('module', 'records');
        formData.set('action', 'getChangesetJson');
        formData.set('changeset_id', key);

        $div.fadeTo('fast', 0.2, function() {
            genericAjaxPost(formData, null, null, function(json) {
                // getChangesetJson returns the changeset content dict { <record_key>:content }.
                const content = ('object' == typeof json && json.hasOwnProperty(record_key)) ? json[record_key] : '';
                contentCache[key] = content;
                $div.fadeTo('slow', 1.0);
                cb(content);
            });
        });
    };

    // null = nothing focused yet. The popup opens at the TOP OF THE DOCUMENT, not on a change, so starting at
    // 0 made the first "next" advance to the SECOND change -- the first one was never visited.
    let diff_step = null;

    // Identical panes are worth SAYING rather than making someone scroll the whole document to discover. The
    // note replaces the step buttons, which would otherwise sit there inviting a press that does nothing.
    const $identical = $popup.find('[data-cerb-diff-identical]');
    const $steps = $popup.find('[data-cerb-diff-steps]');

    const syncDiffState = function() {
        const count = viewer.getDiffs().length;

        // One or the other, never both: the note occupies the step buttons' slot.
        $steps.toggle(count > 0);
        $identical.toggle(0 === count);
    };

    const setPane = function(side, key) {
        loadContent(key, function(content) {
            if('left' == side)
                viewer.setLeft(content);
            else
                viewer.setCurrent(content);
            diff_step = null;
            syncDiffState();
        });
    };

    // "Restore this version" writes the LEFT (historical) document back. Only shown when the host registered an
    // onRestore handler (read-only hosts don't) and the left pane isn't "current" (restoring current is a no-op).
    const $restore = $popup.find('[data-cerb-toolbar-button-restore]');
    const updateRestoreState = function() {
        $restore.toggle(viewer.hasRestore() && 'current' != leftSel.value);
    };

    // The opposing-selection marker shows the panel the OTHER column occupies: the left menu flags the right
    // panel's revision, and vice versa.
    const leftMenu = new CerbUI.SelectMenu(leftSel, {
        filter: false,
        onRender: makeRenderer(rightSel, 'window-right'),
        onSelect: function(value) { setPane('left', value); updateRestoreState(); },
    });

    const rightMenu = new CerbUI.SelectMenu(rightSel, {
        filter: false,
        onRender: makeRenderer(leftSel, 'window-left'),
        onSelect: function(value) { setPane('right', value); },
    });

    if(showCurrent) {
        // Editor mode: left = newest snapshot, right stays on "Current" (seeded by the host below).
        if(newestKey) {
            viewer.setLeft(newestContent);
            leftMenu.setValue(newestKey);
        }
    } else {
        // No-current mode: right = newest snapshot, left = the one before it, so the latest change shows by default.
        if(newestKey) {
            viewer.setCurrent(newestContent);
            rightMenu.setValue(newestKey);
        }
        if(secondKey) {
            leftMenu.setValue(secondKey);
            setPane('left', secondKey);
        } else if(newestKey) {
            // Only one snapshot — show it on both sides (nothing earlier to diff against).
            viewer.setLeft(newestContent);
            leftMenu.setValue(newestKey);
        }
    }
    updateRestoreState();

    const onStepToDiff = function() {
        const delta = $(this).is('[data-cerb-toolbar-button-prev-change]') ? -1 : 1;
        const count = viewer.getDiffs().length;

        if(!count)
            return;

        if(null === diff_step) {
            // First press: land on the first change going forward, the last going backward.
            diff_step = (delta > 0) ? 0 : count - 1;
        } else {
            diff_step += delta;

            if(diff_step < 0) {
                diff_step = count - 1;
            } else if(diff_step > count - 1) {
                diff_step = 0;
            }
        }

        viewer.scrollToDiff(diff_step);
    };

    $popup.find('[data-cerb-toolbar-button-prev-change]').on('click', onStepToDiff);
    $popup.find('[data-cerb-toolbar-button-next-change]').on('click', onStepToDiff);

    $restore.on('click', function(e) {
        e.stopPropagation();
        viewer.restore();
        $popup.dialog('close');
    });

    $popup.triggerHandler($.Event('cerb-diff-viewer-ready', { viewer: viewer }));

    // Editor mode: the host's ready handler ran synchronously and seeded the right pane via setCurrent() — capture it
    // so "Current" can also be shown in the left column. (No-current mode has no live "Current" to capture.)
    if(showCurrent)
        contentCache['current'] = viewer.getCurrent();

    // Now that the host has (or hasn't) registered onRestore, finalize the Restore button's visibility.
    updateRestoreState();

    // Last: in editor mode the right pane only exists once the host's ready handler seeded it above, so an
    // earlier check would compare against an empty document and never report identical.
    syncDiffState();
})();
</script>
{/if}