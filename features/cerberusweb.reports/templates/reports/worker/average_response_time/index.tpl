<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.worker.response_time')}</h2>

<div class="block">

<b>{$translate->_('reports.ui.worker.response_time.date_range')}</b>
{if $invalidDate}<font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font>{/if}

<form action="{devblocks_url}c=reports&a=report.workers.averageresponsetime{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
<input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal"></div>
</form>

<a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
<br>

<br>
<h3>{$translate->_('reports.ui.worker.response_time.worker_responses')}</h3>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$worker_responses item=responses key=worker_id}
		{if $responses.replies != 0}{math assign=response_time equation="x/y/60" x=$responses.time y=$responses.replies format="%0.1f"}
		{else}{assign var=response_time value=0}{/if}
		<tr>
			<td style="padding-right:20px;">
				<b>{$workers.$worker_id->first_name}&nbsp;{$workers.$worker_id->last_name}</b>&nbsp;&nbsp;({$workers.$worker_id->email})</b>
			</td>
			{if $response_time==0}<td valign="top"></td>
			{elseif $response_time>1440}<td valign="top">{math equation="x/1440" x=$response_time format="%0.1f"} {$translate->_('common.days')|lower}</td>
			{elseif $response_time>60}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} {$translate->_('common.hours')|lower}</td>
			{else}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} {$translate->_('common.minutes')|lower}</td>{/if}
			<td valign="top">({$responses.replies} replies)</td>
		</tr>
	{/foreach}
</table>

<br>
<h3>{$translate->_('reports.ui.worker.response_time.group_responses')}</h3>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$group_responses item=responses key=group_id}
		{if $responses.replies != 0}{math assign=response_time equation="x/y/60" x=$responses.time y=$responses.replies format="%0.1f"}
		{else}{assign var=response_time value=0}{/if}
		<tr>
			<td style="padding-right:20px;">
				<b>{$groups.$group_id->name}</b> &nbsp;
			</td>
			{if $response_time==0}<td valign="top"> &nbsp; </td>
			{elseif $response_time>1440}<td valign="top">{math equation="x/1440" x=$response_time format="%0.1f"} {$translate->_('common.days')|lower} &nbsp; </td>
			{elseif $response_time>60}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} {$translate->_('common.hours')|lower} &nbsp; </td>
			{else}<td valign="top">{math equation="x/60" x=$response_time format="%0.1f"} {$translate->_('common.minutes')|lower} &nbsp; </td>{/if}
			<td valign="top">({$responses.replies} replies)</td>
		</tr>
	{/foreach}
</table>

</div>

