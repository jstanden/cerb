<fieldset class="peek" style="background:none;">
	<legend>{'example.report'|devblocks_translate|capitalize}</legend>

	<form id="frmExampleReport" action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=example.report{/devblocks_url}" method="POST">
		&nbsp; 
		Tickets created between: 
		<input type="text" name="date_from" size="24" value="{$date_from}">
		 and 
		<input type="text" name="date_to" size="24" value="{$date_to}">
		 in 
		<select name="group_id">
			<option value="" {if empty($group_id)}selected="selected"{/if}>- any group -</option>
			{foreach from=$groups item=group key=k}
			<option value="{$k}" {if $group_id==$k}selected="selected"{/if}>{$group->name}</option>
			{/foreach}
		</select>
		<button type="submit">Update</button>
	</form>	
</fieldset>

<canvas id="example_chart" width="500" height="200">
	Your browser does not support HTML5 Canvas.
</canvas>

<script type="text/javascript">
	$canvas = $('#example_chart');
	context = $canvas.get(0).getContext('2d');

	{if !empty($ticket_stats) && $ticket_stats.total}
	// Pie chart
	colors = ['#6BA81E', '#F9B326', '#455460'];
	arclen = 0;
	radius = 90;
	piecenter_x = radius + 5;
	piecenter_y = radius + 5;

	context.beginPath();
	context.moveTo(piecenter_x, piecenter_y);
	context.fillStyle = colors[0];
	partlen = (2 * Math.PI * {$ticket_stats.open/$ticket_stats.total});
	context.arc(piecenter_x, piecenter_y, radius, arclen, arclen + partlen, false);
	context.lineTo(piecenter_x, piecenter_y);
	context.fill();
	arclen += partlen;
	
	context.beginPath();
	context.moveTo(piecenter_x, piecenter_y);
	context.fillStyle = colors[1];
	partlen = (2 * Math.PI * {$ticket_stats.waiting/$ticket_stats.total});
	context.arc(piecenter_x, piecenter_y, radius, arclen, arclen + partlen, false);
	context.lineTo(piecenter_x, piecenter_y);
	context.fill();
	arclen += partlen;
	
	context.beginPath();
	context.moveTo(piecenter_x, piecenter_y);
	context.fillStyle = colors[2];
	partlen = (2 * Math.PI * {$ticket_stats.closed/$ticket_stats.total});
	context.arc(piecenter_x, piecenter_y, radius, arclen, arclen + partlen, false);
	context.lineTo(piecenter_x, piecenter_y);
	context.fill();
	arclen += partlen;
	
	// Legend
	context.font = 'bold 12px Verdana';
	context.textBaseline = 'top';
	legend_x = (radius * 2) + 25;
	legend_y = 10;
	
	context.fillStyle = colors[0];
	context.fillRect(legend_x,legend_y,20,20);
	legend_x += 25;
	
 	label = "{'status.open'|devblocks_translate|capitalize} ({$ticket_stats.open})";
 	context.fillStyle = 'black';
 	context.fillText(label, legend_x, legend_y);
 	legend_y += 25;
 	legend_x -= 25;
	
	context.fillStyle = colors[1];
	context.fillRect(legend_x,legend_y,20,20);
	legend_x += 25;
	
 	label = "{'status.waiting'|devblocks_translate|capitalize} ({$ticket_stats.waiting})";
 	context.fillStyle = 'black';
 	context.fillText(label, legend_x, legend_y);
 	legend_y += 25;
 	legend_x -= 25;
	
	context.fillStyle = colors[2];
	context.fillRect(legend_x,legend_y,20,20);
	legend_x += 25;
	
	label = "{'status.closed'|devblocks_translate|capitalize} ({$ticket_stats.closed})";
	context.fillStyle = 'black';
	context.fillText(label, legend_x, legend_y);
 	legend_y += 25;
 	legend_x -= 25;
 	
 	{else}
	context.font = 'bold 14px Verdana';
	context.textBaseline = 'top';
 	context.fillText('No data', 0, 0);
 	
	{/if}
</script>