<div class="bot-chat-object" data-delay-ms="{$delay_ms|default:0}" data-typing-indicator="true">
	<div class="bot-chat-message bot-chat-left">
		{if !$hide_author && $bot}
		<div class="bot-chat-message-author">
			<img src="{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}" style="height:16px;width:16px;border-radius:5px;vertical-align:middle;">
			<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$bot->id}"><b>{$bot->name}</b></a>:
		</div>
		{/if}
		{if in_array($format, ['markdown','html'])}
		<div class="bot-chat-message-bubble">
			{$message nofilter}
		</div>
		{else}
		<div class="bot-chat-message-bubble">
			{$message|escape|nl2br nofilter}
		</div>
		{/if}
		{*
		<div class="bot-chat-message-time">
			{time()|devblocks_date:'h:ia'}
		</div>
		*}
	</div>
	
	<br clear="all">
</div>