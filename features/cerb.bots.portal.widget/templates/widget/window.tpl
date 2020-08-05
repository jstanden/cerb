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
			<textarea name="message" style="display:none;"></textarea>
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
	var $msg = $form.find('textarea[name=message]');
	
	var $spinner = $('<div class="cerb-bot-chat-message cerb-bot-chat-left"><div class="cerb-bot-chat-message-bubble"><svg class="cerb-spinner" viewBox="0 0 100 100" style="width:16px;" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="45"/></svg></div></div>');
	
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
	
	$close.on('click', function(e) {
		e.stopPropagation();
		$embed.trigger('cerb-bot-close');
	});
	
	$(window).on('resize', function(e) {
		$convo.trigger('update');
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
					
					message_queue.add(func, 0);
				}
				
				var func = function() {
					$object.appendTo($convo).hide().fadeIn();
					$spinner.hide();
					$convo.trigger('update');
				}
				
				message_queue.add(func, parseInt(delay));
			});
		});
	});
	
	$form.submit(function(e) {
		e.stopPropagation();
		
		var txt = $msg.val();
		
		if(txt.length > 0) {
			// Create outgoing message in log
			var $new_msg = document.createElement('div');
			$new_msg.className = 'cerb-bot-chat-message cerb-bot-chat-right';
			
			var $bubble = document.createElement('div');
			$bubble.className = 'cerb-bot-chat-message-bubble';
			$bubble.innerText = txt;
			
			$new_msg.appendChild($bubble);
			
			$br = document.createElement('br');
			$br.setAttribute('clear', 'all');
			
			$convo.append($new_msg);
			$convo.append($br);
		}
		
		$convo.trigger('cerb-bot-chat-message-send');
	});
	
	$form.submit();
	
})(document.getElementById('cerb-portal').jQuery);
</script>