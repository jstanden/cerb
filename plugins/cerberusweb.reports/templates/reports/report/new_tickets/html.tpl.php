<b>Date Range:</b>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.tickets.new_tickets">
<input type="hidden" name="extid_a" value="getNewTicketsReport">
<input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'reportNewTickets')">Refresh</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
</form>

<a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">1 year</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">6 months</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">3 months</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">1 month</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">1 week</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">1 day</a>
<br>
<br>

<img src="{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.new_tickets&extid_a=drawTicketGraph&start={$start}&end={$end}{/devblocks_url}" style="border:1px solid rgb(200,200,200);margin:5px;padding:5px;">	<br>
<br>

{if !empty($group_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$groups key=group_id item=group}
		{assign var=counts value=$group_counts.$group_id}
		{if !empty($counts.total)}
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$groups.$group_id->name}</h2></td>
			</tr>
			
			{if !empty($counts.0)}
			<tr>
				<td style="padding-left:10px;padding-right:20px;">Inbox</td>
				<td align="right">{$counts.0}</td>
				<td></td>
			</tr>
			{/if}
			
			{foreach from=$group_buckets.$group_id key=bucket_id item=b}
				{if !empty($counts.$bucket_id)}
				<tr>
					<td style="padding-left:10px;padding-right:20px;">{$b->name}</td>
					<td align="right">{$counts.$bucket_id}</td>
					<td></td>
				</tr>
				{/if}
			{/foreach}

			<tr>
				<td></td>						
				<td align="right" style="border-top:1px solid rgb(200,200,200);"><b>{$counts.total}</b></td>
				<td style="padding-left:10px;">{*<b>(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/{if $age_term=='d'}day{else}mo{/if})</b>*}</td>
			</tr>
		{/if}
	{/foreach}
	</table>
{/if}

