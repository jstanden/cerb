{$msg_id = uniqid()}
<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}">
	{if $mode == 'multiple'}
		<textarea class="cerb-bot-chat-input" placeholder="{$placeholder}" style="height:150px;" autocomplete="off">{$default}</textarea>
	{else}
		<input type="text" class="cerb-bot-chat-input" placeholder="{$placeholder}" value="{$default}" autocomplete="off">
	{/if}
	
	<div>
		<button type="button" class="cerb-bot-chat-button send">{'common.send'|devblocks_translate|capitalize}</button>
	</div>

	<script type="text/javascript">
	(function($) {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_convo = $('#cerb-bot-chat-window div.cerb-bot-chat-window-convo');
		var $chat_window_input_form = $('form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		var $button_send = $msg.find('button.send').hide();
		
		$button_send
			.show()
			.on('click', function(e) {
				$chat_input.val($txt.val());
				$chat_window_input_form.submit();
				$msg.remove();
				$chat_window_convo.trigger('update');
			})
		;
		
		{if $mode == 'multiple'}
			var $txt = $msg.find('textarea')
				.blur()
				.focus()
				.select()
				;
			
		{else}
			var $txt = $msg.find('input:text')
				.blur()
				.focus()
				.select()
				.on('keyup', function(e) {
					var keycode = e.keyCode || e.which;
					if(13 != keycode)
						return;
					
					$chat_input.val($txt.val());
					$chat_window_input_form.submit();
					$msg.remove();
				})
				;
		{/if}
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
