{$msg_id = uniqid()}
<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" id="{$msg_id}" style="text-align:center;">
	<div class="bot-chat-message bot-chat-right">
		<div class="bot-chat-message-bubble">
			<div class="cerb-ui-record-chooser"></div>
			{if $selection == "multiple"}
			<button type="button" class="cerb-ui-button cerb-chooser-done"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.done'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		const $msg = $('#{$msg_id}');
		const $chat_window_convo = $('#{$layer} div.bot-chat-window-convo');
		const $chat_input = $('#{$layer} form.bot-chat-window-input-form').find('textarea[name=message]');

		if(!(window.CerbUI && CerbUI.RecordChooser))
			return;

		// Push the chosen record id(s) into the chat input, echo each as an outgoing bubble, then send.
		function commit(items) {
			items = [].concat(items || []).filter(Boolean);
			if(!items.length)
				return;

			$chat_input.val(items.map(function(it) { return parseInt(it.id, 10); }).join(','));

			items.forEach(function(it) {
				const $out = $('<div class="bot-chat-message bot-chat-right"></div>');
				$('<div class="bot-chat-message-bubble"></div>').text(it.label).appendTo($out.appendTo($chat_window_convo));
				$('<br clear="all">').insertAfter($out);
			});

			rc.destroy();
			$msg.remove();
			$chat_window_convo.trigger('bot-chat-message-send');
		}

		const rc = new CerbUI.RecordChooser($msg.find('.cerb-ui-record-chooser')[0], {
			context: '{$context}',
			name: 'ids',
			multiple: {if $selection == "multiple"}true{else}false{/if},
			query: '{$query|escape:'javascript' nofilter}'{if $selection != "multiple"},
			onSelect: function(item) { commit([item]); }{/if}
		});

		{if $selection == "multiple"}
		$msg.find('.cerb-chooser-done').on('click', function() { commit(rc.getValue()); });
		{/if}

		requestAnimationFrame(function() { $msg.find('.cerb-ui-record-chooser--input').focus(); });
	});
	</script>
</div>
