<div id="cerb-bot-chat-window" class="cerb-bot-chat-window">
	{*<div class="cerb-bot-chat-window-avatar"><img src="{devblocks_url}c=resource&p=cerb.bots.portal.widget&f=images/cerby.png{/devblocks_url}"></div>*}
	<div class="cerb-bot-chat-window-close"></div>
	<div class="cerb-bot-chat-window-header">
		<b>{$bot_name}</b>
	</div>
	<div class="cerb-bot-chat-window-convo"></div>
	
	<div class="cerb-bot-chat-window-input">
		<form class="cerb-bot-chat-window-input-form" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="session_id" value="{$session_id}">
			<input type="hidden" name="message" value="">
		</form>
	</div>
</div>

<script type="text/javascript">
(function($) {
	var $embed = $('#cerb-portal');
	var base_url = $embed.get()[0].baseUrl;
	var $window = $('#cerb-bot-chat-window');
	var $close = $window.find('div.cerb-bot-chat-window-close');
	var $convo = $window.find('div.cerb-bot-chat-window-convo');
	var $form = $window.find('form');
	var $msg = $form.find('input:hidden[name=message]');
	
	var $spinner = $('<div class="cerb-bot-chat-message cerb-bot-chat-left"><div class="cerb-bot-chat-message-bubble"><span class="cerb-ajax-spinner" style="zoom:0.5;-moz-transform:scale(0.5);"></span></div></div>');
	
	$close.on('click', function(e) {
		e.stopPropagation();
		$embed.trigger('cerb-bot-close');
	});
	
	$convo.on('update', function(e) {
		e.stopPropagation();
		$(this).scrollTop(this.scrollHeight);
	});
	
	$convo.on('cerb-bot-chat-message-send', function(e) {
		e.stopPropagation();
		
		$spinner
			.appendTo($convo)
			.fadeIn()
			;
		
		$convo.trigger('update');
		
		$.ajax({
			type: 'post',
			url: base_url + 'interaction/message',
			xhrFields: { withCredentials: true },
			cache: false,
			data: $form.serialize()
			
		}).done(function(html) {
			var $response = $(html);
			var delay_ms = 0;
			
			if(0 == $response.length) {
				$spinner.hide();
				return;
			}
			
			$response.each(function(i) {
				var $object = $(this);
				var delay = 0;
				var is_typing = false;
				
				if($object.is('.cerb-bot-chat-object')) {
					delay = $object.attr('data-delay-ms');
					is_typing = $object.attr('data-typing-indicator');
					
					if(isNaN(delay))
						delay = 0;
				}
				
				if(is_typing) {
					var func = function() {
						$spinner.appendTo($convo).show();
						$convo.trigger('update');
					}
					
					setTimeout(func, delay_ms);
				}
				
				delay_ms += parseInt(delay);
				
				var func = function() {
					$object.appendTo($convo).hide().fadeIn();
					$spinner.hide();
					$convo.trigger('update');
				}
				
				setTimeout(func, delay_ms);
			});
		});
	});
	
	$form.submit(function(e) {
		e.stopPropagation();
		
		var txt = $msg.val();
		
		if(txt.length > 0) {
			// Create outgoing message in log
			var $new_msg = $('<div class="cerb-bot-chat-message cerb-bot-chat-right"></div>');
			var $bubble = $('<div class="cerb-bot-chat-message-bubble"></div>');
			
			$bubble.text(txt).appendTo($new_msg.appendTo($convo));
			
			$('<br clear="all">').insertAfter($new_msg);
		}
		
		$convo.trigger('cerb-bot-chat-message-send');
	});
	
	$form.submit();
	
})(document.getElementById('cerb-portal').jQuery);
</script>