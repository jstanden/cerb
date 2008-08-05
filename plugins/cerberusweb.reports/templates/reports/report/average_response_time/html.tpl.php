<div class="block">

<b>Date Range:</b>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.workers.averageresponsetime">
<input type="hidden" name="extid_a" value="getAverageResponseTimeReport">
<input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'reportAverageResponseTime')">Refresh</button>
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
<h3>Worker Responses</h3>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$worker_responses item=responses key=worker_id}
		{if $responses.replies != 0}{math assign=response_time equation="x/y/60" x=$responses.time y=$responses.replies format="%0.1f"}
		{else}{assign var=response_time value=0}{/if}
		<tr>
			<td style="padding-right:20px;">
				<b>{$workers.$worker_id->first_name}&nbsp;{$workers.$worker_id->last_name}</b>&nbsp;&nbsp;({$workers.$worker_id->email})</b>
			</td>
			{if $response_time==0}<td valign="top"></td>
			{elseif $response_time>1440}<td valign="top">{math equation="x/1440" x=$response_time format="%0.1f"} days</td>
			{elseif $response_time>60}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} hours</td>
			{else}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} minutes</td>{/if}
			<td valign="top">({$responses.replies} replies)</td>
		</tr>
	{/foreach}
</table>

<br>
<h3>Group Responses</h3>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$group_responses item=responses key=group_id}
		{if $responses.replies != 0}{math assign=response_time equation="x/y/60" x=$responses.time y=$responses.replies format="%0.1f"}
		{else}{assign var=response_time value=0}{/if}
		<tr>
			<td style="padding-right:20px;">
				<b>{$groups.$group_id->name}</b> &nbsp;
			</td>
			{if $response_time==0}<td valign="top"> &nbsp; </td>
			{elseif $response_time>1440}<td valign="top">{math equation="x/1440" x=$response_time format="%0.1f"} days &nbsp; </td>
			{elseif $response_time>60}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} hours &nbsp; </td>
			{else}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} minutes &nbsp; </td>{/if}
			<td valign="top">({$responses.replies} replies)</td>
		</tr>
	{/foreach}
</table>

</div>


