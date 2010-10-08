<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h2>{$translate->_('reports.ui.ticket.oldest_open')}</h2>

<form action="{devblocks_url}c=reports&report=report.tickets.oldest_open_tickets{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
{$translate->_('reports.ui.created_from')} <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
{$translate->_('reports.ui.date_to')} <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
<div id="divCal"></div>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>{$active_worker->name}
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="$('#start').val('Jan 1 {$year}');$('#end').val('Dec 31 {$year} 23:59:59');$('#btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}
<br>

{if !empty($oldest_tickets)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$oldest_tickets key=group_id item=group_tickets}
		{if !empty($group_tickets)}
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$groups.$group_id->name}</h2></td>
			</tr>
			
			{foreach from=$group_tickets item=ticket_entry}
				<tr>
					<td style="padding-left:10px;padding-right:20px;"><a href="{devblocks_url}c=display&id={$ticket_entry->mask}{/devblocks_url}">{$ticket_entry->mask}</a></td>
					<td><a href="{devblocks_url}c=display&id={$ticket_entry->mask}{/devblocks_url}">{$ticket_entry->subject}</a></td>
					<td>{$ticket_entry->created_date|date_format:"%Y-%m-%d"}</td>
				</tr>
			{/foreach}
		{/if}
	{/foreach}
	</table>
{/if}

