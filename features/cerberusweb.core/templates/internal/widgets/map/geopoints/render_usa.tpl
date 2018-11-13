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
			
			var pointPath = d3.geoPath()
				.projection(projection)
				.pointRadius(5)
				;
				
			var path = d3.geoPath()
				.projection(projection)
				;
				
			var widget = d3.select('#widget{$widget->id}');
				
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
				;
			
			d3.json('{devblocks_url}c=resource&p=cerberusweb.core&f=maps/us.json{/devblocks_url}?v={$smarty.const.APP_BUILD}').then(function(us) {
				g.append('g')
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
				
				var points = {json_encode($points) nofilter};
				
				for(series_key in points.objects) {
					g.append('g')
						.selectAll('.point')
							.data(topojson.feature(points, points.objects[series_key]).features)
						.enter().append('path')
							.attr('fill', 'red')
							.attr('stroke', 'black')
							.attr('stroke-width', '.5px')
							.attr('class', 'point')
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
					
					label.text(JSON.stringify(d.properties));
					
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
				}
				
				var selected_index = i;
				
				g.transition()
					.duration(750)
					.attr('transform', 'translate(' + width/2 + ',' + height/2 + ')scale(' + k + ')translate(' + -x + ',' + -y + ')')
					.style('stroke-width', 1.5/k + 'px')
					;
			}
			
			function clickedState(d, i) {
				var x, y, k;
				
				if(d && centered !== d) {
					var centroid = path.centroid(d);
					x = centroid[0];
					y = centroid[1];
					k = 2;
					centered = d;
					label.text(d.properties.NAME + ' (' +  d.properties.STUSPS +')');
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
				}
				
				var selected_index = i;
				
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