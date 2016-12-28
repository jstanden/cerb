{$div_id = "botchat_{uniqid()}"}
<style type="text/css">
.bot-chat-window {
}

.bot-chat-window > div.bot-chat-window-convo {
	overflow:auto;
	height: 350px;
	max-height: 350px;
}

.bot-chat-window div.bot-chat-message {
	margin: 10px 10px 5px 10px;
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

.bot-chat-window > div.bot-chat-window-convo > div.bot-chat-message.bot-chat-left > div.bot-chat-message-bubble {
	background-color:rgb(240,240,240);
	border:1px solid rgb(230,230,230);
	border:0;
	border-radius:0px 12px 12px 12px;
}

.bot-chat-window > div.bot-chat-window-convo > div.bot-chat-message.bot-chat-right > div.bot-chat-message-bubble {
	color:white;
	background-color:rgb(35,150,255);
	border:0;
	border-radius:12px 0px 12px 12px;
}

.bot-chat-window > div.bot-chat-window-convo div.bot-chat-message-time {
	color:rgb(180,180,180);
}

.bot-chat-window > div.bot-chat-window-input {
	width:100%;
	padding:0;
	text-align:center;
}

.bot-chat-window > div.bot-chat-window-input INPUT[name=message] {
	width:95%;
	font-size:1em;
	padding:5px;
	border-radius:5px;
	border:1px solid rgb(220,220,220);
	margin-top: 5px;
}
</style>

<div id="{$div_id}" class="bot-chat-window">

	<div class="bot-chat-window-convo">
	
		<div class="bot-chat-message bot-chat-left">
			<div class="bot-chat-message-bubble">
				Hello, {$active_worker->first_name}.  Say &quot;<b>help</b>&quot; for a list of things I can help you with.
			</div>
		</div>
		
		<br clear="all">
	</div>
	
	<div class="bot-chat-window-input">
		<form class="bot-chat-window-input-form" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="c" value="internal">
			<input type="hidden" name="a" value="consoleSendMessage">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			<input type="text" name="message" placeholder="write a message, or type 'help'" autocomplete="off" autofocus="autofocus">
		</form>
	</div>
	
</div>

<script type="text/javascript">
$(function() {
	// [TODO] Change this to be dynamic
	var $popup = genericAjaxPopupFind('#{$div_id}');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "Cerb");
		
		$window = $popup.closest('div.ui-dialog');
		$window
			.css('position', 'fixed')
			.css('left', '100%')
			.css('top', '100%')
			.css('margin-left', '-550px')
			.css('margin-top', '-500px')
			;
	
		var $chat_window_convo = $popup.find('div.bot-chat-window-convo');
		var $chat_window_input_form = $('form.bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input:text');
		
		$popup.click(function() {
			$chat_input.focus();
		});
		
		$chat_window_convo.on('update', function(e) {
			$(this).scrollTop(this.scrollHeight);
		});
		
		$chat_window_input_form.submit(function() {
			var txt = $chat_input.val();
			
			// Create outgoing message in log
			var $msg = $('<div class="bot-chat-message bot-chat-right"></div>');
			var $bubble = $('<div class="bot-chat-message-bubble"></div>');
			
			$bubble.text(txt).appendTo($msg.appendTo($chat_window_convo));
			
			$('<br clear="all">').insertAfter($msg);
			
			// Show loading icon placeholder
			
			var $loading = $('<div class="bot-chat-message bot-chat-left"><div class="bot-chat-message-bubble"><span class="cerb-ajax-spinner"></span></div></div>')
				.appendTo($chat_window_convo)
				;
			
			$chat_window_convo.trigger('update');
			
			// Send message and wait for request
			
			genericAjaxPost($chat_window_input_form, '', null, function(html) {
				$loading.hide();
				var $response = $(html).hide().insertAfter($loading).fadeIn();
				$response.find('.cerb-peek-trigger').cerbPeekTrigger();
				$loading.remove();
				$chat_window_convo.trigger('update');
				$chat_input.val('');
			});
		});
	});
});
</script>