{* Reusable automation node-graph viewer. Shared by the editor's Visualize tab and the
   card/profile "Automation: Graph" widgets. Params: $graph (required), $graph_uid, $minimap,
   $graph_height, $interactive_editor (editor-only goto + tab-refresh wiring),
   $framing ('fit' = frame the whole graph; 'focus' = zoom to the start flow, top-aligned). *}
{$graph_uid = $graph_uid|default:uniqid('graph')}
{$minimap = $minimap|default:false}
{$graph_height = $graph_height|default:500}
{$interactive_editor = $interactive_editor|default:false}
{$framing = $framing|default:'fit'}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
    .cerb-automation-graph { margin-top:0.5em; border:1px solid var(--cerb-color-background-contrast-225); border-radius:0.5em; overflow:hidden; }
    /* Read-only editor nodes jump the editor to their source line on double-click. */
    .cerb-automation-graph--interactive .cerb-ui-node { cursor:pointer; }
</style>

<div class="cerb-automation-graph-viewer">
    <div class="cerb-code-editor-toolbar">
        <button type="button" data-action="fit" title="Fit"><span class="cerb-icons cerb-icon-resize-full"></span></button>
        <button type="button" data-action="focus-start" title="Focus start"><span class="cerb-icons cerb-icon-flag"></span></button>
        <button type="button" data-action="zoom-in" title="Zoom in"><span class="cerb-icons cerb-icon-zoom-in"></span></button>
        <button type="button" data-action="zoom-out" title="Zoom out"><span class="cerb-icons cerb-icon-zoom-out"></span></button>
        {if $interactive_editor}<button type="button" data-action="refresh" title="Refresh"><span class="cerb-icons cerb-icon-refresh"></span></button>{/if}
    </div>

    {* CerbUI.NodeGraph enhances this seed markup on construction (security boundary: only data-* is read). *}
    <div id="{$graph_uid}" class="cerb-automation-graph{if $interactive_editor} cerb-automation-graph--interactive{/if}" style="height:{$graph_height}px;">
        {foreach from=$graph.nodes item=node}
            <div data-node-id="{$node.id}" data-node-type="{$node.type}" data-label="{$node.label}" data-tier="{$node.tier}" data-line="{$graph.symbol_meta[$node.id]|default:''}"{if isset($node.icon)} data-node-icon="{$node.icon}"{/if}{if isset($node.description)} data-description="{$node.description}"{/if}{if $node.inletCorner} data-inlet-corner{/if}>
                {if $node.branches}
                    {foreach from=$node.branches item=branch}
                        <span data-branch data-name="{$branch.name}" data-label="{$branch.label}"{if isset($branch.line)} data-line="{$branch.line}"{/if}></span>
                    {/foreach}
                {/if}
                {if $node.previewRows}
                    {foreach from=$node.previewRows item=row}
                        <span data-preview-row data-label="{$row.label}" data-icon="{$row.icon}" data-tooltip="{$row.tooltip}"{if isset($row.variant)} data-variant="{$row.variant}"{/if}{if isset($row.line)} data-line="{$row.line}"{/if}></span>
                    {/foreach}
                {/if}
            </div>
        {/foreach}
        {foreach from=$graph.edges item=edge}
            <div data-edge data-source="{$edge.source}" data-target="{$edge.target}"{if $edge.sourceHandle} data-source-handle="{$edge.sourceHandle}"{/if}{if $edge.targetHandle} data-target-handle="{$edge.targetHandle}"{/if}{if $edge.bidirectional} data-bidirectional{/if}{if $edge.curve} data-curve{/if}></div>
        {/foreach}
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
    var host = document.getElementById('{$graph_uid}');
    if(!host) return;
    var CFG = { minimap: {if $minimap}true{else}false{/if}, interactiveEditor: {if $interactive_editor}true{else}false{/if}, framing: '{$framing}' };
{literal}
    if(!(window.CerbUI && CerbUI.NodeGraph)) return;

    var $graph = $(host);
    var $viewer = $graph.closest('.cerb-automation-graph-viewer');
    var $toolbar = $viewer.find('.cerb-code-editor-toolbar');

    // Capture node id → editor line BEFORE constructing (the constructor clears the seed markup as it enhances).
    var symbolMeta = {};
    host.querySelectorAll('[data-node-id]').forEach(function(el) {
        var line = el.getAttribute('data-line');
        if(line !== null && line !== '') symbolMeta[el.getAttribute('data-node-id')] = parseInt(line, 10);
    });

    // Abstract automation block types (ids match getSyntaxGraphForViewer() in dao/automation.php).
    var NODE_TYPES = [
        { id:'start',    label:'Start',    icon:'flag',     headerColor:'#48bb78', category:'Flow',     ports:'center', start:true },
        { id:'action',   label:'Action',   icon:'cube',     headerColor:'#3182ce', category:'Action',   ports:'center' },
        { id:'await',    label:'Await',    icon:'clock',    headerColor:'#3182ce', category:'Await',    ports:'center' },
        { id:'decision', label:'Decision', icon:'hierarchy', headerColor:'#d69e2e', category:'Decision', ports:'center' },
        { id:'outcome',  label:'Outcome',  icon:'signpost', headerColor:'#0987a0', category:'Outcome',  ports:'center' },
        { id:'loop',     label:'Loop',     icon:'repeat',   headerColor:'#805ad5', category:'Loop',     ports:'center' },
        { id:'return',   label:'Return',   icon:'return',   headerColor:'#38a169', category:'Return',   ports:'center', terminal:true },
        { id:'exit',     label:'Exit',     icon:'octagon',  headerColor:'#e53e3e', category:'Exit',     ports:'center', terminal:true }
    ];

    var opts = { nodeTypes: NODE_TYPES, minimap: CFG.minimap };

    // Double-click a node/part → emit a semantic event any host can act on: the automation editor jumps its
    // script editor to that source line; the card/profile widget switches to its Code pane and reveals the line.
    var $popup = CFG.interactiveEditor ? genericAjaxPopupFind($graph) : null;

    var openAt = function(line, nodeId) {
        host.dispatchEvent(new CustomEvent('cerb-automation-graph:node-open', {
            bubbles: true,
            detail: { nodeId: (nodeId == null ? null : nodeId), line: (line == null ? null : line) }
        }));
        if($popup && line != null)
            $popup.trigger($.Event('cerb-automation-editor--goto', { editor_line: line }));
    };

    opts.onNodeOpen = function(n) {
        if(!n) return;
        openAt(symbolMeta.hasOwnProperty(n.id) ? symbolMeta[n.id] : null, n.id);
    };

    opts.onNodePartOpen = function(part) {
        if(!part) return;
        openAt((part.line == null) ? null : part.line, part.name || null);
    };

    // The constructor reads the seed markup and renders the graph (no loadJSON needed).
    var g = new CerbUI.NodeGraph(host, opts);

    // 'focus' frames just the START node at 100% (top-aligned, horizontally centered on that node) so it's always
    // in view and the flow reads down/right — ignoring the rest of the graph's extent (focusLane would frame the
    // whole lane's bbox, pushing the start off-screen when the graph fans right). 'fit' frames everything.
    var focusStart = function() {
        var startNode = null;
        g.nodes.forEach(function(node) {
            if(!startNode && node.el && node.el.getAttribute('data-node-type') === 'start') startNode = node;
        });
        if(startNode && g.canvas) g.canvas.focusNodes([startNode], 1);
        else g.focusLane(0);
    };
    if(CFG.framing === 'focus') focusStart();

    // Editor-only: re-fire the visualization tab's onTabSelected (re-posts the live script + redraws).
    if(CFG.interactiveEditor) {
        $toolbar.find('[data-action=refresh]').on('click', function() {
            if(window.CerbUI && CerbUI.Tabs) CerbUI.Tabs.fromPanel(host)?.refresh();
        });
    }

    $toolbar.find('[data-action=focus-start]').on('click', focusStart);
    $toolbar.find('[data-action=fit]').on('click', function() { g.fit(); });
    $toolbar.find('[data-action=zoom-in]').on('click', function() { if(g.canvas) g.canvas.zoomIn(); });
    $toolbar.find('[data-action=zoom-out]').on('click', function() { if(g.canvas) g.canvas.zoomOut(); });
{/literal}
})();
</script>
