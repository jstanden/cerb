<div id="{$layer}" class="bot-chat-window">
	<div class="bot-chat-window-convo"></div>
	
	<div class="bot-chat-window-input">
		<form class="bot-chat-window-input-form" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="c" value="internal">
			<input type="hidden" name="a" value="consoleSendMessage">
			<input type="hidden" name="layer" value="{$layer}">
			<input type="hidden" name="message" value="">
			<input type="hidden" name="session_id" value="{$session_id}">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		</form>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$layer}');
	var $spinner = $('<div class="bot-chat-message bot-chat-left"><div class="bot-chat-message-bubble"><span class="cerb-ajax-spinner" style="zoom:0.5;"></span></div></div>')
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{$bot_name|escape:'javascript' nofilter}");
		
		{if $bot_image_url}
		$popup.closest('.ui-dialog').find('.ui-dialog-title')
			.prepend(
				$('<img/>')
					.addClass('cerb-avatar')
					.css('width', '24px')
					.css('height', '24px')
					.css('margin-right', '5px')
					.attr('src', '{$bot_image_url|escape:'javascript' nofilter}')
			)
			;
		{/if}
		
		var $window = $popup.closest('div.ui-dialog');
		$window
			.css('position', 'fixed')
			.css('left', '100%')
			.css('top', '100%')
			.css('margin-left', '-550px')
			.css('margin-top', '-650px')
			;
		
		var $chat_window_convo = $popup.find('div.bot-chat-window-convo');
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_message = $chat_window_input_form.find('input:hidden[name=message]');
		
		$chat_window_convo.on('update', function(e) {
			$(this).scrollTop(this.scrollHeight);
		});
		
		$chat_window_convo.on('bot-chat-close', function(e) {
			genericAjaxPopupDestroy('{$layer}');
		});
		
		$chat_window_convo.on('bot-chat-message-send', function() {
			// Show loading icon placeholder
			
			$spinner
				.appendTo($chat_window_convo)
				.fadeIn()
				;
			
			$chat_window_convo.trigger('update');
			
			// Send message and wait for request
			
			genericAjaxPost($chat_window_input_form, '', null, function(html) {
				var $response = $(html);
				var delay_ms = 0;
				
				if(0 == $response.length) {
					$spinner.hide();
					return;
				}
				
				$response.find('.cerb-peek-trigger').cerbPeekTrigger();
				
				$response.each(function(i) {
					var $object = $(this);
					var delay = 0;
					var is_typing = false;
					
					if($object.is('.bot-chat-object')) {
						delay = $object.attr('data-delay-ms');
						is_typing = $object.attr('data-typing-indicator');
						
						if(isNaN(delay))
							delay = 0;
					}
					
					if(is_typing) {
						var func = function() {
							$spinner.appendTo($chat_window_convo).show();
							$chat_window_convo.trigger('update');
						}
						
						setTimeout(func, delay_ms);
					}
					
					delay_ms += parseInt(delay);
					
					var func = function() {
						$spinner.hide();
						$object.appendTo($chat_window_convo).hide().fadeIn();
						$chat_window_convo.trigger('update');
					}
					
					setTimeout(func, delay_ms);
				});
			});
		});
		
		$chat_window_input_form.submit(function() {
			var txt = $chat_message.val();
		
			if(txt.length > 0) {
				// Create outgoing message in log
				var $msg = $('<div class="bot-chat-message bot-chat-right"></div>');
				var $bubble = $('<div class="bot-chat-message-bubble"></div>');
				
				$bubble.text(txt).appendTo($msg.appendTo($chat_window_convo));
				
				$('<br clear="all">').insertAfter($msg);
			}
			
			$chat_window_convo.trigger('bot-chat-message-send');
		});
		
		// Submit form when open
		$chat_window_input_form.submit();
	});
});
</script>