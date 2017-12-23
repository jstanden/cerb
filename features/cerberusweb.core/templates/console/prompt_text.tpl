{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}" style="text-align:center;">
	{if $mode == 'multiple'}
		<textarea class="bot-chat-input" placeholder="{$placeholder}" style="height:150px;" autocomplete="off">{$default}</textarea>
		
		<div>
			<button type="button" class="bot-chat-button send">{'common.send'|devblocks_translate|capitalize}</button>
		</div>
	{else}
	<input type="text" class="bot-chat-input" placeholder="{$placeholder}" value="{$default}" autocomplete="off" autofocus="autofocus">
	{/if}

	<script type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		
		{if $mode == 'multiple'}
			var $button_send = $msg.find('button.send').hide();
			
			var $txt = $msg.find('textarea')
				.blur()
				.focus()
				.select()
				;
			
			$button_send
				.show()
				.on('click', function(e) {
					$chat_input.val($txt.val());
					$chat_window_input_form.submit();
					$msg.remove();
				})
			;
			
		{else}
			var $txt = $msg.find('input:text')
				.blur()
				.focus()
				.on('keyup', function(e) {
					var keycode = e.keyCode || e.which;
					if(13 != keycode)
						return;
					
					$chat_input.val($txt.val());
					$chat_window_input_form.submit();
					$msg.remove();
				})
				;
			;
		{/if}
		
	});
	</script>
</div>
