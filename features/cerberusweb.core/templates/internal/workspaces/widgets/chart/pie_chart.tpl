<canvas id="widget{$widget->id}_axes_canvas" width="325" height="220" style="position:absolute;cursor:crosshair;display:none;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="325" height="220">
	Your browser does not support HTML5 Canvas.
</canvas>

<div class="subtotals" style="margin-top:5px;">
{foreach from=$widget->params['wedge_labels'] item=label key=idx name=labels}
{if !empty($label)}
<div class="subtotal" style="display:inline-block;">
	{$color = $widget->params['wedge_colors'][$idx]}
	{if empty($color)}{$color = end($widget->params['wedge_colors'])}{/if}
	<span style="width:10px;height:10px;display:inline-block;background-color:{$color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<span class="label" style="font-weight:bold;vertical-align:middle;">{$label}</span> <small>({$widget->params['wedge_values'][$idx]|number_format:0})</small>
</div>
{/if}
{/foreach}
</div>

<script type="text/javascript">
try {
	$widget = $('#widget{$widget->id}');
	width = $widget.width();
	$widget.find('canvas').attr('width', width);
	
	var options = {
		{if !empty($widget->params.wedge_values)}'wedge_values': {json_encode($widget->params.wedge_values) nofilter},{/if}
		{if !empty($widget->params.wedge_colors)}'wedge_colors': {json_encode($widget->params.wedge_colors) nofilter},{/if}
		'radius': 90
	};
	
	drawPieChart($('#widget{$widget->id}_canvas'), options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			
			chart_top = 15;
			chart_height = canvas.height - chart_top;
			chart_width = canvas.width;
			
			arclen = 2 * Math.PI;
			piecenter_x = Math.floor(chart_width/2);
			piecenter_y = Math.floor(chart_height/2);

			$(this).data('piecenter_x', piecenter_x);
			$(this).data('piecenter_y', piecenter_y);
			
			// Cache: Plots chart coords
			
			area_sum = 0;
			
			for(idx in options.wedge_values) {
				area_sum += options.wedge_values[idx];
			}
			
			wedges = [];
			arclen = 0;
			partlen = 0;
			
			for(idx in options.wedge_values) {
				area_used += options.wedge_values[idx];
				area = options.wedge_values[idx];
				partlen = 2 * Math.PI * (options.wedge_values[idx]/area_sum);
				
				wedges[wedges.length] = {
					'index': idx,
					'value': area,
					'ratio': area / area_sum,
					'start': arclen,
					'part_length': partlen,
					'length': arclen + partlen
				}
				
				arclen += partlen;
			}
			
			//$(this).data('area_sum', area_sum);
			$(this).data('wedges', wedges);
		})
		.mousemove(function(e) {
			debug = false;
			
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			$widget = $('#widget{$widget->id}');
			
			chart_top = 15;
			chart_height = canvas.height - chart_top;
			chart_width = canvas.width;
			
			options = $(this).data('model');
			wedges = $(this).data('wedges');
			piecenter_x = $(this).data('piecenter_x');
			piecenter_y = $(this).data('piecenter_y');
			radius = options.radius || 90;
			
			context.clearRect(0, 0, canvas.width, canvas.height);

			var x = 0;
			
			if(undefined != e.offsetX) {
				x = e.offsetX;
				y = e.offsetY;
				
			} else if(undefined != e.layerX) {
				x = e.layerX;
				y = e.layerY;
				
			} else if(null != e.originalEvent && undefined != e.originalEvent.layerX) {
				x = e.originalEvent.layerX;
				y = e.originalEvent.layerY;
			}
			
			new_radius = Math.sqrt(Math.pow(piecenter_x - x, 2) + Math.pow(piecenter_y - y, 2));
					
			if(debug) {
				context.beginPath();
				context.moveTo(piecenter_x, piecenter_y);
				context.fillStyle = '#CCCCCC';
				context.arc(piecenter_x, piecenter_y, new_radius, 0, 2 * Math.PI, false);
				context.lineTo(piecenter_x, piecenter_y);
				context.fill();
			}
			
			origin = { 'x': piecenter_x, 'y': piecenter_y - new_radius };

			angle = Math.atan2(y - origin.y, x - origin.x) * 2;
			
			scaled_point = { 
				'x': Math.floor(piecenter_x + Math.sin(Math.PI - angle) * radius),
				'y': Math.floor(piecenter_y + Math.cos(Math.PI - angle) * radius)  
			};

			if(debug) {
				context.beginPath();
				context.fillStyle = 'red';
				context.arc(x, y, 2.5, 0, 2 * Math.PI, false);
				context.fill();
			}
			
			if(debug) {
				context.beginPath();
				context.fillStyle = 'black';
				context.arc(scaled_point.x, scaled_point.y, 5, 0, 2 * Math.PI, false);
				context.fill();
			}

			area_sum = 0;
			closest_dist = 100;
			closest_point = null;
			closest_wedge = null;

			me = Math.atan2(scaled_point.y - piecenter_y, scaled_point.x - piecenter_x);
			
			if(me < 0)
				me = 2 * Math.PI + me;
			
			for(idx in wedges) {
				angle = Math.PI - ((area_sum * Math.PI * 2) + (Math.PI/2));

				position = {
					'x': Math.floor(piecenter_x + radius * Math.sin(angle)),
					'y': Math.floor(piecenter_y + radius * Math.cos(angle))
				};
				
				it = Math.atan2(position.y - piecenter_y, position.x - piecenter_x);

				if(it < 0)
					it = 2 * Math.PI + it;
				
				dist = me - it;
				
				if(dist > 0 && dist < closest_dist) {
					closest_dist = dist;
					closest_point = position;
					closest_wedge = wedges[idx];
				}
				
				if(debug) {
					color = options.wedge_colors[idx];
					if(undefined == color)
						color = options.wedge_colors[options.wedge_colors.length-1];
					context.beginPath();
					context.fillStyle = color;
					context.arc(position.x, position.y, 8, 0, 2 * Math.PI, false);
					context.fill();
				}
				
				area_sum += wedges[idx].ratio;
			}

			if(debug) {
				context.beginPath();
				context.fillStyle = 'red';
				context.arc(closest_point.x, closest_point.y, 5, 0, 2 * Math.PI, false);
				context.fill();
			}
			
			if(null == closest_wedge)
				return;
			
			context.beginPath();
			context.moveTo(piecenter_x, piecenter_y);
			color = options.wedge_colors[closest_wedge.index];
			if(undefined == color)
				color = options.wedge_colors[options.wedge_colors.length-1];
			context.fillStyle = color;
			context.strokeStyle = color;
			context.lineWidth = 3;
			context.lineCap = 'round';
			context.arc(piecenter_x, piecenter_y, radius + 10, closest_wedge.start, closest_wedge.length, false);
			context.lineTo(piecenter_x, piecenter_y);
			context.fill();
			
			context.beginPath();
			context.strokeStyle = 'rgba(255,255,255,0.7)';
			context.lineWidth = 15;
			context.lineCap = 'square';
			context.arc(piecenter_x, piecenter_y, radius + 8, closest_wedge.start, closest_wedge.length, false);
			context.stroke();
			
			$labels = $widget.find('div.subtotals > div.subtotal > span.label');
			$labels
				.css('background-color', '')
				;
			$labels.filter(':nth(' + closest_wedge.index + ')')
				.css('background-color', 'rgb(255,235,128)')
				;
		})
		.mouseout(function() {
			$widget = $('#widget{$widget->id}');
			
			$labels = $widget.find('div.subtotals > div.subtotal > span.label');
			$labels
				.css('background-color', '')
				;
		})
		;		
	
} catch(e) {
}
</script>
