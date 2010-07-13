<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('timetracking.ui.reports.time_spent_org')}</h2>

<form action="{devblocks_url}c=reports&report=report.timetracking.timespentorg{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
{$translate->_('timetracking.ui.reports.from')} <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
{$translate->_('timetracking.ui.reports.to')} <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal"></div>
</form>

{$translate->_('timetracking.ui.reports.past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>

{if $invalidDate}<div><font color="red"><b>{$translate->_('timetracking.ui.reports.invalid_date')}</b></font></div>{/if}

<!-- Chart -->

{if !empty($data)}
<div id="placeholder" style="margin:1em;width:650px;height:{20+(32*count($data))}px;"></div>

<script language="javascript" type="text/javascript">
	$(function() {
		var d = [
			{foreach from=$data item=row key=iter name=iters}
			[{$row.mins}, {$iter}]{if !$smarty.foreach.iters.last},{/if}
			{/foreach}
		];
		
		var options = {
			lines: { show: false, fill: false },
			bars: { show: true, fill: true, horizontal: true, align: "center", barWidth: 1 },
			points: { show: false, fill: false },
			grid: {
				borderWidth: 0,
				horizontalLines: false,
				hoverable: false
			},
			xaxis: {
				min: 0,
				minTickSize: 1,
				tickFormatter: function(val, axis) {
					return Math.floor(val).toString();
				}
			},
			yaxis: {
				ticks: [
					{foreach from=$data item=row key=iter name=iters}
					[{$iter},"<b>{$row.value|escape}</b>"]{if !$smarty.foreach.iters.last},{/if}
					{/foreach}
				]
			}
		} ;
		
		$.plot($("#placeholder"), [d], options);
	} );
</script>
{/if}

<!-- Table -->

{if !empty($time_entries)}
		{foreach from=$time_entries item=org_entry key=org_id}
		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="2">
				<h2>
				  {if empty($org_entry.org_name)}
				  {$translate->_('timetracking.ui.reports.time_spent_org.no_organization')}
				  {else}
				  {$org_entry.org_name}
				  {/if}
				</h2>
				<span style="margin-bottom:10px;"><b>{$org_entry.total_mins} {$translate->_('common.minutes')|lower}</b></span>
				</td>
			</tr>
			{foreach from=$org_entry.entries item=time_entry key=time_entry_id}
				{if is_numeric($time_entry_id)}
					{assign var=entry_worker_id value=$time_entry.worker_id}
					{assign var=source_ext_id value=$time_entry.source_extension_id}
					{assign var=source_id value=$time_entry.source_id}
					{assign var=worker_name value=$workers.$entry_worker_id->getName()}
					{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}
					
					{if isset($worker_name)}
						{assign var=worker_name value=$worker_name}
					{else}
						{assign var=worker_name value=$generic_worker}
					{/if}					
					<tr>
						<td>{$time_entry.log_date|date_format:"%Y-%m-%d"}</td>
						<td>
							{assign var=tagged_worker_name value="<B>"|cat:$worker_name|cat:"</B>"}
							{assign var=tagged_mins value="<B>"|cat:$time_entry.mins|cat:"</B>"}
							{assign var=tagged_activity value="<B>"|cat:$time_entry.activity_name|cat:"</B>"}
					
							{'timetracking.ui.tracked_desc'|devblocks_translate:$tagged_worker_name:$tagged_mins:$tagged_activity}

							{if !empty($source_ext_id)}
								{assign var=source value=$sources.$source_ext_id}
								{if !empty($source)}<small>(<a href="{$source->getLink($source_id)}">{$source->getLinkText($source_id)}</a>)</small>{/if}
							{/if}
						</td>
					</tr>
					{if !empty($time_entry.notes)}
					<tr>
						<td></td>
						<td><i>{$time_entry.notes}</i></td>
					</tr>
					{/if}
				{/if}
			{/foreach}
			</table>
			</div>
			<br>
	{/foreach}
{/if}

