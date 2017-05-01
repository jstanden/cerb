<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}">
	<script type="text/javascript">
	$(function() {
		var $chat_window_input_form = $('form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input:text');
		
		$chat_input
			.attr('placeholder', '{$placeholder|escape:'js' nofilter}')
			.blur()
			.focus()
		;
	})
	</script>
</div>
