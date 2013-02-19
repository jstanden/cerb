<fieldset class="peek">
<legend>{$translate->_('reports.ui.virtual_attendant.usage')}</legend>

<form action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=report.virtual_attendants.usage{/devblocks_url}" method="POST" id="frmRange">
<input type="hidden" name="c" value="reports">
<b>{$translate->_('reports.ui.date_from')}</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>{$translate->_('reports.ui.date_to')}</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>

<b>Sort by:</b> 
<select name="sort_by">
	<option value="uses" {if $sort_by=='uses'}selected="selected"{/if}>Uses</option>
	<option value="avg_elapsed_ms" {if $sort_by=='avg_elapsed_ms'}selected="selected"{/if}>Runtime (avg)</option>
	<option value="elapsed_ms" {if $sort_by=='elapsed_ms'}selected="selected"{/if}>Runtime (total)</option>
	<option value="event" {if $sort_by=='event'}selected="selected"{/if}>Event</option>
	<option value="owner" {if $sort_by=='owner'}selected="selected"{/if}>Owner</option>
</select>

<div id="divCal"></div>

<b>{$translate->_('reports.ui.date_past')}</b> 
<a href="javascript:;" onclick="$('#start').val('big bang');$('#end').val('now');">{$translate->_('reports.ui.filters.all_time')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');">{$translate->_('common.today')|lower}</a>
<br>

<div>
	<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
</div>
</form>

<br>

<table cellpadding="5" cellspacing="0">
	<tr>
		<th># Uses</th>
		<th>Avg. Runtime</th>
		<th>Total Runtime</th>
		<th>Behavior</th>
		<th>Event</th>
		<th>Owner</th>
	</tr>
	{foreach from=$stats item=row}
	<tr style="background-color:{cycle values="#F0F0F0,#FFFFFF"};">
		<td valign="top" align="center" style="color:rgb(0,150,0);font-weight:bold;">{$row.uses}</td>
		<td valign="top"  align="center">
			{$row.avg_elapsed_ms} ms
		</td>
		<td valign="top"  align="center">
			{($row.elapsed_ms/1000)|devblocks_prettysecs}
		</td>
		<td valign="top" >
			{$row.title}
		</td>
		<td valign="top">
			{$row.event}
		</td>
		<td valign="top">
			{$row.owner}
		</td>
	</tr>
	{/foreach}
</table>

</fieldset>