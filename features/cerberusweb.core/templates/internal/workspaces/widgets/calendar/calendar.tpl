{$guid = uniqid()}

<form id="frm{$guid}" action="#" style="margin-bottom:5px;width:98%;">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	<div style="float:left;">
		<span style="font-weight:bold;font-size:150%;">{$calendar_properties.calendar_date|devblocks_date:'F Y'}</span>
	</div>

	<div style="float:right;">
		<span style="margin-right:10px;">
			<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		</span>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&nocache=1&month={$calendar_properties.prev_month}&year={$calendar_properties.prev_year}');">&lt;</button>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&nocache=1&month=&year=');">Today</button>
		<button type="button" onclick="genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&nocache=1&month={$calendar_properties.next_month}&year={$calendar_properties.next_year}');">&gt;</button>
	</div>
	
	<br clear="all">
</form>

<table cellspacing="0" cellpadding="0" border="0" class="calendar">
<tr class="heading">
{if $calendar->params.start_on_mon}
	<th>Mon</th>
	<th>Tue</th>
	<th>Wed</th>
	<th>Thu</th>
	<th>Fri</th>
	<th>Sat</th>
	<th>Sun</th>
{else}
	<th>Sun</th>
	<th>Mon</th>
	<th>Tue</th>
	<th>Wed</th>
	<th>Thu</th>
	<th>Fri</th>
	<th>Sat</th>
{/if}
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
							<a href="javascript:;" class="cerb-peek-trigger" data-context="{$event.context}" data-context-id="{$event.context_id}">{$event.label}</a>
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
$(function() {
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
	
	var $frm = $('#frm{$guid}');
	
	$widget.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved cerb-peek-deleted', function(e) {
			var month = (e.month) ? e.month : '{$calendar_properties.month}';
			var year = (e.year) ? e.year : '{$calendar_properties.year}';
			
			genericAjaxGet('widget{$widget->id}','c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id={$widget->id}&nocache=1&month=' + month + '&year=' + year);
			event.stopPropagation();
		})
		.on('cerb-peek-closed', function(e) {
		})
		;
});
</script>