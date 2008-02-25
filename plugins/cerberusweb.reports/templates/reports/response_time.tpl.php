<div class="block">

<h2>Average Response Time</h2>
<b>Range (days):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=365d');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=180d');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=90d');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=30d');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=7d');">7 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=1d');">past 24 hours</a>
<br>
<b>Range (months):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=12mo');">12 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=6mo');">6 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=3mo');">3 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=1mo');">1 month</a>
<br>
<b>Range (custom):</b> 
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAverageResponseTime" name="formAverageResponseTime" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="getAverageResponseTimeReport">
<input type="text" name="startART" id="startART" size="10" value="{$startART}"><button type="button" onclick="ajax.getDateChooser('dateART',this.form.startART);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<input type="text" name="endART" id="endART" size="10" value="{$endART}"><button type="button" onclick="ajax.getDateChooser('dateART',this.form.endART);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" onclick="genericAjaxPost('formAverageResponseTime', 'reportAverageResponseTime')">Refresh</button>
<div id="dateART" style="display:none;position:absolute;z-index:1;"></div>
</form>

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
