{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true" id="{$msg_id}">
	<div class="bot-chat-message bot-chat-right">
		<div class="bot-chat-message-bubble">
			{foreach from=$options item=option}
			<button type="button" class="bot-chat-button" style="{if $style}{$style}{/if}" value="{$option}">{$option}</button>
			{/foreach}
		</div>
	</div>
	
	<br clear="all">
	
	<script type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
	
		var $chat_window_input_form = $('form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input:text');
		
		$chat_input
			.attr('disabled','disabled')
			.attr('data-placeholder', $chat_input.attr('placeholder'))
			.attr('placeholder','(choose an option above)')
		;
		
		$msg.find('button.bot-chat-button')
			.click(function() {
				var $button = $(this);
				
				var txt = $button.val();
				
				$msg.remove();
		
				$chat_input
					.removeAttr('disabled')
					.attr('placeholder', $chat_input.attr('data-placeholder'))
				;
				
				$chat_input.val(txt);
				$chat_window_input_form.submit();
			})
			.first()
			.focus()
		;
	})
	</script>
</div>
