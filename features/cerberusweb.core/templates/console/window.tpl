<div id="{$layer}" class="bot-chat-window">
	<div class="bot-chat-window-convo"></div>
	
	<div class="bot-chat-window-input">
		<form class="bot-chat-window-input-form" action="javascript:;" onsubmit="return false;" method="post">
			<input type="hidden" name="c" value="profiles">
			<input type="hidden" name="a" value="invoke">
			<input type="hidden" name="module" value="bot">
			<input type="hidden" name="action" value="sendMessage">
			<input type="hidden" name="layer" value="{$layer}">
			<textarea name="message" style="display:none;"></textarea>
			<input type="hidden" name="session_id" value="{$session_id}">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		</form>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$layer}');
	var $spinner = Devblocks.getSpinner().css('max-width', '24px');

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
		
		$popup.closest('.ui-dialog').find('.ui-dialog-titlebar-close')
			.attr('tabindex', '-1')
			;
		
		var $window = $popup.closest('div.ui-dialog');
		var $chat_window_convo = $popup.find('div.bot-chat-window-convo');
		var $chat_window_input_form = $('#{$layer} form.bot-chat-window-input-form');
		var $chat_message = $chat_window_input_form.find('textarea[name=message]');
		
		// Responsive scaling
		
		$window
			.css('position', 'fixed')
			;
		
		if($(window).height() <= 500) {
			$chat_window_convo.css('height', ($(window).height() - 100) + 'px');
		}
		
		if($(window).width() <= 600) {
			$window.css('width', ($(window).width()) + 'px');
			$window.position({ my: "middle bottom", at: "middle bottom", of: $(window) });
			
		} else {
			$window.position({ my: "right bottom", at: "right-25 bottom-25", of: $(window) });
		}
		
		// Message queue
		
		var message_queue = (function() {
			var API;
			var queue = [];
			var job = null;
			var timer;
			
			function next() {
				if(job !== null) {
					job.func();
					job = null;
				}
				
				if(0 < queue.length) {
					job = queue.shift();
					timer = setTimeout(next, job.delay_ms);
					
				} else {
					timer = setTimeout(next, 250);
				}
			}
			
			timer = setTimeout(next, 0);
			
			return API = {
				add: function(func, delay_ms) {
					queue.push({ func: func, delay_ms: delay_ms });
				}
			}
		})();
		
		// Chat window actions
		
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
				
				if(0 == $response.length) {
					$spinner.hide();
					return;
				}
				
				$response.find('.cerb-peek-trigger').cerbPeekTrigger();
				$response.find('.cerb-search-trigger').cerbSearchTrigger();
				$response.find('.cerb-bot-trigger').cerbBotTrigger();
				
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
						
						message_queue.add(func, 0);
					}
					
					var func = function() {
						$spinner.hide();
						$object.appendTo($chat_window_convo).hide().fadeIn();
						$chat_window_convo.trigger('update');
					}
					
					message_queue.add(func, parseInt(delay));
				});
			});
		});
		
		$chat_window_input_form.submit(function() {
			var txt = $chat_message.val();
			
			if(txt.length > 0) {
				// Create outgoing message in log
				var $new_msg = document.createElement('div');
				$new_msg.className = 'bot-chat-message bot-chat-right';
				
				var $bubble = document.createElement('div');
				$bubble.className = 'bot-chat-message-bubble';
				$bubble.innerText = txt;
				
				$new_msg.appendChild($bubble);
				
				$br = document.createElement('br');
				$br.setAttribute('clear', 'all');
				
				$chat_window_convo.append($new_msg);
				$chat_window_convo.append($br);
			}
			
			$chat_window_convo.trigger('bot-chat-message-send');
		});
		
		// Submit form when open
		$chat_window_input_form.submit();
	});
});
</script>