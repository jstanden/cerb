<div id="widget{$widget->id}_clock" style="font-family:Arial,Helvetica;font-weight:bold;color:{$widget->params.color|default:'#57970A'};text-align:center;">
	<div class="date" style="font-size:22px;"></div>
	<div class="time" style="font-size:32px;"></div>
</div>

<script type="text/javascript">
try {
	var $widget = $('#widget{$widget->id}');
	
	var tick = function() {
		var $container = $('DIV#widget{$widget->id}_clock');
		
		if($container.length == 0) {
			return;
		}
		
		var $clock_date = $container.find('> div.date');
		var $clock_time = $container.find('> div.time');
		
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
		
		$clock_date.html(d.toDateString());
		$clock_time.html(time_string);
	};
	
	tick();
	$widget.off('dashboard_heartbeat').on('dashboard_heartbeat', tick);
	
} catch(e) {
}
</script>