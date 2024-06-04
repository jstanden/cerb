<div id="widget{$widget->id}_clock" style="font-weight:bold;color:var(--cerb-color-table-row-text);text-align:center;">
	<div class="date" style="font-size:22px;"></div>
	<div class="time" style="font-size:32px;"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
try {
	var $widget = $('#widget{$widget->id}_clock');
	var $widget_container = $widget.closest('.cerb-workspace-widget');
	
	var tick = function() {
		var $widget = $('#widget{$widget->id}_clock');
		var $widget_container = $widget.closest('.cerb-workspace-widget');
		
		if(0 === $widget_container.length) {
			return;
		}
		
		var $clock_date = $widget.find('> div.date');
		var $clock_time = $widget.find('> div.time');
		
		// Convert time to UTC
		var d = new Date();
		d.setTime(d.getTime() + (d.getTimezoneOffset() * 60000));
		
		// Set the offset to our desired timezone
		d.setTime(d.getTime() + ({$offset} * 1000));
		
		var h = d.getHours();
		var m = d.getMinutes();
		var is_am = (h < 12);
		
		m = (m < 10) ? ('0' + m) : m;
		
		// 12-hour / 24-hour
		{if empty($widget->params.format)}
		h = (h > 12) ? (h - 12) : h;
		h = (h == 0) ? 12 : h;
		var time_string = h + ':' + m + (is_am ? ' AM' : ' PM');
		{else}
		h = (h < 10) ? ('0' + h) : h;
		var time_string = h + ':' + m;
		{/if}
		
		$clock_date.text(d.toDateString());
		$clock_time.text(time_string);
	};
	
	tick();
	$widget_container.off('cerb-dashboard-heartbeat').on('cerb-dashboard-heartbeat', tick);
	
} catch(e) {
	if(console && console.error)
		console.error(e);
}
});
</script>