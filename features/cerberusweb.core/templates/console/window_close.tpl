<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}">
<script type="text/javascript">
setTimeout(function(e) {
	var $window = $('#{$layer}');
	var $convo = $window.find('div.bot-chat-window-convo');
	$convo.trigger('bot-chat-close');
}, 250);
</script>
</div>