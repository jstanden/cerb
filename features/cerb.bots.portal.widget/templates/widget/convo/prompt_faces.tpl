{$msg_id = uniqid()}
<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}">
	<div class="cerb-bot-chat-message cerb-bot-chat-right">
		<div class="cerb-bot-chat-message-bubble" style="background-color:white;border:5px solid #2396FF;">
			<canvas class="rating-face rating-happy" title="happy" data-color="#008800" height="64" width="64"></canvas>
			<canvas class="rating-face rating-neutral" title="undecided" data-color="#FF8800" height="64" width="64" style="margin:0 20px;"></canvas>
			<canvas class="rating-face rating-unhappy" title="unhappy" data-color="#880000" height="64" width="64"></canvas>
		</div>
	</div>

	<script type="text/javascript">
	(function($) {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_convo = $('#cerb-bot-chat-window div.cerb-bot-chat-window-convo');
		var $chat_window_input_form = $('form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input[name=message]');
		
		$msg.find('canvas.rating-face')
			.css('cursor', 'pointer')
			.click(function(e) {
				e.stopPropagation();
				
				var $this = $(this);
				
				$chat_input.val($this.attr('title'));
				$chat_window_input_form.submit();
				$msg.remove();
			})
			;
		
		// ======================
		// Happy
		
		var $canvas = $msg.find('canvas.rating-happy');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var color = $canvas.attr('data-color');
		
		var radius = (canvas.height - 6)/2;
		
		var center_x = center_y = canvas.height/2;
		var x = center_x;
		var y = center_y;
		
		context.fillStyle = color;
		context.strokeStyle = color;
		context.lineWidth = 5;
		
		// Outer circle
		context.beginPath();
		context.arc(x, y, radius, 0, 2 * Math.PI);
		context.stroke();
		
		var y = y - 8;
		
		// Left eye 
		context.beginPath();
		context.arc(x-10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Right eye 
		context.beginPath();
		context.arc(x+10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Smile 
		context.beginPath();
		context.arc(center_x, center_y+3, 16, Math.PI, 2 * Math.PI, true);
		context.stroke();
		
		// ======================
		// Neutral
		
		var $canvas = $msg.find('canvas.rating-neutral');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var color = $canvas.attr('data-color');
		
		var radius = (canvas.height - 6)/2;
		
		var center_x = center_y = canvas.height/2;
		var x = center_x;
		var y = center_y;
		
		context.fillStyle = color;
		context.strokeStyle = color;
		context.lineWidth = 5;
		
		// Outer circle
		context.beginPath();
		context.arc(x, y, radius, 0, 2 * Math.PI);
		context.stroke();
		
		var y = y - 8;
		
		// Left eye 
		context.beginPath();
		context.arc(x-10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Right eye 
		context.beginPath();
		context.arc(x+10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Stoic
		context.beginPath();
		context.moveTo(center_x-12, center_y+10);
		context.lineTo(center_x+12, center_y+10);
		context.stroke();
		
		// ======================
		// Unhappy
		
		var $canvas = $msg.find('canvas.rating-unhappy');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var color = $canvas.attr('data-color');
		
		var radius = (canvas.height - 6)/2;
		
		var center_x = center_y = canvas.height/2;
		var x = center_x;
		var y = center_y;
		
		context.fillStyle = color;
		context.strokeStyle = color;
		context.lineWidth = 5;
		
		// Outer circle
		context.beginPath();
		context.arc(x, y, radius, 0, 2 * Math.PI);
		context.stroke();
		
		var y = y - 8;
		
		// Left eye 
		context.beginPath();
		context.arc(x-10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Right eye 
		context.beginPath();
		context.arc(x+10, y, 6, 0, 2 * Math.PI);
		context.fill();
		
		// Frown 
		context.beginPath();
		context.arc(center_x, center_y+18, 12, Math.PI, 2 * Math.PI, false);
		context.stroke();
	
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
