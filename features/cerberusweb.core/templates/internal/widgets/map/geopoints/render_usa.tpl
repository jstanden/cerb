<div id="widget{$widget->id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadScripts([
		'/resource/devblocks.core/js/d3/d3.v5.min.js',
		'/resource/devblocks.core/js/d3/topojson.v3.min.js'
	], function() {
		try {
			var width = 500,
				height = 250,
				centered;
				
			var projection = d3.geoAlbersUsa()
				.scale(500)
				.translate([width/2, height/2])
				;
			
			var path = d3.geoPath()
				.projection(projection)
				;
				
			var widget = d3.select('#widget{$widget->id}')
				.style('position', 'relative')
				;
				
			var svg = widget.append('svg:svg')
				.attr('viewBox', '0 0 ' + width + ' ' + height)
				;
				
			svg.append('rect')
				.style('fill', 'none')
				.style('.pointer-events', 'all')
				.on('click', clickedState)
				;

			var g = svg.append('g');
			
			var label = widget.append('div')
				.style('font-weight', 'bold')
				.style('margin', '5px')
				.style('padding', '5px')
				.style('position', 'absolute')
				.style('top', '0')
				.style('left', '0')
				.style('background-color', 'rgba(220,220,220,0.8)')
				.style('display', 'none')
				;
			
			d3.json('{devblocks_url}c=resource&p=cerberusweb.core&f=maps/us.json{/devblocks_url}?v={$smarty.const.APP_BUILD}').then(function(us) {
				g.append('g')
					// [TODO] State fill
					.style('fill', '#aaa')
				.selectAll('path')
					.data(topojson.feature(us, us.objects.states).features)
				.enter().append('path')
					.attr('d', path)
					.on('click', clickedState)
				;
				
				g.append('path')
					.datum(topojson.mesh(us, us.objects.states, function(a,b) {
							return a !== b;
					}))
					.attr('fill', 'none')
					.attr('stroke', '#fff')
					.attr('stroke-width', '0.5px')
					.attr('stroke-linejoin', 'round')
					.attr('stroke-linecap', 'round')
					.attr('pointer-events', 'none')
					.attr('d', path)
					;

                var pointPath = d3.geoPath()
                    .projection(projection)
                    .pointRadius(function(d) {
                        var point_radius = Devblocks.getObjectKeyByPath(d.properties, 'cerb.map.point.radius');
                        return point_radius || 3;
                    })
                ;

                var points = {json_encode($points) nofilter};
				
				// [TODO] Give all these to a callback first
				for(var series_key in points.objects) {
					g.append('g')
						.selectAll('.point')
							.data(topojson.feature(points, points.objects[series_key]).features)
						.enter().append('path')
							.attr('fill', function(d,i) {
								var point_fill = Devblocks.getObjectKeyByPath(d.properties, 'cerb.map.point.color');
								return point_fill || 'red';
							})
							.attr('stroke', function(d,i) {
								var border_color = Devblocks.getObjectKeyByPath(d.properties, 'cerb.map.point.border_color');
								return border_color || 'black';
							})
							.attr('stroke-width', function(d,i) {
								var border_width = Devblocks.getObjectKeyByPath(d.properties, 'cerb.map.point.border_width');
								return border_width || 1;
							})
							.attr('class', 'point')
							.style('cursor', 'pointer')
							//.style('pointer-events', 'none')
							.attr('d', pointPath)
							.on('click.zoon', clickedPOI)
						;
				}
			});
			
			function clickedPOI(d, i) {
				var x, y, k;
				
				if(d && centered !== d) {
					var centroid = path.centroid(d);
					x = centroid[0];
					y = centroid[1];
					k = 2;
					centered = d;
					
					var label_text = Devblocks.getObjectKeyByPath(d.properties, 'cerb.map.point.label');
					
					if(label_text) {
						$(label.node()).html(label_text);
					} else {
						label.text(d.properties.name);
					}
					
					label.style('display', 'inline-block');
					
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
					label.style('display', 'none');
				}

				g.transition()
					.duration(750)
					.attr('transform', 'translate(' + width/2 + ',' + height/2 + ')scale(' + k + ')translate(' + -x + ',' + -y + ')')
					.style('stroke-width', 1.5/k + 'px')
					;
			}
			
			// [TODO] Callback through widget
			function clickedState(d, i) {
				var x, y, k;
				
				if(d && centered !== d) {
					var centroid = path.centroid(d);
					x = centroid[0];
					y = centroid[1];
					k = 2;
					centered = d;
					label.text(d.properties.NAME + ' (' +  d.properties.STUSPS +')');
					label.style('display', 'inline-block');
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
					label.style('display', 'none');
				}
				
				g.selectAll('path')
					.each(function(d, i) {
						var selected = d === centered;
						
						if(selected) {
							d3.select(this)
								.style('fill', 'orange')
								;
						} else {
							d3.select(this)
								.style('fill', null)
								;
						}
						return selected;
					})
					;
					
				g.transition()
					.duration(750)
					.attr('transform', 'translate(' + width/2 + ',' + height/2 + ')scale(' + k + ')translate(' + -x + ',' + -y + ')')
					.style('stroke-width', 1.5/k + 'px')
					;
			}

		} catch(e) {
			console.error(e);
		}
	});
});
</script>