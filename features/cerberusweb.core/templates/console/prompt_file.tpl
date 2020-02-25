{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}" style="text-align:center;">
	<div class="bot-chat-message bot-chat-right">
		<div class="bot-chat-message-bubble">
			<button type="button" class="bot-chat-file" autofocus="autofocus"><span class="glyphicons glyphicons-paperclip"></span></button>
		</div>
	</div>
	
	<script type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_convo = $('#{$layer} div.bot-chat-window-convo');
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		
		$msg.find('button.bot-chat-file').click(function(e) {
			e.stopPropagation();
			
			var is_single = 1;
			var $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=' + (is_single ? '1' : '0'), null, true, '50%');
			
			$chooser.one('chooser_save', function(event) {
				event.stopPropagation();
				
				if(!event.values || 0 == event.values.length)
					return;
				
				for(var idx in event.values) {
					$chat_input.val(event.values[idx]);
					
					// Create outgoing message in log
					var $m = $('<div class="bot-chat-message bot-chat-right"></div>');
					var $bubble = $('<div class="bot-chat-message-bubble"></div>');
					$bubble.text(event.labels[idx]).appendTo($m.appendTo($chat_window_convo));
					$('<br clear="all">').insertAfter($m);
					
					$chat_window_convo.trigger('bot-chat-message-send');
				}
				
				$msg.remove();
			});
		});
	});
	</script>
</div>

