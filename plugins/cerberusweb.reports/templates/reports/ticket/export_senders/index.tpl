<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('reports.ui.ticket.export_senders')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.tickets.export_senders">
<input type="hidden" name="extid_a" value="getExportSendersReport">
{$translate->_('reports.ui.date_from')} <input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
{$translate->_('reports.ui.date_to')} <input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'report');">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
<br>
{$translate->_('common.bucket')|capitalize}: <select name="inbox_id" onchange="genericAjaxPost('frmRange', 'report');">
 		<option value="">-- {$translate->_('common.all')|capitalize} --</option>
 		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
 		<optgroup label="Inboxes">
 		{foreach from=$teams item=team}
 			<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (*){/if}</option>
 		{/foreach}
 		</optgroup>
 		{foreach from=$team_categories item=categories key=teamId}
 			{assign var=team value=$teams.$teamId}
 			<optgroup label="-- {$team->name} --">
 			{foreach from=$categories item=category}
			<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
		{/foreach}
		</optgroup>
		{/foreach}
 	</select>

</form>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>{$active_worker->name}
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="document.getElementById('start').value='Jan 1 {$year}';document.getElementById('end').value='Dec 31 {$year}';document.getElementById('btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}
<br>

<div id="report"></div>
<script language="javascript" type="text/javascript">
{literal}	
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
