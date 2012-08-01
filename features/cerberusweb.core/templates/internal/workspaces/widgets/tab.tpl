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

function drawChart($canvas, params) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
	chart_top = 10;
	
	chart_width = 325;
	chart_height = canvas.height - chart_top;

	max_value = 0;

	// Find the max y-value across every series

	for(series_idx in params.series) {
		for(idx in params.series[series_idx].data) {
			value = params.series[series_idx].data[idx][1];
			if(value > max_value)
				max_value = value;
		}
	}
	
	// Loop through multiple series
	
	for(series_idx in params.series) {
		series = params.series[series_idx];
		
		if(null == series.options.line_color)
			params.series[series_idx].options.line_color = 'rgba(5,141,199,1)';
		
		if(null == series.options.fill_color)
			params.series[series_idx].options.fill_color = 'rgba(5,141,199,0.1)';
		
		context.beginPath();
		context.fillStyle = series.options.fill_color;

		x = 0;
		y = 0;
		tick = 0;
		
		context.moveTo(0, canvas.height);
		
		count = series.data.length;
		xtick_width = chart_width / (count-1);
		ytick_height = chart_height / max_value;
	
		// Fill
		
		//if(0 == series_idx) {
			for(idx in series.data) {
				value = series.data[idx][1];
				x = tick;
				y = chart_height - (ytick_height * value) + chart_top;
				context.lineTo(x, y);
				tick += xtick_width;
			}
			
			context.lineTo(x, canvas.height);
			
			context.fill();
		//}

		// Stroke

		context.beginPath();
		context.strokeStyle = series.options.line_color;
		//context.lineWidth = (0 == series_idx) ? 3 : 2;
		context.lineWidth = 3;
		
		tick = 0;
		
		for(idx in series.data) {
			value = series.data[idx][1];
			x = tick;
			y = chart_height - (ytick_height * value) + chart_top - (context.lineWidth/2);
			context.lineTo(tick, y);
			tick += xtick_width;
		}

		context.stroke();
		
		// Dots
		
		if(count < 50) {
			tick = 0;
			
			context.fillStyle = series.options.line_color;
			context.strokeStyle = series.options.line_color;
			
			for(idx in series.data) {
				context.beginPath();
				
				value = series.data[idx][1];
				
				if(value > 0) {
					x = tick;
					y = chart_height - (ytick_height * value) + chart_top - (context.lineWidth/2 + 1.25);
					
				 	context.arc(x, y, 2.5, 0, 2 * Math.PI, false);
				 	context.fill();
				 	context.stroke();
				}
				
				tick += xtick_width;
			}
		}
		
	} // end series
}

function drawBarGraph($canvas, options) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
	default_colors = ['#455460','#6BA81E','#F9BE28','#D23E2E','#DDDDDD','#F67A3A','#D9E14B','#BBBBBB','#5896C3','#55C022','#8FB933'];
	
	// [TODO] This should make sure all series are the same length
	count = options.series[0].data.length;

	chart_width = canvas.width;
	chart_height = canvas.height;
	
	series_heights = [];
	stack_heights = [];
	max_value = 0;
	
	// Find the highest y-value of each series
	for(idx=0; idx < count; idx++) {
		stack_heights[idx] = 0;
		series_heights[idx] = 0;
		
		for(series_idx in options.series) {
			series = options.series[series_idx];
			
			if(null == series.data || 0 == series.data.length)
				continue;
			
			series_heights[idx] += series.data[idx][1];
		}
		
		// Find max height across all series
		if(series_heights[idx] > max_value)
			max_value = series_heights[idx];
	}
	
	xtick_width = Math.floor(chart_width / count);
	ytick_height = chart_height / max_value;

	context.lineWidth = 1;
	half_width = context.lineWidth/2;
	
	for(series_idx in options.series) {
		series = options.series[series_idx];

		if(0 == series.data.length)
			continue;
		
		if(null != series.options.color)
			color = series.options.color;
		else if(null != default_colors[series_idx])
			color = default_colors[series_idx];
		else
			color = '#455460';

		x = 0;
		
		for(idx in series.data) {
			context.fillStyle = color;
			
			value = series.data[idx][1];

			if(0 == value) {
				x = Math.round(x + xtick_width);
				continue;
			}
			
			bar_floor = Math.floor(ytick_height * stack_heights[idx]);
			
			//x = Math.round(idx * xtick_width);
			y = Math.floor(chart_height - (ytick_height * value) - bar_floor);
			
			context.beginPath();
			context.moveTo(x, chart_height - bar_floor);
			context.lineTo(x, y);
			
			x = Math.floor(x + xtick_width);
			
			context.lineTo(x-1, y);
			context.lineTo(x-1, chart_height - bar_floor);
			context.fill();
			
			stack_heights[idx] += value;
		}
	}
}

