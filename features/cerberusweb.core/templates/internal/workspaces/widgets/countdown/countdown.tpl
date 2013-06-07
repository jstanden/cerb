{$target_timestamp = $widget->params.target_timestamp}

<div id="widget{$widget->id}_countdown" style="font-family:Arial,Helvetica;font-weight:bold;line-height:36px;font-size:32px;white-space:nowrap;color:{$widget->params.color|default:'#57970A'};text-align:center;">
	<span class="counter">{$label}</span>
</div>

<script type="text/javascript">
try {
	var $widget = $('#widget{$widget->id}');
	
	var tick = function() {
		var $container = $('DIV#widget{$widget->id}_countdown');
		
		if($container.length == 0) {
			return;
		}
		
		var $counter = $container.find('> span.counter');
		
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
		
		$counter.html(label);
		
		// Auto-scale text

		var container_width = $container.width();
		
		var counter_width = $counter.width();
		
		var old_font_size = parseInt($counter.css('fontSize'), 10);
		var multiplier = (container_width / counter_width) * 0.9;
		var font_size = parseInt(old_font_size * multiplier);
		
		if(font_size < 16)
			font_size = 16;
		
		if(font_size > 36)
			font_size = 36;

		if(Math.abs(old_font_size - font_size) < 2)
			return;
		
		$counter.css('fontSize', font_size);
	};
	
	tick();
	$widget.off('dashboard_heartbeat').on('dashboard_heartbeat', tick);
	
} catch(e) {
}
</script>