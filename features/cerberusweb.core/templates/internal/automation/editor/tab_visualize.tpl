{$div_uid = uniqid('graph')}

<style>
    .cerb-graph-dag svg { width: 100%; }
    .cerb-graph-dag .node { cursor: pointer; }
    .cerb-graph-dag.node .label-container { stroke: var(--cerb-color-text); fill: var(--cerb-color-background); }
    .cerb-graph-dag .node circle { fill: var(--cerb-color-background); stroke: var(--cerb-color-text); }
    .cerb-graph-dag .node ellipse { fill: var(--cerb-color-background); stroke: var(--cerb-color-text); }
    .cerb-graph-dag .node polygon { fill: var(--cerb-color-background); stroke: var(--cerb-color-text); }
    .cerb-graph-dag .node polyline { fill: var(--cerb-color-background); stroke: var(--cerb-color-text); }
    .cerb-graph-dag .node rect { fill: var(--cerb-color-background); stroke: var(--cerb-color-text); }
    .cerb-graph-dag .node text { fill: var(--cerb-color-text); }
    .cerb-graph-dag marker { stroke: var(--cerb-color-background-contrast-200); fill: var(--cerb-color-background-contrast-200); }
    .cerb-graph-dag .edgePath { stroke: var(--cerb-color-background-contrast-200); }
    .cerb-graph-dag .edgeLabel { fill: var(--cerb-color-background-contrast-170); stroke: var(--cerb-color-background); paint-order: stroke; stroke-width: 3; }
</style>

<div>
    <div class="cerb-code-editor-toolbar">
        <button type="button"><span class="glyphicons glyphicons-refresh"></span></button>
    </div>

    <div id="{$div_uid}" class="cerb-graph-dag"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
Devblocks.loadResources({
    'js': [
        '/resource/devblocks.core/js/d3/d3.v5.min.js',
        '/resource/devblocks.core/js/dagre/dagre-d3.min.js',
    ]
}, function() {
    var $graph = $('#{$div_uid}');
    var $popup = genericAjaxPopupFind($graph);

    var $panel = $graph.closest('.ui-tabs-panel');
    var $tabs = $panel.closest('.ui-tabs');
    var $toolbar = $panel.find('.cerb-code-editor-toolbar');
    
    $toolbar.find('button').on('click', function() {
        var tab_id = $tabs.tabs('option','active');
        $tabs.tabs('option', 'active', null)
        $tabs.tabs('option', 'active', tab_id);
    });

    let symbolMeta = {$graph.symbol_meta|json_encode nofilter};

    $graph.on('click', function(e) {
        let $target = $(e.target);
        
        if($target.closest('g.node')) {
            let node_id = $target.closest('g.node').attr('data-id');
            
            if(!node_id)
                return;
            
            if(symbolMeta.hasOwnProperty(node_id)) {
                $popup.trigger($.Event('cerb-automation-editor--goto', {
                    editor_line: symbolMeta[node_id]
                }));
            }
        }
    });
    
    var g = new dagreD3.graphlib.Graph({ compound:true })
        .setGraph({
            //ranker: 'network-simplex',
            //ranker: 'tight-tree',
            //rankdir: 'LR',
            //align: 'DR',
            nodesep: 25,
            edgesep: 25,
            ranksep: 25,
        })
        .setDefaultEdgeLabel(function() { return { } })
    ;
    
    let dagNodes = {$graph.nodes|json_encode nofilter};
    let dagEdges = {$graph.edges|json_encode nofilter};
    
    for(var prop in dagNodes) {
        if(dagNodes.hasOwnProperty(prop)) {
            let attrs = { label: dagNodes[prop].label, shape: dagNodes[prop].shape || 'rect' };
            
            g.setNode(prop, attrs);
            
            if(dagNodes[prop].hasOwnProperty('parent')) {
                g.setParent(prop, dagNodes[prop].parent);
            }
        }
    }
    
    for(prop in dagEdges) {
        if(dagEdges.hasOwnProperty(prop)) {
            var $edge = dagEdges[prop];
            g.setEdge($edge.from, $edge.to, { label: $edge.label || '', labelpos: 'c', curve: d3.curveBasis });
        }
    }
    
    $('<svg height=500><g/></svg>').attr('width', $graph.width()).appendTo($graph);
    
    var svg = d3.select('#{$div_uid} svg'),
        inner = svg.select('g');
    
    var zoom = d3.zoom()
        .clickDistance(4)
        .on('zoom', function() {
            inner.attr('transform', d3.event.transform);
        })
    ;
    svg.call(zoom);
    
    var render = new dagreD3.render();

    render(inner, g);
    
    inner.selectAll('g.node')
        .attr('data-id', function(v) { return v; } )
    ;
    
    var initialScale = 0.75;
    svg.call(zoom.transform, d3.zoomIdentity.translate((svg.attr("width") - g.graph().width * initialScale) / 2, 20).scale(initialScale));
    svg.attr('height', g.graph().height * initialScale + 40);
});
</script>