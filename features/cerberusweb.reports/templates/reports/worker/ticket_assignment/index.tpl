<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.worker.ticket_assignment')}</h2>

<form action="{devblocks_url}c=reports&report=report.workers.ticket_assignment{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<br>

<!-- Chart -->

{if !empty($data)}
<div id="placeholder" style="margin:1em;width:650px;height:{20+(32*count($data))}px;"></div>

<script language="javascript" type="text/javascript">
	$(function() {
		var d = [
			{foreach from=$data item=row key=iter}
			[{$row.hits}, {$iter}],
			{/foreach}
		];
		
		var options = {
			lines: { show: false, fill: false },
			bars: { show: true, fill: true, horizontal: true, align: "center", barWidth: 1 },
			points: { show: false, fill: false },
			grid: {
				borderWidth: 0,
				horizontalLines: false,
				hoverable: false,
			},
			xaxis: {
				min: 0,
				minTickSize: 1,
				tickFormatter: function(val, axis) {
					return Math.floor(val).toString();
				},
			},
			yaxis: {
				ticks: [
					{foreach from=$data item=row key=iter}
					[{$iter},"<b>{$row.value|escape:'quotes'}</b>"],
					{/foreach}
				]
			}
		} ;
		
		$.plot($("#placeholder"), [d], options);
	} );
</script>
{/if}

<!-- Table -->

{if $invalidDate}<div><font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font></div>{/if}

<table cellspacing="0" cellpadding="2" border="0">
{foreach from=$ticket_assignments item=assigned_tickets key=worker_id}
	<tr>
		<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$workers.$worker_id->first_name} {$workers.$worker_id->last_name}</h2></td>
	</tr>

	{foreach from=$assigned_tickets item=ticket}
	<tr>
		<td style="padding-right:20px;"><a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{$ticket->mask}</a></td>
		<td align="left"><a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{$ticket->subject}</a></td>
		<td>{$ticket->created_date|date_format:"%Y-%m-%d"}</td>
	</tr>
	{/foreach}
	
{/foreach}
</table>
