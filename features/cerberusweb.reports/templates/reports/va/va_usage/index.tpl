<fieldset class="peek">
<legend>{'reports.ui.virtual_attendant.usage'|devblocks_translate}</legend>

<form action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=report.virtual_attendants.usage{/devblocks_url}" method="POST" id="frmRange">
<input type="hidden" name="c" value="reports">
<b>{'reports.ui.date_from'|devblocks_translate}</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>{'reports.ui.date_to'|devblocks_translate}</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>

<b>Sort by:</b> 
<select name="sort_by">
	<option value="uses" {if $sort_by=='uses'}selected="selected"{/if}>Uses</option>
	<option value="avg_elapsed_ms" {if $sort_by=='avg_elapsed_ms'}selected="selected"{/if}>Runtime (avg)</option>
	<option value="elapsed_ms" {if $sort_by=='elapsed_ms'}selected="selected"{/if}>Runtime (total)</option>
	<option value="event" {if $sort_by=='event'}selected="selected"{/if}>Event</option>
	<option value="va_name" {if $sort_by=='va_name'}selected="selected"{/if}>Virtual Attendant</option>
	{*<option value="va_owner" {if $sort_by=='va_owner'}selected="selected"{/if}>Virtual Attendant Owner</option>*}
</select>

<div id="divCal"></div>

<b>{'reports.ui.date_past'|devblocks_translate}</b> 
<a href="javascript:;" onclick="$('#start').val('big bang');$('#end').val('now');">{'reports.ui.filters.all_time'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');">{'reports.ui.filters.1_year'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');">{'reports.ui.filters.1_month'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');">{'reports.ui.filters.1_week'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');">{'reports.ui.filters.1_day'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');">{'common.today'|devblocks_translate|lower}</a>
<br>

<div>
	<button type="submit" id="btnSubmit">{'reports.common.run_report'|devblocks_translate|capitalize}</button>
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
		<th>Virtual Attendant</th>
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
			<a href="{devblocks_url}c=profiles&w=virtual_attendant&id={$row.va_id}-{$row.va_name|devblocks_permalink}{/devblocks_url}" title="{$row.va_owner}">{$row.va_name}</span>
		</td>
	</tr>
	{/foreach}
</table>

</fieldset>