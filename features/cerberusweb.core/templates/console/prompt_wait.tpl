<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}">
	<script type="text/javascript">
	$(function() {
		var $chat_window_convo = $('#cerb-bot-chat-window div.bot-chat-window-convo');
		var $chat_window_input_form = $('#cerb-bot-chat-window form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input:text');
		var $chat_message = $chat_window_input_form.find('input:hidden[name=message]');
		
		$chat_input
			.blur()
		;
		
		var cb = function() {
			$chat_message.val('');
			
			$chat_input
				.blur()
			;
			
			$chat_window_convo.trigger('bot-chat-message-send');
		}
		
		setTimeout(cb, 100);
	})
	</script>
</div>
