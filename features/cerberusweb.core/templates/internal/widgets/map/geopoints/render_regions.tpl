{$map_divid = uniqid('map_')}

<div id="{$map_divid}">
    <div data-cerb-label style="font-weight:bold;margin:5px;padding:5px;position:absolute;top:0;left:0;background-color:var(--cerb-callout-background);border: 1px solid var(--cerb-color-background-contrast-160);z-index:2;display:none;">
        <div data-cerb-label--close style="position:absolute;top:-7px;right:-8px;cursor:pointer;">
            <span class="glyphicons glyphicons-remove-2" style="font-size:16px;"></span>
        </div>
        <div data-cerb-label--contents style="max-height:25.5em;max-width:25.5em;overflow:auto;"></div>
    </div>

    <div data-cerb-toolbar style="position:absolute;top:0;right:0;">
        <button type="button" data-cerb-button="reset"><span class="glyphicons glyphicons-restart"></span></button>
        <button type="button" data-cerb-button="zoom-in"><span class="glyphicons glyphicons-zoom-in"></span></button>
        <button type="button" data-cerb-button="zoom-out"><span class="glyphicons glyphicons-zoom-out"></span></button>
    </div>
    <div data-cerb-legend style="display:none;position:absolute;bottom:5px;left:5px;padding:2px;background-color:var(--cerb-callout-background);text-shadow:0 0 1.5px white;"></div>
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
            var $widget = $('#{$map_divid}');
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
                label_contents,
                zoom
            ;
            
            var points = {if $points_json}{$points_json nofilter}{else}{ 'type': 'FeatureCollection', 'features': [] }{/if};
            var region_properties = {if $region_properties_json}{$region_properties_json nofilter}{else}{ }{/if}

            Promise.all(jobs).then(function(results) {
                $loading.remove();

                var widget = d3.select('#{$map_divid}')
                    .style('position', 'relative')
                    ;

                label = widget.select('[data-cerb-label]');
                label_contents = widget.select('[data-cerb-label--contents]');
                
                widget.select('[data-cerb-label--close]')
                    .on('click', function() {
                        label.style('display', 'none');
                        label_contents.text('');

                        selectedPoint = null;
                        selectedRegion = null;

                        focusRegion(selectedRegion);
                        focusPoint(selectedPoint);
                    })
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
                            .style('r', function() {
                                var point_radius = d3.select(this).attr('r');
                                return point_radius / d3.event.transform.k + 'px';
                            })
                            .attr('stroke-width', function() {
                                var stroke_width = d3.select(this).attr('r') * 0.2;
                                return stroke_width / d3.event.transform.k + 'px';
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
                
                var fill_color_key = null;
                var fill_color_map = { };
                
                {if $map.regions.fill.choropleth && $map.regions.fill.choropleth.property}
                    var domain = [];
                    fill_color_key = "{$map.regions.fill.choropleth.property|escape:'javascript'}";;
                {elseif $map.regions.fill.color_map && $map.regions.fill.color_map.property}
                    fill_color_key = "{$map.regions.fill.color_map.property|escape:'javascript'}";
                    {if $map.regions.fill.color_map.colors}
                    fill_color_map = {$map.regions.fill.color_map.colors|json_encode nofilter};
                    {/if}
                {elseif $map.regions.fill.color_key && $map.regions.fill.color_key.property}
                    fill_color_key = "{$map.regions.fill.color_key.property|escape:'javascript'}";
                {/if}
                
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
                            {if $map.regions.properties.join.property}
                                var k = {$map.regions.properties.join.property|json_encode nofilter};
                                
                                if(typeof k !== 'string' || !d.properties.hasOwnProperty(k))
                                    return d;
                                
                                var join_property = d.properties[k];
                                
                                {if 'upper' == $map.regions.properties.join.case}
                                join_property = join_property.toUpperCase();
                                {elseif 'lower' == $map.regions.properties.join.case}
                                join_property = join_property.toLowerCase();
                                {/if}
                            
                                if(results[1]) {
                                    if (results[1].hasOwnProperty(join_property)) {
                                        d.properties = Object.assign(d.properties, results[1][join_property]);
                                    }
                                }
                            
                                if(region_properties.hasOwnProperty(join_property)) {
                                    d.properties = Object.assign(d.properties, region_properties[join_property]);
                                }
                            {/if}
                            
                            return d;
                        }
                    )
                    .enter()
                    {if $map.regions.filter && $map.regions.filter.property}
                        .filter(function(d) {
                            var k = {$map.regions.filter.property|json_encode nofilter},
                                v,
                                not = false
                            ;
                            
                            {if !is_null($map.regions.filter.not)}
                                v = {$map.regions.filter.not|json_encode nofilter};
                                not = true;
                            {elseif !is_null($map.regions.filter.is)}
                                v = {$map.regions.filter.is|json_encode nofilter};
                            {else}
                                return false;
                            {/if}

                            if(typeof k != 'string')
                                return false;

                            if(!d.properties.hasOwnProperty(k))
                                return true === not;
                            
                            if(typeof v == 'number')
                                v = v.toString();

                            if(typeof v == 'object' && typeof v.filter == 'function') {
                                var hits = v.filter(function(vv) { if (vv == d.properties[k] ) return true; }).length;
                                return (0 === hits) === not;
                            } else if(typeof v == 'string') {
                                return (d.properties[k] != v) === not;
                            }

                            return false;
                        })
                    {/if}
                    {if $map.regions.fill.choropleth}
                        .filter(function(d) {
                            if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                                domain.push(parseFloat(d.properties[fill_color_key]));
                            }
                            
                            return true;
                        })
                    {/if}
                ;

                // Draw the scale legend
                {if $map.regions.fill.choropleth && $map.resource.name}
                var fill_color_from = '{if $map.regions.fill.choropleth.colors.0}{$map.regions.fill.choropleth.colors.0}{else}#f4e153{/if}';
                var fill_color_to = '{if $map.regions.fill.choropleth.colors.1}{$map.regions.fill.choropleth.colors.1}{else}#362142{/if}';
                var fill_classes = '{if $map.regions.fill.choropleth.classes}{$map.regions.fill.choropleth.classes}{else}5{/if}';

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
                    .style('display', 'block')
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
                    .attr('stroke', 'var(--cerb-color-background)')
                    .attr('stroke-width', '0.2px')
                    .attr('stroke-linejoin', 'round')
                    .attr('stroke-linecap', 'round')
                    {if $map.regions.fill.choropleth}
                    .attr('fill', function(d) {
                        if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                            var v = parseFloat(d.properties[fill_color_key]);
                            return colors(v);
                        }
                        
                        return 'var(--cerb-color-background-contrast-170)';
                    })
                    {elseif $map.regions.fill.color_key}
                    .attr('fill', function(d) {
                        if(fill_color_key && d.properties.hasOwnProperty(fill_color_key)) {
                            return d.properties[fill_color_key];
                        }

                        return 'var(--cerb-color-background-contrast-170)';
                    })
                    {elseif $map.regions.fill.color_map}
                    .attr('fill', function(d) {
                        if(fill_color_key && fill_color_map && d.properties.hasOwnProperty(fill_color_key)) {
                            var v = d.properties[fill_color_key];
                            
                            if(fill_color_map.hasOwnProperty(v))
                                return fill_color_map[v];
                        }

                        return 'var(--cerb-color-background-contrast-170)';
                    })
                    {else}
                    .attr('fill', 'var(--cerb-color-background-contrast-170)')
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
                            var k = {$map.points.filter.property|json_encode nofilter},
                                v,
                                not = false
                            ;

                            {if !is_null($map.points.filter.not)}
                            v = {$map.points.filter.not|json_encode nofilter};
                            not = true;
                            {elseif !is_null($map.points.filter.is)}
                            v = {$map.points.filter.is|json_encode nofilter};
                            {else}
                            return false;
                            {/if}

                            if(typeof k != 'string')
                                return false;

                            if(!d.properties.hasOwnProperty(k))
                                return true === not;

                            if(typeof v == 'number')
                                v = v.toString();
                            
                            if(typeof v == 'object' && typeof v.filter == 'function') {
                                var hits = v.filter(function(vv) { if (vv == d.properties[k] ) return true; }).length;
                                return (0 === hits) === not;
                            } else if(typeof v == 'string') {
                                return (d.properties[k] != v) === not;
                            }

                            return false;
                        })
                        {/if}
                        .append('circle')
                        {if $map.points.size.value_map}
                        .attr('r', function(d) {
                            var k = {$map.points.size.value_map.property|json_encode nofilter};
                            var v_default = {$map.points.size.default|floatval} || 2.0;
                            var v_map = {$map.points.size.value_map.values|json_encode nofilter} || [];
                            
                            if(!k || !d.properties.hasOwnProperty(k))
                                return v_default;
                            
                            var v = d.properties[k];

                            if('object' != typeof v_map || !v_map.hasOwnProperty(v) || isNaN(parseFloat(v_map[v])))
                                return v_default;
                            
                            return parseFloat(v_map[v]);
                        })
                        .attr('stroke-width', function(d) {
                            var k = {$map.points.size.value_map.property|json_encode nofilter};
                            var v_default = {$map.points.size.default|floatval} || 2.0;
                            var v_map = {$map.points.size.value_map.values|json_encode nofilter} || [];
                            
                            if(!k || !d.properties.hasOwnProperty(k))
                                return v_default * 0.2;
                            
                            var v = d.properties[k];
                            
                            if('object' != typeof v_map || !v_map.hasOwnProperty(v) || isNaN(parseFloat(v_map[v])))
                                return v_default * 0.2;
                            
                            return parseFloat(v_map[v]) * 0.2;
                        })
                        {elseif $map.points.size.default}
                        .attr('r', function(d) {
                            var v_default = {$map.points.size.default|floatval} || 1.5;
                            return v_default;
                        })
                        .attr('stroke-width', function(d) {
                            var v_default = {$map.points.size.default|floatval} || 1.5;
                            return v_default * 0.2;
                        })
                        {else}
                        .attr('r', '2')
                        .attr('stroke-width', '0.4')
                        {/if}
                        {if $map.points.fill.color_map}
                        .attr('fill', function(d) {
                            var k = {$map.points.fill.color_map.property|json_encode nofilter};
                            var v_default = {$map.points.fill.default|json_encode nofilter} || 'var(--cerb-color-background-contrast-50)';
                            var v_map = {$map.points.fill.color_map.colors|json_encode nofilter} || [];

                            if(!k || !d.properties.hasOwnProperty(k))
                                return v_default;

                            var v = d.properties[k];
                            
                            if('object' != typeof v_map || !v_map.hasOwnProperty(v))
                                return v_default;
                            
                            return v_map[v];
                        })
                        {elseif $map.points.fill.default}
                        .attr('fill', function(d) {
                            var v_default = {$map.points.fill.default|json_encode nofilter} || 'var(--cerb-color-background-contrast-100)';
                            return v_default;
                        })
                        {else}
                        .attr('fill', 'var(--cerb-color-background-contrast-100)')
                        {/if}
                        .attr('stroke', 'white')
                        .attr('transform', function (d) {
                            if(d.hasOwnProperty('geometry') && d.geometry.hasOwnProperty('coordinates')) {
                                return 'translate(' + projection([
                                    d.geometry.coordinates[0],
                                    d.geometry.coordinates[1]
                                ]) + ')';
                            }
                            return null;
                        })
                        .attr('class', 'point')
                        .style('cursor', 'pointer')
                        .style('pointer-events', 'all')
                        .on('click.zoom', clickedPOI)
                    ;
                }
                
                currentTransform = d3.zoomIdentity;
                
                {if $map.projection.zoom}
                    var zoom_long_lat = {if $map.projection.zoom.latitude && $map.projection.zoom.longitude}[{$map.projection.zoom.longitude|floatval}, {$map.projection.zoom.latitude|floatval}]{else}null{/if};
                    var zoom_scale = {if $map.projection.zoom.scale}{$map.projection.zoom.scale|floatval}{else}null{/if};
                    
                    zoomToLongLat(zoom_long_lat, zoom_scale);                
                {/if}
            }, function(e) {
                $loading.remove();
                if(console && console.error)
                    console.error(e.message);
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

                    label.style('display', 'none');
                    label_contents.text('');
                })
            ;

            // Button zoom in
            $map_toolbar.find('[data-cerb-button="zoom-in"]')
                .on('click', function() {
                    var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                    var screen_y = (height/2 + -currentTransform.y) / currentTransform.k;
                    var long_lat = projection.invert([screen_x, screen_y]);

                    var new_scale = currentTransform.k * 1.5;
                    
                    zoomToLongLat(long_lat, new_scale);
                })
            ;

            // Button zoom out
            $map_toolbar.find('[data-cerb-button="zoom-out"]')
                .on('click', function() {
                    var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                    var screen_y = (height/2 + -currentTransform.y) /currentTransform.k;
                    var long_lat = projection.invert([screen_x, screen_y]);

                    var new_scale = currentTransform.k * 1/1.5;
                    
                    zoomToLongLat(long_lat, new_scale);
                })
            ;
            
            function zoomToLongLat(long_lat, new_scale) {
                if(null == long_lat) {
                    var screen_x = (width/2 + -currentTransform.x) / currentTransform.k;
                    var screen_y = (height/2 + -currentTransform.y) /currentTransform.k;
                    long_lat = projection.invert([screen_x, screen_y]);
                }
                
                if(null == new_scale) {
                    new_scale = currentTransform.k;
                }
                
                new_scale = Math.max(Math.min(40, new_scale), 1);
                
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
            }

            // [TODO] Button current coordinates (target, riflescope)

            function clickedPOI(d) {
                if(d && selectedPoint !== d) {
                    selectedPoint = d;
                    
                    {if $widget && is_a($widget, 'Model_ProfileWidget')}
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
                                label.style('display', 'inline-block');
                                label_contents.html(json.sheet);
    
                            } else {
                                var keys = {if $map.points.label.properties}{$map.points.label.properties|json_encode nofilter}{else}undefined{/if};
                                var title = {$map.points.label.title|json_encode nofilter};
                                setLabelToProperties(d, keys, title);
                            }
                        });
                        {else}
                            var keys = {if $map.points.label.properties}{$map.points.label.properties|json_encode nofilter}{else}undefined{/if};
                            var title = {$map.points.label.title|json_encode nofilter};
                            setLabelToProperties(d, keys, title);
                        {/if}

                    {elseif $widget && is_a($widget, 'Model_WorkspaceWidget')}
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
                                label.style('display', 'inline-block');
                                label_contents.html(json.sheet);
    
                            } else {
                                var keys = {if $map.points.label.properties}{$map.points.label.properties|json_encode nofilter}{else}undefined{/if};
                                var title = {$map.points.label.title|json_encode nofilter};
                                setLabelToProperties(d, keys, title);
                            }
                        });
                        {else}
                            var keys = {if $map.points.label.properties}{$map.points.label.properties|json_encode nofilter}{else}undefined{/if};
                            var title = {$map.points.label.title|json_encode nofilter};
                            setLabelToProperties(d, keys, title);
                        {/if}
                    {else}
                        var keys = {if $map.points.label.properties}{$map.points.label.properties|json_encode nofilter}{else}undefined{/if};
                        var title = {$map.points.label.title|json_encode nofilter};
                        setLabelToProperties(d, keys, title);
                    {/if}
                    
                } else {
                    selectedPoint = null;
                    label.style('display', 'none');
                    label_contents.text('');
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
                                .style('fill', 'var(--cerb-color-background-contrast-230)')
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

                        if(null == selectedPoint || selected) {
                            d3.select(this)
                                .style('fill', null)
                            ;
                        } else {
                            d3.select(this)
                                .style('fill', 'var(--cerb-color-background-contrast-50)')
                            ;
                        }
                        return selected;
                    })
                ;
            }
            
            function setLabelToProperties(d, property_meta, title) {
                if(!property_meta) {
                    property_meta = { };
                    Object.keys(d.properties).forEach(function(k) {
                        property_meta[k] = { };
                    });
                }
                
                label_contents.text('');
                
                if(!title)
                    title = 'name';
                
                if(d.properties.hasOwnProperty(title))
                    label_contents.append('h1').style('color', 'inherit').style('margin','0').text(d.properties[title]);
                
                label_contents
                    .append('table')
                    .selectAll('tbody')
                    .data(Object.keys(property_meta))
                    .enter()
                    .append('tbody')
                    .append('tr')
                    .append('td')
                    .style('text-align', 'right')
                    .style('font-weight', 'normal')
                    .text(function(k) {
                        if(property_meta[k].hasOwnProperty('label')) {
                            return property_meta[k].label + ': ';
                        }
                        
                        return k + ': ';
                    })
                    .select(function() {
                        return this.parentNode;
                    })
                    .append('td')
                    .text(function(k) {
                        if(d.properties.hasOwnProperty(k)) {
                            if(property_meta[k].hasOwnProperty('format')) {
                                switch(property_meta[k].format) {
                                    case 'number':
                                        return d3.format(",")(d.properties[k]);
                                }
                            }
                            return d.properties[k];
                        }
                        return '';
                    })
                ;

                label.style('display', 'inline-block');
            }
            
            function clickedRegion(d) {
                if(d && selectedRegion !== d) {
                    selectedRegion = d;

                    {if $widget && is_a($widget, 'Model_ProfileWidget')}
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
                                label.style('display', 'inline-block');
                                label_contents.html(json.sheet);
    
                            } else {
                                var keys = {if $map.regions.label.properties}{$map.regions.label.properties|json_encode nofilter}{else}undefined{/if};
                                var title = {$map.regions.label.title|json_encode nofilter};
                                setLabelToProperties(d, keys, title);
                            }
                        });
                        {else}
                            var keys = {if $map.regions.label.properties}{$map.regions.label.properties|json_encode nofilter}{else}undefined{/if};
                            var title = {$map.regions.label.title|json_encode nofilter};
                            setLabelToProperties(d, keys, title);
                        {/if}

                    {elseif $widget && is_a($widget, 'Model_WorkspaceWidget')}
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
                                label.style('display', 'inline-block');
                                label_contents.html(json.sheet);
    
                            } else {
                                var keys = {if $map.regions.label.properties}{$map.regions.label.properties|json_encode nofilter}{else}undefined{/if};
                                var title = {$map.regions.label.title|json_encode nofilter};
                                setLabelToProperties(d, keys, title);
                            }
                        });
                        {else}
                            var keys = {if $map.regions.label.properties}{$map.regions.label.properties|json_encode nofilter}{else}undefined{/if};
                            var title = {$map.regions.label.title|json_encode nofilter};
                            setLabelToProperties(d, keys, title);
                        {/if}
                    {else}
                        var keys = {if $map.regions.label.properties}{$map.regions.label.properties|json_encode nofilter}{else}undefined{/if};
                        var title = {$map.regions.label.title|json_encode nofilter};
                        setLabelToProperties(d, keys, title);
                    {/if}
                    
                } else {
                    selectedRegion = null;
                    label.style('display', 'none');
                    label_contents.text('');
                }
                
                selectedPoint = null;
                
                focusRegion(selectedRegion);
                focusPoint(selectedRegion); // intentional
            }
            
		} catch(e) {
			console.error(e);
		}
	});
});
</script>