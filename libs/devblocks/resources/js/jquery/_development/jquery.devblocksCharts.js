/***********************************************************************
| Devblocks(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
$.fn.extend({
	devblocksCharts : function(type, options) {
		var getMousePositionFromEvent = function(e) {
			var pos = { x: 0, y: 0 };
			
			if(undefined != e.offsetX) {
				pos.x = e.offsetX;
				pos.y = e.offsetY;
				
			} else if(undefined != e.layerX) {
				pos.x = e.layerX;
				pos.y = e.layerY;
				
			} else if(null != e.originalEvent && undefined != e.originalEvent.layerX) {
				pos.x = e.originalEvent.layerX;
				pos.y = e.originalEvent.layerY;
			}
			
			return pos;
		}
		
		var drawGauge = function($canvas, options) {
			var canvas = $canvas.get(0);
			var context = canvas.getContext('2d');
			
			var chart_height = canvas.height;
			var chart_width = canvas.width;
			
			if(null == options.threshold_colors)
				options.threshold_colors = ['#6BA81E', '#F9B326', '#D23E2E'];
			
			if(null == options.radius)
				options.radius = 90;
			
			if(null == options.legend)
				options.legend = true;
			
			if(null == options.metric)
				options.metric = 0;
			
			if(null == options.metric_label)
				options.metric_label = options.metric;
			
			if(null == options.threshold_values)
				options.threshold_values = [33, 66, 100];
			
			if(null == options.threshold_labels)
				options.threshold_labels = options.threshold_values;
			
			var metric_min = options.metric_min ? options.metric_min : 0;
			var metric_max = options.threshold_values[options.threshold_values.length-1];
			
			if(options.metric < metric_min)
				options.metric = metric_min;
			
			if(options.metric > metric_max)
				options.metric = metric_max;
			
			if(null != options.metric_compare && options.metric_compare > metric_max)
				options.metric_compare = metric_max;
			
			// Dial
			
			var piecenter_x = options.radius + 10;
			var piecenter_y = options.radius + 5;
			
			var angle_start = Math.PI;
			var range = metric_max - metric_min;
			var value_last = metric_min;
			
			for(var idx in options.threshold_values) {
				context.beginPath();
				context.moveTo(piecenter_x, piecenter_y);
				context.fillStyle = options.threshold_colors[idx];
				var value = parseFloat(options.threshold_values[idx]);
				var angle_wedge = (Math.abs(value_last - value)/range * Math.PI);
				context.arc(piecenter_x, piecenter_y, options.radius, angle_start, angle_start + angle_wedge, false);
				angle_start += angle_wedge;
				value_last = value;
				context.lineTo(piecenter_x, piecenter_y);
				context.fill();
				//context.stroke();
			}
			
			// White out the inside of the gauge
			context.beginPath();
			context.moveTo(piecenter_x, piecenter_y);
			context.fillStyle = '#FFFFFF';
			context.arc(piecenter_x, piecenter_y, options.radius * 0.75, Math.PI, 2 * Math.PI, false);
			context.lineTo(piecenter_x, piecenter_y);
			context.fill();
			
			// Label the min
			if(options.metric_label_min) {
				context.font = '10px Verdana';
				context.fillStyle = 'gray';
				context.textBaseline = 'top';
				var measure = context.measureText(options.metric_label_min);
				context.fillText(options.metric_label_min, 10, piecenter_y+3);
			}
			
			// Label the max
			if(options.metric_label_max) {
				context.font = '10px Verdana';
				context.fillStyle = 'gray';
				context.textBaseline = 'top';
				var measure = context.measureText(options.metric_label_max);
				context.fillText(options.metric_label_max, chart_width-10-measure.width, piecenter_y+3);
			}
			
			// Legend
			if(options.legend) {
				context.font = 'bold 12px Verdana';
				context.textBaseline = 'top';
				legend_x = (options.radius * 2) + 35;
				legend_y = 10;
				
				for(idx in options.threshold_values) {
					context.fillStyle = options.threshold_colors[idx];
					context.fillRect(legend_x,legend_y,20,20);
					legend_x += 25;
					
					label = options.threshold_labels[idx];
					context.fillStyle = 'black';
					context.fillText(label, legend_x, legend_y);
					legend_y += 25;
					legend_x -= 25;
				}
			}
			
			// Comparison Needle
			if(null != options.metric_compare) {
				context.save();
				context.translate(piecenter_x, piecenter_y);
				theta = (Math.PI/metric_max) * options.metric_compare;
				context.rotate(theta);
				context.beginPath();
				context.strokeStyle = '#F9B326';
				context.lineWidth = 5;
				context.moveTo(-1 * options.radius * 0.9,0);
				context.lineTo(-1 * options.radius * 1.1,0);
				context.stroke();
				context.restore();
			}
			
			// Knob
			context.beginPath();
			context.fillStyle = 'black';
			context.arc(piecenter_x, piecenter_y, 8, 0, 2 * Math.PI, false);
			context.fill();
			
			context.save();
			
			// Needle
			context.translate(piecenter_x, piecenter_y);
			var theta = (Math.PI/range) * Math.abs(metric_min - options.metric);
			context.rotate(theta);
			context.beginPath();
			context.fillStyle = 'black';
			context.moveTo(3,4);
			context.lineTo(3,-4);
			context.lineTo(-1 * (options.radius * 1.1),0);
			context.fill();
			
			// Accent
			context.beginPath();
			context.strokeStyle = '#383838';
			context.moveTo(0,0);
			context.lineWidth = 1;
			context.lineTo(-1 * (options.radius * 1.1),0);
			context.stroke();
			
			context.restore();
			
			// Metric
			context.font = 'bold 15px Verdana';
			context.fillStyle = 'black';
			context.textBaseline = 'top';
			var measure = context.measureText(options.metric_label);
			context.fillText(options.metric_label, piecenter_x-(measure.width/2), piecenter_y+12);
		};
		
		var drawPieChart = function($canvas, options) {
			$canvas
				.data('model', options)
				.each(function(e) {
					var $canvas = $(this);
					var canvas = $canvas.get(0);
					var context = canvas.getContext('2d');
					
					var options = $canvas.data('model');
					
					var chart_height = canvas.height;
					var chart_width = canvas.width;
					
					var arclen = 2 * Math.PI;
					var piecenter_x = Math.floor(chart_width/2);
					var piecenter_y = Math.floor(chart_height/2);

					$canvas.data('piecenter_x', piecenter_x);
					$canvas.data('piecenter_y', piecenter_y);
					
					// Cache: Plots chart coords
					
					var area_sum = 0;
					
					for(idx in options.wedge_values) {
						area_sum += options.wedge_values[idx];
					}
					
					var wedges = [];
					var arclen = 0;
					var partlen = 0;
					
					for(idx in options.wedge_values) {
						area_used += options.wedge_values[idx];
						var area = options.wedge_values[idx];
						var partlen = 2 * Math.PI * (options.wedge_values[idx]/area_sum);
						
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
					
					$canvas.data('wedges', wedges);
				})
				;
			
			var canvas = $canvas.get(0);
			var context = canvas.getContext('2d');
			
			var chart_width = canvas.width;
			var chart_height = canvas.height;

			if(null == options.wedge_colors)
				options.wedge_colors = [
					'#57970A',
					'#007CBD',
					'#7047BA',
					'#8B0F98',
					'#CF2C1D',
					'#E97514',
					'#FFA100',
					'#3E6D07',
					'#345C05',
					'#005988',
					'#004B73',
					'#503386',
					'#442B71',
					'#640A6D',
					'#55085C',
					'#951F14',
					'#7E1A11',
					'#A8540E',
					'#8E470B',
					'#B87400',
					'#9C6200',
					'#CCCCCC',
				];

			if(null == options.radius)
				options.radius = 90;
			
			// Wedges
			
			var arclen = 2 * Math.PI;
			var piecenter_x = Math.floor(chart_width/2);
			var piecenter_y = Math.floor(chart_height/2);

			var area_sum = 0;
			var area_used = 0;
			
			for(idx in options.wedge_values) {
				area_sum += options.wedge_values[idx];
			}
			
			for(idx in options.wedge_values) {
				context.beginPath();
				context.moveTo(piecenter_x, piecenter_y);
				context.fillStyle = options.wedge_colors[idx];
				context.strokeStyle = options.wedge_colors[idx];
				context.lineWidth = 1;
				context.lineCap = 'square';
				partlen = 2 * Math.PI * (options.wedge_values[idx]/area_sum);
				area_used += options.wedge_values[idx];
				context.arc(piecenter_x, piecenter_y, options.radius, arclen, arclen + partlen, false);
				context.lineTo(piecenter_x, piecenter_y);
				context.fill();
				context.stroke();
				arclen += partlen;
			}
			
			$canvas.on('mousemove click', function(e) {
				var canvas = $(this).get(0);
				var context = canvas.getContext('2d');
				
				var chart_height = canvas.height;
				var chart_width = canvas.width;
				
				var options = $(this).data('model');
				var wedges = $(this).data('wedges');
				var piecenter_x = $(this).data('piecenter_x');
				var piecenter_y = $(this).data('piecenter_y');
				var radius = options.radius || 90;
				
				var mouse_pos = getMousePositionFromEvent(e);
				
				var new_radius = Math.sqrt(Math.pow(piecenter_x - mouse_pos.x, 2) + Math.pow(piecenter_y - mouse_pos.y, 2));
						
				var origin = { 'x': piecenter_x, 'y': piecenter_y - new_radius };

				var angle = Math.atan2(mouse_pos.y - origin.y, mouse_pos.x - origin.x) * 2;
				
				var scaled_point = { 
					'x': Math.floor(piecenter_x + Math.sin(Math.PI - angle) * radius),
					'y': Math.floor(piecenter_y + Math.cos(Math.PI - angle) * radius)
				};

				var area_sum = 0;
				var closest_dist = 100;
				var closest_point = null;
				var closest_wedge = null;

				var me = Math.atan2(scaled_point.y - piecenter_y, scaled_point.x - piecenter_x);
				
				if(me < 0)
					me = 2 * Math.PI + me;
				
				for(idx in wedges) {
					var angle = Math.PI - ((area_sum * Math.PI * 2) + (Math.PI/2));

					var position = {
						'x': Math.floor(piecenter_x + radius * Math.sin(angle)),
						'y': Math.floor(piecenter_y + radius * Math.cos(angle))
					};
					
					var it = Math.atan2(position.y - piecenter_y, position.x - piecenter_x);

					if(it < 0)
						it = 2 * Math.PI + it;
					
					var dist = me - it;
					
					if(dist > 0 && dist < closest_dist) {
						closest_dist = dist;
						closest_point = position;
						closest_wedge = wedges[idx];
					}
					
					area_sum += wedges[idx].ratio;
				}

				if(null == closest_wedge)
					return;
				
				// Trigger the event
				var event = new jQuery.Event('devblocks-chart-' + e.type, {
					'closest': closest_wedge
				});
				$(this).trigger(event);
			});
		};
		
		var drawLineChart = function($canvas, params) {
			$canvas
				.data('model', params)
				// [TODO] This could be handled on the main pass through below
				.each(function(e) {
					canvas = $(this).get(0);
					context = canvas.getContext('2d');
		
					options = $(this).data('model');
					
					var margin = 5;
					var chart_width = canvas.width;
					var chart_height = canvas.height - (2 * margin);
					
					var max_value = 0;
					var min_value = 0;
				
					// Cache: Find the max y-value across every series
				
					for(series_idx in options.series) {
						for(idx in options.series[series_idx].data) {
							value = options.series[series_idx].data[idx].y;
							
							max_value = Math.max(value, max_value);
							min_value = Math.min(value, min_value);
						}
					}
		
					var range = Math.abs(max_value - min_value);
					
					var zero_ypos = Math.floor(chart_height * (max_value/range)) + margin - (0 == margin % 2 ? 0 : 0.5);
		
					// Cache: Plots chart coords
					
					plots = [];
					
					for(series_idx in options.series) {
						series = options.series[series_idx];
		
						if(null == series.data)
							continue;
						
						plots[series_idx] = [];
						
						count = series.data.length;
						xtick_width = chart_width / (count-1);
						ytick_height = chart_height / range;
						
						for(idx in series.data) {
							point = series.data[idx];
							
							chart_x = idx * xtick_width;
							
							value_yheight = Math.floor(ytick_height * Math.abs(point.y));
							
							if(point.y >= 0) {
								chart_y = zero_ypos - value_yheight;
								
							} else {
								chart_y = zero_ypos + value_yheight;
								
							}
							
							len = plots[series_idx].length;
							
							plots[series_idx][len] = {
								'chart_x': chart_x,
								'chart_y': chart_y,
								'data': point
							};
						}
					}
					
					$(this).data('plots', plots);
				})
				;
			
			var canvas = $canvas.get(0);
			var context = canvas.getContext('2d');
			
			var margin = 5;
			var chart_width = canvas.width;
			var chart_height = canvas.height - (2 * margin);
			
			var max_value = 0;
			var min_value = 0;

			// Find the max y-value across every series

			for(series_idx in params.series) {
				for(idx in params.series[series_idx].data) {
					value = params.series[series_idx].data[idx].y;
					
					max_value = Math.max(value, max_value);
					min_value = Math.min(value, min_value);
				}
			}

			var range = Math.abs(max_value - min_value);
			
			// Find the y-zero line (it may not be the bottom if we have negative values)
			
			var zero_ypos = Math.floor(chart_height * (max_value/range)) + margin - (0 == margin % 2 ? 0 : 0.5);
			
			context.lineWidth = 1;
			
			var default_line_colors = [
				'rgba(87, 151, 10, 1)',
				'rgba(0, 124, 189, 1)',
				'rgba(112, 71, 186, 1)',
				'rgba(139, 15, 152, 1)',
				'rgba(207, 44, 29, 1)',
				'rgba(233, 117, 20, 1)',
				'rgba(255, 161, 0, 1)',
				'rgba(62, 109, 7, 1)',
				'rgba(52, 92, 5, 1)',
				'rgba(0, 89, 136, 1)',
				'rgba(0, 75, 115, 1)',
				'rgba(80, 51, 134, 1)',
				'rgba(68, 43, 113, 1)',
				'rgba(100, 10, 109, 1)',
				'rgba(85, 8, 92, 1)',
				'rgba(149, 31, 20, 1)',
				'rgba(126, 26, 17, 1)',
				'rgba(168, 84, 14, 1)',
				'rgba(142, 71, 11, 1)',
				'rgba(184, 116, 0, 1)',
				'rgba(156, 98, 0, 1)',
				'rgba(204, 204, 204, 1)'
			];
			
			var default_fill_colors = [
				'rgba(87, 151, 10, 0.35)',
				'rgba(0, 124, 189, 0.35)',
				'rgba(112, 71, 186, 0.35)',
				'rgba(139, 15, 152, 0.35)',
				'rgba(207, 44, 29, 0.35)',
				'rgba(233, 117, 20, 0.35)',
				'rgba(255, 161, 0, 0.35)',
				'rgba(62, 109, 7, 0.35)',
				'rgba(52, 92, 5, 0.35)',
				'rgba(0, 89, 136, 0.35)',
				'rgba(0, 75, 115, 0.35)',
				'rgba(80, 51, 134, 0.35)',
				'rgba(68, 43, 113, 0.35)',
				'rgba(100, 10, 109, 0.35)',
				'rgba(85, 8, 92, 0.35)',
				'rgba(149, 31, 20, 0.35)',
				'rgba(126, 26, 17, 0.35)',
				'rgba(168, 84, 14, 0.35)',
				'rgba(142, 71, 11, 0.35)',
				'rgba(184, 116, 0, 0.35)',
				'rgba(156, 98, 0, 0.35)',
				'rgba(204, 204, 204, 0.35)'
			];
			
			// Loop through multiple series
			for(series_idx in params.series) {
				var series = params.series[series_idx];
				
				if(null == series.data)
					continue;
				
				if(null == series.options.line || series.options.line) {
					if(null != series.options.line_color)
						params.series[series_idx].options.line_color = series.options.line_color;
					else if(null != default_line_colors[series_idx])
						params.series[series_idx].options.line_color = default_line_colors[series_idx];
					else
						params.series[series_idx].options.line_color = 'rgba(5,141,199,1)';
				}
				
				if(null == series.options.fill || series.options.fill) {
					if(null != series.options.fill_color)
						params.series[series_idx].options.fill_color = series.options.fill_color;
					else if(null != default_fill_colors[series_idx])
						params.series[series_idx].options.fill_color = default_fill_colors[series_idx];
					else
						params.series[series_idx].options.fill_color = 'rgba(5,141,199,0.15)';
				}
				
				context.beginPath();

				x = 0;
				y = 0;
				tick = 0;

				count = series.data.length;
				xtick_width = chart_width / (count-1);
				ytick_height = chart_height / range;
				
				// Fill
				
				if(null != series.options.fill_color) {
					context.moveTo(0, zero_ypos);
					
					for(idx in series.data) {
						value = series.data[idx].y;
						
						x = tick;
						value_yheight = Math.floor(ytick_height * Math.abs(value));
						
						if(value >= 0) {
							y = zero_ypos - value_yheight;
							
						} else {
							y = zero_ypos + value_yheight;
							
						}
						
						context.lineTo(x, y);
						
						tick += xtick_width;
					}
				
					context.lineTo(x, zero_ypos);
					
					context.fillStyle = series.options.fill_color;
					context.fill();
				}

				// Stroke
				
				if(series.options.line_color) {
					context.beginPath();
					context.strokeStyle = series.options.line_color;
					context.lineJoin = 'round';
					context.lineWidth = 3;
					
					tick = 0;
					
					for(idx in series.data) {
						value = series.data[idx].y;
						
						x = tick;
						value_yheight = Math.floor(ytick_height * Math.abs(value));
						
						if(value >= 0) {
							y = zero_ypos - value_yheight;
							
						} else {
							y = zero_ypos + value_yheight;
							
						}
						
						context.lineTo(x, y);
						tick += xtick_width;
					}
	
					context.stroke();
				}
				
			} // end series
			
			$canvas.on('mousemove click', function(e) {
				var options = $(this).data('model');
				var plots = $(this).data('plots');
				
				var mouse_pos = getMousePositionFromEvent(e);
				
				var closest = {
					'dist': 1000,
					'chart_x': 0,
					'chart_y': 0,
					'data': [],
					'series_idx': null
				};
				
				for(series_idx in plots) {
					var count = plots[series_idx].length;
					var series = options.series[series_idx];
					
					for(idx in plots[series_idx]) {
						var plot = plots[series_idx][idx];
						var dist = 0;
						
						if(plots.length == 1) {
							dist = Math.abs(mouse_pos.x-plot.chart_x);
						} else {
							dist = Math.sqrt(Math.pow(mouse_pos.x-plot.chart_x,2) + Math.pow(mouse_pos.y-plot.chart_y,2));
						}
						
						if(dist < closest.dist) {
							closest.dist = dist;
							closest.data = plot.data;
							closest.chart_x = plot.chart_x;
							closest.chart_y = plot.chart_y;
							closest.series_idx = series_idx;
						}
					}
				}

				// Trigger the event
				var event = new jQuery.Event('devblocks-chart-' + e.type, {
					'closest': closest
				});
				$(this).trigger(event);
				
			});
		};
		
		var drawBarChart = function($canvas, params) {
			$canvas
				.data('model', params)
				// [TODO] This could be handled on the main pass through below
				.each(function(e) {
					var canvas = $(this).get(0);
					var context = canvas.getContext('2d');
					
					var options = $(this).data('model');
					
					var chart_height = canvas.height;
					var chart_width = canvas.width;
					
					// Cache: Plots chart coords
					
					var plots = [];
					
					var series_idx =  0;
					var series = options.series[series_idx];

					if(null == series.data)
						return;
					
					plots[series_idx] = [];
					
					// [TODO] This should make sure all series are the same length
					var count = series.data.length;
					var xtick_width = Math.floor(chart_width / count);
					
					$(this).data('xtick_width', xtick_width);
					
					var chart_x = 0;
					
					for(idx in series.data) {
						var point = series.data[idx];
						
						plots[plots.length] = {
							'chart_x': chart_x,
							'index': idx
						};

						chart_x = Math.floor(chart_x + xtick_width);
					}

					$(this).data('plots', plots);
				})
				;
			
			try {
				var canvas = $canvas.get(0);
				var context = canvas.getContext('2d');
				
				var default_colors = ['#57970A', '#007CBD', '#7047BA', '#8B0F98', '#CF2C1D', '#E97514', '#FFA100', '#3E6D07', '#345C05', '#005988', '#004B73', '#503386', '#442B71', '#640A6D', '#55085C', '#951F14', '#7E1A11', '#A8540E', '#8E470B', '#B87400', '#9C6200', '#CCCCCC'];
				
				if(null == options.series[0].data)
					return;
				
				// [TODO] This should make sure all series are the same length
				var count = options.series[0].data.length;
			
				var chart_width = canvas.width;
				var chart_height = canvas.height - 1;
				
				var stack_data = [];
				
				var highest_stack = 0;
				var lowest_stack = 0;

				// Build the series metadata
				for(idx=0; idx < count; idx++) {
					stack_data[idx] = { total: 0, pos: 0, neg: 0, pos_drawn: 0, neg_drawn: 0 };
					
					for(series_idx in params.series) {
						var series = params.series[series_idx];
						
						if(null == series.data || 0 == series.data.length)
							continue;
						
						var val = series.data[idx].y;
						
						stack_data[idx].total += val;
						
						if(val >= 0)
							stack_data[idx].pos += val;
						else
							stack_data[idx].neg += val;
					}
					
					// Find max height across all series
					highest_stack = Math.max(stack_data[idx].total, highest_stack);
					lowest_stack = Math.min(stack_data[idx].total, lowest_stack);
				}
				
				xtick_width = Math.floor(chart_width / count);
				yrange = (highest_stack-lowest_stack);
				ytick_height = chart_height / yrange;
			
				// Find the zero y-position by using a proportion of highest stack to range
				zero_ypos = Math.floor(chart_height * (highest_stack/yrange));
				
				context.lineWidth = 1;
				
				context.beginPath();
				context.moveTo(0, zero_ypos);
				context.lineTo(0, zero_ypos+1);
				context.lineTo(chart_width, zero_ypos+1);
				context.lineTo(chart_width, zero_ypos);
				context.fillStyle = '#BBBBBB';
				context.fill();
				
				for(series_idx in params.series) {
					var series = params.series[series_idx];
			
					if(null == series.data || 0 == series.data.length)
						continue;
					
					if(null != series.options.color)
						params.series[series_idx].options.color = series.options.color;
					else if(null != default_colors[series_idx])
						params.series[series_idx].options.color = default_colors[series_idx];
					else
						params.series[series_idx].options.color = '#455460';
			
					var x = 0;
					
					for(idx in series.data) {
						try {
							context.fillStyle = params.series[series_idx].options.color;
							
							value = series.data[idx].y;
				
							if(0 == value) {
								x = Math.floor(x + xtick_width);
								continue;
							}
							
							// [TODO] This gives a rounding error in bar heights w/ diff stacks of same total
							//		We need to level the bars before getting to this point
							stack_yheight = Math.round(ytick_height * Math.abs(value));
							
							// Always draw at least one pixel of height
							stack_yheight = Math.max(stack_yheight, 1);
							
							// Above the zero line
							if(value >= 0) {
								y = zero_ypos - stack_data[idx].pos_drawn - stack_yheight;
								stack_data[idx].pos_drawn += stack_yheight;
								
							// Below the zero line
							} else {
								y = zero_ypos + 1 + stack_data[idx].neg_drawn;
								stack_data[idx].neg_drawn += stack_yheight;
								
							}
							
							context.beginPath();
							context.moveTo(x, y);
							context.lineTo(x, y + stack_yheight);
							
							x = Math.floor(x + xtick_width);
							
							context.lineTo(x-1, y + stack_yheight);
							context.lineTo(x-1, y);
							context.fill();
							
						} catch(e) {
							//console.log(e);
						}
					}
				}
				
				$canvas.on('mousemove click', function(e) {
					var canvas = $(this).get(0);
					var context = canvas.getContext('2d');
					
					var chart_height = canvas.height;
					var chart_width = canvas.width;
					
					var options = $(this).data('model');
					var plots = $(this).data('plots');
					var xtick_width = $(this).data('xtick_width');

					var mouse_pos = getMousePositionFromEvent(e);
					
					var closest = {
						'dist': 1000,
						'data': null,
					};
					
					for(idx in plots) {
						var plot = plots[idx];
						var dist = Math.abs(mouse_pos.x-(plot.chart_x+(xtick_width/2)));
						
						if(dist < closest.dist) {
							closest.dist = dist;
							closest.data = plot;
						}
					}
					
					if(null == closest.data)
						return;

					// Trigger the event
					var event = new jQuery.Event('devblocks-chart-' + e.type, {
						'closest': closest
					});
					$(this).trigger(event);
				});
				
			} catch(e) {
				//console.log(e);
			}
		};
		
		var drawScatterPlot = function($canvas, options) {
			$canvas
				.data('model', options)
				.each(function(e) {
					var $canvas = $(this);
					var canvas = $canvas.get(0);
					var context = canvas.getContext('2d');
					
					var options = $canvas.data('model');
					
					// Cache
					
					var margin = 5;
					var chart_width = canvas.width - (2 * margin);
					var chart_height = canvas.height - (2 * margin);
				
					// Stats for the entire dataset
					
					var stats = {
						x_min: Number.MAX_VALUE,
						x_max: Number.MIN_VALUE,
						y_min: Number.MAX_VALUE,
						y_max: Number.MIN_VALUE,
						x_range: 0,
						y_range: 0
					}
					
					for(series_idx in options.series) {
						var series = options.series[series_idx];
						
						if(null == series.data)
							continue;
						
						for(idx in series.data) {
							var data = series.data[idx];
							var x = data.x;
							var y = data.y;
							
							stats.x_min = Math.min(stats.x_min,x);
							stats.x_max = Math.max(stats.x_max,x);
							stats.y_min = Math.min(stats.y_min,y);
							stats.y_max = Math.max(stats.y_max,y);
						}		
					}
				
					stats.x_range = Math.abs(stats.x_max - stats.x_min);
					stats.y_range = Math.abs(stats.y_max - stats.y_min);
					
					// Stats for each series
					
					var series_stats = [];
					
					for(series_idx in options.series) {
						var series = options.series[series_idx];
						
						if(null == series.data)
							continue;
						
						var minmax = {
							x_min: Number.MAX_VALUE,
							x_max: Number.MIN_VALUE,
							y_min: Number.MAX_VALUE,
							y_max: Number.MIN_VALUE,
							x_range: 0,
							y_range: 0
						}
						
						for(idx in series.data) {
							var data = series.data[idx];
							var x = data.x;
							var y = data.y;
				
							minmax.x_min = Math.min(minmax.x_min, x);
							minmax.x_max = Math.max(minmax.x_max, x);
							minmax.y_min = Math.min(minmax.y_min, y);
							minmax.y_max = Math.max(minmax.y_max, y);
						}
						
						minmax.x_range = Math.abs(minmax.x_max - minmax.x_min);
						minmax.y_range = Math.abs(minmax.y_max - minmax.y_min);
						
						series_stats[series_idx] = minmax;
					}
					
					// Cache: Plots chart coords
					
					var plots = [];

					for(series_idx in options.series) {
						var series = options.series[series_idx];
						
						if(options.axes_independent) {
							stat = series_stats[series_idx];
						} else {
							stat = stats;
						}
						
						if(null == series || null == series.data)
							continue;

						var xaxis_tick = (stat.x_range != 0) ? (chart_width / stat.x_range) : chart_width;
						var yaxis_tick = (stat.y_range != 0) ? (chart_height / stat.y_range) : chart_height;
						
						plots[series_idx] = [];
						
						for(idx in series.data) {
							var data = series.data[idx];
							var x = data.x - stat.x_min;
							var y = data.y - stat.y_min;
							
							var chart_x = (xaxis_tick * x) + margin;
							var chart_y = chart_height - (yaxis_tick * y) + margin;
							
							plots[series_idx][idx] = {
								'chart_x': chart_x,
								'chart_y': chart_y,
								'data': data
							};
						}
					}
					
					$canvas.data('plots', plots);
				});
			
			var canvas = $canvas.get(0);
			var context = canvas.getContext('2d');
			
			var margin = 5;
			var chart_width = canvas.width - (2 * margin);
			var chart_height = canvas.height - (2 * margin);

			// Stats for the entire dataset
			
			var stats = {
				x_min: Number.MAX_VALUE,
				x_max: Number.MIN_VALUE,
				y_min: Number.MAX_VALUE,
				y_max: Number.MIN_VALUE,
				x_range: 0,
				y_range: 0
			}
			
			for(series_idx in options.series) {
				var series = options.series[series_idx];
				
				if(null == series.data)
					continue;
				
				for(idx in series.data) {
					var data = series.data[idx];
					var x = data.x;
					var y = data.y;
					
					stats.x_min = Math.min(stats.x_min,x);
					stats.x_max = Math.max(stats.x_max,x);
					stats.y_min = Math.min(stats.y_min,y);
					stats.y_max = Math.max(stats.y_max,y);
				}
			}

			stats.x_range = Math.abs(stats.x_max - stats.x_min);
			stats.y_range = Math.abs(stats.y_max - stats.y_min);
			
			// Stats for each series
			
			var series_stats = [];
			
			for(series_idx in options.series) {
				var series = options.series[series_idx];
				
				if(null == series.data)
					continue;
				
				var minmax = {
					x_min: Number.MAX_VALUE,
					x_max: Number.MIN_VALUE,
					y_min: Number.MAX_VALUE,
					y_max: Number.MIN_VALUE,
					x_range: 0,
					y_range: 0
				}
				
				for(idx in series.data) {
					var data = series.data[idx];
					var x = data.x;
					var y = data.y;

					minmax.x_min = Math.min(minmax.x_min, x);
					minmax.x_max = Math.max(minmax.x_max, x);
					minmax.y_min = Math.min(minmax.y_min, y);
					minmax.y_max = Math.max(minmax.y_max, y);
				}
				
				minmax.x_range = Math.abs(minmax.x_max - minmax.x_min);
				minmax.y_range = Math.abs(minmax.y_max - minmax.y_min);
				
				series_stats[series_idx] = minmax;
			}
			
			// [TODO] If we're not using independent axes, find the biggest/smallest values
			//		among all the series
			
			var default_colors = [
				'rgba(87, 151, 10, 1)',
				'rgba(0, 124, 189, 1)',
				'rgba(112, 71, 186, 1)',
				'rgba(139, 15, 152, 1)',
				'rgba(207, 44, 29, 1)',
				'rgba(233, 117, 20, 1)',
				'rgba(255, 161, 0, 1)',
				'rgba(62, 109, 7, 1)',
				'rgba(52, 92, 5, 1)',
				'rgba(0, 89, 136, 1)',
				'rgba(0, 75, 115, 1)',
				'rgba(80, 51, 134, 1)',
				'rgba(68, 43, 113, 1)',
				'rgba(100, 10, 109, 1)',
				'rgba(85, 8, 92, 1)',
				'rgba(149, 31, 20, 1)',
				'rgba(126, 26, 17, 1)',
				'rgba(168, 84, 14, 1)',
				'rgba(142, 71, 11, 1)',
				'rgba(184, 116, 0, 1)',
				'rgba(156, 98, 0, 1)',
				'rgba(204, 204, 204, 1)'
			];
			
			var is_zeroline_drawn = false;
			
			// Plot
			
			for(series_idx in options.series) {
				var series = options.series[series_idx];

				if(series.data == null)
					continue;
				
				if(options.axes_independent) {
					stat = series_stats[series_idx];
				} else {
					stat = stats;
				}
				
				var xaxis_tick = (stat.x_range != 0) ? (chart_width / stat.x_range) : chart_width;
				var yaxis_tick = (stat.y_range != 0) ? (chart_height / stat.y_range) : chart_height;

				// Draw a zero line if we have dependent axes
				if(options.y_line_zero && !options.axes_independent && !is_zeroline_drawn) {
					var zero_ypos = Math.floor(chart_height * (stats.y_max/stats.y_range)) + margin + 2 - (0 == margin % 2 ? 0 : 0.5);
					context.lineWidth = 1;
					
					context.beginPath();
					context.moveTo(0, zero_ypos);
					context.lineTo(0, zero_ypos-1);
					context.lineTo(canvas.width, zero_ypos-1);
					context.lineTo(canvas.width, zero_ypos);
					context.strokeStyle = '#BBBBBB';
					context.stroke();
				}
				
				if(series.options.color)
					options.series[series_idx].options.color = series.options.color;
				else if(null != default_colors[series_idx])
					options.series[series_idx].options.color = default_colors[series_idx];
				else
					options.series[series_idx].options.color = 'rgba(75,75,75,1)';
				
				context.fillStyle = options.series[series_idx].options.color;
				context.strokeStyle = options.series[series_idx].options.color;
				
				for(idx in series.data) {
					var data = series.data[idx];
					var x = data.x - stat.x_min;
					var y = data.y - stat.y_min;
					
					var chart_x = (xaxis_tick * x) + margin;
					var chart_y = chart_height - (yaxis_tick * y) + margin;
					
					/*
					// [TODO] Make this configurable
					if(series_idx == 1) {
						label = '+';
						
					} else if(series_idx == 2) {
						label = 'x';
						
					} else if(series_idx == 3) {
						label = '*';
						
					} else {
						label = 'o';
						
					}
					*/
					
					label = 'o';
					
					context.font = '12px Courier';
					var measure = context.measureText(label);
					
					context.beginPath();
					context.fillText(label, chart_x-measure.width/2, chart_y+measure.width/2);
					context.fill();
				}
			}
			
			$canvas.on('mousemove click', function(e) {
				var options = $(this).data('model');
				var plots = $(this).data('plots');
				
				var mouse_pos = getMousePositionFromEvent(e);
				
				var closest = {
					'dist': 1000,
					'chart_x': 0,
					'chart_y': 0,
					'data': [],
					'series_idx': null
				};

				for(series_idx in plots) {
					var count = plots[series_idx].length;
					var series = options.series[series_idx];
					
					for(idx in plots[series_idx]) {
						var plot = plots[series_idx][idx];
						var dist = Math.sqrt(Math.pow(mouse_pos.x-plot.chart_x,2) + Math.pow(mouse_pos.y-plot.chart_y,2));
						
						if(dist < closest.dist) {
							closest.dist = dist;
							closest.data = plot.data;
							closest.chart_x = plot.chart_x;
							closest.chart_y = plot.chart_y;
							closest.series_idx = series_idx;
						}
					}
				}
				
				if(closest.data == [])
					return;

				// Trigger the event
				var event = new jQuery.Event('devblocks-chart-' + e.type, {
					'closest': closest
				});
				$(this).trigger(event);
			});
		};
		
		this.each(function() {
			if($(this)[0].tagName.toLowerCase() !== 'canvas')
				return;
			
			var $canvas = $(this);
			
			switch(type) {
				case 'gauge':
					drawGauge($canvas, options);
					break;
					
				case 'pie':
					drawPieChart($canvas, options);
					break;
				
				case 'line':
					drawLineChart($canvas, options);
					break;
					
				case 'bar':
					drawBarChart($canvas, options);
					break;
					
				case 'scatterplot':
					drawScatterPlot($canvas, options);
					break;
			}
		});
		
		return $(this);
	}
});

