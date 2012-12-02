<style type="text/css">
DIV.dashboard-widget {
	margin:5px 5px 10px 5px;
}

DIV.dashboard-widget DIV.dashboard-widget-title {
	background-color:rgb(220,220,220);
	padding:5px 10px;
	font-size:120%;
	font-weight:bold;
	cursor:move;
	border-radius:10px;
	-webkit-border-radius:10px;
	-moz-border-radius:10px;
	-o-border-radius:10px;
}

DIV.dashboard-widget DIV.updated {
	text-align:left;
	color:rgb(200,200,200);
	display:none;
}
</style>

<script type="text/javascript">
function drawGauge($canvas, options) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
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
	
	metric_max = options.threshold_values[options.threshold_values.length-1];

	if(options.metric > metric_max)
		options.metric = metric_max;
	
	if(null != options.metric_compare && options.metric_compare > metric_max)
		options.metric_compare = metric_max;
	
	// Dial
	
	arclen = Math.PI;
	piecenter_x = options.radius + 10;
	piecenter_y = options.radius + 5;

	area_used = 0;
	
	for(idx in options.threshold_values) {
		context.beginPath();
		context.moveTo(piecenter_x, piecenter_y);
		context.fillStyle = options.threshold_colors[idx];
		//context.strokeStyle = context.fillStyle;
		partlen = Math.PI * ((options.threshold_values[idx]-area_used)/metric_max);
		area_used += options.threshold_values[idx]-area_used;
		context.arc(piecenter_x, piecenter_y, options.radius, arclen, arclen + partlen, false);
		context.lineTo(piecenter_x, piecenter_y);
		context.fill();
		//context.stroke();
		arclen += partlen;
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
 	theta = (Math.PI/metric_max) * options.metric;
 	context.rotate(theta);
 	context.beginPath();
 	context.fillStyle = 'black';
 	context.moveTo(3,6);
 	context.lineTo(3,-6);
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
	measure = context.measureText(options.metric_label);
 	context.fillText(options.metric_label, piecenter_x-(measure.width/2), piecenter_y+10);		
}

function drawPieChart($canvas, options) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
	chart_width = canvas.width;
	chart_height = canvas.height;

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
	
	arclen = 2 * Math.PI;
	piecenter_x = Math.floor(chart_width/2);
	piecenter_y = Math.floor(chart_height/2);

	area_sum = 0;
	area_used = 0;
	
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
 	
}

function drawChart($canvas, params) {
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
	
	// Loop through multiple series
	for(series_idx in params.series) {
		series = params.series[series_idx];
		
		if(null == series.data)
			continue;
		
		if(null == series.options.line_color)
			params.series[series_idx].options.line_color = 'rgba(5,141,199,1)';
		
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

		context.beginPath();
		context.strokeStyle = series.options.line_color;
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
		
	} // end series
}

