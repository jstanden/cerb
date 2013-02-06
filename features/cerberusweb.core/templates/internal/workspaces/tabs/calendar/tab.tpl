{if empty($workspace_tab->params.context_extid)}

<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Configure the calendar</h1>
	
	<p>
		Click the <button type="button" class="edit-tab toolbar-item"><span class="cerb-sprite2 sprite-ui-tab-gear"></span> Edit Tab</button> button.
	</p>
</div>

{else}

{$guid = uniqid()}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_date|devblocks_date:'F Y'}</span>
		<span style="margin-left:10px;">
		</span>
	</div>
	
	<div style="float:right;">
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=pages&a=showWorkspaceTab&id={$workspace_tab->id}&month={$prev_month}&year={$prev_year}');">&lt;</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=pages&a=showWorkspaceTab&id={$workspace_tab->id}&month={$month}&year={$year}');">Today</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=pages&a=showWorkspaceTab&id={$workspace_tab->id}&month={$next_month}&year={$next_year}');">&gt;</button>
	</div>
	
	<br clear="all">
</form>

<table cellspacing="0" cellpadding="0" border="0" class="calendar">
<tr class="heading">
	<th>Sun</th>
	<th>Mon</th>
	<th>Tue</th>
	<th>Wed</th>
	<th>Thu</th>
	<th>Fri</th>
	<th>Sat</th>
</tr>
{foreach from=$calendar_weeks item=week name=weeks}
<tr class="week">
	{foreach from=$week item=day name=days}
		<td class="{if $today == $day.timestamp}today{/if}{if $day.is_padding} inactive{/if}{if $smarty.foreach.days.last} cellborder_r{/if}{if $smarty.foreach.weeks.last} cellborder_b{/if}">
			<div class="day_header">
				{if $today == $day.timestamp}
				<a href="javascript:;" onclick="">Today, {$today|devblocks_date:"M d"}</a>
				{else}
				<a href="javascript:;" onclick="">{$day.dom}</a>
				{/if}
			</div>
			<div class="day_contents">
				{if $calendar_events.{$day.timestamp}}
					{foreach from=$calendar_events.{$day.timestamp} item=event}
						<div class="event" style="background-color:{$workspace_tab->params.color|default:'#A0D95B'};"><a href="javascript:;" style="color:rgb(0,0,0);text-decoration:none;" title="{$event.label}">{$event.label}</a></div>
					{/foreach}
				{/if}
			</div>
		</td>
	{/foreach}
</tr>
{/foreach}
</table>

<script type="text/javascript">
$frm = $('#frm{$guid}');
$tab = $frm.closest('div.ui-tabs-panel');
</script>
{/if}