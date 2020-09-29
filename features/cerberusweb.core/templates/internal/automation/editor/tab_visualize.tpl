<div>
    <div class="cerb-code-editor-toolbar">
        <button type="button"><span class="glyphicons glyphicons-refresh"></span></button>
    </div>

    {$div_uid = uniqid('graph')}

    <div id="{$div_uid}" style="overflow:auto;"></div>
</div>

<script type="text/javascript">
Devblocks.loadResources({
    'js': [
        '/resource/devblocks.core/js/d3/d3.v5.min.js'
    ]
}, function() {
    var $graph = $('#{$div_uid}');
    var $popup = genericAjaxPopupFind($graph);

    var $panel = $graph.closest('.ui-tabs-panel');
    var $tabs = $panel.closest('.ui-tabs');
    var $toolbar = $panel.find('.cerb-code-editor-toolbar');

    $toolbar.find('button').on('click', function() {
        $tabs.tabs('load', $tabs.tabs('option','active'));
    });

    var data = {$ast_json nofilter};

    var node_width = 30;
    var node_height = 100;

    var root = d3.hierarchy(data);
    root.dx = node_width;
    root.dy = node_height;

    var layout = d3.tree()
        .nodeSize([root.dx,root.dy])
    ;

    layout(root);

    var x0 = Infinity;
    var x1 = -x0;

    root.each(function(d) {
        if(d.x > x1) x1 = d.x;
        if(d.x < x0) x0 = d.x;
    });

    var maxHeight = -Infinity;
    var minHeight = Infinity;
    var maxWidth = -Infinity;
    var minWidth = Infinity;

    var visitor = function(n) {
        maxWidth = Math.max(maxWidth, n.x);
        minWidth = Math.min(minWidth, n.x);
        maxHeight = Math.max(maxHeight, n.y);
        minHeight = Math.min(minHeight, n.y);

        if(n.children && n.children.length > 0) {
            n.children.forEach(function(c) {
                visitor(c);
            });
        }
    };

    visitor(root);

    var height = Math.abs(maxWidth) + Math.abs(minWidth) + node_width;
    var width = Math.abs(minHeight) + Math.abs(maxHeight) + node_height;

    var svg = d3.select('#{$div_uid}').append('svg')
        // .attr('viewBox', [0, 0, width, height])
        .attr('width', width + 20)
        .attr('height', height + 20)
    ;

    var defs = svg.append('defs');

    defs.append('marker')
        .attr('id', 'arrow')
        .attr('markerWidth', '10')
        .attr('markerHeight', '10')
        .attr('refX', '0')
        .attr('refY', '3')
        .attr('orient', 'auto')
        .attr('markerUnits', 'strokeWidth')
        .append('path')
            .attr('d', 'M0,0 L0,6 L5,3 z')
            .attr('fill', 'gray')
    ;

    var canvas = svg.append('g')
        .attr('transform', 'translate(50,' + (Math.abs(minWidth) + node_width/2 + 5) + ')')
    ;

    // [TODO] Arc
    canvas.append('g')
        .selectAll('line')
        .data(root.links())
        .enter()
        .append('line')
        .attr('stroke', 'lightgray')
        .attr('x1', function(d) { return d.source.y; })
        .attr('y1', function(d) { return d.source.x; })
        .attr('x2', function(d) { return d.target.y; })
        .attr('y2', function(d) { return d.target.x; })
        //.attr('marker-end', function(d) {
            //if(-1 !== $.inArray(d.target.data.type, ['return','error','yield'])) {
            //    return 'url(#arrow)';
            //}
        //})
    ;

    var nodes = canvas.append('g')
        .selectAll('circle')
        .data(root.descendants())
        .enter();

    var clicked = function(d) {
        $popup.trigger($.Event('cerb-automation-editor--goto', {
            editor_line: d.data.line
        }));
    };

    nodes
        // .filter(function(d) {
        //     return d.data.type !== 'yield' && d.data.type !== 'decision'
        // })
        .append('circle')
        //.classed('node', true)
        .attr('fill', function(d) {
            if(d.data.type === 'yield') {
                return 'lightblue';
            } else if(d.data.type === 'return') {
                return 'green';
            } else if (d.data.type === 'error') {
                return 'red';
            }

            return 'lightgray';
        })
        .attr('stroke', 'white')
        .attr('r', function(d) {
            return 3.5;
        })
        .attr('cx', function(d) { return d.y; })
        .attr('cy', function(d) { return d.x; })
        .style('cursor', 'pointer')
        .on('click', clicked)
    ;

    /*
    nodes
        .filter(function(d) {
            return d.data.type !== 'yield' && d.data.type !== 'decision'
        })
        .append('circle')
        //.classed('node', true)
        .attr('fill', function(d) {
            if(d.data.type === 'yield') {
                return 'lightblue';
            } else if(d.data.type === 'return') {
                return 'green';
            } else if (d.data.type === 'error') {
                return 'red';
            }

            return 'lightgray';
        })
        .attr('stroke', 'white')
        .attr('r', function(d) {
            return 5;
        })
        .attr('cx', function(d) { return d.y; })
        .attr('cy', function(d) { return d.x; })
        .style('cursor', 'pointer')
        .on('click', clicked)
    ;

    nodes
        .filter(function(d) {
            return d.data.type === 'yield'
        })
        .append('rect')
        //.classed('node', true)
        .attr('fill', function(d) {
            if(d.data.type === 'yield') {
                return 'lightblue';
            } else if(d.data.type === 'return') {
                return 'green';
            } else if (d.data.type === 'error') {
                return 'red';
            }

            return 'gray';
        })
        .attr('width', 10)
        .attr('height', 10)
        .attr('x', function(d) { return d.y-5; })
        .attr('y', function(d) { return d.x-5; })
        .style('cursor', 'pointer')
        .on('click', clicked)
    ;

    nodes
        .filter(function(d) {
            return d.data.type === 'decision'
        })
        .append('polyline')
        .attr('points', function(d) {
            return '' + ' ' + (d.y-8) + ',' + (d.x+6) + ' ' + (d.y) + ',' + (d.x-6) + ' ' + (d.y+8) + ',' + (d.x+6);
        })
        //.classed('node', true)
        //.attr('stroke', 'black')
        .attr('fill', 'black')
        // .attr('width', 10)
        // .attr('height', 10)
        // .attr('x', function(d) { return d.y-5; })
        // .attr('y', function(d) { return d.x-5; })
        .style('cursor', 'pointer')
        .on('click', clicked)
    ;
    */

    nodes.append('text')
        .attr('x', function(d) { return d.y; })
        .attr('y', function(d) { return d.x-10; })
        .attr('fill', 'black')
        //.attr('stroke', 'white')
        .attr('dominant-baseline', 'middle')
        .attr('text-anchor', 'middle')
        //.attr('text-length', '50px')
        .style('font-weight', 'bold')
        .style('cursor', 'pointer')
        .text(function(d) {
            if(d.data.name) {
                if (d.data.name.length > 15) {
                    return d.data.name.substr(0, 15) + '...';
                } else {
                    return d.data.name;
                }
            }
        })
        .on('click', clicked)
        .append('title')
        .text(function(d) {
            return d.data.path;
        })
    ;
});
</script>