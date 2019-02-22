{$target_timestamp = $widget->params.target_timestamp}

<div id="widget{$widget->id}_countdown" style="font-family:Arial,Helvetica;font-weight:bold;line-height:36px;font-size:32px;color:{$widget->params.color|default:'#57970A'};text-align:center;">
	<span class="counter">{$label}</span>
</div>

<script type="text/javascript">
$(function() {
try {
	var $widget = $('#widget{$widget->id}_countdown');
	var $widget_container = $widget.closest('.cerb-workspace-widget');
	
	var tick = function() {
		var $widget = $('#widget{$widget->id}_countdown');
		var $widget_container = $widget.closest('.cerb-workspace-widget');
		
		if($widget_container.length == 0)
			return;
		
		var $counter = $widget.find('> span.counter');
		
		var now = Math.floor(new Date().getTime()/1000);
		var then = {$target_timestamp};
		var diff = then - now;
		var secs = Math.abs(diff);
		var is_elapsed = (diff < 0) ? true : false;
		var outs = [];
		
		if(is_elapsed) {
			secs = 0;
		}
		
		if(secs >= 86400 * 365) {
			var years = Math.floor(secs / (86400 * 365));
			secs -= (years * 86400 * 365);
			outs.push(years + ' year' + (years != 1 ? 's' : ''));
		}
		
		if(secs >= 86400 * 7) {
			var weeks = Math.floor(secs / (86400 * 7));
			secs -= (weeks * 86400 * 7);
			outs.push(weeks + ' week' + (weeks != 1 ? 's' : ''));
		}
		
		if(secs >= 86400) {
			var days = Math.floor(secs / 86400);
			secs -= (days * 86400);
			outs.push(days + ' day' + (days != 1 ? 's' : ''));
		}
		
		if(secs >= 3600) {
			var hours = Math.floor(secs / 3600);
			secs -= (hours * 3600);
			outs.push(hours + ' hour' + (hours != 1 ? 's' : ''));
		}
		
		if(secs >= 60) {
			var mins = Math.floor(secs / 60);
			secs -= (mins * 60);
			outs.push(mins + ' min' + (mins != 1 ? 's' : ''));
		}
		
		outs.push(secs + ' sec' + (secs != 1 ? 's' : ''));
	
		if(outs.length > 2) {
			outs = outs.slice(0,2);
		}
		
		var label = outs.join(', ');
		
		$counter.text(label);
	};
	
	tick();
	$widget_container.off('cerb-dashboard-heartbeat').on('cerb-dashboard-heartbeat', tick);
	
} catch(e) {
	if(console && console.error)
		console.error(e);
}
});
</script>