{$msg_id = uniqid()}
<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}">
	<input type="text" class="cerb-bot-chat-input" placeholder="{$placeholder}" autocomplete="off">

	<script type="text/javascript">
	(function($) {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_convo = $('#cerb-bot-chat-window div.cerb-bot-chat-window-convo');
		var $chat_window_input_form = $('form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input[name=message]');
		
		var $txt = $msg.find('input:text')
			.blur()
			.focus()
			.on('keyup', function(e) {
				if(13 != e.keyCode)
					return;
				
				$chat_input.val($txt.val());
				$chat_window_input_form.submit();
				$msg.remove();
			})
			;
		;
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