function drawScatterplot($canvas, options) {
	canvas = $canvas.get(0);
	context = canvas.getContext('2d');
	
	//default_colors = ['#455460','#6BA81E','#F9BE28','#D23E2E','#DDDDDD','#F67A3A','#D9E14B','#BBBBBB','#5896C3','#55C022','#8FB933'];
	//default_colors = ['#D23E2E','#6BA81E'];
	
	margin = 5;
	chart_width = canvas.width - (2 * margin);
	chart_height = canvas.height - (2 * margin);

	// Find the min/max values for each axis
	
	x_min = Number.MAX_VALUE;
	x_max = Number.MIN_VALUE;
	y_min = Number.MAX_VALUE;
	y_max = Number.MIN_VALUE;
	
	for(series_idx in options.series) {
		series = options.series[series_idx];
		
		for(idx in series.data) {
			data = series.data[idx];
			x = data[0];
			y = data[1];
			
			x_min = Math.min(x_min,x);
			x_max = Math.max(x_max,x);
			y_min = Math.min(y_min,y);
			y_max = Math.max(y_max,y);
		}		
	}

	/*
	 * [TODO] This could support different scales per series
	 * [TODO] This could also support sets where min != 0 by calculating max-min and subtracting min from all values
	 */
	xaxis_tick = chart_width / x_max; 
	yaxis_tick = chart_height / y_max; 
	
	// Plot
	
	for(series_idx in options.series) {
		series = options.series[series_idx];
		context.fillStyle = series.options.color;
		context.strokeStyle = series.options.color;
		
		for(idx in series.data) {
			data = series.data[idx];
			x = data[0];
			y = data[1];
			
			chart_x = (xaxis_tick * x) + margin;
			chart_y = chart_height - (yaxis_tick * y) + margin;
			
			context.beginPath();
			context.arc(chart_x, chart_y, 2.5, 0, 2 * Math.PI, false);
			//context.fill();
			context.stroke();
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
				<input type="hidden" name="widget_pos[]" value="{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="34%" valign="top" class="column">
			{foreach from=$columns.1 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				<input type="hidden" name="widget_pos[]" value="{$widget_id}">
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			</div>
			{/foreach}
		</td>
		<td width="33%" valign="top" class="column">
			{foreach from=$columns.2 item=widget key=widget_id}
			<div class="dashboard-widget" id="widget{$widget_id}">
				<input type="hidden" name="widget_pos[]" value="{$widget_id}">
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
			$('#dashboard{$workspace_tab->id} tr td:first').append($new_widget);

			// Redraw
			genericAjaxGet('widget' + widget_id,'c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id=' + widget_id);
			genericAjaxPopup('widget_edit','c=internal&a=handleSectionAction&section=dashboards&action=showWidgetPopup&widget_id=' + widget_id,null,false,'500');
		});
	});
	
	var dragTimer = null;
	
	$('table#dashboard{$workspace_tab->id} td.column').sortable({
		'items': 'div.dashboard-widget',
		'handle': 'div.dashboard-widget-title',
		'placeholder': 'ui-state-highlight',
		'forcePlaceholderSize': true,
		'tolerance': 'pointer',
		'cursorAt': { 'top':0, 'left':0 },
		'connectWith': 'table#dashboard{$workspace_tab->id} td.column',
		'update':function(e) {
			/*
			if(typeof dragTimer =="number") {
				window.clearTimeout(dragTimer);
				delete dragTimer;
			}
			*/
			
			//dragTimer = window.setTimeout(function(e) {
				// [TODO] Multiple dashboards in tabs
				$dashboard = $('TABLE#dashboard{$workspace_tab->id}').first();
				
				// [TODO] Number of columns
				$col1 = $dashboard.find('TR > TD:nth(0)').find('input:hidden').map(function() { return $(this).val(); }).get().join(',');
				$col2 = $dashboard.find('TR > TD:nth(1)').find('input:hidden').map(function() { return $(this).val(); }).get().join(',');
				$col3 = $dashboard.find('TR > TD:nth(2)').find('input:hidden').map(function() { return $(this).val(); }).get().join(',');
				
				widget_positions = '&column[]=' + $col1 + '&column[]=' + $col2 + '&column[]=' + $col3;

				genericAjaxGet('', 'c=internal&a=handleSectionAction&section=dashboards&action=setWidgetPositions&workspace_tab_id={$workspace_tab->id}' + widget_positions)
				
			//}, 500);
		}
	});

	$('canvas').click(function(e) {
		//console.log(e.offsetX + ',' + e.offsetY);
	});
</script>