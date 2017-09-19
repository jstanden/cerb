{$msg_id = uniqid()}
<div class="cerb-bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}">
	<table width="100%" cellspacing="2" cellpadding="0">
		<tr>
			{$ratings = range($options.range_from,$options.range_to)}
			{if 0 == count($options) % 2}
				{$mid = round(count($ratings)/2)}
			{else}
				{$mid = floor(count($ratings)/2)}
			{/if}
			
			{foreach from=$ratings item=rating name=ratings}
			
			{if $options.color_mid && $options.color_mid != '#FFFFFF'}
				{if $rating < $mid}
					{$ratio = $rating / $mid}
					{$color = DevblocksPlatform::colorLerp($options.color_from, $options.color_mid, $ratio)}
				{else}
					{$ratio = ($rating-$mid) / ($smarty.foreach.ratings.total-$mid)}
					{$color = DevblocksPlatform::colorLerp($options.color_mid, $options.color_to, $ratio)}
				{/if}
			{else}
				{$ratio = $smarty.foreach.ratings.index / ($smarty.foreach.ratings.total-1)}
				{$color = DevblocksPlatform::colorLerp($options.color_from, $options.color_to, $ratio)}
			{/if}
			
			<td align="center">
				<label>
					<div style="color:white;font-weight:bold;background-color:{$color};">{$rating}</div>
					<input type="radio" name="rating" value="{$rating}/{$options.range_to}">
				</label>
			</td>
			{/foreach}
		</tr>
	</table>
	
	<div style="color:rgb(175,175,175);font-size:90%;margin-top:5px;">
		{if $options.label_from}<div style="float: left;margin-left:20px;">{$options.label_from}</div>{/if}
		{if $options.label_to}<div style="float: right;margin-right:20px;">{$options.label_to}</div>{/if}
	</div>
	
	<br clear="all">
	
	<div class="cerb-bot-chat-message cerb-bot-chat-right">
		<div class="cerb-bot-chat-message-bubble" style="background-color:white;">
			<button type="button" class="cerb-bot-chat-button" style="display:none;">Send rating</button>
		</div>
	</div>

	<script type="text/javascript">
	(function($) {
		var $msg = $('#{$msg_id}');
		
		var $chat_window_convo = $('#cerb-bot-chat-window div.cerb-bot-chat-window-convo');
		var $chat_window_input_form = $('form.cerb-bot-chat-window-input-form');
		var $chat_input = $chat_window_input_form.find('input[name=message]');
		var $button = $msg.find('button');
		
		$msg.find('table').on('click', function(e) {
			var $target = $(e.target);
			
			if(!$target.is('input[type=radio]'))
				return;
			
			$button.text('Send rating: ' + $target.val()).fadeIn().focus();
		});
		
		$button.click(function(e) {
			e.stopPropagation();
			
			var $radio = $msg.find('input[name=rating]:checked');
			
			if($radio.val() == undefined)
				return;
			
			$chat_input.val($radio.val());
			$chat_window_input_form.submit();
			$msg.remove();
		});
		
	})(document.getElementById('cerb-portal').jQuery);
	</script>
</div>
