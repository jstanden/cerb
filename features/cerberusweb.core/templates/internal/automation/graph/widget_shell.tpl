{* Card/profile "Automation: Graph" widget shell: a segmented Graph | Code toggle.
   Graph (default, visible so NodeGraph measures/fits correctly) reuses the shared graph partial;
   Code lazily builds a read-only KataEditor (highlight + folding) on first reveal. *}
<div class="cerb-automation-graph-widget" id="{$el_id}">
    <div class="cerb-ui-switcher" data-graph-switcher>
        <button type="button" class="cerb-ui-switcher--active" data-value="graph"><span class="cerb-icons cerb-icon-nodes"></span> Graph</button>
        <button type="button" data-value="code"><span class="cerb-icons cerb-icon-embed"></span> Code</button>
    </div>

    <div data-pane="graph">
        {include file="devblocks:cerberusweb.core::internal/automation/graph/render.tpl" graph=$graph graph_uid="`$el_id`Graph" minimap=$minimap graph_height=$graph_height interactive_editor=false framing="fit"}
    </div>

    <div data-pane="code" style="display:none;">
        {* Overlay editors must live inside .cerb-ui-form: it re-normalizes the transparent --input textarea
           (line-height/padding/chrome) so it stays in lockstep with the colored mirror, and the flex --field
           stretches the editor to full width. Legacy global TEXTAREA rules break both otherwise. *}
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <textarea data-graph-code-editor spellcheck="false">{$automation->script}</textarea>
            </div>
        </div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
    var root = document.getElementById('{$el_id}');
    if(!root) return;
{literal}
    var $root = $(root);
    var $graphPane = $root.find('[data-pane=graph]');
    var $codePane = $root.find('[data-pane=code]');
    var codeEditor = null;

    // Build the read-only editor lazily: an editor constructed in a hidden pane can't measure its rows.
    function buildCodeEditor() {
        if(!codeEditor && window.CerbUI && CerbUI.KataEditor)
            codeEditor = new CerbUI.KataEditor($codePane.find('[data-graph-code-editor]')[0], { readOnly: true, minLines: 6, maxLines: 30 });
        return codeEditor;
    }

    function showPane(value) {
        if('code' === value) {
            $graphPane.hide();
            $codePane.show();
            buildCodeEditor();
        } else {
            $codePane.hide();
            $graphPane.show();
        }
    }

    var switcher = (window.CerbUI && CerbUI.Switcher)
        ? new CerbUI.Switcher($root.find('[data-graph-switcher]')[0], { onSelect: function(value) { showPane(value); } })
        : null;

    // Double-click a graph node → switch to Code and reveal that node's source line (0-based, like the editor).
    $graphPane.on('cerb-automation-graph:node-open', function(e) {
        var detail = (e.originalEvent && e.originalEvent.detail) || e.detail;
        var line = detail ? detail.line : null;
        if(line == null) return;

        if(switcher) switcher.setValue('code', { fireCallback: true });
        else showPane('code');

        var ed = buildCodeEditor();
        if(!ed) return;
        // Defer so a just-built editor has laid out before we scroll (gotoLine auto-reveals any fold).
        window.requestAnimationFrame(function() {
            ed.scrollToLine(line);
            ed.gotoLine(line + 1);
            if(typeof ed.flashLine === 'function') ed.flashLine(line, { color: 'orange' });   // pulse the landing line
        });
    });
{/literal}
})();
</script>
