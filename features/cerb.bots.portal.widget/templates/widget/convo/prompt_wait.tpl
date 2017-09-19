<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}">
	<script type="text/javascript">
	(function($) {
		var $chat_window_convo = $('#cerb-bot-chat-window div.cerb-bot-chat-window-convo');
		var $chat_window_input_form = $('#cerb-bot-chat-window form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input[name=message]');
		
		var cb = function() {
			$chat_input.val('');
			$chat_window_convo.trigger('cerb-bot-chat-message-send');
		}
		
		setTimeout(cb, 250);
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
