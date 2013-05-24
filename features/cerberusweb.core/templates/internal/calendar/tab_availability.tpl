{$guid = uniqid()}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_properties.calendar_date|devblocks_date:'F Y'}</span>
		{*
		{if empty($calendar->params.manual_disabled)}
		<span style="margin-left:10px;">
			<button type="button" class="create_event"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
		</span>
		{/if}
		*}
		{if !empty($calendar)}
		<span style="margin-left:10px;">
			Availability based on <a href="{devblocks_url}c=profiles&what=calendar&id={$calendar->id}-{$calendar->name|devblocks_permalink}{/devblocks_url}" style="font-weight:bold;">{$calendar->name}</a>
		</span>
		{/if}
	</div>

	<div style="float:right;">
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&id={$calendar->id}&month={$calendar_properties.prev_month}&year={$calendar_properties.prev_year}');">&lt;</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&id={$calendar->id}&month=&year=');">Today</button>
		<button type="button" onclick="genericAjaxGet($(this).closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&id={$calendar->id}&month={$calendar_properties.next_month}&year={$calendar_properties.next_year}');">&gt;</button>
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
{$now = strtotime('now')}
{foreach from=$calendar_properties.calendar_weeks item=week name=weeks}
<tr class="week">
	{foreach from=$week item=day name=days}
		{$is_today = $calendar_properties.today == $day.timestamp}
		<td class="{if $is_today}today{/if}{if $day.is_padding} inactive{/if}{if $smarty.foreach.days.last} cellborder_r{/if}{if $smarty.foreach.weeks.last} cellborder_b{/if}">
			<div class="day_header">
				{if $is_today}
				<a href="javascript:;" onclick="">Today, {$calendar_properties.today|devblocks_date:"M d"}</a>
				{else}
				<a href="javascript:;" onclick="">{$day.dom}</a>
				{/if}
			</div>
			<div class="day_contents">
				{if $calendar_events.{$day.timestamp}}
					{foreach from=$calendar_events.{$day.timestamp} item=event}
						<div class="event" style="background-color:{$event.color|default:'#C8C8C8'};" link="{$event.link}">
							{if $is_today && $now >= $event.ts && $now <= $event.ts_end}<span class="cerb-sprite2 sprite-tick-circle"></span>{/if}
							<span style="color:rgb(0,0,0);">{$event.label}</span>
							{*<a href="javascript:;" style="color:rgb(0,0,0);">{$event.label}</a>*}
						</div>
					{/foreach}
				{/if}
			</div>
		</td>
	{/foreach}
</tr>
{/foreach}
</table>

{*
<script type="text/javascript">
$frm = $('#frm{$guid}');
$tab = $frm.closest('div.ui-tabs-panel');

$openEvtPopupEvent = function(e) {
	var $this = $(this);
	var link = '';
	
	if($this.is('button')) {
		link = 'ctx://{$context|default:"cerberusweb.contexts.calendar_event"}:0';
	
	} else if($this.is('div.event')) {
		link = $this.attr('link');
	}
	
	if(link.substring(0,6) == 'ctx://') {
		var context_parts = link.substring(6).split(':');
		var context = context_parts[0];
		var context_id = context_parts[1];
		
		$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context=' + context + '&context_id=' + context_id  + '&calendar_id={$calendar->id}',null,false,'600');
		
		$popup.one('popup_saved calendar_event_save calendar_event_delete', function(event) {
			var month = (event.month) ? event.month : '{$calendar_properties.month}';
			var year = (event.year) ? event.year : '{$calendar_properties.year}';
			
			genericAjaxGet($('#frm{$guid}').closest('div.ui-tabs-panel'), 'c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&id={$calendar->id}&month=' + month + '&year=' + year);
			event.stopPropagation();
		});
		
	} else {
		// [TODO] Regular link
	}
}

{if empty($calendar->params.manual_disabled)}
$frm.find('button.create_event').click($openEvtPopupEvent);
{/if}

$tab.find('TABLE.calendar TR.week div.day_contents').find('div.event').click($openEvtPopupEvent);
*}
</script>