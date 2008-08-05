<b>Date Range:</b>
{if $invalidDate}<font color="red"><b>Invalid Date specified.  Please try again.</b></font>{/if}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.tickets.worker_replies">
<input type="hidden" name="extid_a" value="getWorkerRepliesReport">
<input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'reportWorkerReplies')">Refresh</button>
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

<img src="{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.worker_replies&extid_a=drawRepliesGraph&start={$start}&end={$end}{/devblocks_url}" style="border:1px solid rgb(200,200,200);margin:5px;padding:5px;">
<br>

{if !empty($worker_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		
		<tr>
			<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);"><h2>{$workers.$worker_id->getName()}</h2></td>
		</tr>
		
		{foreach from=$counts item=team_hits key=team_id}
			{if is_numeric($team_id)}
			<tr>
				<td style="padding-right:20px;">{$groups.$team_id->name}</td>
				<td align="right">{$team_hits}</td>
				<td></td>
			</tr>
			{/if}
		{/foreach}
		
		<tr>
			<td></td>
			<td style="border-top:1px solid rgb(200,200,200);" align="right"><b>{$counts.total}</b></td>
			<td style="padding-left:10px;"><b>(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/day)</b></td>
		</tr>
		
		{/if}
	{/foreach}
	</table>
{/if}


