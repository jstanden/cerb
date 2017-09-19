{$msg_id = uniqid()}
<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true" id="{$msg_id}">
	<div class="cerb-bot-chat-message cerb-bot-chat-right">
		<div class="cerb-bot-chat-message-bubble">
			{foreach from=$labels item=label key=idx}
			{$image = $images.$idx}
			{if $label}
			<button type="button" class="cerb-bot-chat-button-image" value="{$label}" title="{$label}"><img src="{$image}"></button>
			{/if}
			{/foreach}
		</div>
	</div>
	
	<br clear="all">

	<script type="text/javascript">
	(function($) {
		var $msg = $('#{$msg_id}');
	
		var $chat_window_input_form = $('form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input[name=message]');
		
		$msg.find('button.cerb-bot-chat-button-image')
			.click(function() {
				var $button = $(this);
				var txt = $button.val();
				
				$chat_input.val(txt);
				$chat_window_input_form.submit();
				$msg.remove();
			})
			.first()
			.focus()
		;
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
