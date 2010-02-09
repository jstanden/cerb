<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('reports.ui.worker.worker_history')}</h2>

<form action="{devblocks_url}c=reports&a=report.workers.worker_history{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
{$translate->_('reports.ui.date_from')} <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
{$translate->_('reports.ui.date_to')} <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal"></div>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="document.getElementById('start').value='Jan 1 {$year}';document.getElementById('end').value='Dec 31 {$year}';$('#btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}

<br>

{$translate->_('reports.ui.worker')} <select name="worker_id" onchange="this.form.submit();">
{foreach from=$workers item=worker key=k name=workers}
	<option value="{$k}"{if $k==$worker_id} selected{/if}>{$worker->getName()}</option>
{/foreach}
</select>
</form>


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
{foreach from=$tickets_replied item=replied_tickets key=day}
	<tr>
		<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$day}</h2></td>
	</tr>

	{foreach from=$replied_tickets item=ticket}
	<tr>
		<!--  <td style="padding-right:20px;"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->mask}</a></td> -->
		<td align="left"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->subject}</a></td>
		<td style="padding-right:20px;"><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$ticket->email|escape:'url'}&view_id=0',null,false,'500');">{$ticket->email}</a></td>
		<!-- <td>{$ticket->created_date|devblocks_date}</td>-->
	</tr>
	{/foreach}
{/foreach}
</table>
