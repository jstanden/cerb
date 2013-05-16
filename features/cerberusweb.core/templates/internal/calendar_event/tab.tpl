{$guid = uniqid()}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_date|devblocks_date:'F Y'}</span>
		<span style="margin-left:10px;">
			<button type="button" class="create_event"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
		</span>
	</div>

	<div style="float:right;">
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarTab&id={$calendar->id}&month={$prev_month}&year={$prev_year}');">&lt;</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarTab&id={$calendar->id}&month={$month}&year={$year}');">Today</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarTab&id={$calendar->id}&month={$next_month}&year={$next_year}');">&gt;</button>
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
						<div class="event event{$event.id}{if $event.is_available} available{/if}" event_id="{$event.id}"><a href="javascript:;">{$event.name}</a></div>
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

$openEvtPopupEvent = function(e) {
	$this = $(this);
	
	if($this.is('button')) {
		event_id = 0;	
	} else if($this.is('div.event')) {
		event_id = $this.attr('event_id');
	}

	$popup = genericAjaxPopup('event','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_CALENDAR_EVENT}&context_id=' + event_id + '&calendar_id={$calendar->id}',null,false,'600');
	
	$popup.one('calendar_event_save', function(event) {
		if(event.month && event.year) {
			genericAjaxGet($('#frm{$guid}').closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarTab&id={$calendar->id}&month=' + event.month + '&year=' + event.year);
		}
		
		event.stopPropagation();
	});
	
	$popup.one('calendar_event_delete', function(event) {
		$frm = $('#frm{$guid}');
		$tab = $frm.closest('div.ui-tabs-panel');
		
		if(event.event_id) {
			$tab.find('TABLE.calendar TR.week div.day_contents div.event' + event.event_id).remove();
		}
		
		event.stopPropagation();
	});
}

$frm.find('button.create_event').click($openEvtPopupEvent);
$tab.find('TABLE.calendar TR.week div.day_contents').find('div.event').click($openEvtPopupEvent);
</script>