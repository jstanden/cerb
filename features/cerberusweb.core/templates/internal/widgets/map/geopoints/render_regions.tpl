<div id="widget{$widget->id}">
    <div data-cerb-toolbar style="position:absolute;top:5px;right:5px;">
        <button type="button" data-cerb-button="reset"><span class="glyphicons glyphicons-restart"></span></button>
        <button type="button" data-cerb-button="zoom-in"><span class="glyphicons glyphicons-zoom-in"></span></button>
        <button type="button" data-cerb-button="zoom-out"><span class="glyphicons glyphicons-zoom-out"></span></button>
    </div>
    <div data-cerb-legend style="position:absolute;bottom:5px;left:5px;padding:2px;background-color:rgba(235,235,235,0.5);text-shadow:0 0 1.5px white;"></div>
    <div data-cerb-coordinates style="position:absolute;bottom:5px;right:5px;text-shadow:0 0 1.5px white;"></div>
</div>

<script type="text/javascript">
$(function() {
	Devblocks.loadScripts([
		'/resource/devblocks.core/js/d3/d3.v5.min.js',
		'/resource/devblocks.core/js/d3/topojson.v3.min.js'
	], function() {
		try {
            var jobs = [];
            var emptyPromise = function(resolve) { resolve(undefined); };
            var $widget = $('#widget{$widget->id}');
            var $map_toolbar = $widget.find('> [data-cerb-toolbar]');
            var $coords = $widget.find('[data-cerb-coordinates]');
            var $loading = $('<div/>').append(Devblocks.getSpinner());
            $widget.prepend($loading);
            
            {if $map.resource.name}
            jobs.push(d3.json('{devblocks_url}c=ui&a=resource&key={$map.resource.name}{/devblocks_url}?v={$map.resource.updated_at}'));
            {else}
            jobs.push(new Promise(emptyPromise));
            {/if}
            
            {if $map.regions.properties.resource.name}
            jobs.push(d3.json('{devblocks_url}c=ui&a=resource&key={$map.regions.properties.resource.name}{/devblocks_url}?v={$map.regions.properties.resource.updated_at}'));
            {else}
            jobs.push(new Promise(emptyPromise));
            {/if}

            {if $map.points.resource.name}
            jobs.push(d3.json('{devblocks_url}c=ui&a=resource&key={$map.points.resource.name}{/devblocks_url}?v={$map.points.resource.updated_at}'));
            {else}
            jobs.push(new Promise(emptyPromise));
            {/if}
            
            var width = 600,
                height = 325,
                projection,
                currentTransform,
                selectedRegion,
                selectedPoint,
                svg,
                g,
                label,
                zoom,
                region_label_property,
                point_label_property
            ;
            
            var points = {if $points_json}{$points_json nofilter}{else}{ 'type': 'FeatureCollection', 'features': [] }{/if};

            Promise.all(jobs).then(function(results) {
                $loading.remove();

                var widget = d3.select('#widget{$widget->id}')
                    .style('position', 'relative')
                    ;
                
                svg = widget.append('svg:svg')
                    .attr('viewBox', '0 0 ' + width + ' ' + height)
                ;
                
                {if 'albersUsa' == $map.projection.type}
                    projection = d3.geoAlbersUsa()
                        .scale({$map.projection.scale|escape:'javascript'})
                        .translate([width/2, height/2])
                        ;
                {elseif 'naturalEarth' == $map.projection.type}
                    projection = d3.geoNaturalEarth1()
                        .scale({$map.projection.scale|escape:'javascript'})
                        .translate([width/2, height/2])
                        .center([{$map.projection.center.longitude|escape:'javascript'}, {$map.projection.center.latitude|escape:'javascript'}])
                        ;
                {else}
                    projection = d3.geoMercator()
                        .scale({$map.projection.scale|escape:'javascript'})
                        .translate([width/2, height/2])
                        .center([{$map.projection.center.longitude|escape:'javascript'}, {$map.projection.center.latitude|escape:'javascript'}])
                        ;
                {/if}
                
                var path = d3.geoPath()
                    .projection(projection)
                    ;
    
                zoom = d3.zoom()
                    .scaleExtent([1, 40])
                    .clickDistance(10)
                    .on("zoom", function() {
                        // Resize all geometry
                        g.attr('transform', function() {
                            return d3.event.transform;
                        });
                        
                        g.selectAll('path.region')
                            .attr('stroke-width', function() {
                                return (0.2 / d3.event.transform.k) + 'px';
                            })
                        ;
    
                        // Scale down the POIs as we zoom in
                        g.selectAll('circle.point')
                            .attr('r', function() {
                                var point_radius = 2;
                                return point_radius / d3.event.transform.k;
                            })
                            .attr('stroke-width', function(d) {
                                var border_width = 0.5;
                                return border_width / d3.event.transform.k;
                            })
                        ;
                    })
                    .on("end", function() {
                        currentTransform = d3.event.transform;
                        
                        var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                        var screen_y = (height/2 + -currentTransform.y) /currentTransform.k;
                        
                        // [TODO] Store this
                        var long_lat = projection.invert([screen_x, screen_y]);
                        
                        $coords.text('Lat: ' + long_lat[1].toFixed(4) + ' Long: ' + long_lat[0].toFixed(4) + ' Scale: ' + (projection.scale() * currentTransform.k).toFixed(0))
                    })
                ;
    
                g = svg.append('g');
                
                svg
                    .style('pointer-events', 'none')
                    .call(zoom)
                    .on('dblclick.zoom', function() {
                        return false;
                    })
                ;
                
                {if $map.regions.label.property}
                region_label_property = "{$map.regions.label.property|escape:'javascript'}";
                {/if}
                
                {if $map.points.label.property}
                point_label_property = "{$map.points.label.property|escape:'javascript'}";
                {/if}
                
                var fill_color_key = null;
                var fill_color_map = { };
                
                {if 'color_map' == $map.regions.fill.mode && $map.regions.fill.params.property}
                    fill_color_key = "{$map.regions.fill.params.property|escape:'javascript'}";
                    {if $map.regions.fill.params.colors}
                    fill_color_map = {$map.regions.fill.params.colors|json_encode nofilter};
                    {/if}
                {elseif 'color' == $map.regions.fill.mode && $map.regions.fill.params.property}
                    fill_color_key = "{$map.regions.fill.params.property|escape:'javascript'}";
                {elseif 'choropleth' == $map.regions.fill.mode && $map.regions.fill.params.property}
                    var domain = [];
                    fill_color_key = "{$map.regions.fill.params.property|escape:'javascript'}";;
                {/if}
                
                label = widget.append('div')
                    .style('font-weight', 'bold')
                    .style('margin', '5px')
                    .style('padding', '5px')
                    .style('position', 'absolute')
                    .style('top', '0')
                    .style('left', '0')
                    .style('background-color', 'rgba(220,220,220,0.8)')
                    .style('display', 'none')
                    ;
                
                var map_regions = g.append('g')
                .selectAll('.region')
                    .data(
                        function() {
                            if('object' !== typeof results[0]) {
                                svg.remove();
                                widget.append('div').text('Failed to load map resource.');
                                $map_toolbar.remove();
                                return [];
                            }
                            
                            var regions = results[0];
                            var json_type = regions.type;
                            
                            if(json_type === 'Topology') {
                                var first_key = Object.keys(regions.objects)[0];
                                
                                if(!first_key)
                                    return [];
                                
                                return topojson.feature(regions, regions.objects[first_key]).features;
                            } else {
                                return regions.features;
                            }
                        },
                        function(d) {
                            {if $map.regions.properties.join}
                                var regions_data = results[1];
                                var k = {$map.regions.properties.join.property|json_encode nofilter};
                                
                                if(typeof k !== 'string' || !d.properties.hasOwnProperty(k))
                                    return d;
                                
                                var join_property = d.properties[k];
                                
                                {if 'upper' == $map.regions.properties.join.case}
                                join_property = join_property.toUpperCase();
                                {elseif 'lower' == $map.regions.properties.join.case}
                                join_property = join_property.toLowerCase();
                                {/if}
                                
                                if(regions_data.hasOwnProperty(join_property) && regions_data[join_property].hasOwnProperty('properties')) {
                                    d.properties = Object.assign(d.properties, regions_data[join_property].properties);
                                }
                            {/if}
                            
                            return d;
                        }
                    )
                    .enter()
                    {if $map.regions.filter}
                        .filter(function(d) {
                            var k = {$map.regions.filter.params.property|json_encode nofilter};
                            var v = {$map.regions.filter.params.value|json_encode nofilter};
                            var not = {if 'not' == $map.regions.filter.mode}true{else}false{/if};

                            if(typeof k != 'string')
                                return false;

                            if(!d.properties.hasOwnProperty(k))
                                return true === not;

                            if(typeof v == 'object') {
                                return ($.inArray(d.properties[k], v) === -1) === not;
                            } else if(typeof v == 'string') {
                                return (d.properties[k] !== v) === not;
                            }

                            return false;
                        })
                    {/if}
                    {if 'choropleth' == $map.regions.fill.mode}
                        .filter(function(d) {
                            if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                                domain.push(parseFloat(d.properties[fill_color_key]));
                            }
                            
                            return true;
                        })
                    {/if}
                ;

                // Draw the scale legend
                {if 'choropleth' == $map.regions.fill.mode && $map.resource.name}
                var fill_color_from = '{if $map.regions.fill.params.colors.0}{$map.regions.fill.params.colors.0}{else}#f4e153{/if}';
                var fill_color_to = '{if $map.regions.fill.params.colors.1}{$map.regions.fill.params.colors.1}{else}#362142{/if}';
                var fill_classes = '{if $map.regions.fill.params.classes}{$map.regions.fill.params.classes}{else}5{/if}';

                var fill_extents = d3.extent(domain);
                var fill_range = d3.interpolateRound(fill_extents[0],fill_extents[1]);
                var fill_legend = d3.quantize(fill_range, fill_classes);

                var colors = d3.scaleQuantize()
                    .domain(fill_extents)
                    .range(d3.quantize(d3.interpolateHcl(fill_color_from,fill_color_to), fill_classes))
                ;

                // [TODO] This should be optional (some properties are in millions, etc)
                var fill_format = function(n) {
                    if(n > 1000000000000) {
                        return (n / 1000000000000).toFixed(1) + 'T';
                    } else if(n > 1000000000) {
                        return (n / 1000000000).toFixed(1) + 'B';
                    } else if(n > 1000000) {
                        return (n / 1000000).toFixed(1) + 'M';
                    } else if(n > 1000) {
                        return (n / 1000).toFixed(1) + 'K';
                    } else {
                        return n;
                    }
                }
                
                var legend = widget.select('[data-cerb-legend]');
                
                legend
                    .append('div')
                        .style('display', 'inline-block')
                        .style('margin-right', '5px')
                        .text(fill_format(fill_extents[0]))
                    ;
                
                legend
                    .selectAll('div.swatch')
                    .data(fill_legend)
                    .enter()
                    .append('div')
                        .classed('swatch', true)
                        .style('display', 'inline-block')
                        .style('width', '10px')
                        .style('height', '10px')
                        .style('background-color', function(d) {
                            return colors(d);
                        })
                        .attr('title', function(d, i) {
                            var spread = Math.abs(fill_extents[0]) + Math.abs(fill_extents[1]);
                            var spread_step = spread / fill_classes;

                            var from = Math.ceil(fill_extents[0] + (i * spread_step));
                            var to = Math.ceil(fill_extents[0] + ((i+1) * spread_step))-1;

                            return fill_format(from) + ' - ' + fill_format(to);
                        })
                    ;
                
                legend
                    .append('div')
                        .style('display', 'inline-block')
                        .style('padding-left', '5px')
                        .text(fill_format(fill_extents[1]))
                ;
                {/if}
                
                map_regions
                    .append('path')
                    .attr('d', path)
                    .attr('class', 'region')
                    .attr('stroke', '#fff')
                    .attr('stroke-width', '0.2px')
                    .attr('stroke-linejoin', 'round')
                    .attr('stroke-linecap', 'round')
                    {if 'color' == $map.regions.fill.mode}
                    .attr('fill', function(d) {
                        if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                            return d.properties[fill_color_key];
                        }
                        
                        return '#aaa';
                    })
                    {elseif 'color_map' == $map.regions.fill.mode}
                    .attr('fill', function(d) {
                        if(fill_color_key && fill_color_map && d.properties.hasOwnProperty(fill_color_key)) {
                            var v = d.properties[fill_color_key];
                            
                            if(fill_color_map.hasOwnProperty(v))
                                return fill_color_map[v];
                        }
                        
                        return '#aaa';
                    })
                    {elseif 'choropleth' == $map.regions.fill.mode}
                    .attr('fill', function(d) {
                        if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                            var v = parseFloat(d.properties[fill_color_key]);
                            return colors(v);
                        }
                        
                        return '#aaa';
                    })
                    {else}
                    .attr('fill', '#aaa')
                    {/if}
                    .style('pointer-events', 'all')
                    .on('click.zoom', clickedRegion)
                ;

                if(points || 'object' === typeof results[2]) {
                    // [TODO] We need to handle merging GeoJSON + TopoJSON
                    // [TODO] Or just drop TopoJSON 
                    if('object' === typeof results[2] 
                        && results[2].hasOwnProperty('features')
                        && points.hasOwnProperty('features')) {
                        points.features = points.features.concat(results[2].features);
                    }
                    
                    g.append('g')
                        .selectAll('.point')
                        .data(function() {
                            var json_type = points.type;

                            if(json_type === 'Topology') {
                                var first_key = Object.keys(points.objects)[0];

                                if(!first_key)
                                    return [];

                                return topojson.feature(points, points.objects[first_key]).features;
                            } else { // [TODO] FeatureCollection
                                return points.features;
                            }
                        })
                        .enter()
                        {if $map.points.filter}
                        .filter(function(d) {
                            var k = {$map.points.filter.params.property|json_encode nofilter};
                            var v = {$map.points.filter.params.value|json_encode nofilter};
                            var not = {if 'not' == $map.points.filter.mode}true{else}false{/if};

                            if(typeof k != 'string')
                                return false;

                            if(!d.properties.hasOwnProperty(k))
                                return true === not;

                            if(typeof v == 'object') {
                                return ($.inArray(d.properties[k], v) === -1) === not;
                            } else if(typeof v == 'string') {
                                return (d.properties[k] !== v) === not;
                            }

                            return false;
                        })
                        {/if}
                        .append('circle')
                        .attr('r', '2')
                        .attr('fill', 'rgb(100,100,100)')
                        .attr('stroke-width', '0.5')
                        .attr('stroke', 'white')
                        .attr('transform', function (d) {
                            return 'translate(' + projection([
                                d.geometry.coordinates[0],
                                d.geometry.coordinates[1]
                            ]) + ')';
                        })
                        .attr('class', 'point')
                        .style('cursor', 'pointer')
                        .style('pointer-events', 'all')
                        .on('click.zoom', clickedPOI)
                    ;
                }
                
                currentTransform = d3.zoomIdentity;
            });
    
            // Button reset
            $map_toolbar.find('[data-cerb-button=reset]')
                .on('click', function() {
                    svg.transition().duration(1000).call(
                        zoom.transform,
                        d3.zoomIdentity
                    );

                    selectedPoint = null;
                    selectedRegion = null;

                    focusRegion(selectedRegion);
                    focusPoint(selectedPoint);
                    
                    $(label.node()).empty().hide();
                });

            // Button zoom in
            $map_toolbar.find('[data-cerb-button="zoom-in"]')
                .on('click', function() {
                    var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                    var screen_y = (height/2 + -currentTransform.y) / currentTransform.k;
                    var long_lat = projection.invert([screen_x, screen_y]);
                    
                    var new_scale = Math.min(40, currentTransform.k * 1.5);
                    
                    var new_point = 
                        {if 'albersUsa' == $map.projection.type}
                            d3.geoAlbersUsa()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                (long_lat)
                        {elseif 'naturalEarth' == $map.projection.type}
                            d3.geoNaturalEarth1()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                .center(projection.center())
                                (long_lat)
                        {else}
                            d3.geoMercator()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                .center(projection.center())
                                (long_lat)
                        {/if}
                        ;
                    
                    var t = d3.zoomIdentity.translate(width/2 -new_point[0], height/2 -new_point[1]).scale(new_scale);
                    svg.call(zoom.transform, t);
                });

            // Button zoom out
            $map_toolbar.find('[data-cerb-button="zoom-out"]')
                .on('click', function() {
                    var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                    var screen_y = (height/2 + -currentTransform.y) /currentTransform.k;
                    var long_lat = projection.invert([screen_x, screen_y]);

                    var new_scale = Math.max(1, currentTransform.k * 1/1.5);

                    var new_point =
                        {if 'albersUsa' == $map.projection.type}
                            d3.geoAlbersUsa()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                (long_lat)
                        {elseif 'naturalEarth' == $map.projection.type}
                            d3.geoNaturalEarth1()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                .center(projection.center())
                                (long_lat)
                        {else}
                            d3.geoMercator()
                                .scale(projection.scale() * new_scale)
                                .translate([width/2 * new_scale, height/2 * new_scale])
                                .center(projection.center())
                                (long_lat)
                        {/if}
                        ;

                    var t = d3.zoomIdentity.translate(width/2 -new_point[0], height/2 -new_point[1]).scale(new_scale);
                    svg.call(zoom.transform, t);
                });

            // [TODO] Button current coordinates (target, riflescope)

            function clickedPOI(d) {
                if(d && selectedPoint !== d) {
                    selectedPoint = d;
                    
                    {if is_a($widget, 'Model_ProfileWidget')}
                        {if $widget->extension_params.automation.map_clicked}
                        var formData = new FormData();
                        formData.set('c', 'profiles');
                        formData.set('a', 'invokeWidget');
                        formData.set('widget_id', '{$widget->id}');
                        formData.set('action', 'mapClicked');
    
                        Devblocks.objectToFormData(
                            {
                                feature_type: 'point',
                                feature_properties: d.properties
                            },
                            formData
                        );
    
                        genericAjaxPost(formData, null, null, function(json) {
                            if('object' != typeof json)
                                return;
                            
                            if(json.hasOwnProperty('error')) {
                                Devblocks.clearAlerts();
                                Devblocks.createAlertError(json.error);
                                
                            } else if(json.hasOwnProperty('sheet')) {
                                $(label.node()).html(json.sheet).show();
    
                            } else {
                                if(point_label_property && d.properties.hasOwnProperty(point_label_property)) {
                                    var label_text = d.properties[point_label_property];
                                    $(label.node()).text(label_text).show();
                                }
                            }
                        });
                        {else}
                            if(point_label_property && d.properties.hasOwnProperty(point_label_property)) {
                                label.text(d.properties[point_label_property]);
                                label.style('display', 'inline-block');
                            }
                        {/if}

                    {elseif is_a($widget, 'Model_WorkspaceWidget')}
                        {if $widget->params.automation.map_clicked}
                        var formData = new FormData();
                        formData.set('c', 'pages');
                        formData.set('a', 'invokeWidget');
                        formData.set('widget_id', '{$widget->id}');
                        formData.set('action', 'mapClicked');
    
                        Devblocks.objectToFormData(
                            {
                                feature_type: 'point',
                                feature_properties: d.properties
                            },
                            formData
                        );
    
                        genericAjaxPost(formData, null, null, function(json) {
                            if('object' != typeof json)
                                return;

                            if(json.hasOwnProperty('error')) {
                                Devblocks.clearAlerts();
                                Devblocks.createAlertError(json.error);
                                
                            } else if(json.hasOwnProperty('sheet')) {
                                $(label.node()).html(json.sheet).show();
    
                            } else {
                                if(point_label_property && d.properties.hasOwnProperty(point_label_property)) {
                                    var label_text = d.properties[point_label_property];
                                    $(label.node()).text(label_text).show();
                                }
                            }
                        });
                        {else}
                            if(point_label_property && d.properties.hasOwnProperty(point_label_property)) {
                                label.text(d.properties[point_label_property]);
                                label.style('display', 'inline-block');
                            } else if(d.properties.hasOwnProperty('name')) {
                                label.text(d.properties.name);
                                label.style('display', 'inline-block');
                            }
                        {/if}
                    {/if}
                    
                } else {
                    selectedPoint = null;
                    label.text('');
                    label.style('display', 'none');
                }
                
                selectedRegion = null;
                
                focusRegion(selectedPoint); // This is intentionally not null
                focusPoint(selectedPoint);
            }
            
            function focusRegion(selectedRegion) {
                g.selectAll('path')
                    .each(function(d) {
                        var selected = d === selectedRegion;

                        if(null == selectedRegion || selected) {
                            d3.select(this)
                                .style('fill', null)
                            ;
                        } else {
                            d3.select(this)
                                .style('fill', 'rgb(230,230,230)')
                            ;
                        }
                        return selected;
                    })
                ;
            }
            
            function focusPoint(selectedPoint) {
                g.selectAll('circle')
                    .each(function(d) {
                        var selected = d === selectedPoint;

                        if(selected) {
                            d3.select(this)
                                .style('fill', 'red')
                            ;
                        } else {
                            d3.select(this)
                                .style('fill', null)
                            ;
                        }
                        return selected;
                    })
                ;
            }
            
            function clickedRegion(d) {
                if(d && selectedRegion !== d) {
                    selectedRegion = d;

                    {if is_a($widget, 'Model_ProfileWidget')}
                        {if $widget->extension_params.automation.map_clicked}
                        var formData = new FormData();
                        formData.set('c', 'profiles');
                        formData.set('a', 'invokeWidget');
                        formData.set('widget_id', '{$widget->id}');
                        formData.set('action', 'mapClicked');
    
                        Devblocks.objectToFormData(
                            {
                                feature_type: 'region',
                                feature_properties: d.properties
                            },
                            formData
                        );
    
                        genericAjaxPost(formData, null, null, function(json) {
                            if('object' != typeof json)
                                return;

                            if(json.hasOwnProperty('error')) {
                                Devblocks.clearAlerts();
                                Devblocks.createAlertError(json.error);

                            } else if(json.hasOwnProperty('sheet')) {
                                $(label.node()).html(json.sheet).show();
    
                            } else {
                                if(region_label_property && d.properties.hasOwnProperty(region_label_property)) {
                                    var label_text = d.properties[region_label_property];
                                    $(label.node()).text(label_text).show();
                                }
                            }
                        });
                        {else}
                            if(region_label_property && d.properties.hasOwnProperty(region_label_property)) {
                                label.text(d.properties[region_label_property]);
                                label.style('display', 'inline-block');
                            }
                        {/if}

                    {elseif is_a($widget, 'Model_WorkspaceWidget')}
                        {if $widget->params.automation.map_clicked}
                        var formData = new FormData();
                        formData.set('c', 'pages');
                        formData.set('a', 'invokeWidget');
                        formData.set('widget_id', '{$widget->id}');
                        formData.set('action', 'mapClicked');
    
                        Devblocks.objectToFormData(
                            {
                                feature_type: 'region',
                                feature_properties: d.properties
                            },
                            formData
                        );
    
                        genericAjaxPost(formData, null, null, function(json) {
                            if('object' != typeof json)
                                return;

                            if(json.hasOwnProperty('error')) {
                                Devblocks.clearAlerts();
                                Devblocks.createAlertError(json.error);

                            } else if(json.hasOwnProperty('sheet')) {
                                $(label.node()).html(json.sheet).show();
    
                            } else {
                                if(region_label_property && d.properties.hasOwnProperty(region_label_property)) {
                                    var label_text = d.properties[region_label_property];
                                    $(label.node()).text(label_text).show();
                                }
                            }
                        });
                        {else}
                            if(region_label_property && d.properties.hasOwnProperty(region_label_property)) {
                                label.text(d.properties[region_label_property]);
                                label.style('display', 'inline-block');
                            } else if(d.properties.hasOwnProperty('name')) {
                                label.text(d.properties.name);
                                label.style('display', 'inline-block');
                            }
                        {/if}
                    {/if}
                    
                } else {
                    selectedRegion = null;
                    label.text('');
                    label.style('display', 'none');
                }
                
                selectedPoint = null;
                
                focusRegion(selectedRegion);
                focusPoint(selectedPoint);
            }
            
		} catch(e) {
			console.error(e);
		}
	});
});
</script>