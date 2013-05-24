{$guid = uniqid()}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_properties.calendar_date|devblocks_date:'F Y'}</span>
	</div>

	<div style="float:right;">
		<span style="margin-right:10px;">
			{* [TODO] If calendar is writeable *}
			{if empty($calendar->params.manual_disabled)}
				<button type="button" class="event-create"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
			{/if}
			<button type="button" class="calendar-edit"><span class="cerb-sprite2 sprite-gear"></span></button>
		</span>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&month={$calendar_properties.prev_month}&year={$calendar_properties.prev_year}');">&lt;</button>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&month=&year=');">Today</button>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&month={$calendar_properties.next_month}&year={$calendar_properties.next_year}');">&gt;</button>
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
{foreach from=$calendar_properties.calendar_weeks item=week name=weeks}
<tr class="week" style="height:50px;">
	{foreach from=$week item=day name=days}
		<td class="{if $calendar_properties.today == $day.timestamp}today{/if}{if $day.is_padding} inactive{/if}{if $smarty.foreach.days.last} cellborder_r{/if}{if $smarty.foreach.weeks.last} cellborder_b{/if}" style="cursor:pointer;">
			<div class="day_header">
				{$day.dom}
			</div>
			<div style="text-align:center;">
				{if $calendar_events.{$day.timestamp}}
					<div class="badge" style="background:none;background-color:#999;border:0;margin:5px;">&nbsp;{$calendar_events.{$day.timestamp}|count}&nbsp;</div>
					<div class="bubble-popup" style="position:absolute;display:none;border-radius:5px;text-align:left;background-color:rgb(240,240,240);padding:5px;border:3px solid rgb(150,150,150);white-space:nowrap;">
					<b>{$day.timestamp|devblocks_date:'M d Y'}</b>
					{foreach from=$calendar_events.{$day.timestamp} item=event}
						<div class="bubble-popup-event" link="{$event.link}" style="border-radius:5px;padding:3px;margin-bottom:2px;{if $event.color}background-color:{$event.color}{/if}">
							<a href="javascript:;" style="color:rgb(0,0,0);">{$event.label}</a>
						</div>
					{/foreach}
					</div>
				{/if}
			</div>
		</td>
	{/foreach}
</tr>
{/foreach}
</table>

<script type="text/javascript">
var $widget = $('#widget{$widget->id}');
var $calendar = $widget.find('TABLE.calendar');
var $calendar_cell = $calendar.find('TR.week:first TD:first');

// Size all calendar cells to be square
$calendar.find('TR.week').css('min-height', $calendar_cell.width() + 'px');

$calendar.find('TR.week TD').hoverIntent({
	sensitivity:10,
	interval:100,
	timeout:0,
	over:function() {
		var $this = $(this);
		$this.addClass('hover');
		
		var $window = $(window);
		
		var $tooltip = $this.find('DIV.bubble-popup');
		
		if($tooltip.length == 0)
			return;
		
		$tooltip.show();

		if($tooltip.offset().left + 16 + $tooltip.width() > $window.width()) {
			var left_offset = $tooltip.offset().left + 16 + $tooltip.width() - $window.width() + 10;
			$tooltip.css('margin-left', '-' + left_offset + 'px');
		}
		
		$tooltip.show();
	},
	out:function() {
		var $this = $(this);
		$this.removeClass('hover');
		var $tooltip = $this.find('DIV.bubble-popup');
		
		if($tooltip.length == 0)
			return;
		
		$tooltip.css('margin-left', 0);
		$tooltip.hide();
	}
});

$frm = $('#frm{$guid}');

$frm.find('button.calendar-edit').click(function() {
	$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_CALENDAR}&context_id={$calendar->id}',null,false,'550');
	$popup.one('calendar_save', function(event) {
		event.stopPropagation();

		var month = (event.month) ? event.month : '{$calendar_properties.month}';
		var year = (event.year) ? event.year : '{$calendar_properties.year}';
		
		genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&month=' + month + '&year=' + year);
	});
});

$openEvtPopupEvent = function(e) {
	var $this = $(this);
	var link = '';
	
	if($this.is('button')) {
		link = 'ctx://{$context|default:"cerberusweb.contexts.calendar_event"}:0';
	
	} else if($this.is('DIV.bubble-popup-event')) {
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
			
			genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&month=' + month + '&year=' + year);
		
			event.stopPropagation();
		});
		
	} else {
		// [TODO] Regular link
	}
}

{if empty($calendar->params.manual_disabled)}
$frm.find('button.event-create').click($openEvtPopupEvent);
{/if}

$calendar.find('TR.week DIV.bubble-popup-event').click($openEvtPopupEvent);
</script>