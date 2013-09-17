<fieldset class="peek">
<legend>{'reports.ui.snippets.popularity'|devblocks_translate}</legend>

<form action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=report.snippets.popularity{/devblocks_url}" method="POST" id="frmRange">
<input type="hidden" name="c" value="reports">
<b>{'reports.ui.date_from'|devblocks_translate}</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>{'reports.ui.date_to'|devblocks_translate}</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>

{$limits = [25,50,100,250,500,1000]}
<b>Limit:</b> 
<select name="limit">
	<option value="">show all</option>
	{foreach from=$limits item=limit_to}
	<option value="{$limit_to}" {if $limit_to==$limit}selected="selected"{/if}>{$limit_to}</option>
	{/foreach}
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

<b>{'reports.ui.filters.worker'|devblocks_translate}</b> 
<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
{if is_array($filter_worker_ids) && !empty($filter_worker_ids)}
<ul class="chooser-container bubbles">
	{foreach from=$filter_worker_ids item=filter_worker_id}
	{$filter_worker = $workers.{$filter_worker_id}}
	{if !empty($filter_worker)}
	<li>{$filter_worker->getName()}<input type="hidden" name="worker_id[]" value="{$filter_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
	{/if}
	{/foreach}
</ul>
{/if}

<div>
	<button type="submit" id="btnSubmit">{'reports.common.run_report'|devblocks_translate|capitalize}</button>
</div>
</form>

<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td width="50%" align="center" valign="top">
			<h3>Most popular</h3>
			<table cellpadding="5" cellspacing="0">
				{foreach from=$most_popular item=row}
				<tr style="background-color:{cycle values="#F0F0F0,#FFFFFF"};">
					<td align="center" style="color:rgb(0,150,0);font-weight:bold;">{$row.snippet_uses}</td>
					<td>{$row.snippet_title}</td>
				</tr>
				{/foreach}
			</table>
		</td>
		<td width="50%" align="center" style="padding-left:30px;" valign="top">
			<h3>Least popular</h3>
			<table cellpadding="5" cellspacing="0">
				{foreach from=$least_popular item=row}
				<tr style="background-color:{cycle values="#F0F0F0,#FFFFFF"};">
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$row.snippet_uses}</td>
					<td>{$row.snippet_title}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>

</fieldset>

<script type="text/javascript">
	$('#frmRange button.chooser_worker').each(function(event) {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
</script>