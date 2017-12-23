{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}" style="text-align:center;">
	<div class="bot-chat-message bot-chat-right">
		<div class="bot-chat-message-bubble">
			<button type="button" autofocus="autofocus" class="chooser-abstract" data-field-name="ids[]" data-context="{$context}" {if $selection != "multiple"}data-single="true"{/if} {if $autocomplete == 1}data-autocomplete="{$query}"{/if} data-query="{$query}" data-shortcuts="false"><span class="glyphicons glyphicons-search"></span></button>
			<ul class="bubbles chooser-container" style="display:none;"></ul>
		</div>
	</div>

	<script type="text/javascript">
	$(function() {
		var $msg = $('#{$msg_id}');
		var $ul = $msg.find('ul.chooser-container');
		var $button = $msg.find('button.chooser-abstract');
		
		var $chat_window_convo = $('#{$layer} div.bot-chat-window-convo');
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('textarea[name=message]');
		
		$button
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $selections = $ul.find('li input:hidden');
				var ids = $selections.map(function(e) { return parseInt($(this).val()); }).get().join(',');
				
				$chat_input.val(ids);
				
				$selections.each(function() {
					var $this = $(this);
					// Create outgoing message in log
					var $msg = $('<div class="bot-chat-message bot-chat-right"></div>');
					var $bubble = $('<div class="bot-chat-message-bubble"></div>');
					$bubble.text($this.attr('title')).appendTo($msg.appendTo($chat_window_convo));
					$('<br clear="all">').insertAfter($msg);
				});
				
				$msg.remove();
				
				$chat_window_convo.trigger('bot-chat-message-send');
			})
		;
		
		{if $autocomplete == 1}
		$button.next('input[type=search]').focus();
		{/if}
	});
	</script>
</div>