function drawBarGraph($canvas, options) {
	try {
		canvas = $canvas.get(0);
		context = canvas.getContext('2d');
		
		default_colors = ['#455460','#6BA81E','#F9BE28','#D23E2E','#DDDDDD','#F67A3A','#D9E14B','#BBBBBB','#5896C3','#55C022','#8FB933'];
		
		if(null == options.series[0].data)
			return;
		
		// [TODO] This should make sure all series are the same length
		count = options.series[0].data.length;
	
		chart_width = canvas.width;
		chart_height = canvas.height - 1;
		
		stack_data = [];
		
		highest_stack = 0;
		lowest_stack = 0;

		// Build the series metadata
		for(idx=0; idx < count; idx++) {
			stack_data[idx] = { total: 0, pos: 0, neg: 0, pos_drawn: 0, neg_drawn: 0 };
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(null == series.data || 0 == series.data.length)
					continue;
				
				val = series.data[idx].y;
				
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
		
		for(series_idx in options.series) {
			series = options.series[series_idx];
	
			if(null == series.data || 0 == series.data.length)
				continue;
			
			if(null != series.options.color)
				color = series.options.color;
			else if(null != default_colors[series_idx])
				color = default_colors[series_idx];
			else
				color = '#455460';
	
			x = 0;
			
			for(idx in series.data) {
				try {
					context.fillStyle = color;
					
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
	} catch(e) {
		//console.log(e);
	}
}

function drawScatterplot($canvas, options) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
	margin = 5;
	chart_width = canvas.width - (2 * margin);
	chart_height = canvas.height - (2 * margin);

	// Stats for the entire dataset
	
	stats = {
		x_min: Number.MAX_VALUE,
		x_max: Number.MIN_VALUE,
		y_min: Number.MAX_VALUE,
		y_max: Number.MIN_VALUE,
		x_range: 0,
		y_range: 0
	}
	
	for(series_idx in options.series) {
		series = options.series[series_idx];
		
		if(null == series.data)
			continue;
		
		for(idx in series.data) {
			data = series.data[idx];
			x = data.x;
			y = data.y;
			
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
		series = options.series[series_idx];
		
		if(null == series.data)
			continue;
		
		minmax = {
			x_min: Number.MAX_VALUE,
			x_max: Number.MIN_VALUE,
			y_min: Number.MAX_VALUE,
			y_max: Number.MIN_VALUE,
			x_range: 0,
			y_range: 0
		}
		
		for(idx in series.data) {
			data = series.data[idx];
			x = data.x;
			y = data.y;

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
	
	// Plot
	
	for(series_idx in options.series) {
		series = options.series[series_idx];

		if(series.data == null)
			continue;
		
		if(options.axes_independent) {
			stat = series_stats[series_idx];
		} else {
			stat = stats;
		}
		
		xaxis_tick = chart_width / stat.x_range;
		yaxis_tick = chart_height / stat.y_range;
		
		context.fillStyle = series.options.color;
		context.strokeStyle = series.options.color;
		
		for(idx in series.data) {
			data = series.data[idx];
			x = data.x - stat.x_min;
			y = data.y - stat.y_min;
			
			chart_x = (xaxis_tick * x) + margin;
			chart_y = chart_height - (yaxis_tick * y) + margin;
			
			if(series_idx == 1) {
				label = '+';
				
			} else if(series_idx == 2) {
				label = 'x';
				
			} else if(series_idx == 3) {
				label = '*';
				
			} else {
				label = 'o';
				
			}
			
			context.font = '12px Courier';
			measure = context.measureText(label);
			
			context.beginPath();
			context.fillText(label, chart_x-measure.width/2, chart_y+measure.width/2);
			context.fill();
			
			/*
			context.beginPath();
			context.arc(chart_x, chart_y, 2.5, 0, 2 * Math.PI, false);
			context.stroke();
			*/
		}
	}
}
</script>

<form id="frmAddWidget{$workspace_tab->id}" action="#">
<button type="button" class="add_widget"><span class="cerb-sprite2 sprite-plus-circle"></span> Add Widget</button>
</form>

<table cellpadding="0" cellspacing="0" border="0" width="100%" id="dashboard{$workspace_tab->id}">
	<tr>
		<td width="33%" valign="top" class="column">
			{foreach from=$columns.0 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="34%" valign="top" class="column">
			{foreach from=$columns.1 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="33%" valign="top" class="column">
			{foreach from=$columns.2 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
	</tr>
</table>

<script type="text/javascript">
	$frm = $('#frmAddWidget{$workspace_tab->id} button.add_widget').click(function(e) {
		$popup = genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id=0&workspace_tab_id={$workspace_tab->id}',null,false,'500');
		$popup.one('new_widget', function(e) {
			if(null == e.widget_id)
				return;
			
			var widget_id = e.widget_id;
			
			// Create the widget DOM
			$new_widget = $('<div class="dashboard-widget" id="widget' + widget_id + '"></div>');
			
			// Append it to the first column
			$dashboard = $('#dashboard{$workspace_tab->id}');
			
			$dashboard.find('tr td:first').prepend($new_widget);
			
			// Redraw
			genericAjaxGet('widget' + widget_id,'c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id=' + widget_id);
			genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id=' + widget_id,null,false,'550');
			
			// Save new order
			$dashboard.trigger('reorder');
		});
	});
	
	var dragTimer = null;
	
	var $dashboard = $('#dashboard{$workspace_tab->id}');
	
	// Reusable hover events
	$dashboard.on('mouseover mouseout', 'div.dashboard-widget', 
		function(e) {
			if(e.type=='mouseover') {
				$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').show();
				$(this).trigger('widget-hover');
			} else {
				$(this).find('div.dashboard-widget-title > div.toolbar, canvas.overlay').hide();
				$(this).trigger('widget-unhover');
			}
		}
	);
	
	$dashboard.bind('reorder', function(e) {
		$dashboard = $(this);
		
		// [TODO] Number of columns
		$col1 = $dashboard.find('TR > TD:nth(0)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		$col2 = $dashboard.find('TR > TD:nth(1)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		$col3 = $dashboard.find('TR > TD:nth(2)').find('input:hidden[name="widget_pos[]"]').map(function() { return $(this).val(); }).get().join(',');
		
		widget_positions = '&column[]=' + $col1 + '&column[]=' + $col2 + '&column[]=' + $col3;

		genericAjaxGet('', 'c=internal&a=handleSectionAction&section=dashboards&action=setWidgetPositions&workspace_tab_id={$workspace_tab->id}' + widget_positions)
	});
	
	$dashboard.find('td.column').sortable({
		'items': 'div.dashboard-widget',
		'handle': 'div.dashboard-widget-title',
		'distance': 20,
		'placeholder': 'ui-state-highlight',
		'forcePlaceholderSize': true,
		'tolerance': 'pointer',
		'cursorAt': { 'top':0, 'left':0 },
		'connectWith': 'table#dashboard{$workspace_tab->id} td.column',
		'update':function(e) {
			$('table#dashboard{$workspace_tab->id}').trigger('reorder');
		}
	});
</script>