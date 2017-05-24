<style type="text/css">
.bot-chat-window {
}

.bot-chat-window > div.bot-chat-window-convo {
	overflow:auto;
	height: 500px;
	max-height: 500px;
}

.bot-chat-window div.bot-chat-message {
	margin: 10px 0px 5px 0px;
}

.bot-chat-window div.bot-chat-message p {
	margin: 2px;
}

.bot-chat-window div.bot-chat-message ul, .bot-chat-window div.bot-chat-message ol {
	margin: 2px;
	padding-left: 25px;
}

.bot-chat-window div.bot-chat-message-emote {
	color:rgb(150,150,150);
}

.bot-chat-window .bot-chat-left {
	float:left;
	text-align:left;
}

.bot-chat-window .bot-chat-right {
	float:right;
	text-align:right;
}

.bot-chat-window div.bot-chat-message-bubble {
	border-radius:5px;
	display:inline-block;
	padding:10px;
	margin-bottom:1px;
	text-align:left;
}

.bot-chat-window > div.bot-chat-window-convo div.bot-chat-message.bot-chat-left > div.bot-chat-message-bubble {
	background-color:rgb(240,240,240);
	border:1px solid rgb(230,230,230);
	border:0;
	border-radius:12px 12px 12px 0px;
}

.bot-chat-window > div.bot-chat-window-convo div.bot-chat-message.bot-chat-right > div.bot-chat-message-bubble {
	color:white;
	background-color:rgb(35,150,255);
	border:0;
	border-radius:12px 12px 0px 12px;
}

.bot-chat-window > div.bot-chat-window-convo div.bot-chat-message-time {
	color:rgb(180,180,180);
}

.bot-chat-window INPUT.bot-chat-input {
	width:95%;
	padding:5px;
	margin-top: 10px;
	border-radius:5px;
	border:1px solid rgb(220,220,220);
}
</style>

<div id="cerb-bot-chat-window" class="bot-chat-window">

	<div class="bot-chat-window-convo">
	</div>
	
	<div class="bot-chat-window-input">
		<form class="bot-chat-window-input-form" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="c" value="internal">
			<input type="hidden" name="a" value="consoleSendMessage">
			<input type="hidden" name="message" value="">
			<input type="hidden" name="session_id" value="{$session_id}">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		</form>
	</div>
	
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#cerb-bot-chat-window');
	var $spinner = $('<div class="bot-chat-message bot-chat-left"><div class="bot-chat-message-bubble"><span class="cerb-ajax-spinner" style="zoom:0.5;"></span></div></div>')
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{$bot->name|escape:'javascript' nofilter}");
		
		var $window = $popup.closest('div.ui-dialog');
		$window
			.css('position', 'fixed')
			.css('left', '100%')
			.css('top', '100%')
			.css('margin-left', '-550px')
			.css('margin-top', '-650px')
			;
		
		var $chat_window_convo = $popup.find('div.bot-chat-window-convo');
		var $chat_window_input_form = $('form.bot-chat-window-input-form');
		var $chat_message = $chat_window_input_form.find('input:hidden[name=message]');
		
		$chat_window_convo.on('update', function(e) {
			$(this).scrollTop(this.scrollHeight);
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